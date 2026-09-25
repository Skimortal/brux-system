<?php

namespace App\Calendar;

use Symfony\Component\HttpFoundation\Request;

/**
 * State of the calendar page as carried in the URL.
 */
final class CalendarQuery
{
    public const VIEWS = ['day' => 'Tag', 'week' => 'Woche', 'month' => 'Monat'];
    public const LAYOUTS = ['rooms' => 'Räume', 'all' => 'Gesamt'];

    /**
     * @param EntryGroup[] $hidden
     */
    public function __construct(
        public readonly \DateTimeImmutable $date,
        public readonly string $view = 'week',
        public readonly string $layout = 'rooms',
        public readonly array $hidden = [],
        public readonly ?string $panel = null,
        public readonly string $side = 'keys',
    ) {
    }

    public static function fromRequest(Request $request, ?\DateTimeImmutable $today = null): self
    {
        $today ??= new \DateTimeImmutable('today');
        $date = $today;
        $raw = $request->query->getString('date');
        if ($raw !== '' && ($parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw)) !== false) {
            $date = $parsed;
        }
        $view = $request->query->getString('view', 'week');
        $layout = $request->query->getString('layout', 'rooms');
        $hidden = [];
        foreach (explode(',', $request->query->getString('hide')) as $value) {
            $group = EntryGroup::tryFrom(trim($value));
            if ($group !== null) {
                $hidden[] = $group;
            }
        }
        $panel = $request->query->getString('panel');
        $side = $request->query->getString('side', 'keys');

        return new self(
            $date,
            \array_key_exists($view, self::VIEWS) ? $view : 'week',
            \array_key_exists($layout, self::LAYOUTS) ? $layout : 'rooms',
            $hidden,
            $panel !== '' ? $panel : null,
            \in_array($side, ['keys', 'todos'], true) ? $side : 'keys',
        );
    }

    public function isHidden(EntryGroup $group): bool
    {
        return \in_array($group, $this->hidden, true);
    }

    /** First day shown (inclusive). */
    public function rangeStart(): \DateTimeImmutable
    {
        return match ($this->view) {
            'day' => $this->date,
            'month' => self::monday($this->date->modify('first day of this month')),
            default => self::monday($this->date),
        };
    }

    /** Day after the last day shown (exclusive). */
    public function rangeEnd(): \DateTimeImmutable
    {
        return match ($this->view) {
            'day' => $this->date->modify('+1 day'),
            'month' => self::monday($this->date->modify('last day of this month'))->modify('+7 days'),
            default => $this->rangeStart()->modify('+7 days'),
        };
    }

    public function previousDate(): \DateTimeImmutable
    {
        return match ($this->view) {
            'day' => $this->date->modify('-1 day'),
            'month' => $this->date->modify('first day of previous month'),
            default => $this->date->modify('-7 days'),
        };
    }

    public function nextDate(): \DateTimeImmutable
    {
        return match ($this->view) {
            'day' => $this->date->modify('+1 day'),
            'month' => $this->date->modify('first day of next month'),
            default => $this->date->modify('+7 days'),
        };
    }

    /**
     * Query parameters for links, with overrides applied. Null removes a parameter.
     *
     * @param array<string, string|null> $overrides
     *
     * @return array<string, string>
     */
    public function params(array $overrides = []): array
    {
        $params = [
            'date' => $this->date->format('Y-m-d'),
            'view' => $this->view,
            'layout' => $this->layout,
            'hide' => implode(',', array_map(static fn (EntryGroup $g) => $g->value, $this->hidden)),
            'panel' => $this->panel,
            'side' => $this->side,
        ];
        foreach ($overrides as $key => $value) {
            $params[$key] = $value;
        }
        $defaults = ['view' => 'week', 'layout' => 'rooms', 'side' => 'keys', 'hide' => ''];

        return array_filter(
            $params,
            static fn ($value, $key) => $value !== null && $value !== '' && ($defaults[$key] ?? null) !== $value,
            \ARRAY_FILTER_USE_BOTH
        );
    }

    /** @return array<string, string> parameters that toggle a filter group */
    public function toggleParams(EntryGroup $group): array
    {
        $hidden = $this->hidden;
        if ($this->isHidden($group)) {
            $hidden = array_filter($hidden, static fn (EntryGroup $g) => $g !== $group);
        } else {
            $hidden[] = $group;
        }

        return $this->params(['hide' => implode(',', array_map(static fn (EntryGroup $g) => $g->value, $hidden))]);
    }

    private static function monday(\DateTimeImmutable $date): \DateTimeImmutable
    {
        $weekday = (int) $date->format('N');

        return $date->modify(sprintf('-%d days', $weekday - 1))->setTime(0, 0);
    }
}
