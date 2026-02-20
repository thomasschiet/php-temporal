---
title: PlainDate
description: A calendar date with no time or timezone — year, month, day.
---

`Temporal\PlainDate` represents a calendar date (year, month, day) with no time or timezone information. It corresponds to `Temporal.PlainDate` in the TC39 proposal.

## Constructor

```php
new PlainDate(int $year, int $month, int $day)
```

```php
use Temporal\PlainDate;

$date = new PlainDate(2025, 3, 14);
echo $date; // 2025-03-14
```

**Throws** `DateRangeException` if the month or day are out of range, or if the date is outside the supported epoch day range.

## Properties

All properties are `readonly`.

| Property | Type | Description |
|----------|------|-------------|
| `$year` | `int` | The year (can be negative for BCE) |
| `$month` | `int` | The month (1–12) |
| `$day` | `int` | The day of the month (1–31) |
| `$calendarId` | `string` | Always `'iso8601'` (virtual) |
| `$dayOfWeek` | `int` | ISO day of week: Monday=1, Sunday=7 (virtual) |
| `$dayOfYear` | `int` | Day of year, 1-based (virtual) |
| `$weekOfYear` | `int` | ISO week number 1–53 (virtual) |
| `$yearOfWeek` | `int` | ISO week-numbering year (virtual) |
| `$daysInMonth` | `int` | Number of days in this month (virtual) |
| `$daysInYear` | `int` | 365 or 366 (virtual) |
| `$inLeapYear` | `bool` | Whether this is a leap year (virtual) |

Virtual properties are accessed via `__get()` and are listed in the class PHPDoc.

## Static Constructors

### `from()`

```php
public static function from(string|array|PlainDate $item): PlainDate
```

Create a `PlainDate` from:
- An ISO 8601 date string (`'2025-03-14'`)
- An associative array (`['year' => 2025, 'month' => 3, 'day' => 14]`)
- Another `PlainDate` instance (copies it)

```php
$a = PlainDate::from('2025-03-14');
$b = PlainDate::from(['year' => 2025, 'month' => 3, 'day' => 14]);
$c = PlainDate::from($a); // copy
```

### `fromEpochDays()`

```php
public static function fromEpochDays(int $epochDays): PlainDate
```

Create from a count of days since the Unix epoch (1970-01-01 = 0).

```php
$date = PlainDate::fromEpochDays(0);
echo $date; // 1970-01-01
```

## Conversion Methods

### `toEpochDays()`

```php
public function toEpochDays(): int
```

Returns the number of days since the Unix epoch.

### `toPlainDateTime()`

```php
public function toPlainDateTime(?PlainTime $time = null): PlainDateTime
```

Combine with a `PlainTime` (defaults to midnight) to get a `PlainDateTime`.

```php
$date = PlainDate::from('2025-03-14');
$dt   = $date->toPlainDateTime(new PlainTime(9, 30));
echo $dt; // 2025-03-14T09:30:00
```

### `toPlainYearMonth()`

```php
public function toPlainYearMonth(): PlainYearMonth
```

### `toPlainMonthDay()`

```php
public function toPlainMonthDay(): PlainMonthDay
```

### `toZonedDateTime()`

```php
public function toZonedDateTime(TimeZone|string|array $options): ZonedDateTime
```

Convert to a `ZonedDateTime`. The `$options` parameter can be:
- A `TimeZone` or timezone string: `'Europe/Amsterdam'`
- An array: `['timeZone' => 'Europe/Amsterdam', 'disambiguation' => 'compatible']`

```php
$date = PlainDate::from('2025-03-14');
$zdt  = $date->toZonedDateTime('Europe/Amsterdam');
```

### `getISOFields()`

```php
public function getISOFields(): array
```

Returns `['isoYear' => ..., 'isoMonth' => ..., 'isoDay' => ...]`.

## Mutation Methods

All mutation methods return **new instances**.

### `with()`

```php
public function with(array $fields): PlainDate
```

Return a copy with specific fields replaced.

```php
$date = PlainDate::from('2025-03-14');
$first = $date->with(['day' => 1]);
echo $first; // 2025-03-01
```

### `add()`

```php
public function add(array $duration, string $overflow = 'constrain'): PlainDate
```

Add a duration. The `$overflow` option controls how out-of-range results are handled:
- `'constrain'` *(default)*: clamp to valid range
- `'reject'`: throw `\ArithmeticError`

```php
$date = PlainDate::from('2025-01-31');
echo $date->add(['months' => 1]);               // 2025-02-28 (constrained)
echo $date->add(['months' => 1], 'reject');     // ArithmeticError
echo $date->add(['years' => 1, 'days' => 10]); // 2026-02-10
```

### `subtract()`

```php
public function subtract(array $duration, string $overflow = 'constrain'): PlainDate
```

Subtract a duration. Same overflow semantics as `add()`.

## Comparison Methods

### `until()`

```php
public function until(PlainDate $other, string|array $options = []): Duration
```

Returns a `Duration` representing the time from this date to `$other`.

```php
$start = PlainDate::from('2025-01-01');
$end   = PlainDate::from('2025-12-31');
echo $start->until($end)->days; // 364
```

Options:
- `largestUnit`: `'year'`, `'month'`, `'week'`, `'day'` *(default)*
- `smallestUnit`: smallest unit to include (default `'day'`)
- `roundingMode`: `'trunc'` *(default)*, `'ceil'`, `'floor'`, `'halfExpand'`

### `since()`

```php
public function since(PlainDate $other, string|array $options = []): Duration
```

Like `until()` but in reverse — the duration from `$other` to this date.

### `compare()`

```php
public static function compare(PlainDate $a, PlainDate $b): int
```

Returns `-1`, `0`, or `1`. Suitable for `usort()`.

```php
$dates = [PlainDate::from('2025-06-01'), PlainDate::from('2025-01-01')];
usort($dates, PlainDate::compare(...));
```

### `equals()`

```php
public function equals(PlainDate $other): bool
```

Returns `true` if both dates represent the same day.

## String Representation

```php
public function __toString(): string      // "2025-03-14"
public function jsonSerialize(): string   // "2025-03-14"
```

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `MIN_EPOCH_DAYS` | `-100_000_001` | Earliest supported date (April 19, −271821) |
| `MAX_EPOCH_DAYS` | `100_000_000` | Latest supported date (September 13, +275760) |
