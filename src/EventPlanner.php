<?php

declare(strict_types=1);

namespace GoPromotional\EventPlanner;

use InvalidArgumentException;

/**
 * Event planning helpers: a countdown, a planning timeline with due dates,
 * a giveaway quantity estimator and the latest order date for printed
 * merchandise (working days). Same behaviour as the npm, PyPI, crates.io and
 * RubyGems versions of gopromotional-event-planner.
 */
final class EventPlanner
{
    /** Default planning template: [offsetDays before the event, category, task]. Negative = after. */
    public const TIMELINE_TEMPLATE = [
        [56, 'planning', 'Confirm budget and attendee estimate'],
        [49, 'venue', 'Book the venue'],
        [42, 'merchandise', 'Order branded merchandise and giveaways'],
        [35, 'comms', 'Send invitations or open registration'],
        [28, 'programme', 'Confirm speakers and agenda'],
        [21, 'venue', 'Arrange catering'],
        [14, 'merchandise', 'Approve print proofs and signage'],
        [7, 'comms', 'Send a reminder to attendees'],
        [3, 'logistics', 'Confirm deliveries and the staffing rota'],
        [1, 'logistics', 'Pack the event kit and giveaways'],
        [0, 'logistics', 'Event day: set up the stand and registration'],
        [-2, 'comms', 'Send thank-you and follow-up messages'],
    ];

    /** Parse "YYYY-MM-DD" into a day number (days since 1970-01-01). */
    public static function toDay(string $date): int
    {
        $date = trim($date);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            throw new InvalidArgumentException("Invalid date \"$date\" (expected YYYY-MM-DD)");
        }
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        if (!checkdate($mo, $d, $y)) {
            throw new InvalidArgumentException("Invalid calendar date \"$date\"");
        }
        return intdiv(gmmktime(0, 0, 0, $mo, $d, $y), 86400);
    }

    /** Format a day number as "YYYY-MM-DD". */
    public static function fromDay(int $day): string
    {
        return gmdate('Y-m-d', $day * 86400);
    }

    /** ISO weekday: 1 = Monday ... 7 = Sunday. */
    public static function weekday(int $day): int
    {
        $w = ((($day + 4) % 7) + 7) % 7;
        return $w === 0 ? 7 : $w;
    }

    public static function todayIso(): string
    {
        return date('Y-m-d');
    }

    /** Days until an event, split into weeks + days. */
    public static function countdown(string $eventDate, ?string $today = null): array
    {
        $ev = self::toDay($eventDate);
        $t = self::toDay($today ?? self::todayIso());
        $days = $ev - $t;
        $abs = abs($days);
        return [
            'eventDate' => self::fromDay($ev),
            'today' => self::fromDay($t),
            'days' => $days,
            'weeks' => intdiv($abs, 7),
            'remainderDays' => $abs % 7,
            'status' => $days > 0 ? 'upcoming' : ($days === 0 ? 'today' : 'past'),
        ];
    }

    /**
     * Planning timeline with a due date per task, sorted by due date.
     * When $today is given, each row also gets daysLeft and overdue.
     *
     * @param array<int, array{0:int,1:string,2:string}>|null $template
     */
    public static function timeline(string $eventDate, ?string $today = null, ?array $template = null): array
    {
        $ev = self::toDay($eventDate);
        $t = $today === null ? null : self::toDay($today);
        $rows = [];
        foreach ($template ?? self::TIMELINE_TEMPLATE as [$offset, $category, $task]) {
            $due = $ev - $offset;
            $row = ['task' => $task, 'category' => $category, 'offsetDays' => $offset, 'due' => self::fromDay($due)];
            if ($t !== null) {
                $row['daysLeft'] = $due - $t;
                $row['overdue'] = $due < $t;
            }
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => strcmp($a['due'], $b['due']));
        return $rows;
    }

    private static function ceilClean(float $x): int
    {
        return (int) ceil(round($x * 1e6) / 1e6);
    }

    /**
     * Estimate how many giveaway items to order.
     * base = attendees x days x uptake x perPerson (rounded up); buffer = base x buffer (rounded up).
     */
    public static function giveawayQuantity(float $attendees, float $days = 1, float $uptake = 0.6, float $perPerson = 1, float $buffer = 0.1): array
    {
        foreach (compact('attendees', 'days', 'uptake', 'perPerson', 'buffer') as $k => $v) {
            if (!is_finite($v) || $v < 0) {
                throw new InvalidArgumentException("$k must be a non-negative number");
            }
        }
        if ($uptake > 1) {
            throw new InvalidArgumentException('uptake must be between 0 and 1');
        }
        $base = self::ceilClean($attendees * $days * $uptake * $perPerson);
        $extra = self::ceilClean($base * $buffer);
        return ['base' => $base, 'buffer' => $extra, 'total' => $base + $extra];
    }

    /** Step back $n working days (Mon-Fri) from a day number. */
    public static function subtractWorkingDays(int $day, int $n): int
    {
        while ($n > 0) {
            $day--;
            if (self::weekday($day) <= 5) {
                $n--;
            }
        }
        return $day;
    }

    /** Latest date to order printed merchandise: production + delivery + safety working days before the event. */
    public static function orderByDate(string $eventDate, int $productionDays = 10, int $deliveryDays = 3, int $safetyDays = 2, ?string $today = null): array
    {
        foreach (compact('productionDays', 'deliveryDays', 'safetyDays') as $k => $v) {
            if ($v < 0) {
                throw new InvalidArgumentException("$k must be a non-negative integer");
            }
        }
        $workingDays = $productionDays + $deliveryDays + $safetyDays;
        $by = self::subtractWorkingDays(self::toDay($eventDate), $workingDays);
        $out = ['orderBy' => self::fromDay($by), 'workingDays' => $workingDays];
        if ($today !== null) {
            $out['daysFromToday'] = $by - self::toDay($today);
            $out['late'] = $out['daysFromToday'] < 0;
        }
        return $out;
    }
}
