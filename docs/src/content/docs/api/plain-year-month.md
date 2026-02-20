---
title: PlainYearMonth
description: A year and month with no specific day — useful for monthly billing cycles, expiry dates, etc.
---

`Temporal\PlainYearMonth` represents a specific month in a specific year, with no day information. Useful for representing things like a billing period, a credit card expiry, or any month-granularity concept.

It corresponds to `Temporal.PlainYearMonth` in the TC39 proposal.

## Constructor

```php
new PlainYearMonth(int $year, int $month)
```

```php
use Temporal\PlainYearMonth;

$ym = new PlainYearMonth(2025, 3);
echo $ym; // 2025-03
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$year` | `int` | Year |
| `$month` | `int` | Month (1–12) |
| `$calendarId` | `string` | Always `'iso8601'` (virtual) |
| `$daysInMonth` | `int` | Days in this month (virtual) |
| `$daysInYear` | `int` | 365 or 366 (virtual) |
| `$inLeapYear` | `bool` | Whether leap year (virtual) |
| `$monthsInYear` | `int` | Always 12 (virtual) |

## Static Constructors

### `from()`

```php
public static function from(string|array|PlainYearMonth $item): PlainYearMonth
```

```php
$ym = PlainYearMonth::from('2025-03');
$ym = PlainYearMonth::from(['year' => 2025, 'month' => 3]);
```

## Mutation Methods

### `with()`

```php
public function with(array $fields): PlainYearMonth
```

Return a copy with specific fields replaced.

```php
$ym   = PlainYearMonth::from('2025-03');
$next = $ym->with(['month' => 4]);
echo $next; // 2025-04
```

### `add()`

```php
public function add(array|Duration $duration): PlainYearMonth
```

```php
$ym   = PlainYearMonth::from('2025-11');
$next = $ym->add(['months' => 3]);
echo $next; // 2026-02
```

### `subtract()`

```php
public function subtract(array|Duration $duration): PlainYearMonth
```

## Comparison Methods

### `until()`

```php
public function until(PlainYearMonth $other): Duration
```

### `since()`

```php
public function since(PlainYearMonth $other): Duration
```

### `compare()`

```php
public static function compare(PlainYearMonth $a, PlainYearMonth $b): int
```

### `equals()`

```php
public function equals(PlainYearMonth $other): bool
```

## Conversion

### `toPlainDate()`

```php
public function toPlainDate(int $day): PlainDate
```

Create a `PlainDate` by supplying a specific day of the month.

```php
$ym   = PlainYearMonth::from('2025-03');
$date = $ym->toPlainDate(14);
echo $date; // 2025-03-14
```

### `getISOFields()`

```php
public function getISOFields(): array
```

## String Representation

```php
public function __toString(): string    // "2025-03"
public function jsonSerialize(): string // same
```
