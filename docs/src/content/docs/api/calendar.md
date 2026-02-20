---
title: Calendar
description: Calendar system facade — ISO 8601, Gregory, Buddhist, ROC, and Japanese calendars.
---

`Temporal\Calendar` represents a calendar system. Five calendars are supported: **ISO 8601** (`'iso8601'`), **Gregory** (`'gregory'`), **Buddhist** (`'buddhist'`), **ROC/Minguo** (`'roc'`), and **Japanese** (`'japanese'`).

The `Calendar` class is a facade over `CalendarProtocol` implementations. It is primarily used internally by other types, but is exposed for direct use when calendar operations are needed.

## Construction

### `from()`

```php
public static function from(string|Calendar $item): Calendar
```

Create a `Calendar` from a string identifier (case-insensitive) or another `Calendar`.

```php
use Temporal\Calendar;

$iso      = Calendar::from('iso8601');
$gregory  = Calendar::from('gregory');
$buddhist = Calendar::from('buddhist');
$roc      = Calendar::from('roc');
$japanese = Calendar::from('japanese');
```

**Throws** `UnsupportedCalendarException` for unrecognised identifiers.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `string` | Calendar identifier: `'iso8601'`, `'gregory'`, `'buddhist'`, `'roc'`, or `'japanese'` |

## Factory Methods

### `dateFromFields()`

```php
public function dateFromFields(array $fields, string $overflow = 'constrain'): PlainDate
```

Create a `PlainDate` from field values, with overflow handling.

### `yearMonthFromFields()`

```php
public function yearMonthFromFields(array $fields, string $overflow = 'constrain'): PlainYearMonth
```

### `monthDayFromFields()`

```php
public function monthDayFromFields(array $fields, string $overflow = 'constrain'): PlainMonthDay
```

## Arithmetic

### `dateAdd()`

```php
public function dateAdd(PlainDate $date, Duration $duration, string $overflow = 'constrain'): PlainDate
```

### `dateUntil()`

```php
public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit = 'day'): Duration
```

## Field Helpers

### `fields()`

```php
public function fields(array $fields): array
```

Returns the list of field names that this calendar supports.

### `mergeFields()`

```php
public function mergeFields(array $fields, array $additionalFields): array
```

Merge two sets of fields, with `$additionalFields` taking precedence.

### Individual Field Accessors

| Method | Parameter | Return | Description |
|--------|-----------|--------|-------------|
| `year()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | Year |
| `month()` | `PlainDate\|PlainDateTime\|PlainYearMonth\|PlainMonthDay` | `int` | Month (1–12) |
| `monthCode()` | `PlainDate\|PlainDateTime\|PlainYearMonth\|PlainMonthDay` | `string` | Month code (e.g. `'M03'`) |
| `day()` | `PlainDate\|PlainDateTime\|PlainMonthDay` | `int` | Day of month |
| `dayOfWeek()` | `PlainDate\|PlainDateTime` | `int` | ISO day of week (1=Monday) |
| `dayOfYear()` | `PlainDate\|PlainDateTime` | `int` | Day of year (1-based) |
| `weekOfYear()` | `PlainDate\|PlainDateTime` | `int` | ISO week number (1–53) |
| `daysInWeek()` | — | `int` | Always 7 |
| `daysInMonth()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | Days in month |
| `daysInYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | 365 or 366 |
| `monthsInYear()` | — | `int` | Always 12 |
| `inLeapYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `bool` | Whether leap year |
| `era()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `?string` | `null` (ISO), `'ce'`/`'bce'` (Gregory), `'be'` (Buddhist) |
| `eraYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `?int` | `null` (ISO), era-relative year (Gregory/Buddhist) |

## Comparison / Equality

```php
public function equals(Calendar $other): bool
public function __toString(): string // returns the calendar $id
```

## Supported Calendars

| Identifier | Class | Eras | Year mapping |
|------------|-------|------|-------------|
| `iso8601` | `IsoCalendar` | None (`null`) | ISO year directly |
| `gregory` | `GregoryCalendar` | `ce` (year >= 1), `bce` (year <= 0) | ISO 1 = CE 1, ISO 0 = BCE 1, ISO -1 = BCE 2 |
| `buddhist` | `BuddhistCalendar` | `be` | ISO year + 543 (e.g. ISO 2025 = BE 2568) |
| `roc` | `RocCalendar` | `roc` (year >= 1912), `before-roc` (year < 1912) | ISO 1912 = ROC 1, ISO 2024 = ROC 113 |
| `japanese` | `JapaneseCalendar` | `meiji`, `taisho`, `showa`, `heisei`, `reiwa` (era-relative years); `japanese` for pre-Meiji | ISO 2024 = Reiwa 6 |

All calendars share the same month/day structure and leap year rules. Non-ISO calendars add era support and calendar-relative year numbering on top of the ISO 8601 foundation.

Unsupported identifiers (e.g. `'hebrew'`, `'islamic'`) throw `UnsupportedCalendarException`.

## PlainDateTime and ZonedDateTime Calendar Support

`PlainDateTime` and `ZonedDateTime` now carry a `CalendarProtocol` and expose `withCalendar()`:

```php
use Temporal\PlainDateTime;
use Temporal\ZonedDateTime;

// PlainDateTime
$pdt = new PlainDateTime(2024, 3, 15, 12, 30);
$pdtJp = $pdt->withCalendar('japanese');
echo $pdtJp->era;      // 'reiwa'
echo $pdtJp->eraYear;  // 6
echo (string) $pdtJp;  // '2024-03-15T12:30:00[u-ca=japanese]'

// ZonedDateTime
$zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
$zdtRoc = $zdt->withCalendar('roc');
echo $zdtRoc->calendarId; // 'roc'
echo $zdtRoc->era;        // 'roc'
echo $zdtRoc->eraYear;    // 59 (1970 - 1912 + 1)
```

The calendar annotation `[u-ca=...]` is appended to ISO 8601 strings for non-ISO calendars, and is parsed back when using `PlainDateTime::from()` or `ZonedDateTime::from()` with an annotated string.
