<?php

namespace App\Twig;

use App\Calendar\CalendarQuery;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    public function getFilters(): array
    {
        return [
            // ICU patterns, e.g. "EEEE, d. MMMM y" or "EE d.M."
            new TwigFilter('de_date', $this->formatDate(...)),
            // WordPress HTML as clean plain text with paragraphs
            new TwigFilter('plain_text', $this->plainText(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cal_path', $this->calendarPath(...)),
        ];
    }

    public function plainText(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }
        $text = preg_replace('#<(br|/p|/h[1-6]|/li|/div|/blockquote|/figure)\b[^>]*>#i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $lines = array_map('trim', preg_split('/\R/u', $text) ?: []);
        $text = implode("\n", $lines);

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    public function formatDate(?\DateTimeInterface $date, string $pattern = 'd.M.y'): string
    {
        if ($date === null) {
            return '';
        }
        $formatter = new \IntlDateFormatter('de_AT', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $date->getTimezone(), null, $pattern);

        return (string) $formatter->format($date);
    }

    /**
     * @param array<string, string|null> $overrides
     */
    public function calendarPath(CalendarQuery $query, array $overrides = []): string
    {
        return $this->urls->generate('app_calendar', $query->params($overrides));
    }
}
