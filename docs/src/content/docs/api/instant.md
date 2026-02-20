---
title: Instant
description: A precise point in time as nanoseconds since the Unix epoch.
---

`Temporal\Instant` represents a specific point in time as nanoseconds since the Unix epoch (`1970-01-01T00:00:00Z`). It has no timezone or calendar — it is a pure UTC timestamp with nanosecond precision.

It corresponds to `Temporal.Instant` in the TC39 proposal.

## Construction

`Instant` does not have a public constructor. Use the static factory methods:

### `fromEpochNanoseconds()`

```php
public static function fromEpochNanoseconds(int $nanoseconds): Instant
```

```php
use Temporal\Instant;

$instant = Instant::fromEpochNanoseconds(1_741_939_200_000_000_000);
```

### `fromEpochMicroseconds()`

```php
public static function fromEpochMicroseconds(int $microseconds): Instant
```

### `fromEpochMilliseconds()`

```php
public static function fromEpochMilliseconds(int $milliseconds): Instant
```

Useful for interoperating with JavaScript's `Date.now()`:

```php
$instant = Instant::fromEpochMilliseconds(1_741_939_200_000);
```

### `fromEpochSeconds()`

```php
public static function fromEpochSeconds(int $seconds): Instant
```

### `from()`

```php
public static function from(Instant|string $value): Instant
```

Parse from an ISO 8601 UTC string:

```php
$instant = Instant::from('2025-03-14T09:30:00Z');
$instant = Instant::from('2025-03-14T09:30:00.123456789Z');
$instant = Instant::from('2025-03-14T10:30:00+01:00'); // offset is normalised to UTC
```

## Properties

Properties are virtual (accessed via `__get()`).

| Property | Type | Description |
|----------|------|-------------|
| `$epochNanoseconds` | `int` | Nanoseconds since Unix epoch |
| `$epochMicroseconds` | `int` | Microseconds (truncated toward zero) |
| `$epochMilliseconds` | `int` | Milliseconds (truncated toward zero) |
| `$epochSeconds` | `int` | Seconds (truncated toward zero) |

```php
$instant = Instant::from('2025-03-14T09:30:00Z');
echo $instant->epochSeconds;      // 1741942200
echo $instant->epochMilliseconds; // 1741942200000
echo $instant->epochNanoseconds;  // 1741942200000000000
```

## Precision Range Note

An `Instant` is stored as a single PHP `int`. On 64-bit systems PHP integers are 64 bits, which limits nanosecond precision to roughly **1678-09-17 to 2262-04-11**. For dates outside this range use `epochMilliseconds` or `epochSeconds`.

## Arithmetic

### `add()`

```php
public function add(Duration|array $duration): Instant
```

Add a duration. Only time-based components are allowed (hours, minutes, seconds, etc.) — calendar-based components (years, months) require a timezone and are not supported on `Instant`.

```php
$instant = Instant::from('2025-03-14T00:00:00Z');
$later   = $instant->add(['hours' => 3, 'minutes' => 30]);
```

### `subtract()`

```php
public function subtract(Duration|array $duration): Instant
```

### `until()`

```php
public function until(Instant $other): Duration
```

Duration from this instant to `$other`.

### `since()`

```php
public function since(Instant $other): Duration
```

### `round()`

```php
public function round(string|array $options): Instant
```

Round to a time unit. Options:
- `smallestUnit`: `'hour'`, `'minute'`, `'second'`, `'millisecond'`, `'microsecond'`, `'nanosecond'`
- `roundingMode`: `'halfExpand'` *(default)*, `'ceil'`, `'floor'`, `'trunc'`
- `roundingIncrement`: integer (default `1`)

```php
$i       = Instant::from('2025-03-14T09:32:47Z');
$rounded = $i->round('minute');
echo $rounded; // 2025-03-14T09:33:00Z
```

## Comparison

### `compare()`

```php
public static function compare(Instant $a, Instant $b): int
```

### `equals()`

```php
public function equals(Instant $other): bool
```

## Conversion

### `toZonedDateTimeISO()`

```php
public function toZonedDateTimeISO(TimeZone|string $timeZone): ZonedDateTime
```

Convert to a `ZonedDateTime` using the ISO 8601 calendar.

```php
$instant = Instant::from('2025-03-14T09:30:00Z');
$zdt     = $instant->toZonedDateTimeISO('Europe/Amsterdam');
echo $zdt; // 2025-03-14T10:30:00+01:00[Europe/Amsterdam]
```

### `toZonedDateTime()`

```php
public function toZonedDateTime(TimeZone|string|array $options): ZonedDateTime
```

Like `toZonedDateTimeISO()` but accepts array options (e.g. `['timeZone' => '...', 'calendar' => 'iso8601']`).

## String Representation

```php
public function __toString(): string    // "2025-03-14T09:30:00Z"
public function jsonSerialize(): string // same
```

Always outputs a UTC string ending in `Z`, with sub-second precision included only when non-zero.
