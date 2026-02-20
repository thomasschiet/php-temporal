---
title: ZonedDateTime
description: A date and time in a specific IANA timezone, with DST awareness.
---

`Temporal\ZonedDateTime` represents a specific moment in time in a specific IANA timezone, including DST awareness. It is the combination of an `Instant`, a `TimeZone`, and a calendar.

It corresponds to `Temporal.ZonedDateTime` in the TC39 proposal.

## Construction

### `fromEpochNanoseconds()`

```php
public static function fromEpochNanoseconds(int $ns, TimeZone|string $timeZone): ZonedDateTime
```

Create from a UTC nanosecond timestamp and a timezone.

```php
use Temporal\ZonedDateTime;

$zdt = ZonedDateTime::fromEpochNanoseconds(1_741_942_200_000_000_000, 'Europe/Amsterdam');
```

### `from()`

```php
public static function from(string|array|ZonedDateTime $item): ZonedDateTime
```

Parse from an ISO 8601 string with a timezone annotation:

```php
$zdt = ZonedDateTime::from('2025-03-14T09:30:00+01:00[Europe/Amsterdam]');
$zdt = ZonedDateTime::from('2025-03-14T09:30:00Z[UTC]');
```

## Properties

All properties are virtual (computed via `__get()`).

### Date/Time Components

| Property | Type | Description |
|----------|------|-------------|
| `$year` | `int` | Year in the local timezone |
| `$month` | `int` | Month (1–12) |
| `$day` | `int` | Day (1–31) |
| `$hour` | `int` | Hour (0–23) |
| `$minute` | `int` | Minute (0–59) |
| `$second` | `int` | Second (0–59) |
| `$millisecond` | `int` | Millisecond component (0–999) |
| `$microsecond` | `int` | Microsecond component (0–999) |
| `$nanosecond` | `int` | Nanosecond component (0–999) |
| `$dayOfWeek` | `int` | ISO day of week (1=Monday, 7=Sunday) |
| `$dayOfYear` | `int` | Day of year (1-based) |
| `$weekOfYear` | `int` | ISO week number |
| `$yearOfWeek` | `int` | ISO week-numbering year |
| `$daysInMonth` | `int` | Days in this month |
| `$daysInYear` | `int` | 365 or 366 |
| `$inLeapYear` | `bool` | Whether this is a leap year |
| `$hoursInDay` | `int\|float` | Hours in this local day (23, 24, or 25 across DST) |

### Timezone/Epoch Properties

| Property | Type | Description |
|----------|------|-------------|
| `$timeZone` | `TimeZone` | The associated timezone |
| `$offset` | `string` | UTC offset string, e.g. `'+01:00'` |
| `$offsetNanoseconds` | `int` | UTC offset in nanoseconds |
| `$epochNanoseconds` | `int` | Nanoseconds since Unix epoch |
| `$epochMicroseconds` | `int` | Microseconds since epoch |
| `$epochMilliseconds` | `int` | Milliseconds since epoch |
| `$epochSeconds` | `int` | Seconds since epoch |
| `$calendarId` | `string` | Always `'iso8601'` |

## Conversion Methods

### `toInstant()`

```php
public function toInstant(): Instant
```

### `toPlainDateTime()`

```php
public function toPlainDateTime(): PlainDateTime
```

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

### `getISOFields()`

```php
public function getISOFields(): array
```

## Mutation Methods

### `with()`

```php
public function with(array $fields): ZonedDateTime
```

Return a copy with specific local date/time fields replaced. Handles DST transitions.

### `withTimeZone()`

```php
public function withTimeZone(TimeZone|string $timeZone): ZonedDateTime
```

Return a copy interpreted in a different timezone (same instant, different local time).

```php
$zdt    = ZonedDateTime::from('2025-03-14T09:30:00+01:00[Europe/Amsterdam]');
$nyZdt  = $zdt->withTimeZone('America/New_York');
echo $nyZdt; // 2025-03-14T04:30:00-04:00[America/New_York]
```

### `withPlainDate()`

```php
public function withPlainDate(PlainDate $date): ZonedDateTime
```

Replace the date portion, keeping the time and timezone.

### `withPlainTime()`

```php
public function withPlainTime(?PlainTime $time = null): ZonedDateTime
```

Replace the time portion (defaults to midnight).

### `startOfDay()`

```php
public function startOfDay(): ZonedDateTime
```

Return the first instant of this calendar day in this timezone. Handles DST gaps (e.g. where midnight doesn't exist).

### `add()`

```php
public function add(Duration|array $duration): ZonedDateTime
```

DST-aware addition. Calendar-unit arithmetic (days, months, years) is performed in local time, then any offset change is resolved.

```php
$zdt = ZonedDateTime::from('2025-03-29T12:00:00+01:00[Europe/Amsterdam]');
// Add 1 day — crosses the spring DST transition
$next = $zdt->add(['days' => 1]);
echo $next; // 2025-03-30T12:00:00+02:00[Europe/Amsterdam]
```

### `subtract()`

```php
public function subtract(Duration|array $duration): ZonedDateTime
```

### `round()`

```php
public function round(string|array $options): ZonedDateTime
```

## Comparison Methods

### `until()`

```php
public function until(ZonedDateTime $other): Duration
```

### `since()`

```php
public function since(ZonedDateTime $other): Duration
```

### `compare()`

```php
public static function compare(ZonedDateTime $a, ZonedDateTime $b): int
```

Compares by the underlying `epochNanoseconds` value.

### `equals()`

```php
public function equals(ZonedDateTime $other): bool
```

## String Representation

```php
public function __toString(): string    // "2025-03-14T09:30:00+01:00[Europe/Amsterdam]"
public function jsonSerialize(): string // same
```

The string includes the UTC offset and the IANA timezone annotation.

## DST Disambiguation

When a local time is ambiguous (clocks fall back) or doesn't exist (clocks spring forward), the `from()` method uses the `disambiguation` option in the string or in an options array:

| Mode | Description |
|------|-------------|
| `'compatible'` | *(default)* Use the earlier offset for gaps, the earlier occurrence for folds |
| `'earlier'` | Use the earlier possibility |
| `'later'` | Use the later possibility |
| `'reject'` | Throw `AmbiguousTimeException` |
