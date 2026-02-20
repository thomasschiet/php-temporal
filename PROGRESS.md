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

### 3. PlainDateTime (2026-02-20)
- Created `src/PlainDateTime.php` — full implementation:
  - Constructor with validation for all 9 fields (year, month, day + 6 time fields)
  - `from()` static constructor (accepts string, array, or PlainDateTime)
  - ISO 8601 datetime string parsing: `YYYY-MM-DDTHH:MM:SS[.fraction]` with extended years and lowercase `t`
  - `toPlainDate()` / `toPlainTime()` — extract date and time parts
  - `withPlainDate()` / `withPlainTime()` — replace date or time part
  - `with()` for field overrides
  - `add()` / `subtract()` — calendar arithmetic for date parts, nanosecond arithmetic for time parts, with correct day carry-over (uses floor division for negative ns)
  - `until()` / `since()` — Duration with day + sub-day components, balanced so time part stays within one day
  - `compare()` / `equals()`
  - `__toString()` — delegates to `PlainDate::__toString()` + `T` + `PlainTime::__toString()`
- Created `tests/PlainDateTimeTest.php` — 70 tests, all passing
- Total: 200 tests, all passing

### 4. Duration (2026-02-20)
- Replaced `src/Duration.php` stub with full implementation:
  - Constructor with mixed-sign validation; `sign` and `blank` computed readonly properties
  - `from()` static constructor accepting Duration, array, or ISO 8601 string
  - ISO 8601 parsing: `P[n]Y[n]M[n]W[n]DT[n]H[n]M[n]S` with fractional seconds (up to 9 digits)
  - `negated()` / `abs()` for sign manipulation
  - `with()` for field overrides
  - `add()` / `subtract()` field-by-field arithmetic
  - `compare()` static method (time-field comparison via nanosecond totals)
  - `total(unit)` — convert to a given unit as a float (nanoseconds through weeks)
  - `round(unit)` — half-expand rounding to a given smallest unit
  - `__toString()` — ISO 8601 format with sub-second decimal fractions, sign prefix
- Created `tests/DurationTest.php` — 77 tests, all passing
- Total: 277 tests, all passing

## Current Task

- **Instant** — epoch-based timestamp with nanosecond precision

## Next Tasks

5. `ZonedDateTime` — Instant + TimeZone + Calendar
6. `TimeZone` — IANA time zones via OS
7. `Calendar` — ISO 8601 calendar
8. `PlainYearMonth`, `PlainMonthDay` — partial date types
9. Parsing — ISO 8601 string parsing for all types
