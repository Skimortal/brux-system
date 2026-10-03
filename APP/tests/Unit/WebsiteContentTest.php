<?php

namespace App\Tests\Unit;

use App\Entity\Production;
use App\Service\WebsiteContent;
use PHPUnit\Framework\TestCase;

final class WebsiteContentTest extends TestCase
{
    /** Shortened post of a production as WordPress stores it. */
    private const POST = <<<'HTML'
        <!-- wp:heading {"className":"untertitel"} -->
        <h2 class="wp-block-heading untertitel">von Herz-Impro-Innsbruck | Improvisationstheater</h2>
        <!-- /wp:heading -->

        <!-- wp:post-featured-image {"width":"","height":""} /-->

        <!-- wp:group {"layout":{"type":"constrained"}} -->
        <div class="wp-block-group"><!-- wp:paragraph {"className":"allgemeineinfos"} -->
        <p class="allgemeineinfos">Wir empfehlen euch vorab Karten zu reservieren.<br>Bei Fragen bitte gerne melden:&nbsp;<a href="mailto:info@brux.at">info@brux.at</a></p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"className":"stueckbeschreibung short"} -->
        <p class="stueckbeschreibung short"><strong>Herz-Impro</strong> kreiert spontan Szenen.</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"className":"stueckbeschreibung"} -->
        <p class="stueckbeschreibung">Begleitet vom Trio Bucca Bossa.</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"className":"pressestimmen displaynone"} -->
        <p class="pressestimmen displaynone">Pressestimmen</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"className":"credits"} -->
        <p class="credits">Regie: Anna</p>
        <!-- /wp:paragraph --></div>
        <!-- /wp:group -->
        HTML;

    public function testPostIsSplitIntoSections(): void
    {
        $fields = (new WebsiteContent())->parse(self::POST);

        self::assertSame('von Herz-Impro-Innsbruck | Improvisationstheater', $fields['subtitle']);
        self::assertStringContainsString('vorab Karten zu reservieren.<br>', $fields['generalInfo']);
        self::assertSame('<p><strong>Herz-Impro</strong> kreiert spontan Szenen.</p>', $fields['shortDescription']);
        self::assertSame('<p>Begleitet vom Trio Bucca Bossa.</p>', $fields['description']);
        self::assertNull($fields['pressQuotes'], 'hidden sections are empty');
        self::assertSame('<p>Regie: Anna</p>', $fields['credits']);
        self::assertNull($fields['additionalInfo']);
    }

    public function testSectionsBecomeTheTemplate(): void
    {
        $production = (new Production())
            ->setSubtitle('von OFFTANZ Tirol | Tanz & Performance')
            ->setGeneralInfo(WebsiteContent::DEFAULT_GENERAL_INFO)
            ->setDescription('<p>Erster Absatz.</p><p>Zweiter <em>Absatz</em>.</p><ul><li>Punkt</li></ul>');

        $html = (new WebsiteContent())->render($production);

        self::assertStringContainsString('<h2 class="wp-block-heading untertitel">von OFFTANZ Tirol | Tanz &amp; Performance</h2>', $html);
        self::assertStringContainsString('<!-- wp:post-featured-image', $html);
        self::assertStringContainsString('<p class="stueckbeschreibung">Erster Absatz.</p>', $html);
        self::assertStringContainsString('<p class="stueckbeschreibung">Zweiter <em>Absatz</em>.</p>', $html);
        self::assertStringContainsString('<ul class="wp-block-list stueckbeschreibung"><!-- wp:list-item -->', $html);
        self::assertStringContainsString('<p class="pressestimmen displaynone">Pressestimmen</p>', $html);
        self::assertStringContainsString('<p class="stueckbeschreibung short displaynone">Kurzbeschreibung</p>', $html);
    }

    public function testRoundTripKeepsTheContent(): void
    {
        $content = new WebsiteContent();
        $production = (new Production())->setContentHtml(self::POST);
        $content->fillSections($production);

        $again = $content->parse($content->render($production));

        self::assertSame($production->getSubtitle(), $again['subtitle']);
        self::assertSame($production->getShortDescription(), $again['shortDescription']);
        self::assertSame($production->getCredits(), $again['credits']);
    }

    public function testPostWithoutTemplateGoesIntoTheDescription(): void
    {
        $production = (new Production())->setContentHtml('<p>Nur Text</p>');
        (new WebsiteContent())->fillSections($production);

        self::assertSame('<p>Nur Text</p>', $production->getDescription());
        self::assertNull($production->getSubtitle());
    }
}
