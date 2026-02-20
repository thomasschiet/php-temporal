# PHP Temporal

A faithful port of the [TC39 Temporal API](https://tc39.es/proposal-temporal/docs/) to PHP.

PHP Temporal provides a complete, modern date/time library with nanosecond precision, DST-aware arithmetic, and multi-calendar support &mdash; all without depending on `\DateTime` or any PHP extensions.

## Features

- **All 11 Temporal types** &mdash; `PlainDate`, `PlainTime`, `PlainDateTime`, `Duration`, `Instant`, `ZonedDateTime`, `TimeZone`, `Calendar`, `PlainYearMonth`, `PlainMonthDay`, `Now`
- **Fully immutable** &mdash; every operation returns a new instance
- **Nanosecond precision** &mdash; `Instant` and `ZonedDateTime` track time to the nanosecond
- **DST-aware** &mdash; `ZonedDateTime` correctly handles daylight saving transitions and ambiguous/skipped times
- **ISO 8601 parsing** &mdash; extended years, calendar annotations, UTC offset suffixes
- **Multi-calendar support** &mdash; ISO 8601, Gregorian (with eras), and Buddhist calendars, extensible via `CalendarProtocol`
- **Typed exceptions** &mdash; a 9-class exception hierarchy under `Temporal\Exception`
- **Zero runtime dependencies** &mdash; pure PHP, no extensions required
- **5,400+ tests** &mdash; including 4,200+ from the TC39 test262 reference suite

## Requirements

- PHP 8.4 or 8.5

## Installation

```bash
composer require php-temporal/php-temporal
```

## Quick Start

### Dates

```php
use Temporal\PlainDate;

$date = PlainDate::from('2025-03-14');

$later = $date->add(['months' => 1, 'days' => 5]);
echo $later; // 2025-04-19

$duration = $date->until(PlainDate::from('2025-12-31'));
echo $duration->days; // 292
```

### Times

```php
use Temporal\PlainTime;

$time = PlainTime::from('09:30:00');

$later = $time->add(['minutes' => 90]);
echo $later; // 11:00:00

$rounded = $time->round(['smallestUnit' => 'minute', 'roundingIncrement' => 15]);
echo $rounded; // 09:30:00
```

### Durations

```php
use Temporal\Duration;

$d = Duration::from('P1Y2M3DT4H5M6S');
echo $d->years;  // 1
echo $d->months; // 2

$balanced = Duration::from(['hours' => 25])->balance('days');
echo $balanced; // P1DT1H
```

### Instants and Time Zones

```php
use Temporal\Instant;
use Temporal\ZonedDateTime;

$instant = Instant::fromEpochMilliseconds(1741939200000);
$zdt = $instant->toZonedDateTimeISO('Europe/Amsterdam');
echo $zdt->toPlainDate(); // 2025-03-14

$zdt = ZonedDateTime::from('2025-03-14T09:30:00[Europe/Amsterdam]');
echo $zdt->offset; // +01:00

// DST-aware arithmetic
$zdt2 = $zdt->add(['months' => 1]);
```

### Current Time

```php
use Temporal\Now;

$instant = Now::instant();
$date    = Now::plainDateISO('Europe/Amsterdam');
$time    = Now::plainTimeISO('America/New_York');
$tz      = Now::timeZoneId();
```

## API Overview

| Type | Description |
|------|-------------|
| `PlainDate` | Calendar date (year, month, day) without time or time zone |
| `PlainTime` | Wall-clock time without date or time zone |
| `PlainDateTime` | Date and time without time zone |
| `Duration` | Length of time (years through nanoseconds) |
| `Instant` | Exact moment on the UTC timeline (nanosecond precision) |
| `ZonedDateTime` | Date/time in a specific time zone (DST-aware) |
| `TimeZone` | IANA time zone or fixed UTC offset |
| `Calendar` | Calendar system (ISO 8601, Gregorian, Buddhist) |
| `PlainYearMonth` | Year and month without a day |
| `PlainMonthDay` | Month and day without a year |
| `Now` | Static helpers for the current date, time, and instant |

## Development

```bash
# Install dependencies
composer install

# Run core tests
./vendor/bin/phpunit --testsuite Temporal

# Run TC39 test262 reference tests
./vendor/bin/phpunit --testsuite test262

# Run all tests
./vendor/bin/phpunit

# Format, lint, and analyze
./vendor/bin/mago fmt
./vendor/bin/mago lint
./vendor/bin/mago analyze
```

## License

MIT
