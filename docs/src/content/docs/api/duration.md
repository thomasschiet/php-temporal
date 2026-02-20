---
title: Duration
description: A length of time with year, month, week, day, and sub-day components.
---

`Temporal\Duration` represents a length of time. Unlike most date/time types, a `Duration` is not a point in time — it describes a difference that can be added to or subtracted from date/time values.

A `Duration` has ten components: `years`, `months`, `weeks`, `days`, `hours`, `minutes`, `seconds`, `milliseconds`, `microseconds`, and `nanoseconds`.

## Constructor

```php
new Duration(
    int $years = 0,
    int $months = 0,
    int $weeks = 0,
    int $days = 0,
    int $hours = 0,
    int $minutes = 0,
    int $seconds = 0,
    int $milliseconds = 0,
    int $microseconds = 0,
    int $nanoseconds = 0
)
```

```php
use Temporal\Duration;

$d = new Duration(1, 2, 0, 3, 4, 5, 6); // 1y 2m 3d 4h 5m 6s
echo $d; // P1Y2M3DT4H5M6S
```

All components default to `0`. All components must have the same sign (or be zero).

## Properties

All properties are `readonly`.

| Property | Type | Description |
|----------|------|-------------|
| `$years` | `int` | Year component |
| `$months` | `int` | Month component |
| `$weeks` | `int` | Week component |
| `$days` | `int` | Day component |
| `$hours` | `int` | Hour component |
| `$minutes` | `int` | Minute component |
| `$seconds` | `int` | Second component |
| `$milliseconds` | `int` | Millisecond component |
| `$microseconds` | `int` | Microsecond component |
| `$nanoseconds` | `int` | Nanosecond component |
| `$sign` | `int` | `-1`, `0`, or `1` — overall sign of the duration |
| `$blank` | `bool` | `true` if all components are zero |

## Static Constructors

### `from()`

```php
public static function from(Duration|array|string $value): Duration
```

Create from:
- An ISO 8601 duration string
- An associative array with duration field names
- Another `Duration` (copies it)

```php
$d = Duration::from('P1Y2M3DT4H5M6S');
$d = Duration::from('P2W');                   // 2 weeks
$d = Duration::from('PT90M');                 // 90 minutes
$d = Duration::from('-P1Y');                  // negative 1 year
$d = Duration::from(['years' => 1, 'days' => 3]);
```

## Arithmetic Methods

### `negated()`

```php
public function negated(): Duration
```

Return the negation of this duration.

```php
$d = Duration::from('P1Y');
echo $d->negated(); // -P1Y
```

### `abs()`

```php
public function abs(): Duration
```

Return the absolute value (all components non-negative).

### `add()`

```php
public function add(Duration|array|string $other): Duration
```

Add another duration. The result is a new `Duration`.

### `subtract()`

```php
public function subtract(Duration|array|string $other): Duration
```

### `with()`

```php
public function with(array $fields): Duration
```

Return a copy with specific components replaced.

## Analysis Methods

### `total()`

```php
public function total(string|array $unitOrOptions): float
```

Get the total duration in a single unit as a floating-point number.

```php
$d = Duration::from('PT1H30M');
echo $d->total('minutes'); // 90.0
echo $d->total('hours');   // 1.5
```

Options (when passing an array):
- `unit` *(required)*: the target unit
- `relativeTo`: a `PlainDate` or `ZonedDateTime` needed when the duration has calendar units (`years`, `months`, `weeks`)

### `round()`

```php
public function round(string|array $smallestUnitOrOptions): Duration
```

Round to a specified precision.

```php
$d = Duration::from('PT1H32M47S');
echo $d->round('minute'); // PT1H33M
```

Options:
- `smallestUnit` *(required)*
- `largestUnit`
- `roundingMode`: `'halfExpand'` *(default)*, `'ceil'`, `'floor'`, `'trunc'`
- `relativeTo`: required when rounding involves calendar units

### `balance()`

```php
public function balance(string|array $largestUnitOrOptions): Duration
```

Rebalance the duration so that sub-components don't exceed their natural limits, up to the specified largest unit.

```php
$d = Duration::from(['hours' => 25]);
echo $d->balance('days'); // P1DT1H

$d = Duration::from(['minutes' => 90]);
echo $d->balance('hours'); // PT1H30M
```

Options:
- `largestUnit` *(required)*
- `relativeTo`: required for calendar units

## Comparison

### `compare()`

```php
public static function compare(Duration|array|string $one, Duration|array|string $two): int
```

Compare two durations. Requires a `relativeTo` for durations with calendar units.

## String Representation

```php
public function __toString(): string    // "P1Y2M3DT4H5M6S"
public function jsonSerialize(): string // same
```

The output follows ISO 8601 duration notation. Zero-valued components are omitted; a zero duration is output as `PT0S`.

## Notes

- Duration components do **not** need to be balanced. `Duration::from(['hours' => 25])` is valid and distinct from `Duration::from(['days' => 1, 'hours' => 1])` until `balance()` is called.
- All components must share the same sign. A duration cannot have positive years and negative months.
