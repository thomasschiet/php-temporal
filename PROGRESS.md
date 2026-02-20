# PHP Temporal Progress

## Status: In Progress

## Completed Tasks

### 1. Project Setup + PlainDate (2026-02-20)
- Created `phpunit.xml` configuration
- Created `src/PlainDate.php` — full implementation:
  - Constructor with validation (month range, day range, leap year)
  - `from()` static constructor (accepts string, array, or PlainDate)
  - `fromEpochDays()` / `toEpochDays()` using Hinnant's civil-from-days algorithm
  - Computed properties: `dayOfWeek`, `dayOfYear`, `weekOfYear`, `daysInMonth`, `daysInYear`, `inLeapYear`
  - `add()` / `subtract()` with year/month/week/day duration arrays
  - `with()` for field overrides
  - `until()` / `since()` returning a `Duration`
  - `compare()` / `equals()`
  - `__toString()` with ISO 8601 format including extended years
- Created `src/Duration.php` — minimal stub (needed by PlainDate)
- Created `tests/PlainDateTest.php` — 57 tests, all passing

### 2. PlainTime (2026-02-20)
- Created `src/PlainTime.php` — full implementation:
  - Constructor with validation for all 6 fields (hour, minute, second, millisecond, microsecond, nanosecond)
  - `from()` static constructor (accepts string, array, or PlainTime)
  - ISO 8601 time string parsing with 1–9 digit fractional seconds
  - `fromNanosecondsSinceMidnight()` / `toNanosecondsSinceMidnight()` conversion
  - `with()` for field overrides
  - `add()` / `subtract()` with nanosecond-precision arithmetic, wraps around midnight
  - `until()` / `since()` returning a `Duration`
  - `compare()` / `equals()`
  - `__toString()` with ISO 8601 format, omits trailing zero sub-second groups
- Created `tests/PlainTimeTest.php` — 65 tests, all passing
- Total: 122 tests, all passing

## Current Task

- **PlainDateTime**: combination of PlainDate and PlainTime

## Next Tasks

3. `Duration` — full implementation (years, months, weeks, days, hours, minutes, seconds, etc.)
4. `Instant` — epoch-based timestamp with nanosecond precision
5. `ZonedDateTime` — Instant + TimeZone + Calendar
6. `TimeZone` — IANA time zones via OS
7. `Calendar` — ISO 8601 calendar
8. `PlainYearMonth`, `PlainMonthDay` — partial date types
9. Parsing — ISO 8601 string parsing for all types
