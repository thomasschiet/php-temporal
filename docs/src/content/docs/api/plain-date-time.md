---
title: PlainDateTime
description: A calendar date combined with a wall-clock time — no timezone information.
---

`Temporal\PlainDateTime` represents a calendar date combined with a wall-clock time, with no timezone information. It corresponds to `Temporal.PlainDateTime` in the TC39 proposal.

## Constructor

```php
new PlainDateTime(
    int $year,
    int $month,
    int $day,
    int $hour = 0,
    int $minute = 0,
    int $second = 0,
    int $millisecond = 0,
    int $microsecond = 0,
    int $nanosecond = 0
)
```

```php
use Temporal\PlainDateTime;

$dt = new PlainDateTime(2025, 3, 14, 9, 30, 0);
echo $dt; // 2025-03-14T09:30:00
```

## Properties

All properties are `readonly`.

| Property | Type | Description |
|----------|------|-------------|
| `$year` | `int` | Year |
| `$month` | `int` | Month (1–12) |
| `$day` | `int` | Day (1–31) |
| `$hour` | `int` | Hour (0–23) |
| `$minute` | `int` | Minute (0–59) |
| `$second` | `int` | Second (0–59) |
| `$millisecond` | `int` | Millisecond component (0–999) |
| `$microsecond` | `int` | Microsecond component (0–999) |
| `$nanosecond` | `int` | Nanosecond component (0–999) |

Virtual (computed via `__get()`): `calendarId`, `dayOfWeek`, `dayOfYear`, `weekOfYear`, `yearOfWeek`, `daysInMonth`, `daysInYear`, `inLeapYear`.

## Static Constructors

### `from()`

```php
public static function from(string|array|PlainDateTime $item): PlainDateTime
```

```php
$dt = PlainDateTime::from('2025-03-14T09:30:00');
$dt = PlainDateTime::from('2025-03-14T09:30:00.123456789');
$dt = PlainDateTime::from([
    'year' => 2025, 'month' => 3, 'day' => 14,
    'hour' => 9, 'minute' => 30,
]);
```

## Conversion Methods

### `toPlainDate()`

```php
public function toPlainDate(): PlainDate
```

### `toPlainTime()`

```php
public function toPlainTime(): PlainTime
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
public function toZonedDateTime(TimeZone|string $timeZone): ZonedDateTime
```

Convert to a `ZonedDateTime` by interpreting this datetime in the given timezone.

```php
$dt  = PlainDateTime::from('2025-03-14T09:30:00');
$zdt = $dt->toZonedDateTime('Europe/Amsterdam');
echo $zdt; // 2025-03-14T09:30:00+01:00[Europe/Amsterdam]
```

### `getISOFields()`

```php
public function getISOFields(): array
```

## Mutation Methods

### `with()`

```php
public function with(array $fields): PlainDateTime
```

Return a copy with specific fields replaced. Accepts any combination of date and time fields.

### `withPlainDate()`

```php
public function withPlainDate(PlainDate $date): PlainDateTime
```

Return a copy with the date portion replaced.

### `withPlainTime()`

```php
public function withPlainTime(PlainTime $time): PlainDateTime
```

Return a copy with the time portion replaced.

### `add()`

```php
public function add(Duration|array $duration, string $overflow = 'constrain'): PlainDateTime
```

Add a duration. Handles date overflow with `constrain` or `reject`.

### `subtract()`

```php
public function subtract(Duration|array $duration, string $overflow = 'constrain'): PlainDateTime
```

### `round()`

```php
public function round(string|array $options): PlainDateTime
```

Round to the nearest unit. Accepts the same options as `PlainTime::round()`.

```php
$dt      = PlainDateTime::from('2025-03-14T09:32:47');
$rounded = $dt->round('minute');
echo $rounded; // 2025-03-14T09:33:00
```

## Comparison Methods

### `until()`

```php
public function until(PlainDateTime $other, string|array $options = []): Duration
```

### `since()`

```php
public function since(PlainDateTime $other, string|array $options = []): Duration
```

### `compare()`

```php
public static function compare(PlainDateTime $a, PlainDateTime $b): int
```

### `equals()`

```php
public function equals(PlainDateTime $other): bool
```

## String Representation

```php
public function __toString(): string    // "2025-03-14T09:30:00"
public function jsonSerialize(): string // same
```
