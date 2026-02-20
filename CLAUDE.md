# PHP Temporal
This is a WIP library that port the JavaScript Temporal API to a library in PHP.

Key principles:
- No direct dependency on \DateTime or \DateTimeImmutable
- High performance
- Uses test262 as reference tests
- Does not hardcode TimeZone or calendar information, but uses OS where possible
- If support for other calendars than the ISO calendar is hard, it is OK to skip those
- Does not require PHP extensions
- Supports PHP 8.4, 8.5
- PHP Temporal will be built by autonomous agents. Therefore it is extremely important to start with good tests before implementing code
- Use well typed exceptions that extend the relevant PHP exception

## Tooling

Use **mago** (`./vendor/bin/mago`) for formatting, linting, and static analysis:

- Format: `./vendor/bin/mago fmt`
- Lint: `./vendor/bin/mago lint`
- Analyze: `./vendor/bin/mago analyze`

Run all three after implementing any code and fix any issues before committing.
