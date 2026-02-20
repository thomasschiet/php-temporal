---
title: PlainMonthDay
description: A recurring month and day with no year — useful for birthdays, holidays, and anniversaries.
---

`Temporal\PlainMonthDay` represents a recurring month-day combination, independent of any specific year. Useful for birthdays, annual holidays, or any recurring date.

It corresponds to `Temporal.PlainMonthDay` in the TC39 proposal.

## Constructor

```php
new PlainMonthDay(int $month, int $day)
```

```php
use Temporal\PlainMonthDay;

$md = new PlainMonthDay(3, 14); // March 14th
echo $md; // --03-14
```

Note: February 29 is valid — it represents a date that only exists in leap years.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$month` | `int` | Month (1–12) |
| `$day` | `int` | Day of the month (1–31) |
| `$calendarId` | `string` | Always `'iso8601'` (virtual) |

## Static Constructors

### `from()`

```php
public static function from(string|array|PlainMonthDay $item): PlainMonthDay
```

```php
$md = PlainMonthDay::from('--03-14');
$md = PlainMonthDay::from(['month' => 3, 'day' => 14]);
```

## Mutation Methods

### `with()`

```php
public function with(array $fields): PlainMonthDay
```

Return a copy with specific fields replaced.

```php
$md   = PlainMonthDay::from('--03-14');
$next = $md->with(['day' => 15]);
echo $next; // --03-15
```

## Conversion

### `toPlainDate()`

```php
public function toPlainDate(int $year): PlainDate
```

Create a `PlainDate` by supplying a specific year.

```php
$md   = PlainMonthDay::from('--03-14');
$date = $md->toPlainDate(2025);
echo $date; // 2025-03-14
```

For `--02-29`, supplying a non-leap year will throw a `DateRangeException`.

### `getISOFields()`

```php
public function getISOFields(): array
```

Returns `['isoMonth' => ..., 'isoDay' => ..., 'isoYear' => ...]`. The `isoYear` is the reference year used for validation (a reference leap year for Feb 29).

## Equality

```php
public function equals(PlainMonthDay $other): bool
```

## String Representation

```php
public function __toString(): string    // "--03-14"
public function jsonSerialize(): string // same
```

The ISO 8601 format for a month-day is `--MM-DD`.
