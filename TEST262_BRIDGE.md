# test262 Bridge for PHP Temporal

## Problem

The TC39 test262 suite contains **6,300+ Temporal tests** written in JavaScript. This PHP library currently ports test cases manually into PHPUnit — effective but labor-intensive, and only ~75 test262 cases have been transcribed so far. We need a bridge to systematically leverage the full test262 suite.

## Background: How test262 Tests Work

Each test262 file is a JavaScript file with YAML frontmatter:

```javascript
/*---
esid: sec-temporal.plaindate.prototype.add
description: Basic behavior of add()
includes: [temporalHelpers.js]
features: [Temporal]
---*/

const date = Temporal.PlainDate.from("2020-02-29");
const result = date.add({ years: 1 });
assert.sameValue(result.toString(), "2021-02-28");
```

Key metadata fields:
- `esid` — ECMAScript specification section reference
- `description` — One-line test summary
- `includes` — Helper files needed (e.g., `temporalHelpers.js`)
- `features` — Language features tested (e.g., `Temporal`)
- `negative` — For error tests: `{phase, type}` describing expected exception

Many tests are **data-driven** — they contain arrays of `[input, expected]` pairs iterated in a loop. These are the easiest to extract and reuse.

## How Other Non-JS Projects Handle test262

| Project | Language | Approach |
|---------|----------|----------|
| **test262-harness-dotnet** | C# | Generates NUnit test classes from test262 files, embeds a JS engine (Jint) to execute them |
| **LibJS (SerenityOS)** | C++ | Process bridge — Python orchestrator shells out to compiled binary per test |
| **Boa** | Rust | Runs test262 through the JS engine itself (they ARE a JS engine) |
| **php-ecma-intl/test** | PHP | Manual port of intl402 tests to PHPUnit (same as our current approach) |
| **temporal-test262-runner** | JS | Lightweight runner for Temporal polyfill testing (~30s for all 6,300 tests) |

**Key insight**: No standard mechanism exists for running test262 against non-JS implementations. Every project builds its own bridge.

## Recommended Approach: Data Extraction Pipeline

The most practical approach for a PHP library (not a JS engine) is to **extract test data from test262 into JSON fixtures**, then consume them in PHPUnit.

```
test262 repo (JS files)
      │
      ▼
Node.js extractor script
(parses JS, extracts inputs + expected outputs)
      │
      ▼
JSON fixture files (tests/fixtures/*.json)
      │
      ▼
PHPUnit @dataProvider consumes fixtures
      │
      ▼
./vendor/bin/phpunit --testsuite test262
```

### Why not run the JS tests directly?

- test262 tests use JS-specific helpers (`assert.sameValue`, `assert.throws`, `TemporalHelpers.assertDuration`)
- JavaScript and PHP have different type semantics, error types, and runtime behavior
- A process-bridge approach (shelling out to PHP per assertion) is too slow for 6,300 tests
- Many test262 tests check JS engine internals (property descriptors, prototype chains) that don't apply to PHP

### Why data extraction works well

- Many test262 tests are **data-driven** — arrays of `[input, expected_output]` pairs
- The valuable part is the **test data**, not the JS assertion boilerplate
- PHPUnit `@dataProvider` is a natural fit for parameterized test data
- JSON fixtures can be version-controlled and updated independently
- Only translatable tests get extracted (JS-specific tests are skipped)

## Implementation Plan

### Prerequisites

The dev environment already has:
- **Node.js v20.20.0** — for running the extractor script
- **PHP 8.4.18** — for running PHPUnit
- **npm 10.8.2** — for installing the test262-parser package

### Step 1: Clone test262 and install Node.js tooling

```bash
git clone --depth 1 https://github.com/tc39/test262.git tests/test262
npm init -y
npm install test262-parser
echo "tests/test262/" >> .gitignore
echo "node_modules/" >> .gitignore
```

### Step 2: Create the extractor — `tools/extract-test262.mjs`

A Node.js script that:

1. Walks `tests/test262/test/built-ins/Temporal/` directories
2. Parses each `.js` file using the `test262-parser` npm package to extract frontmatter
3. Analyzes the JavaScript test body to identify:
   - **Data-driven tests**: Arrays of test cases → extract as structured data
   - **Simple assertion tests**: Single method call + expected result → extract pair
   - **Negative tests**: Expected error type + trigger code → extract error expectation
   - **Non-translatable tests**: JS engine internals → skip
4. Outputs per-type JSON fixtures to `tests/fixtures/`

Target fixture format:

```json
{
  "type": "PlainDate",
  "method": "add",
  "source": "test/built-ins/Temporal/PlainDate/prototype/add/basic.js",
  "description": "Basic behavior of add()",
  "cases": [
    {
      "input": "2020-02-29",
      "args": { "years": 1 },
      "expected": "2021-02-28"
    },
    {
      "input": "2020-02-29",
      "args": { "years": 4 },
      "expected": "2024-02-29"
    }
  ]
}
```

The extractor should focus on these high-value test directories first:

| Directory | PHP Class | Priority |
|-----------|-----------|----------|
| `PlainDate/prototype/add/`, `subtract/`, `with/`, `from/`, `toString/` | `PlainDate` | High |
| `PlainTime/prototype/add/`, `subtract/`, `with/`, `from/`, `toString/` | `PlainTime` | High |
| `PlainDateTime/prototype/add/`, `subtract/`, `with/`, `from/`, `toString/` | `PlainDateTime` | High |
| `Duration/prototype/add/`, `round/`, `total/`, `from/`, `toString/` | `Duration` | High |
| `Instant/prototype/add/`, `subtract/`, `toString/`, `from/` | `Instant` | Medium |
| `ZonedDateTime/prototype/add/`, `subtract/`, `toString/` | `ZonedDateTime` | Medium |
| `PlainYearMonth/prototype/add/`, `subtract/` | `PlainYearMonth` | Low |
| `PlainMonthDay/prototype/from/` | `PlainMonthDay` | Low |

### Step 3: Create a PHPUnit trait — `tests/Test262DataTrait.php`

A reusable trait that:
- Loads JSON fixture files from `tests/fixtures/`
- Provides a generic data provider that yields `[input, args, expected]` tuples
- Maps test262 method names to PHP Temporal method calls
- Handles type conversion (JS strings → PHP objects)

### Step 4: Create test262-backed PHPUnit test classes

For each Temporal type, create a dedicated test class:

```
tests/Test262PlainDateTest.php
tests/Test262PlainTimeTest.php
tests/Test262DurationTest.php
... etc
```

Each class uses `@dataProvider` to consume the JSON fixtures and calls the corresponding PHP Temporal methods, asserting results match expected outputs.

### Step 5: Configure PHPUnit and Composer

**phpunit.xml** — Add a separate `test262` testsuite so bridge tests run independently:

```xml
<testsuite name="test262">
    <directory>tests</directory>
    <include>tests/Test262*.php</include>
</testsuite>
```

**composer.json** — Add convenience scripts:

```json
{
  "scripts": {
    "test262:update": "cd tests/test262 && git pull && cd ../.. && node tools/extract-test262.mjs",
    "test262:run": "phpunit --testsuite test262"
  }
}
```

### Step 6: Commit JSON fixtures to git

The extracted JSON fixtures should be committed to the repo so that:
- CI doesn't need Node.js or the test262 clone
- Other developers can run bridge tests without setup
- Fixtures serve as documentation of test262 coverage

## Alternative Approach: Process Bridge (Not Recommended)

For completeness, a process bridge would look like:

```
Node.js orchestrator
      │
      ▼ (for each test)
  php eval-temporal.php '{"method":"PlainDate.from","args":["2020-02-29"]}'
      │
      ▼
  PHP executes, returns JSON result
      │
      ▼
  Node.js compares against expected value
```

**Why this is less ideal:**
- Spawns a new PHP process per test — extremely slow for 6,300 tests
- Complex error serialization across process boundary
- Harder to debug failures
- Requires maintaining both a JS orchestrator and a PHP eval harness

This could work as a secondary validation tool but shouldn't be the primary testing mechanism.

## What's NOT Translatable

Some test262 tests check JavaScript-specific behavior that has no PHP equivalent:

- **Property descriptor tests** (`prop-desc.js`) — JS `Object.getOwnPropertyDescriptor` checks
- **Prototype chain tests** — JS prototype inheritance mechanics
- **Symbol tests** — JS Symbol.toPrimitive, Symbol.toStringTag
- **Proxy/trap tests** — JS Proxy observation of internal method calls
- **`this` value tests** — JS `this` binding semantics
- **Type coercion tests** — JS implicit `toString()` / `valueOf()` calls

The extractor should identify and skip these automatically (they typically have distinctive patterns in their `includes` or test body).

## Expected Outcomes

- **Short term**: Proof of concept with PlainDate fixtures (~200-300 extractable test cases)
- **Medium term**: Coverage of all major Temporal types (~2,000-3,000 extractable cases)
- **Long term**: Automated fixture refresh when test262 updates, coverage gap reporting

## Useful Links

- test262 Temporal tests: https://github.com/tc39/test262/tree/main/test/built-ins/Temporal
- test262-parser (npm): https://www.npmjs.com/package/test262-parser
- temporal-test262-runner: https://github.com/js-temporal/temporal-test262-runner
- test262-harness-dotnet (reference): https://github.com/nicolo-ribaudo/test262-harness-dotnet
- test262 INTERPRETING.md: https://github.com/tc39/test262/blob/main/INTERPRETING.md
- test262 CONTRIBUTING.md: https://github.com/tc39/test262/blob/main/CONTRIBUTING.md
