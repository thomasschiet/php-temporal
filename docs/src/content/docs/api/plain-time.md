---
title: PlainTime
description: A wall-clock time with no date or timezone — hour, minute, second, and sub-second precision.
---

`Temporal\PlainTime` represents a wall-clock time (hour, minute, second, and sub-second components) with no date or timezone information. It corresponds to `Temporal.PlainTime` in the TC39 proposal.

## Constructor

```php
new PlainTime(
    int $hour = 0,
    int $minute = 0,
    int $second = 0,
    int $millisecond = 0,
    int $microsecond = 0,
    int $nanosecond = 0
)
```

All parameters default to `0`.

```php
use Temporal\PlainTime;

$time = new PlainTime(9, 30, 0);
echo $time; // 09:30:00

$precise = new PlainTime(9, 30, 0, 123, 456, 789);
echo $precise; // 09:30:00.123456789
```

**Throws** `DateRangeException` if any field is out of its valid range.

## Properties

All properties are `readonly`.

| Property | Type | Range | Description |
|----------|------|-------|-------------|
| `$hour` | `int` | 0–23 | Hour of the day |
| `$minute` | `int` | 0–59 | Minute |
| `$second` | `int` | 0–59 | Second |
| `$millisecond` | `int` | 0–999 | Millisecond component |
| `$microsecond` | `int` | 0–999 | Microsecond component |
| `$nanosecond` | `int` | 0–999 | Nanosecond component |

Note: `millisecond`, `microsecond`, and `nanosecond` are **separate** components (not cumulative). For example, `09:30:00.001002003` has `millisecond=1`, `microsecond=2`, `nanosecond=3`.

## Static Constructors

### `from()`

```php
public static function from(string|array|PlainTime $item): PlainTime
```

Create a `PlainTime` from:
- An ISO 8601 time string
- An associative array (`hour` and `minute` required; others default to 0)
- Another `PlainTime` instance

```php
$t = PlainTime::from('09:30:00');
$t = PlainTime::from('09:30:00.123456789');
$t = PlainTime::from(['hour' => 9, 'minute' => 30]);
```

### `fromNanosecondsSinceMidnight()`

```php
public static function fromNanosecondsSinceMidnight(int $ns): PlainTime
```

Create from a count of nanoseconds since midnight. Values wrap modulo one day.

```php
$ns = 9 * 3_600_000_000_000 + 30 * 60_000_000_000; // 09:30:00
$t  = PlainTime::fromNanosecondsSinceMidnight($ns);
echo $t; // 09:30:00
```

## Conversion Methods

### `toNanosecondsSinceMidnight()`

```php
public function toNanosecondsSinceMidnight(): int
```

### `toPlainDateTime()`

```php
public function toPlainDateTime(PlainDate $date): PlainDateTime
```

Combine with a date to get a `PlainDateTime`.

```php
$time = PlainTime::from('09:30:00');
$date = \Temporal\PlainDate::from('2025-03-14');
$dt   = $time->toPlainDateTime($date);
echo $dt; // 2025-03-14T09:30:00
```

### `getISOFields()`

```php
public function getISOFields(): array
```

Returns an array with all six ISO fields.

## Mutation Methods

### `with()`

```php
public function with(array $fields): PlainTime
```

Return a copy with specific fields replaced.

```php
$time = PlainTime::from('09:30:00');
$noon = $time->with(['hour' => 12]);
echo $noon; // 12:30:00
```

### `add()`

```php
public function add(array $duration): PlainTime
```

Add a duration. Time wraps around midnight — there is no overflow error.

```php
$time = PlainTime::from('23:00:00');
echo $time->add(['hours' => 2]); // 01:00:00 (wraps midnight)
```

### `subtract()`

```php
public function subtract(array $duration): PlainTime
```

Subtract a duration. Time wraps similarly.

### `round()`

```php
public function round(string|array $options): PlainTime
```

Round to the nearest unit. `$options` can be a unit string or an array:
- `smallestUnit`: `'hour'`, `'minute'`, `'second'`, `'millisecond'`, `'microsecond'`, `'nanosecond'`
- `roundingIncrement`: integer (default `1`)
- `roundingMode`: `'halfExpand'` *(default)*, `'ceil'`, `'floor'`, `'trunc'`

```php
$time = PlainTime::from('09:32:47');

echo $time->round('minute');  // 09:33:00
echo $time->round('hour');    // 10:00:00

// Round to nearest 15 minutes
echo $time->round(['smallestUnit' => 'minute', 'roundingIncrement' => 15]);
// 09:30:00
```

## Comparison Methods

### `until()`

```php
public function until(PlainTime $other, string|array $options = []): Duration
```

Duration from this time to `$other`.

### `since()`

```php
public function since(PlainTime $other, string|array $options = []): Duration
```

Duration from `$other` to this time.

### `compare()`

```php
public static function compare(PlainTime $a, PlainTime $b): int
```

Returns `-1`, `0`, or `1`.

### `equals()`

```php
public function equals(PlainTime $other): bool
```

## String Representation

```php
public function __toString(): string     // "09:30:00" or "09:30:00.123456789"
public function jsonSerialize(): string  // same
```

Sub-second parts are omitted if all zero. Trailing zero sub-second groups are omitted but the precision level is maintained.
