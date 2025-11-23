<?php

namespace App\DTO;

class CalendarEventDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $start, // ISO8601
        public string $end,   // ISO8601
        public string $type,  // 'production', 'cleaning', 'technician', 'private'
        public string $color,
        public bool $allDay = false,
        public ?string $description = null,
        public ?string $resourceId = null // Room ID
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start,
            'end' => $this->end,
            'classNames' => ['event-type-' . $this->type], // Für CSS Styling
            'color' => $this->color,
            'allDay' => $this->allDay,
            'description' => $this->description,
            'extendedProps' => [
                'type' => $this->type
            ]
        ];
    }
}
