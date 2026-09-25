<?php

namespace App\Tests\Unit;

use App\Calendar\CalendarBuilder;
use App\Calendar\CalendarQuery;
use App\Entity\Appointment;
use App\Entity\Room;
use App\Enum\AppointmentTypeEnum;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningScheduleRepository;
use App\Repository\KeyLoanRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\RoomRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class CalendarBuilderTest extends TestCase
{
    public function testOverlappingEntriesInTheSameRoomAreFlagged(): void
    {
        $room = new Room();
        $room->setName('Schwarz');
        (new \ReflectionProperty(Room::class, 'id'))->setValue($room, 1);

        $rehearsal = $this->appointment('Probe', '2026-10-15 18:00', '2026-10-15 21:00', $room);
        $meeting = $this->appointment('Sitzung', '2026-10-15 20:30', '2026-10-15 22:00', $room);
        $elsewhere = $this->appointment('Anderswo', '2026-10-15 18:00', '2026-10-15 21:00', null);

        $appointments = $this->createStub(AppointmentRepository::class);
        $appointments->method('findOverlapping')->willReturn([$rehearsal, $meeting, $elsewhere]);
        $performances = $this->createStub(ProductionEventRepository::class);
        $performances->method('findBetween')->willReturn([]);
        $schedules = $this->createStub(CleaningScheduleRepository::class);
        $schedules->method('findActiveBetween')->willReturn([]);
        $loans = $this->createStub(KeyLoanRepository::class);
        $loans->method('findOverlapping')->willReturn([]);
        $rooms = $this->createStub(RoomRepository::class);
        $rooms->method('findBy')->willReturn([$room]);

        $builder = new CalendarBuilder($appointments, $performances, $schedules, $loans, $rooms, new MockClock('2026-10-14 12:00'));
        $view = $builder->build(new CalendarQuery(new \DateTimeImmutable('2026-10-15'), layout: 'rows'));

        $byTitle = [];
        foreach ($view->entries as $entry) {
            $byTitle[$entry->title] = $entry;
        }
        self::assertNotNull($byTitle['Probe']->conflict);
        self::assertNotNull($byTitle['Sitzung']->conflict);
        self::assertNull($byTitle['Anderswo']->conflict);

        // house lane first, then the room; Thursday is index 3
        self::assertSame(['house', 'room-1'], array_column($view->lanes, 'key'));
        self::assertCount(2, $view->lanes[1]['days'][3]);
        self::assertCount(1, $view->lanes[0]['days'][3]);

        // room calendars side by side: the room shows its own entries and those without a room
        $split = $builder->build(new CalendarQuery(new \DateTimeImmutable('2026-10-15')));
        self::assertSame([], $split->lanes);
        self::assertCount(1, $split->split);
        self::assertSame('Schwarz', $split->split[0]['label']);
        self::assertCount(3, $split->split[0]['lane']['days'][3]);
    }

    private function appointment(string $title, string $start, string $end, ?Room $room): Appointment
    {
        $appointment = (new Appointment())
            ->setType(AppointmentTypeEnum::INTERNAL)
            ->setTitle($title)
            ->setStartDate(new \DateTime($start))
            ->setEndDate(new \DateTime($end))
            ->setRoom($room);
        (new \ReflectionProperty(Appointment::class, 'id'))->setValue($appointment, random_int(1, 100000));

        return $appointment;
    }
}
