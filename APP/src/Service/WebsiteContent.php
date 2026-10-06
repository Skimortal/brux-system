<?php

namespace App\Service;

use App\Entity\Production;

/**
 * The text of a production's website post, kept in separate fields here and written
 * as the block template every post on the website uses: subtitle heading, featured
 * image, then each section as paragraphs, lists and headings carrying the CSS class
 * the theme styles.
 * Empty sections stay in the post, hidden with "displaynone", as in WordPress. The short
 * description is the text of the infoscreen and always hidden on the website.
 */
final class WebsiteContent
{
    public const DEFAULT_GENERAL_INFO = '<p>Wir empfehlen euch vorab Karten zu reservieren.<br>Bei Fragen bitte gerne melden:&nbsp;<a href="mailto:info@brux.at">info@brux.at</a></p>';

    /**
     * Sections in post order: field => [CSS class, placeholder text when empty, or null to
     * leave an empty section out]. The intro holds description text that some posts put
     * before the short description.
     */
    /** Sections kept in the post for other uses (the infoscreen) but hidden on the website. */
    private const HIDDEN_SECTIONS = ['shortDescription'];

    private const SECTIONS = [
        'generalInfo' => ['allgemeineinfos', ''],
        'intro' => ['stueckbeschreibung', null],
        // left empty when there is no text, so the infoscreen never shows a placeholder
        'shortDescription' => ['stueckbeschreibung short', ''],
        'description' => ['stueckbeschreibung', 'Stückbeschreibung'],
        'pressQuotes' => ['pressestimmen', 'Pressestimmen'],
        'credits' => ['credits', 'Credits'],
        'additionalInfo' => ['zusaetzlicheinformation', 'Zusätzliche Informationen'],
    ];

    /** Whether the post sections are filled in here, so the post can be built from them. */
    public function hasSections(Production $production): bool
    {
        if ($production->getSubtitle() !== null) {
            return true;
        }
        foreach (array_keys(self::SECTIONS) as $field) {
            if ($production->{'get' . ucfirst($field)}() !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fills the sections of a production that has only the finished post (from WordPress).
     * A post that does not follow the template goes into the description as a whole.
     */
    public function fillSections(Production $production): void
    {
        if ($this->hasSections($production) || trim((string) $production->getContentHtml()) === '') {
            return;
        }

        $fields = $this->parse($production->getContentHtml());
        if ($fields === false) {
            $production->setDescription($production->getContentHtml());

            return;
        }
        foreach ($fields as $field => $value) {
            $production->{'set' . ucfirst($field)}($value);
        }
    }

    /** Builds the post content (WordPress block markup) from the production's fields. */
    public function render(Production $production): string
    {
        $subtitle = htmlspecialchars((string) $production->getSubtitle(), \ENT_QUOTES | \ENT_HTML5);
        $blocks = [
            "<!-- wp:heading {\"className\":\"untertitel\"} -->\n<h2 class=\"wp-block-heading untertitel\">{$subtitle}</h2>\n<!-- /wp:heading -->",
            '<!-- wp:post-featured-image {"width":"","height":""} /-->',
        ];

        $sections = [];
        foreach (self::SECTIONS as $field => [$class, $placeholder]) {
            if (\in_array($field, self::HIDDEN_SECTIONS, true)) {
                $class .= ' displaynone';
            }
            $sectionBlocks = $this->sectionBlocks((string) $production->{'get' . ucfirst($field)}(), $class);
            if ($sectionBlocks !== []) {
                // a section of images only gets its hidden paragraph, so it is recognised again
                $anchored = str_contains(implode('', $sectionBlocks), 'class="' . $class);
                if (!$anchored && $placeholder !== null) {
                    array_unshift($sectionBlocks, $this->paragraphBlock(str_replace(' displaynone', '', $class) . ' displaynone', $placeholder));
                }
                $sections[] = implode("\n\n", $sectionBlocks);
            } elseif ($placeholder !== null) {
                $sections[] = $this->paragraphBlock(str_replace(' displaynone', '', $class) . ' displaynone', $placeholder);
            }
        }
        $blocks[] = "<!-- wp:group {\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\">"
            . implode("\n\n", $sections)
            . "</div>\n<!-- /wp:group -->";

        return implode("\n\n", $blocks);
    }

    /**
     * Splits a post built from the template into the fields; false when the content
     * does not follow the template. A section may span several blocks (paragraphs,
     * lists, headings) that carry its class.
     *
     * @return array<string, ?string>|false field => HTML or null
     */
    public function parse(?string $html): array|false
    {
        if ($html === null || !str_contains($html, 'untertitel')) {
            return false;
        }

        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        libxml_clear_errors();
        $xpath = new \DOMXPath($document);
        $hasClass = static fn (string $class) => sprintf('contains(concat(" ", normalize-space(@class), " "), " %s ")', $class);

        $subtitle = $xpath->query('//*[' . $hasClass('untertitel') . ']')->item(0);
        $fields = ['subtitle' => $subtitle !== null ? trim($subtitle->textContent) : null];

        // one pass in post order: plain "stueckbeschreibung" blocks before the short one are
        // the intro; blocks without a section class belong to the section before them
        $parts = array_fill_keys(array_keys(self::SECTIONS), []);
        $shortSeen = false;
        $current = 'generalInfo';
        $blocks = '//*[self::p or self::ul or self::ol or self::h2 or self::h3 or self::h4 or self::blockquote or self::figure]'
            . '[not(ancestor::ul or ancestor::ol or ancestor::blockquote or ancestor::figure)]';
        foreach ($xpath->query($blocks) as $node) {
            $classes = preg_split('/\s+/', trim((string) $node->getAttribute('class')));
            if (\in_array('untertitel', $classes, true) || \in_array('wp-block-post-featured-image', $classes, true)) {
                continue;
            }
            $field = match (true) {
                \in_array('allgemeineinfos', $classes, true) => 'generalInfo',
                \in_array('stueckbeschreibung', $classes, true) && \in_array('short', $classes, true) => 'shortDescription',
                \in_array('stueckbeschreibung', $classes, true) => $shortSeen ? 'description' : 'intro',
                \in_array('pressestimmen', $classes, true) => 'pressQuotes',
                \in_array('credits', $classes, true) => 'credits',
                \in_array('zusaetzlicheinformation', $classes, true) => 'additionalInfo',
                // visible text after the (hidden) infoscreen text belongs to the description
                default => $current === 'shortDescription' ? 'description' : $current,
            };
            $current = $field;
            if ($field === 'shortDescription') {
                $shortSeen = true;
            }
            // hidden blocks are empty sections, except the infoscreen text unless it is the placeholder
            $text = trim(strip_tags($document->saveHTML($node), '<img>'));
            $hidden = \in_array('displaynone', $classes, true);
            if ($text === '' || ($hidden && (!\in_array($field, self::HIDDEN_SECTIONS, true) || $text === self::SECTIONS[$field][1]))) {
                continue;
            }
            $parts[$field][] = $this->plainCopy($document, $node);
        }
        // without a short description everything is the description
        if (!$shortSeen) {
            $parts['description'] = [...$parts['intro'], ...$parts['description']];
            $parts['intro'] = [];
        }
        foreach ($parts as $field => $blocks) {
            $fields[$field] = $blocks !== [] ? implode('', $blocks) : null;
        }

        return $fields;
    }

    /**
     * The blocks of one section: paragraphs, lists and headings as edited here, each
     * with the section's class; anything else is turned into paragraphs.
     *
     * @return string[]
     */
    private function sectionBlocks(string $html, string $class): array
    {
        if (trim(strip_tags($html, '<img>')) === '') {
            return [];
        }

        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        libxml_clear_errors();
        $body = $document->getElementsByTagName('body')->item(0);

        $blocks = [];
        $inline = '';
        $flush = function () use (&$inline, &$blocks, $class): void {
            if (trim(strip_tags($inline, '<img>')) !== '') {
                $blocks[] = $this->paragraphBlock($class, trim($inline));
            }
            $inline = '';
        };
        foreach ($body->childNodes as $node) {
            $tag = $node instanceof \DOMElement ? strtolower($node->tagName) : null;
            if ($tag === 'ul' || $tag === 'ol') {
                $flush();
                $items = [];
                foreach ($node->childNodes as $item) {
                    if ($item instanceof \DOMElement && strtolower($item->tagName) === 'li') {
                        $items[] = "<!-- wp:list-item -->\n<li>" . $this->innerHtml($document, $item) . "</li>\n<!-- /wp:list-item -->";
                    }
                }
                $ordered = $tag === 'ol' ? '"ordered":true,' : '';
                $blocks[] = "<!-- wp:list {{$ordered}\"className\":\"{$class}\"} -->\n<{$tag} class=\"wp-block-list {$class}\">" . implode("\n\n", $items) . "</{$tag}>\n<!-- /wp:list -->";
            } elseif ($tag !== null && preg_match('/^h([2-4])$/', $tag, $level)) {
                $flush();
                $blocks[] = "<!-- wp:heading {\"level\":{$level[1]},\"className\":\"{$class}\"} -->\n<{$tag} class=\"wp-block-heading {$class}\">" . $this->innerHtml($document, $node) . "</{$tag}>\n<!-- /wp:heading -->";
            } elseif ($tag === 'figure') {
                // images and galleries go through as they are, so their layout classes stay
                $flush();
                $blocks[] = "<!-- wp:html -->\n" . $document->saveHTML($node) . "\n<!-- /wp:html -->";
            } elseif (\in_array($tag, ['p', 'blockquote', 'div'], true)) {
                $flush();
                $inline = $this->innerHtml($document, $node);
                $flush();
            } elseif ($tag === 'hr') {
                $flush();
            } else {
                // loose text and inline elements form a paragraph
                $inline .= $document->saveHTML($node);
            }
        }
        $flush();

        return $blocks;
    }

    private function paragraphBlock(string $class, string $inner): string
    {
        return "<!-- wp:paragraph {\"className\":\"{$class}\"} -->\n<p class=\"{$class}\">{$inner}</p>\n<!-- /wp:paragraph -->";
    }

    /** The element as edited here: same tag and content, without WordPress classes. */
    private function plainCopy(\DOMDocument $document, \DOMElement $node): string
    {
        $tag = strtolower($node->tagName);
        if ($tag === 'ul' || $tag === 'ol') {
            $items = '';
            foreach ($node->childNodes as $item) {
                if ($item instanceof \DOMElement && strtolower($item->tagName) === 'li') {
                    $items .= '<li>' . trim($this->innerHtml($document, $item)) . '</li>';
                }
            }

            return "<{$tag}>{$items}</{$tag}>";
        }
        if ($tag === 'figure') {
            return $document->saveHTML($node);
        }

        return "<{$tag}>" . trim($this->innerHtml($document, $node)) . "</{$tag}>";
    }

    private function innerHtml(\DOMDocument $document, \DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }
}
