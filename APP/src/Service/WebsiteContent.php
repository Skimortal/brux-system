<?php

namespace App\Service;

use App\Entity\Production;

/**
 * The text of a production's website post, kept in separate fields here and written
 * as the block template every post on the website uses: subtitle heading, featured
 * image, then each section as paragraphs, lists and headings carrying the CSS class
 * the theme styles.
 * Empty sections stay in the post, hidden with "displaynone", as in WordPress.
 */
final class WebsiteContent
{
    public const DEFAULT_GENERAL_INFO = '<p>Wir empfehlen euch vorab Karten zu reservieren.<br>Bei Fragen bitte gerne melden:&nbsp;<a href="mailto:info@brux.at">info@brux.at</a></p>';

    /** Paragraph sections in post order: field => [CSS class, placeholder text when empty]. */
    private const SECTIONS = [
        'generalInfo' => ['allgemeineinfos', ''],
        'shortDescription' => ['stueckbeschreibung short', 'Kurzbeschreibung'],
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
            $sectionBlocks = $this->sectionBlocks((string) $production->{'get' . ucfirst($field)}(), $class);
            $sections[] = $sectionBlocks !== []
                ? implode("\n\n", $sectionBlocks)
                : $this->paragraphBlock($class . ' displaynone', $placeholder);
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

        foreach (self::SECTIONS as $field => [$class]) {
            $condition = implode(' and ', array_map($hasClass, explode(' ', $class)));
            $parts = [];
            foreach ($xpath->query("//p[{$condition}] | //ul[{$condition}] | //ol[{$condition}] | //h2[{$condition}] | //h3[{$condition}] | //h4[{$condition}]") as $node) {
                $nodeClasses = preg_split('/\s+/', (string) $node->getAttribute('class'));
                // "stueckbeschreibung" alone must not pick up the short one; hidden ones are empty
                if (\in_array('displaynone', $nodeClasses, true) || ($field === 'description' && \in_array('short', $nodeClasses, true))) {
                    continue;
                }
                if (trim(strip_tags($document->saveHTML($node), '<img>')) === '') {
                    continue;
                }
                $parts[] = $this->plainCopy($document, $node);
            }
            $fields[$field] = $parts !== [] ? implode('', $parts) : null;
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
            } elseif (\in_array($tag, ['p', 'blockquote', 'figure', 'div'], true)) {
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
