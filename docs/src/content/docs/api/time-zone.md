---
title: TimeZone
description: IANA timezone or fixed UTC offset — OS-backed, no hardcoded timezone data.
---

`Temporal\TimeZone` represents an IANA timezone (e.g. `Europe/Amsterdam`) or a fixed UTC offset (e.g. `+05:30`). Timezone data is sourced from the operating system (`/usr/share/zoneinfo` on Linux/macOS) — no hardcoded timezone tables.

It corresponds to `Temporal.TimeZone` in the TC39 proposal.

## Construction

### `new TimeZone()`

```php
new TimeZone(string $id)
```

```php
use Temporal\TimeZone;

$tz = new TimeZone('Europe/Amsterdam');
$tz = new TimeZone('America/New_York');
$tz = new TimeZone('UTC');
$tz = new TimeZone('+05:30');   // fixed offset
$tz = new TimeZone('+00:00');   // UTC as offset
```

**Throws** `UnknownTimeZoneException` if the identifier is not recognised.

### `from()`

```php
public static function from(string|TimeZone $item): TimeZone
```

```php
$tz = TimeZone::from('Asia/Tokyo');
$tz = TimeZone::from($existing);  // copy
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `string` | The timezone identifier (e.g. `'Europe/Amsterdam'` or `'+01:00'`) |

## Methods

### `getOffsetNanosecondsFor()`

```php
public function getOffsetNanosecondsFor(Instant $instant): int
```

Return the UTC offset in nanoseconds for a specific instant.

```php
$tz      = new TimeZone('Europe/Amsterdam');
$instant = \Temporal\Instant::from('2025-07-01T12:00:00Z');
$offset  = $tz->getOffsetNanosecondsFor($instant);
echo $offset / 3_600_000_000_000; // 2 (CEST = UTC+2)
```

### `getOffsetStringFor()`

```php
public function getOffsetStringFor(Instant $instant): string
```

Return the UTC offset as a string for a specific instant.

```php
$tz     = new TimeZone('Europe/Amsterdam');
$summer = \Temporal\Instant::from('2025-07-01T12:00:00Z');
$winter = \Temporal\Instant::from('2025-01-01T12:00:00Z');

echo $tz->getOffsetStringFor($summer); // +02:00
echo $tz->getOffsetStringFor($winter); // +01:00
```

### `getPlainDateTimeFor()`

```php
public function getPlainDateTimeFor(Instant $instant): PlainDateTime
```

Convert a UTC instant to a local date/time in this timezone.

```php
$tz      = new TimeZone('Europe/Amsterdam');
$instant = \Temporal\Instant::from('2025-03-14T08:30:00Z');
$local   = $tz->getPlainDateTimeFor($instant);
echo $local; // 2025-03-14T09:30:00 (UTC+1 in winter)
```

### `getInstantFor()`

```php
public function getInstantFor(PlainDateTime $dateTime, string $disambiguation = 'compatible'): Instant
```

Convert a local date/time to a UTC instant. The `$disambiguation` parameter controls DST gap/fold handling:

| Value | Description |
|-------|-------------|
| `'compatible'` | *(default)* Earlier offset for gaps, earlier occurrence for folds |
| `'earlier'` | Use the earlier possibility |
| `'later'` | Use the later possibility |
| `'reject'` | Throw `AmbiguousTimeException` |

```php
$tz = new TimeZone('Europe/Amsterdam');
$dt = \Temporal\PlainDateTime::from('2025-03-30T02:30:00'); // In the DST gap

$instant = $tz->getInstantFor($dt, 'compatible');
```

### `getPossibleInstantsFor()`

```php
public function getPossibleInstantsFor(PlainDateTime $dateTime): array
```

Return all possible UTC instants for a local date/time. Returns an array of `Instant` objects:
- 2 elements if the time falls in a DST fold (ambiguous)
- 0 elements if the time falls in a DST gap (doesn't exist)
- 1 element otherwise

### `getNextTransition()`

```php
public function getNextTransition(Instant $startingPoint): ?Instant
```

Return the next DST transition after the given instant, or `null` if there are no more transitions (fixed-offset zones).

```php
$tz         = new TimeZone('Europe/Amsterdam');
$now        = \Temporal\Now::instant();
$transition = $tz->getNextTransition($now);

if ($transition !== null) {
    echo $transition; // e.g. 2025-03-30T01:00:00Z (spring forward)
}
```

### `getPreviousTransition()`

```php
public function getPreviousTransition(Instant $startingPoint): ?Instant
```

Return the previous DST transition before the given instant.

### `equals()`

```php
public function equals(TimeZone $other): bool
```

## String Representation

```php
public function __toString(): string
```

Returns the timezone identifier string (e.g. `'Europe/Amsterdam'` or `'+05:30'`).

## Fixed-Offset Zones

A timezone identifier like `'+05:30'` or `'-08:00'` creates a fixed-offset timezone. It has no DST transitions and the offset is always the same.

```php
$tz = new TimeZone('+05:30');
echo $tz->id; // +05:30

// getNextTransition returns null for fixed-offset zones
$next = $tz->getNextTransition(\Temporal\Now::instant());
var_dump($next); // NULL
```
