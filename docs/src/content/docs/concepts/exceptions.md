---
title: Exceptions
description: The PHP Temporal typed exception hierarchy.
---

PHP Temporal uses a typed exception hierarchy under `Temporal\Exception\`. All exceptions extend standard PHP exceptions.

## Hierarchy

```
\Temporal\Exception\TemporalException (extends \RuntimeException)
├── DateRangeException          (extends \RangeError)
├── InvalidDurationException     (extends \InvalidArgumentException)
├── InvalidOptionException       (extends \InvalidArgumentException)
├── InvalidTemporalStringException (extends \InvalidArgumentException)
├── MissingFieldException        (extends \InvalidArgumentException)
├── UnsupportedCalendarException (extends \RuntimeException)
├── UnknownTimeZoneException     (extends \RuntimeException)
└── AmbiguousTimeException       (extends \RuntimeException)
```

## Exception Classes

### `TemporalException`

Base class for all PHP Temporal exceptions that don't fit a standard PHP exception type. Extends `\RuntimeException`.

### `DateRangeException`

Thrown when a date field value is out of the valid range (e.g. month 13, or day 31 in a 30-day month with `reject` overflow).

```php
use Temporal\PlainDate;
use Temporal\Exception\DateRangeException;

try {
    new PlainDate(2025, 13, 1); // month 13 is invalid
} catch (DateRangeException $e) {
    echo $e->getMessage();
}
```

### `InvalidTemporalStringException`

Thrown when an ISO 8601 string cannot be parsed.

```php
use Temporal\PlainDate;
use Temporal\Exception\InvalidTemporalStringException;

try {
    PlainDate::from('not-a-date');
} catch (InvalidTemporalStringException $e) {
    echo $e->getMessage();
}
```

### `InvalidOptionException`

Thrown when an unrecognised option or an invalid option value is provided.

```php
use Temporal\PlainDate;
use Temporal\Exception\InvalidOptionException;

try {
    PlainDate::from('2025-03-14')->until('2025-06-01', ['largestUnit' => 'nanoseconds']);
} catch (InvalidOptionException $e) {
    echo $e->getMessage();
}
```

### `MissingFieldException`

Thrown when a required field is absent from an array or object passed to `from()`.

```php
use Temporal\PlainDate;
use Temporal\Exception\MissingFieldException;

try {
    PlainDate::from(['year' => 2025, 'month' => 3]); // 'day' is missing
} catch (MissingFieldException $e) {
    echo $e->getMessage();
}
```

### `InvalidDurationException`

Thrown when a `Duration` string or array is invalid (e.g. mixed signs across fields).

### `UnknownTimeZoneException`

Thrown when a timezone identifier is not recognised by the system.

```php
use Temporal\TimeZone;
use Temporal\Exception\UnknownTimeZoneException;

try {
    new TimeZone('Mars/OlympusMonsPlanitia');
} catch (UnknownTimeZoneException $e) {
    echo $e->getMessage();
}
```

### `AmbiguousTimeException`

Thrown during DST transitions when a local time is ambiguous (occurs twice) and the `reject` disambiguation mode is used.

### `UnsupportedCalendarException`

Thrown when a non-ISO calendar annotation is encountered in a string (non-ISO calendars are not yet supported).

## Catching Temporal Errors

You can catch all Temporal-specific errors by catching `TemporalException`, or any standard PHP base type:

```php
use Temporal\Exception\TemporalException;

try {
    // ...temporal operations...
} catch (TemporalException $e) {
    // All Temporal-specific runtime errors
} catch (\InvalidArgumentException $e) {
    // InvalidTemporalStringException, InvalidOptionException, etc.
} catch (\RangeError $e) {
    // DateRangeException
}
```
