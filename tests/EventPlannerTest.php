<?php

declare(strict_types=1);

use GoPromotional\EventPlanner\EventPlanner as EP;
use PHPUnit\Framework\TestCase;

final class EventPlannerTest extends TestCase
{
    public function testCountdown(): void
    {
        $this->assertSame(['eventDate' => '2026-12-03', 'today' => '2026-10-19', 'days' => 45, 'weeks' => 6, 'remainderDays' => 3, 'status' => 'upcoming'], EP::countdown('2026-12-03', '2026-10-19'));
        $this->assertSame('today', EP::countdown('2026-10-19', '2026-10-19')['status']);
        $past = EP::countdown('2026-10-10', '2026-10-19');
        $this->assertSame(-9, $past['days']);
        $this->assertSame('past', $past['status']);
    }

    public function testInvalidDates(): void
    {
        $this->expectExceptionMessage('Invalid calendar date');
        EP::toDay('2026-02-30');
    }

    public function testInvalidFormat(): void
    {
        $this->expectExceptionMessage('expected YYYY-MM-DD');
        EP::toDay('19/10/2026');
    }

    public function testWeekday(): void
    {
        $this->assertSame(1, EP::weekday(EP::toDay('2026-10-19')));
        $this->assertSame(4, EP::weekday(EP::toDay('2026-12-03')));
        $this->assertSame(7, EP::weekday(EP::toDay('2026-10-25')));
    }

    public function testTimeline(): void
    {
        $t = EP::timeline('2026-12-03', '2026-10-19');
        $this->assertCount(12, $t);
        $this->assertSame('2026-10-08', $t[0]['due']);
        $this->assertTrue($t[0]['overdue']);
        $merch = array_values(array_filter($t, fn ($r) => str_starts_with($r['task'], 'Order branded')))[0];
        $this->assertSame('2026-10-22', $merch['due']);
        $this->assertSame(3, $merch['daysLeft']);
        $this->assertFalse($merch['overdue']);
        $this->assertSame('2026-12-05', $t[11]['due']);
    }

    public function testGiveaways(): void
    {
        $this->assertSame(['base' => 600, 'buffer' => 60, 'total' => 660], EP::giveawayQuantity(500, 2, 0.6, 1, 0.1));
        $this->assertSame(['base' => 88, 'buffer' => 9, 'total' => 97], EP::giveawayQuantity(250, uptake: 0.35));
        $this->assertSame(['base' => 0, 'buffer' => 0, 'total' => 0], EP::giveawayQuantity(0));
        $this->expectException(InvalidArgumentException::class);
        EP::giveawayQuantity(10, uptake: 1.5);
    }

    public function testOrderBy(): void
    {
        $this->assertSame(['orderBy' => '2026-11-12', 'workingDays' => 15, 'daysFromToday' => 24, 'late' => false], EP::orderByDate('2026-12-03', today: '2026-10-19'));
        $this->assertSame('2026-10-23', EP::orderByDate('2026-10-26', 1, 0, 0)['orderBy']);
        $this->assertTrue(EP::orderByDate('2026-10-21', today: '2026-10-19')['late']);
    }
}
