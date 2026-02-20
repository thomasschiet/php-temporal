---
title: ISO 8601 Parsing
description: PHP Temporal parses ISO 8601 strings for all date/time types.
---

All PHP Temporal types can be created from ISO 8601 strings via the static `from()` method.

## Supported Formats

### PlainDate

```
YYYY-MM-DD
±YYYYYY-MM-DD   (extended years, 6+ digits with explicit sign)
```

```php
use Temporal\PlainDate;

PlainDate::from('2025-03-14');
PlainDate::from('+002025-03-14'); // extended year
PlainDate::from('-000043-07-01'); // 43 BCE
```

### PlainTime

```
HH:MM:SS
HH:MM:SS.sss              (milliseconds)
HH:MM:SS.ssssss           (microseconds)
HH:MM:SS.sssssssss        (nanoseconds)
```

```php
use Temporal\PlainTime;

PlainTime::from('09:30:00');
PlainTime::from('09:30:00.123');
PlainTime::from('09:30:00.123456789');
```

### PlainDateTime

```
YYYY-MM-DDTHH:MM:SS
YYYY-MM-DDTHH:MM:SS.sssssssss
```

```php
use Temporal\PlainDateTime;

PlainDateTime::from('2025-03-14T09:30:00');
PlainDateTime::from('2025-03-14T09:30:00.123456789');
```

### Duration

ISO 8601 duration format:

```
P[n]Y[n]M[n]DT[n]H[n]M[n]S
P[n]W
```

```php
use Temporal\Duration;

Duration::from('P1Y2M3DT4H5M6S');
Duration::from('P2W');          // 2 weeks
Duration::from('PT90M');        // 90 minutes
Duration::from('-P1D');         // negative 1 day
```

### Instant

An Instant string is a UTC instant in ISO 8601 format, always ending with `Z` or an offset:

```
YYYY-MM-DDTHH:MM:SS.sssssssssZ
YYYY-MM-DDTHH:MM:SS+HH:MM
```

```php
use Temporal\Instant;

Instant::from('2025-03-14T09:30:00Z');
Instant::from('2025-03-14T09:30:00.123456789Z');
Instant::from('2025-03-14T10:30:00+01:00');
```

### ZonedDateTime

A ZonedDateTime string includes an IANA timezone annotation in square brackets:

```
YYYY-MM-DDTHH:MM:SS±HH:MM[IANA/Zone]
```

```php
use Temporal\ZonedDateTime;

ZonedDateTime::from('2025-03-14T09:30:00+01:00[Europe/Amsterdam]');
ZonedDateTime::from('2025-03-14T09:30:00Z[UTC]');
```

### PlainYearMonth

```
YYYY-MM
```

```php
use Temporal\PlainYearMonth;

PlainYearMonth::from('2025-03');
```

### PlainMonthDay

```
--MM-DD
```

```php
use Temporal\PlainMonthDay;

PlainMonthDay::from('--03-14');
```

## Associative Arrays

All `from()` methods also accept associative arrays with named fields:

```php
PlainDate::from(['year' => 2025, 'month' => 3, 'day' => 14]);
PlainTime::from(['hour' => 9, 'minute' => 30, 'second' => 0]);
Duration::from(['years' => 1, 'months' => 2, 'days' => 3]);
```

## Round-tripping

Every type implements `__toString()` and `jsonSerialize()` that produce a valid ISO 8601 string, so parsing round-trips perfectly:

```php
$date = PlainDate::from('2025-03-14');
$str  = (string) $date;              // "2025-03-14"
$back = PlainDate::from($str);

echo PlainDate::compare($date, $back); // 0 — equal
```
