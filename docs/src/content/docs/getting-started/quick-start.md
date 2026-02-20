---
title: Quick Start
description: A quick introduction to the key PHP Temporal types with examples.
---

## Working with Dates

```php
<?php

use Temporal\PlainDate;
use Temporal\Duration;

// Create from constructor
$date = new PlainDate(2025, 3, 14);

// Parse from ISO 8601 string
$date = PlainDate::from('2025-03-14');

// Add 1 month and 5 days
$later = $date->add(['months' => 1, 'days' => 5]);
echo $later; // 2025-04-19

// Difference between two dates
$start = PlainDate::from('2025-01-01');
$end   = PlainDate::from('2025-12-31');
$duration = $start->until($end);
echo $duration->days; // 364

// Comparison
$a = PlainDate::from('2025-03-14');
$b = PlainDate::from('2025-06-01');
echo PlainDate::compare($a, $b); // -1 (a is before b)
```

## Working with Times

```php
<?php

use Temporal\PlainTime;

$time = new PlainTime(9, 30, 0);
$time = PlainTime::from('09:30:00');

// Add 90 minutes (wraps around midnight)
$later = $time->add(['minutes' => 90]);
echo $later; // 11:00:00

// Round to nearest 15 minutes
$rounded = $time->round(['smallestUnit' => 'minute', 'roundingIncrement' => 15]);
echo $rounded; // 09:30:00
```

## Working with DateTimes

```php
<?php

use Temporal\PlainDateTime;

$dt = PlainDateTime::from('2025-03-14T09:30:00');

// Separate date and time
echo $dt->toPlainDate(); // 2025-03-14
echo $dt->toPlainTime(); // 09:30:00

// Arithmetic
$tomorrow = $dt->add(['days' => 1]);
echo $tomorrow; // 2025-03-15T09:30:00
```

## Working with Durations

```php
<?php

use Temporal\Duration;

$d = Duration::from('P1Y2M3DT4H5M6S');
echo $d->years;   // 1
echo $d->months;  // 2
echo $d->days;    // 3

// Negate
$neg = $d->negated();

// Balance to a specific largest unit
$balanced = Duration::from(['hours' => 25])->balance('days');
echo $balanced; // P1DT1H
```

## Working with Instants

```php
<?php

use Temporal\Instant;
use Temporal\TimeZone;

// From epoch milliseconds (e.g. JavaScript Date.now())
$instant = Instant::fromEpochMilliseconds(1741939200000);

// Convert to a zoned datetime
$zdt = $instant->toZonedDateTimeISO('Europe/Amsterdam');
echo $zdt->toPlainDate(); // 2025-03-14

// Current instant
$now = \Temporal\Now::instant();
```

## Working with ZonedDateTimes

```php
<?php

use Temporal\ZonedDateTime;
use Temporal\TimeZone;

$zdt = ZonedDateTime::from('2025-03-14T09:30:00[Europe/Amsterdam]');

// Access components
echo $zdt->year;      // 2025
echo $zdt->timeZone;  // Europe/Amsterdam
echo $zdt->offset;    // +01:00

// DST-aware arithmetic
$zdt2 = $zdt->add(['months' => 1]); // Handles DST transitions
```

## The Now Helpers

```php
<?php

use Temporal\Now;

// Current instant (nanosecond precision)
$instant = Now::instant();

// Current date in a given timezone
$date = Now::plainDateISO('Europe/Amsterdam');

// Current time
$time = Now::plainTimeISO('America/New_York');

// System timezone
$tz = Now::timeZoneId();
```
