<?php

namespace App\Tests\Unit;

use App\Entity\Appointment;
use App\Entity\AppointmentTechnician;
use App\Entity\Contact;
use App\Entity\User;
use App\Enum\TechnicianStatus;
use PHPUnit\Framework\TestCase;

final class TechnicianAccessTest extends TestCase
{
    public function testTechnicianAccountHasNoStaffRole(): void
    {
        $technician = (new User())->setRoles(['ROLE_TECHNICIAN']);
        self::assertTrue($technician->isTechnician());
        self::assertSame(['ROLE_TECHNICIAN'], $technician->getRoles(), 'no ROLE_USER, so the staff pages stay closed');

        $staff = (new User())->setRoles([]);
        self::assertFalse($staff->isTechnician());
        self::assertSame(['ROLE_USER'], $staff->getRoles());

        $admin = (new User())->setRoles(['ROLE_ADMIN']);
        self::assertContains('ROLE_USER', $admin->getRoles());
    }

    public function testBoardAccountOnlyReads(): void
    {
        $board = (new User())->setRoles(['ROLE_BOARD']);
        self::assertTrue($board->isBoard());
        self::assertTrue($board->isReadOnly());
        self::assertSame(['ROLE_BOARD'], $board->getRoles());
        self::assertSame('app_board', $board->getHomeRoute());
        self::assertSame('app_technician', (new User())->setRoles(['ROLE_TECHNICIAN'])->getHomeRoute());
        self::assertSame('app_calendar', (new User())->setRoles([])->getHomeRoute());
    }

    public function testOnlyPlannedPeopleCountAsStaffed(): void
    {
        self::assertTrue(TechnicianStatus::ASSIGNED->isStaffed());
        self::assertTrue(TechnicianStatus::ACCEPTED->isStaffed());
        self::assertTrue(TechnicianStatus::CONFIRMED->isStaffed());
        self::assertFalse(TechnicianStatus::DECLINED->isStaffed());
        self::assertFalse(TechnicianStatus::INTERESTED->isStaffed());
    }

    public function testAppointmentFindsAnAssignmentByContact(): void
    {
        $me = new Contact();
        $other = new Contact();
        $appointment = new Appointment();
        $mine = (new AppointmentTechnician())->setContact($me);
        $appointment->addAppointmentTechnician($mine);

        self::assertSame($mine, $appointment->getAssignmentOf($me));
        self::assertNull($appointment->getAssignmentOf($other));
        self::assertNull($appointment->getAssignmentOf(null));
        self::assertSame(TechnicianStatus::ASSIGNED, $mine->getStatus(), 'a new assignment starts as assigned');
    }

    public function testCopiedSeriesMemberIsNotOpenAgain(): void
    {
        $appointment = (new Appointment())->setOpenCallAt(new \DateTimeImmutable());
        self::assertNull((clone $appointment)->getOpenCallAt());
    }
}
