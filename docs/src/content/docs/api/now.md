---
title: Temporal\Now
description: Static helpers for accessing the current date and time.
---

`Temporal\Now` is a static utility class that provides access to the current date and time in various `Temporal` types. It cannot be instantiated.

It corresponds to the `Temporal.Now` namespace in the TC39 proposal.

## Precision

All methods use PHP's `microtime()`, which provides **microsecond** precision. Nanosecond values in returned instances are always multiples of 1000 (i.e., the nanosecond component is always `0`).

## Methods

### `instant()`

```php
public static function instant(): Instant
```

Return the current moment as an `Instant` (nanoseconds since Unix epoch, microsecond precision).

```php
use Temporal\Now;

$now = Now::instant();
echo $now->epochMilliseconds; // e.g. 1741942200000
```

### `timeZoneId()`

```php
public static function timeZoneId(): string
```

Return the system timezone identifier as a string (e.g. `'America/New_York'` or `'UTC'`). This reads `date_default_timezone_get()`.

```php
echo Now::timeZoneId(); // e.g. 'Europe/Amsterdam'
```

### `zonedDateTimeISO()`

```php
public static function zonedDateTimeISO(TimeZone|string|null $timeZone = null): ZonedDateTime
```

Return the current moment as a `ZonedDateTime`.

- If `$timeZone` is `null`, the system timezone is used.

```php
$now = Now::zonedDateTimeISO();                        // system timezone
$now = Now::zonedDateTimeISO('America/New_York');      // specific timezone
$now = Now::zonedDateTimeISO(new \Temporal\TimeZone('UTC'));
```

### `plainDateTimeISO()`

```php
public static function plainDateTimeISO(TimeZone|string|null $timeZone = null): PlainDateTime
```

Return the current date and time (no timezone info) in the given timezone.

```php
$dt = Now::plainDateTimeISO('Europe/Amsterdam');
echo $dt; // e.g. 2025-03-14T09:30:00
```

### `plainDateISO()`

```php
public static function plainDateISO(TimeZone|string|null $timeZone = null): PlainDate
```

Return the current calendar date in the given timezone.

```php
$today = Now::plainDateISO();
echo $today; // e.g. 2025-03-14
```

### `plainTimeISO()`

```php
public static function plainTimeISO(TimeZone|string|null $timeZone = null): PlainTime
```

Return the current wall-clock time in the given timezone.

```php
$time = Now::plainTimeISO('America/Los_Angeles');
echo $time; // e.g. 01:30:00
```

## Why a Separate Class?

`Temporal\Now` follows the TC39 proposal design of keeping "current time" access separate from the pure value types. This makes it easy to mock in tests — you can avoid calling `Now::instant()` directly and instead accept an `Instant` parameter to enable deterministic tests.
