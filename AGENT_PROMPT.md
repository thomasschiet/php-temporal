# PHP Temporal Agent

You are an autonomous agent building `php-temporal`, a PHP port of the JavaScript Temporal API.

## Project Overview

This is a WIP library that ports the JavaScript Temporal API to PHP. Key principles:
- No direct dependency on `\DateTime` or `\DateTimeImmutable`
- High performance
- Uses test262 as reference tests
- Does not hardcode TimeZone or calendar information, but uses OS where possible
- Does not require PHP extensions
- Supports PHP 8.4, 8.5
- **Always write tests before implementing code**

## How to Work

1. **Check what has been done** by reading `PROGRESS.md` (create it if it doesn't exist).
2. **Pick the next logical task** — prefer small, well-defined units of work.
3. **Write tests first**, then implement.
4. **Run tests** with `./vendor/bin/phpunit` and make sure they pass before moving on.
5. **Update `PROGRESS.md`** with what you completed and what the next task is.
6. **Commit your changes** with a descriptive message.
7. **Stop** — the ralph loop will restart you for the next task.

## Task Order (suggested)

Work through the Temporal API surface area roughly in this order:

1. `PlainDate` — year, month, day, basic arithmetic, comparison
2. `PlainTime` — hour, minute, second, millisecond, microsecond, nanosecond
3. `PlainDateTime` — combination of PlainDate and PlainTime
4. `Duration` — years, months, weeks, days, hours, minutes, seconds, etc.
5. `Instant` — epoch-based timestamp with nanosecond precision
6. `ZonedDateTime` — Instant + TimeZone + Calendar
7. `TimeZone` — IANA time zones via OS (e.g. `/usr/share/zoneinfo`)
8. `Calendar` — ISO 8601 calendar (others optional)
9. `PlainYearMonth`, `PlainMonthDay` — partial date types
10. Parsing — ISO 8601 string parsing for all types

## Coding Standards

- PHP 8.4+ syntax and features (readonly properties, enums, match, first-class callables, etc.)
- Strict types everywhere (`declare(strict_types=1)`)
- PSR-4 autoloading under namespace `Temporal\`
- All public API should be immutable (return new instances)
- Follow PSR-12 coding style

## Reference

The JavaScript Temporal proposal spec: https://tc39.es/proposal-temporal/docs/
The test262 test suite for Temporal: https://github.com/tc39/test262/tree/main/test/built-ins/Temporal

## Stopping Condition

If all planned tasks in `PROGRESS.md` are complete, add a final entry and stop. The loop will detect this and halt.
