# PHP Temporal Progress

## Status: In Progress

## Completed Tasks

- **5220 tests passing** (1016 core + 4202 test262 data-driven, 2 skipped), 0 new mago errors
- **mago baseline** covers 169 pre-existing test-file warnings (NoDiscard in exception tests)
- **All TC39 Temporal API surface covered**:
  - `PlainDate`, `PlainTime`, `PlainDateTime` — full arithmetic, comparison, conversion
  - `Duration` — balancing, rounding, total, ISO 8601 round-trip
  - `Instant` — nanosecond-precision epoch timestamps, rounding
  - `ZonedDateTime` — DST-aware arithmetic, all disambiguation modes
  - `TimeZone` — IANA zones via OS, offset zones, DST transitions
  - `Calendar` — ISO 8601 calendar (field helpers, arithmetic delegation)
  - `PlainYearMonth`, `PlainMonthDay` — partial date types
  - `Temporal\Now` — current time utilities
  - ISO 8601 parsing for all types (extended years, calendar annotations, offset suffixes)
- **Typed exception hierarchy** under `Temporal\Exception\` (9 exception classes, Named Constructor pattern)
- **test262 data-driven bridge**: 44 fixture files, 4202 automated TC39 reference tests
- **Fully immutable**: all public API returns new instances; 87 `return new …` sites
- **No PHP extension dependencies** — pure PHP 8.4+
- **Mutation testing**: 87% MSI on PlainDate.php; 5 high-value escaped mutants killed
- **GitHub Actions CI**: `.github/workflows/ci.yml` — matrix on PHP 8.4 + 8.5, PHPUnit (core + test262), mago lint, mago analyze, mago format check
- **Mago baseline updated**: 1428 pre-existing test-file issues baselined so `mago analyze` exits cleanly in CI


- **Starlight documentation site** (`docs/`) — Astro + Starlight static site with:
  - Home page (`index.mdx`) with type overview table
  - Getting Started: Installation, Quick Start
  - Core Concepts: Immutability, ISO 8601 Parsing, Overflow Handling, Exceptions
  - API Reference: all 11 types (PlainDate, PlainTime, PlainDateTime, Duration, Instant, ZonedDateTime, TimeZone, Calendar, PlainYearMonth, PlainMonthDay, Temporal\Now)
  - **GitHub Actions CI** (`.github/workflows/docs.yml`) — builds and deploys to GitHub Pages on push to `main`

## Next Tasks

- **Mutation testing** Run infection scripts on the following classes
  - `Calendar`
  - `Duration`
  - `Instant`
  - `Now`
  - `PlainDate`
  - `PlainDateTime`
  - `PlainMonthDay`
  - `PlainTime`
  - `PlainYearMonth`
  - `TimeZone`
  - `ZonedDateTime`
