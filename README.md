# gopromotional/event-planner

Plan an event or trade show from the command line or your code: a countdown, a 12-step planning timeline with due dates, an estimate of how many giveaway items to order, and the latest order date for printed merchandise in working days.

- **Countdown** to the event in days, and in weeks + days.
- **Planning timeline**: 12 tasks from 8 weeks out to the post-event follow-up, each with a due date, days left and an overdue flag.
- **Giveaway quantity**: attendees × days × uptake × items per person, plus a buffer, rounded up.
- **Order-by date**: production + delivery + safety margin counted back in working days (Mon–Fri).

## Install

```sh
composer require gopromotional/event-planner
```

## Usage

```php
use GoPromotional\EventPlanner\EventPlanner;

EventPlanner::countdown('2026-12-03', '2026-10-19');
// ['eventDate' => '2026-12-03', 'today' => '2026-10-19', 'days' => 45, 'weeks' => 6, 'remainderDays' => 3, 'status' => 'upcoming']

EventPlanner::giveawayQuantity(attendees: 500, days: 2);   // ['base' => 600, 'buffer' => 60, 'total' => 660]
EventPlanner::orderByDate('2026-12-03', today: '2026-10-19'); // ['orderBy' => '2026-11-12', 'workingDays' => 15, ...]

foreach (EventPlanner::timeline('2026-12-03', '2026-10-19') as $row) {
    echo "{$row['due']}  {$row['task']}" . ($row['overdue'] ? ' (overdue)' : '') . PHP_EOL;
}
```

Pure PHP 8.1+, no dependencies. Run the tests with `composer install && composer test`.

## Links

- Website: [branded merchandise](https://www.gopromotional.co.uk/)
- Source code: [https://github.com/nbkdevs/GoPromotional](https://github.com/nbkdevs/GoPromotional) (`packages/homepage/php`)
- Also available on npm, PyPI, crates.io and RubyGems as `gopromotional-event-planner`, and as the Docker image `iamnazzal/gopromotional-event-planner`
- Licence: MIT
