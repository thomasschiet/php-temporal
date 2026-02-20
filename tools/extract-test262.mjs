#!/usr/bin/env node
/**
 * test262 extractor for PHP Temporal
 *
 * Runs test262 Temporal test files in a Node.js VM using the @js-temporal/polyfill,
 * captures TemporalHelpers.assertXxx calls, and writes JSON fixtures to tests/fixtures/.
 *
 * Usage: node tools/extract-test262.mjs [--target PlainDate] [--verbose]
 */

import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync } from 'fs';
import { join, dirname, relative, basename } from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';
import vm from 'vm';

const require = createRequire(import.meta.url);
const { Temporal } = require('@js-temporal/polyfill');

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const TEST262_DIR = join(ROOT, 'tests/test262/test/built-ins/Temporal');
const FIXTURES_DIR = join(ROOT, 'tests/fixtures');

const args = process.argv.slice(2);
const VERBOSE = args.includes('--verbose');
const targetArg = args.find(a => a.startsWith('--target='))?.split('=')[1] ?? null;

// ── Targets: which subdirectories and methods to extract ─────────────────────
const TARGETS = [
  { dir: 'PlainDate/prototype/add',          type: 'PlainDate',      method: 'add' },
  { dir: 'PlainDate/prototype/subtract',     type: 'PlainDate',      method: 'subtract' },
  { dir: 'PlainDate/prototype/with',         type: 'PlainDate',      method: 'with' },
  { dir: 'PlainDate/prototype/until',        type: 'PlainDate',      method: 'until' },
  { dir: 'PlainDate/prototype/since',        type: 'PlainDate',      method: 'since' },
  { dir: 'PlainDate/prototype/equals',       type: 'PlainDate',      method: 'equals' },
  { dir: 'PlainDate/from',                   type: 'PlainDate',      method: 'from' },
  { dir: 'PlainTime/prototype/add',          type: 'PlainTime',      method: 'add' },
  { dir: 'PlainTime/prototype/subtract',     type: 'PlainTime',      method: 'subtract' },
  { dir: 'PlainTime/prototype/with',         type: 'PlainTime',      method: 'with' },
  { dir: 'PlainTime/prototype/until',        type: 'PlainTime',      method: 'until' },
  { dir: 'PlainTime/prototype/since',        type: 'PlainTime',      method: 'since' },
  { dir: 'PlainTime/prototype/equals',       type: 'PlainTime',      method: 'equals' },
  { dir: 'PlainTime/from',                   type: 'PlainTime',      method: 'from' },
  { dir: 'PlainDateTime/prototype/add',      type: 'PlainDateTime',  method: 'add' },
  { dir: 'PlainDateTime/prototype/subtract', type: 'PlainDateTime',  method: 'subtract' },
  { dir: 'PlainDateTime/prototype/with',     type: 'PlainDateTime',  method: 'with' },
  { dir: 'PlainDateTime/prototype/until',    type: 'PlainDateTime',  method: 'until' },
  { dir: 'PlainDateTime/prototype/since',    type: 'PlainDateTime',  method: 'since' },
  { dir: 'PlainDateTime/prototype/equals',   type: 'PlainDateTime',  method: 'equals' },
  { dir: 'PlainDateTime/from',               type: 'PlainDateTime',  method: 'from' },
  { dir: 'Duration/prototype/add',           type: 'Duration',       method: 'add' },
  { dir: 'Duration/prototype/subtract',      type: 'Duration',       method: 'subtract' },
  { dir: 'Duration/prototype/round',         type: 'Duration',       method: 'round' },
  { dir: 'Duration/prototype/total',         type: 'Duration',       method: 'total' },
  { dir: 'Duration/prototype/negated',       type: 'Duration',       method: 'negated' },
  { dir: 'Duration/prototype/abs',           type: 'Duration',       method: 'abs' },
  { dir: 'Duration/prototype/with',          type: 'Duration',       method: 'with' },
  { dir: 'Duration/from',                    type: 'Duration',       method: 'from' },
  { dir: 'Instant/prototype/add',            type: 'Instant',        method: 'add' },
  { dir: 'Instant/prototype/subtract',       type: 'Instant',        method: 'subtract' },
  { dir: 'Instant/prototype/until',          type: 'Instant',        method: 'until' },
  { dir: 'Instant/prototype/since',          type: 'Instant',        method: 'since' },
  { dir: 'Instant/prototype/round',          type: 'Instant',        method: 'round' },
  { dir: 'Instant/prototype/equals',         type: 'Instant',        method: 'equals' },
  { dir: 'Instant/from',                     type: 'Instant',        method: 'from' },
  { dir: 'PlainYearMonth/prototype/add',     type: 'PlainYearMonth', method: 'add' },
  { dir: 'PlainYearMonth/prototype/subtract',type: 'PlainYearMonth', method: 'subtract' },
  { dir: 'PlainYearMonth/prototype/until',   type: 'PlainYearMonth', method: 'until' },
  { dir: 'PlainYearMonth/prototype/since',   type: 'PlainYearMonth', method: 'since' },
  { dir: 'PlainYearMonth/prototype/equals',  type: 'PlainYearMonth', method: 'equals' },
  { dir: 'PlainYearMonth/from',              type: 'PlainYearMonth', method: 'from' },
  { dir: 'PlainMonthDay/prototype/equals',   type: 'PlainMonthDay',  method: 'equals' },
  { dir: 'PlainMonthDay/from',               type: 'PlainMonthDay',  method: 'from' },
];

// ── Skip list: files that test JS-specific internals ─────────────────────────
const SKIP_PATTERNS = [
  'prop-desc', 'builtin', 'branding', 'length', 'name', 'not-a-constructor',
  'subclassing', 'order-of-operations', 'options-read-before', 'calendar-temporal-object',
  'argument-not-object', 'argument-propertybag', 'argument-invalid-property',
  'argument-singular', 'infinity', 'float64', 'result-out-of-range',
  'out-of-range-when', 'precision-exact', 'total-duration-nanoseconds-too-large',
  'non-integer', 'roundingincrement-nan', 'roundingincrement-non-integer',
  'roundingincrement-out-of-range', 'roundingincrement-wrong-type', 'roundingincrement-undefined',
  'roundingincrement-valid', 'throws-on-wrong-offset', 'throws-if-neither',
  'options-wrong-type', 'wrong-type', 'options-undefined', 'options-object',
  'does-not-balance-up-to-weeks', 'bubble-time-unit', 'next-day-out-of-range',
  'relativeto-propertybag', 'relativeto-string-invalid', 'relativeto-string-limits',
  'relativeto-string-wrong-offset', 'relativeto-argument-propertybag', 'relativeto-argument-string',
  'relativeto-number', 'relativeto-infinity', 'relativeto-wrong-type',
  'relativeto-zoneddatetime', 'relativeto-propertybag', 'calendar-possibly-required',
  'days-24-hours', 'argument-mixed-sign', 'argument-duration-out-of-range',
  'argument-duration-max', 'argument-string-invalid', 'argument-string-negative',
  'overflow-invalid', 'overflow-wrong-type', 'overflow-undefined', 'overflow-options',
  'overflow-read', 'largestunit-wrong-type', 'largestunit-invalid', 'largestunit-undefined',
  'smallestunit-wrong-type', 'smallestunit-invalid', 'smallestunit-undefined',
  'roundingmode-wrong-type', 'roundingmode-invalid', 'calendar-invalid',
  'relativeto-no-fractional', 'relativeto-leap-second', 'relativeto-sub-minute',
  'blank-duration', 'durations-do-not-balance', 'limits', 'leap-year-arithmetic',
  'relativeto-date-limits', 'relativeto-duration-out-of-range', 'relativeto-required-properties',
  'relativeto-required-to-round', 'relativeto-required-for-rounding',
  'relativeto-undefined-throw', 'relativeto-ignores', 'relativeto-casts',
  'roundto-invalid', 'singular-units', 'smallestunit-plurals', 'largestunit-plurals',
  'smallestunit-string-shorthand', 'succeeds-with-largest-unit-auto',
  'year-zero', 'relativeto-string.js', 'relativeto-days-24-hours',
  'argument-not-object', 'argument-propertybag-optional', 'argument-propertybag-no',
  'balance-negative-result', 'balance-subseconds',
  'round-and-balance-calendar-units-with-increment-disallowed',
];

function shouldSkip(filename) {
  const base = basename(filename, '.js');
  return SKIP_PATTERNS.some(p => base.includes(p));
}

// ── Helper for converting Temporal objects to strings ────────────────────────
function toStr(v) {
  if (v == null) return null;
  if (typeof v === 'string') return v;
  if (typeof v === 'number' || typeof v === 'boolean' || typeof v === 'bigint') return String(v);
  if (typeof v?.toString === 'function') return v.toString();
  return String(v);
}

// ── Assertion capture ─────────────────────────────────────────────────────────
function makeCapture() {
  const assertions = [];

  function normDuration(y, mo, w, d, h, mi, s, ms, us, ns) {
    return {
      years: y ?? 0, months: mo ?? 0, weeks: w ?? 0, days: d ?? 0,
      hours: h ?? 0, minutes: mi ?? 0, seconds: s ?? 0,
      milliseconds: ms ?? 0, microseconds: us ?? 0, nanoseconds: ns ?? 0,
    };
  }

  const TemporalHelpers = {
    assertPlainDate(actual, year, month, _monthCode, day, description) {
      assertions.push({
        kind: 'assertPlainDate',
        actual: toStr(actual),
        expected: { year, month, day },
        description: description ?? null,
      });
    },
    assertPlainDatesEqual(actual, expected, description) {
      assertions.push({
        kind: 'assertPlainDate',
        actual: toStr(actual),
        expected: { year: expected.year, month: expected.month, day: expected.day },
        description: description ?? null,
      });
    },
    assertPlainTime(actual, h, mi, s, ms, us, ns, description) {
      assertions.push({
        kind: 'assertPlainTime',
        actual: toStr(actual),
        expected: {
          hour: h ?? 0, minute: mi ?? 0, second: s ?? 0,
          millisecond: ms ?? 0, microsecond: us ?? 0, nanosecond: ns ?? 0,
        },
        description: description ?? null,
      });
    },
    assertPlainTimesEqual(actual, expected, description) {
      assertions.push({
        kind: 'assertPlainTime',
        actual: toStr(actual),
        expected: {
          hour: expected.hour ?? 0, minute: expected.minute ?? 0, second: expected.second ?? 0,
          millisecond: expected.millisecond ?? 0, microsecond: expected.microsecond ?? 0,
          nanosecond: expected.nanosecond ?? 0,
        },
        description: description ?? null,
      });
    },
    assertPlainDateTime(actual, y, mo, _mc, d, h, mi, s, ms, us, ns, description) {
      assertions.push({
        kind: 'assertPlainDateTime',
        actual: toStr(actual),
        expected: {
          year: y, month: mo, day: d,
          hour: h ?? 0, minute: mi ?? 0, second: s ?? 0,
          millisecond: ms ?? 0, microsecond: us ?? 0, nanosecond: ns ?? 0,
        },
        description: description ?? null,
      });
    },
    assertPlainDateTimesEqual(actual, expected, description) {
      assertions.push({
        kind: 'assertPlainDateTime',
        actual: toStr(actual),
        expected: {
          year: expected.year, month: expected.month, day: expected.day,
          hour: expected.hour ?? 0, minute: expected.minute ?? 0, second: expected.second ?? 0,
          millisecond: expected.millisecond ?? 0, microsecond: expected.microsecond ?? 0,
          nanosecond: expected.nanosecond ?? 0,
        },
        description: description ?? null,
      });
    },
    assertDuration(actual, y, mo, w, d, h, mi, s, ms, us, ns, description) {
      assertions.push({
        kind: 'assertDuration',
        actual: toStr(actual),
        expected: normDuration(y, mo, w, d, h, mi, s, ms, us, ns),
        description: description ?? null,
      });
    },
    assertDurationsEqual(actual, expected, description) {
      assertions.push({
        kind: 'assertDuration',
        actual: toStr(actual),
        expected: normDuration(
          expected.years, expected.months, expected.weeks, expected.days,
          expected.hours, expected.minutes, expected.seconds,
          expected.milliseconds, expected.microseconds, expected.nanoseconds,
        ),
        description: description ?? null,
      });
    },
    assertPlainYearMonth(actual, y, m, _mc, description) {
      assertions.push({
        kind: 'assertPlainYearMonth',
        actual: toStr(actual),
        expected: { year: y, month: m },
        description: description ?? null,
      });
    },
    assertPlainMonthDay(actual, _mc, d, description) {
      assertions.push({
        kind: 'assertPlainMonthDay',
        actual: toStr(actual),
        expected: { day: d },
        description: description ?? null,
      });
    },
    assertInstantsEqual(actual, expected, description) {
      assertions.push({
        kind: 'assertInstant',
        actual: toStr(actual),
        expected: toStr(expected),
        description: description ?? null,
      });
    },
    // Stubs for helpers we don't care about
    checkPlainDateTimeConversionFastPath() {},
    checkTemporalUnitPluralsAndSingulars() {},
    checkTemporalUnitPluralsOnlyFromOption() {},
    checkRoundingModeProp() {},
    checkOptionalTemporalUnitArgument() {},
    assertEpochNs() {},
    isValidEpochNanoseconds() { return true; },
  };

  const assertModule = {
    sameValue(actual, expected, description) {
      // Only capture primitive comparisons (toString results, numeric values, booleans)
      if (typeof actual === typeof expected && typeof actual !== 'object') {
        assertions.push({
          kind: 'sameValue',
          actual: String(actual),
          expected: String(expected),
          description: description ?? null,
        });
      } else if (actual != null && typeof actual.toString === 'function' &&
                 expected != null && typeof expected === 'string') {
        assertions.push({
          kind: 'sameValue',
          actual: actual.toString(),
          expected: expected,
          description: description ?? null,
        });
      }
    },
    notSameValue() {},
    throws(fn, _errorType, description) {
      try {
        fn();
        assertions.push({ kind: 'shouldThrow', threw: false, description: description ?? null });
      } catch (e) {
        assertions.push({ kind: 'shouldThrow', threw: true, description: description ?? null });
      }
    },
    compareArray() {},
  };

  return { assertions, TemporalHelpers, assert: assertModule };
}

// ── File runner ───────────────────────────────────────────────────────────────
function runTestFile(filePath, source) {
  const { assertions, TemporalHelpers, assert } = makeCapture();

  const context = vm.createContext({
    Temporal,
    TemporalHelpers,
    assert,
    console,
    // Stubs for things tests might access
    $DONE: () => {},
    $262: { createRealm: () => ({}) },
    Array, Object, Math, Number, String, Boolean, Symbol, BigInt,
    Error, TypeError, RangeError, SyntaxError,
    Promise, Set, Map, WeakSet, WeakMap,
    JSON, Reflect, Proxy, undefined,
    isNaN, isFinite, parseInt, parseFloat, encodeURIComponent, decodeURIComponent,
  });

  try {
    vm.runInContext(source, context, { filename: filePath, timeout: 5000 });
  } catch (e) {
    if (VERBOSE) console.error(`  ✗ runtime error: ${e.message}`);
    return null;
  }

  return assertions;
}

// ── Walk directory ────────────────────────────────────────────────────────────
function walkDir(dir) {
  const files = [];
  try {
    for (const entry of readdirSync(dir)) {
      const full = join(dir, entry);
      const stat = statSync(full);
      if (stat.isDirectory()) {
        files.push(...walkDir(full));
      } else if (entry.endsWith('.js')) {
        files.push(full);
      }
    }
  } catch (_) {}
  return files;
}

// ── Main extraction ───────────────────────────────────────────────────────────
mkdirSync(FIXTURES_DIR, { recursive: true });

let totalAssertions = 0;
let totalFiles = 0;
let totalSkipped = 0;

const filteredTargets = targetArg
  ? TARGETS.filter(t => t.type === targetArg)
  : TARGETS;

for (const target of filteredTargets) {
  const targetDir = join(TEST262_DIR, target.dir);
  const files = walkDir(targetDir);

  if (files.length === 0) {
    if (VERBOSE) console.log(`[SKIP] ${target.dir} — directory not found or empty`);
    continue;
  }

  const fixture = {
    type: target.type,
    method: target.method,
    source: `test/built-ins/Temporal/${target.dir}`,
    cases: [],
  };

  for (const filePath of files.sort()) {
    const filename = basename(filePath);
    if (shouldSkip(filename)) {
      totalSkipped++;
      if (VERBOSE) console.log(`  [SKIP] ${filename}`);
      continue;
    }

    let source;
    try {
      source = readFileSync(filePath, 'utf8');
    } catch (_) {
      continue;
    }

    // Skip files that test JS-specific error types we can't map
    if (source.includes('TypeError') || source.includes('detached')) {
      totalSkipped++;
      if (VERBOSE) console.log(`  [SKIP] ${filename} — JS-specific error`);
      continue;
    }
    // Skip files with calendar overrides or proxy traps
    if (source.includes('calendar.') || source.includes('new Proxy') || source.includes('Reflect.')) {
      totalSkipped++;
      if (VERBOSE) console.log(`  [SKIP] ${filename} — proxy/calendar override`);
      continue;
    }

    const assertions = runTestFile(filePath, source);

    if (!assertions) {
      totalSkipped++;
      continue;
    }

    const relPath = relative(join(ROOT, 'tests/test262'), filePath);
    const usable = assertions.filter(a => a.kind !== 'shouldThrow');

    if (usable.length > 0) {
      fixture.cases.push({
        file: relPath,
        assertions: usable,
      });
      totalAssertions += usable.length;
      totalFiles++;
      if (VERBOSE) console.log(`  ✓ ${filename} — ${usable.length} assertion(s)`);
    } else if (assertions.length > 0) {
      totalSkipped++;
      if (VERBOSE) console.log(`  [SKIP] ${filename} — only throw assertions`);
    } else {
      totalSkipped++;
      if (VERBOSE) console.log(`  [SKIP] ${filename} — no assertions captured`);
    }
  }

  if (fixture.cases.length > 0) {
    const outFile = join(FIXTURES_DIR, `${target.type}.${target.method}.json`);
    const existing = (() => {
      try { return JSON.parse(readFileSync(outFile, 'utf8')); } catch (_) { return null; }
    })();

    if (existing) {
      // Merge: add cases from this target dir, avoiding duplicates
      const existingFiles = new Set(existing.cases.map(c => c.file));
      for (const c of fixture.cases) {
        if (!existingFiles.has(c.file)) existing.cases.push(c);
      }
      writeFileSync(outFile, JSON.stringify(existing, null, 2));
    } else {
      writeFileSync(outFile, JSON.stringify(fixture, null, 2));
    }
    console.log(`✓ ${target.type}.${target.method} — ${fixture.cases.length} file(s), written to ${relative(ROOT, outFile)}`);
  } else {
    if (VERBOSE) console.log(`[EMPTY] ${target.dir} — no extractable cases`);
  }
}

console.log(`\nDone: ${totalFiles} files processed, ${totalAssertions} assertions extracted, ${totalSkipped} skipped.`);
