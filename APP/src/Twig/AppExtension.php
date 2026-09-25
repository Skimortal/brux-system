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
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cal_path', $this->calendarPath(...)),
        ];
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
