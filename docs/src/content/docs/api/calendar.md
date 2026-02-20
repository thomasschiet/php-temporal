---
title: Calendar
description: ISO 8601 calendar implementation — delegates date field calculations.
---

`Temporal\Calendar` represents a calendar system. Currently, only the **ISO 8601 calendar** (`'iso8601'`) is supported.

The `Calendar` class is primarily used internally by other types, but is exposed for direct use when calendar operations are needed.

## Construction

### `new Calendar()`

```php
new Calendar(string $id)
```

```php
use Temporal\Calendar;

$cal = new Calendar('iso8601');
```

**Throws** `UnsupportedCalendarException` for any identifier other than `'iso8601'`.

### `from()`

```php
public static function from(string|Calendar $item): Calendar
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `string` | Always `'iso8601'` |

## Factory Methods

### `dateFromFields()`

```php
public function dateFromFields(array $fields, string $overflow = 'constrain'): PlainDate
```

Create a `PlainDate` from field values, with overflow handling.

### `yearMonthFromFields()`

```php
public function yearMonthFromFields(array $fields, string $overflow = 'constrain'): PlainYearMonth
```

### `monthDayFromFields()`

```php
public function monthDayFromFields(array $fields, string $overflow = 'constrain'): PlainMonthDay
```

## Arithmetic

### `dateAdd()`

```php
public function dateAdd(PlainDate $date, Duration $duration, string $overflow = 'constrain'): PlainDate
```

### `dateUntil()`

```php
public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit = 'day'): Duration
```

## Field Helpers

### `fields()`

```php
public function fields(array $fields): array
```

Returns the list of field names that this calendar supports.

### `mergeFields()`

```php
public function mergeFields(array $fields, array $additionalFields): array
```

Merge two sets of fields, with `$additionalFields` taking precedence.

### Individual Field Accessors

| Method | Parameter | Return | Description |
|--------|-----------|--------|-------------|
| `year()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | Year |
| `month()` | `PlainDate\|PlainDateTime\|PlainYearMonth\|PlainMonthDay` | `int` | Month (1–12) |
| `monthCode()` | `PlainDate\|PlainDateTime\|PlainYearMonth\|PlainMonthDay` | `string` | Month code (e.g. `'M03'`) |
| `day()` | `PlainDate\|PlainDateTime\|PlainMonthDay` | `int` | Day of month |
| `dayOfWeek()` | `PlainDate\|PlainDateTime` | `int` | ISO day of week (1=Monday) |
| `dayOfYear()` | `PlainDate\|PlainDateTime` | `int` | Day of year (1-based) |
| `weekOfYear()` | `PlainDate\|PlainDateTime` | `int` | ISO week number (1–53) |
| `daysInWeek()` | — | `int` | Always 7 for ISO |
| `daysInMonth()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | Days in month |
| `daysInYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `int` | 365 or 366 |
| `monthsInYear()` | — | `int` | Always 12 for ISO |
| `inLeapYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `bool` | Whether leap year |
| `era()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `?string` | Always `null` for ISO |
| `eraYear()` | `PlainDate\|PlainDateTime\|PlainYearMonth` | `?int` | Always `null` for ISO |

## Comparison / Equality

```php
public function equals(Calendar $other): bool
public function __toString(): string // 'iso8601'
```

## Note on Other Calendars

Only `'iso8601'` is currently supported. Passing any other calendar identifier (e.g. `'gregory'`, `'japanese'`) will throw `UnsupportedCalendarException`. Calendar annotations in parsed ISO 8601 strings (e.g. `[u-ca=gregory]`) are rejected unless they specify `[u-ca=iso8601]`.
