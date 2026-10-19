<?php

namespace App\Service;

use App\Entity\CalendarFeed;
use App\Repository\RoomRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The subscription links of a feed: one per calendar (and per room where it is split).
 */
class CalendarFeedLinks
{
    public function __construct(private readonly UrlGeneratorInterface $urls, private readonly RoomRepository $rooms)
    {
    }

    /**
     * @return list<array{label: string, url: string, webcal: string}>
     */
    public function links(CalendarFeed $feed): array
    {
        $rooms = $this->rooms->findBy([], ['name' => 'ASC']);
        $links = [];
        foreach ($feed->getProfile()->parts() as $part => $info) {
            $targets = $info['rooms'] ? [null, ...$rooms] : [null];
            foreach ($targets as $room) {
                $url = $this->urls->generate('app_feed_ics', array_filter([
                    'token' => $feed->getToken(),
                    'teil' => \count($feed->getProfile()->parts()) > 1 ? $part : null,
                    'raum' => $room?->getId(),
                ]), UrlGeneratorInterface::ABSOLUTE_URL);
                $links[] = [
                    'label' => $info['label'] . ($info['rooms'] ? ' · ' . ($room?->getName() ?? 'alle Räume') : ''),
                    'url' => $url,
                    'webcal' => (string) preg_replace('#^https?://#', 'webcal://', $url),
                ];
            }
        }

        return $links;
    }
}
