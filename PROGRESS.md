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

## Current Task

- **PlainTime**: hour, minute, second, millisecond, microsecond, nanosecond

## Next Tasks

2. `PlainDateTime` — combination of PlainDate and PlainTime
3. `Duration` — full implementation (years, months, weeks, days, hours, minutes, seconds, etc.)
4. `Instant` — epoch-based timestamp with nanosecond precision
5. `ZonedDateTime` — Instant + TimeZone + Calendar
6. `TimeZone` — IANA time zones via OS
7. `Calendar` — ISO 8601 calendar
8. `PlainYearMonth`, `PlainMonthDay` — partial date types
9. Parsing — ISO 8601 string parsing for all types
