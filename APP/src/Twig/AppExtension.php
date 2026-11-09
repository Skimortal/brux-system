<?php

namespace App\Twig;

use App\Calendar\CalendarQuery;
use App\Service\WebsiteContent;
use App\Service\WordPressPushService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urls,
        private readonly WordPressPushService $push,
        private readonly RequestStack $requests,
        private readonly WebsiteContent $content,
    ) {
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
            new TwigFunction('cal_route', $this->calendarRoute(...)),
            // whether the WordPress write API is set up (reservations, pushes)
            new TwigFunction('wordpress_connected', fn (): bool => $this->push->isConfigured()),
            // heading => HTML of a production's website texts, for reading in the app
            new TwigFunction('website_sections', $this->content->readableSections(...)),
        ];
    }

    public function plainText(?string $html): string
    {
        return WebsiteContent::plainText($html);
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
        return $this->urls->generate($this->calendarRoute(), $query->params($overrides));
    }

    /** The read-only calendars (technicians, board) link to themselves, everything else to the main calendar. */
    public function calendarRoute(): string
    {
        $route = (string) $this->requests->getCurrentRequest()?->attributes->get('_route');

        return match (true) {
            str_starts_with($route, 'app_technician') => 'app_technician',
            str_starts_with($route, 'app_board') => 'app_board',
            default => 'app_calendar',
        };
    }
}
