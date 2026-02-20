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

### 5. Instant (2026-02-20)
- Created `src/Instant.php` — full implementation:
  - Private constructor; epoch nanoseconds stored as a single `int` (range ~1678–2262)
  - `fromEpochNanoseconds()` / `fromEpochMicroseconds()` / `fromEpochMilliseconds()` / `fromEpochSeconds()` static constructors
  - `from()` static constructor (accepts string or Instant)
  - ISO 8601 parsing: `YYYY-MM-DDTHH:MM:SS[.fraction](Z|±HH:MM)` with extended years and lowercase `t`/`z`
  - Computed properties via `__get()`: `epochNanoseconds`, `epochMicroseconds`, `epochMilliseconds`, `epochSeconds` (all truncate toward zero, matching JS BigInt semantics)
  - `add()` / `subtract()` — rejects calendar fields (years/months/weeks); days treated as exactly 24 h
  - `until()` / `since()` — return Duration with hours…nanoseconds (no calendar fields)
  - `round(unit|options)` — halfExpand/ceil/floor/trunc modes; pure integer arithmetic avoids float precision issues
  - `compare()` static method / `equals()` instance method
  - `__toString()` — UTC ISO 8601 string with `Z` suffix, delegates to PlainDate/PlainTime for formatting
- Created `tests/InstantTest.php` — 83 tests, all passing
- Total: 360 tests, all passing

### 6. TimeZone + ZonedDateTime (2026-02-20)
- Created `src/TimeZone.php` — full implementation:
  - Private constructor; stores a single `string $id`
  - `from()` static constructor accepting IANA names (e.g. `America/New_York`), fixed offsets (`+05:30`, `-08:00`), `UTC`, or another TimeZone
  - Validation via PHP's built-in `DateTimeZone` — no DateTime/DateTimeImmutable created
  - `getOffsetNanosecondsFor(Instant)` — looks up the active UTC offset using `DateTimeZone::getTransitions()` with a 25-hour look-back window
  - `getPlainDateTimeFor(Instant)` — converts an Instant to wall-clock PlainDateTime in this zone
  - `getInstantFor(PlainDateTime, disambiguation?)` — converts local time to Instant; iterative two-pass refinement handles DST correctly; `'compatible'` / `'earlier'` / `'later'` / `'reject'` disambiguation modes
  - `equals()` / `__toString()` (returns the ID)
- Created `src/ZonedDateTime.php` — full implementation:
  - Private constructor; stores `int $ns` (epoch nanoseconds) + `TimeZone`
  - `fromEpochNanoseconds(int, TimeZone|string)` static constructor
  - `from()` static constructor (accepts string, array, or ZonedDateTime)
  - ISO 8601 parsing: `YYYY-MM-DDTHH:MM:SS[.frac](Z|±HH:MM)[TZID]` with optional bracket timezone
  - Computed properties via `__get()`: local date-time fields (`year`…`nanosecond`), epoch fields, `offset`, `offsetNanoseconds`, plus PlainDate computed fields (`dayOfWeek`, `daysInMonth`, `inLeapYear`, etc.)
  - `toInstant()` / `toPlainDate()` / `toPlainTime()` / `toPlainDateTime()` conversions
  - `withTimeZone()` — same instant, different zone
  - `with()` — same zone, new local fields (instant changes)
  - `add()` / `subtract()` — calendar fields (years, months, weeks, days) use wall-clock arithmetic; time fields use Instant arithmetic
  - `until()` / `since()` — Duration (hours…nanoseconds) between two instants
  - `compare()` static / `equals()` instance — instant-based; `equals()` also checks timezone identity
  - `__toString()` — `YYYY-MM-DDTHH:MM:SS[.frac]±HH:MM[TZ/ID]`
- Created `tests/TimeZoneTest.php` — 30 tests, all passing
- Created `tests/ZonedDateTimeTest.php` — 57 tests, all passing
- Total: 447 tests, all passing

### 7. PlainYearMonth + PlainMonthDay (2026-02-20)
- Created `src/PlainYearMonth.php` — full implementation:
  - Constructor with month validation
  - `from()` static constructor (accepts string, array, or PlainYearMonth)
  - ISO 8601 parsing: `YYYY-MM` with extended years and leading sign
  - Computed properties via `__get()`: `daysInMonth`, `daysInYear`, `inLeapYear`, `monthsInYear`
  - `with()` for field overrides
  - `add()` / `subtract()` — accepts array or Duration; normalises month overflow/underflow
  - `until()` / `since()` — returns Duration with years and months
  - `toPlainDate(day)` — converts to PlainDate with a given day
  - `compare()` static method / `equals()` instance method
  - `__toString()` — `YYYY-MM` with extended year support
- Created `tests/PlainYearMonthTest.php` — 66 tests, all passing
- Created `src/PlainMonthDay.php` — full implementation:
  - Constructor with month and day validation (reference year 1972 for Feb 29 support)
  - `from()` static constructor (accepts string, array, or PlainMonthDay)
  - ISO 8601 parsing: `--MM-DD`
  - `with()` for field overrides
  - `toPlainDate(year)` — converts to PlainDate; throws if invalid (e.g. Feb 29 in non-leap year)
  - `equals()` instance method
  - `__toString()` — `--MM-DD`
- Created `tests/PlainMonthDayTest.php` — 43 tests, all passing
- Total: 553 tests, all passing

### 8. Parsing Improvements (2026-02-20)

- Updated `PlainDate::fromString()`:
  - Now accepts full datetime strings (`2024-03-15T10:30:00Z`) — date part extracted
  - Now accepts calendar annotations (`[u-ca=iso8601]`, `[x-custom=foo]`, etc.) — silently ignored
- Updated `PlainTime::fromString()`:
  - Now accepts `T`-prefixed time strings (`T10:30:00`) — per TC39 spec
  - Now accepts full datetime strings (`2024-03-15T10:30:00`) — time part extracted
  - Now accepts calendar annotations — silently ignored
- Updated `PlainDateTime::fromString()`:
  - Now accepts optional UTC offset suffix (`Z`, `+05:30`, `-08:00`) — silently ignored
  - Now accepts optional timezone ID bracket (`[America/New_York]`) — silently ignored
  - Now accepts calendar annotations — silently ignored
- Updated `Instant::parse()`:
  - Now supports offsets with seconds component (`+05:30:00`, `-08:00:00`)
  - Now supports `-00:00` as equivalent to UTC
  - Now accepts calendar annotations — silently ignored
- Updated `ZonedDateTime::parse()`:
  - Now supports offsets with seconds component (`+05:30:00`)
  - Now supports multiple trailing annotations (`[u-ca=iso8601][x-foo=bar]`)
  - Bracket groups are classified: TZID (no `=`) vs annotation (`key=value`)
- Created `tests/ParsingTest.php` — 35 tests, all passing
- Total: 588 tests, all passing

### 9. Calendar (2026-02-20)

- Created `src/Calendar.php` — full implementation:
  - Private constructor; `id` property (always 'iso8601')
  - `from(string|Calendar)` static constructor — validates identifier (case-insensitive), throws for unknown calendars
  - `equals()` / `__toString()` — comparison and string representation
  - **Factory methods**: `dateFromFields()`, `yearMonthFromFields()`, `monthDayFromFields()` — create temporal objects from field arrays with `$overflow` parameter validation
  - **Arithmetic**: `dateAdd(PlainDate, Duration)` — delegates to PlainDate::add(); `dateUntil(PlainDate, PlainDate)` — delegates to PlainDate::until() with `$largestUnit` validation
  - **Field helpers**: `fields(array)` — validates field names; `mergeFields(array, array)` — merges with override semantics
  - **Computed property accessors**: `year()`, `month()`, `monthCode()`, `day()`, `dayOfWeek()`, `dayOfYear()`, `weekOfYear()`, `daysInWeek()`, `daysInMonth()`, `daysInYear()`, `monthsInYear()`, `inLeapYear()`, `era()`, `eraYear()` — accept PlainDate/PlainDateTime/PlainYearMonth/PlainMonthDay as appropriate
- Added `calendarId` (always 'iso8601') to `__get()` on: `PlainDate`, `PlainDateTime`, `PlainYearMonth`, `PlainMonthDay`, `ZonedDateTime`
- Added `__get`/`__isset` to `PlainDateTime` for computed date properties (`dayOfWeek`, `dayOfYear`, `weekOfYear`, `daysInMonth`, `daysInYear`, `inLeapYear`, `calendarId`)
- Added `@property-read` PHPDoc annotations to `PlainDate`, `PlainDateTime`, `PlainYearMonth`, `PlainMonthDay` for static analysis
- Created `tests/CalendarTest.php` — 64 tests, all passing
- Total: 652 tests, all passing

### 10. test262 Edge Case Coverage (2026-02-20)

Based on test262 reference tests, added the following improvements:

#### PlainDate overflow option (test262: constrain-days, overflow-reject)
- Added optional `$overflow` parameter to `PlainDate::add()` and `PlainDate::subtract()`
  - `'constrain'` (default): clamps day to last day of resulting month (existing behaviour)
  - `'reject'`: throws `InvalidArgumentException` when day overflows the resulting month
- Added 10 tests covering common year, leap year, and explicit/default overflow modes

#### PlainDate year bounds validation (test262: overflow-adding-months-to-max-year)
- Added `MIN_EPOCH_DAYS = -100_000_001` (April 19, -271821) and `MAX_EPOCH_DAYS = 100_000_000` (September 13, +275760) public constants
- Constructor and `fromEpochDays()` now validate epoch days against these bounds, throwing `\RangeException` on out-of-range values
- `add()` inherits bounds checking via `fromEpochDays()`, so arithmetic past the boundaries throws automatically
- Added 7 tests for boundary construction, arithmetic overflow, and `fromEpochDays` bounds

#### Duration.total() with relativeTo (test262: relativeto-calendar-units-depend-on-relative-date)
- Extended `total()` to accept an options array `['unit' => '...', 'relativeTo' => ...]` in addition to a plain string
- Calendar units `'months'` and `'years'` now supported when `relativeTo` is provided; throws `InvalidArgumentException` when omitted
- Algorithm: applies duration to `relativeTo` date, counts whole months/years, then computes fractional remainder based on the actual calendar month/year length
- Example: 40 days from `2020-02-01` (leap year) = `1 + 11/31` months, because Feb 2020 has 29 days and March has 31
- Added 10 tests covering positive/negative durations, zero, exact year/month boundaries, and options-array form for time units

#### Duration.round() with options object (test262: balances-up-to-weeks)
- Extended `round()` to accept an options array with:
  - `'smallestUnit'` (required), `'largestUnit'` (optional), `'roundingMode'` (default `'halfExpand'`), `'roundingIncrement'` (default `1`), `'relativeTo'` (optional PlainDate)
- When duration has calendar units (years/months) and `smallestUnit` is sub-month, `largestUnit` is required (throws `\RangeException` otherwise — matching TC39 spec)
- With `relativeTo`, the entire duration is converted to total days then expressed in the effective unit and rounded using the given mode and increment
- Supported rounding modes: `'halfExpand'`, `'ceil'`, `'floor'`, `'trunc'`
- Example: `P1M1D.round({ relativeTo: '2024-01-01', largestUnit: 'weeks', smallestUnit: 'weeks', roundingMode: 'ceil', roundingIncrement: 6 })` → `P6W`
- Added 10 tests covering all test262 cases

**Total: 694 tests passing (+42 new)**

## Current Task

- All planned tasks complete.

## Next Tasks

- Additional edge cases (DST transitions in ZonedDateTime, Duration balancing with mixed units)
- Consider adding PlainDateTime.add() overflow option (delegates to PlainDate)
