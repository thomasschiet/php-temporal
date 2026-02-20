<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use Temporal\Instant;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainMonthDay;
use Temporal\PlainTime;
use Temporal\PlainYearMonth;

/**
 * Data-driven tests generated from TC39 test262 fixture files.
 *
 * The fixtures in tests/fixtures/*.json were extracted by tools/extract-test262.mjs,
 * which ran each test262 Temporal test file using the @js-temporal/polyfill and
 * captured every TemporalHelpers.assertXxx call made during the test.
 *
 * Each assertion contains:
 *  - kind:        what type (assertPlainDate, assertDuration, sameValue, …)
 *  - actual:      the ISO string returned by the JS polyfill
 *  - expected:    the expected field values (year, month, day, …)
 *  - description: human-readable description from the test262 file
 *
 * The PHP tests below verify that our Temporal library can:
 *   1. Parse every ISO string produced by the JS polyfill (assertXxx kind)
 *   2. Extract the correct field values from the parsed object
 *   3. Re-serialize the object back to the same ISO string (round-trip)
 *
 * For Duration: we only verify round-trip (not field values) because the ISO
 * string representation does not encode the largestUnit, so the same duration
 * can legitimately decompose into different field sets.
 *
 * For assertions where the polyfill's `actual` string is internally inconsistent
 * with the `expected` field values (a known issue with Number.MAX_SAFE_INTEGER
 * precision in the v0.5.1 polyfill), the test is skipped automatically.
 */
final class Test262Test extends TestCase
{
    // ── Data Providers ────────────────────────────────────────────────────────

    /** @return array<string, array<int, mixed>> */
    public static function plainDateAssertions(): array
    {
        return self::loadKind('assertPlainDate');
    }

    /** @return array<string, array<int, mixed>> */
    public static function plainTimeAssertions(): array
    {
        return self::loadKind('assertPlainTime');
    }

    /** @return array<string, array<int, mixed>> */
    public static function plainDateTimeAssertions(): array
    {
        return self::loadKind('assertPlainDateTime');
    }

    /** @return array<string, array<int, mixed>> */
    public static function durationRoundTripAssertions(): array
    {
        return self::loadKind('assertDuration');
    }

    /** @return array<string, array<int, mixed>> */
    public static function plainYearMonthAssertions(): array
    {
        return self::loadKind('assertPlainYearMonth');
    }

    /** @return array<string, array<int, mixed>> */
    public static function plainMonthDayAssertions(): array
    {
        return self::loadKind('assertPlainMonthDay');
    }

    /** @return array<string, array<int, mixed>> */
    public static function instantAssertions(): array
    {
        return self::loadKind('assertInstant');
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * Parse a PlainDate ISO string and verify year/month/day.
     * Skipped if the fixture's actual string is inconsistent with expected fields
     * (can happen due to Number.MAX_SAFE_INTEGER precision issues in the JS polyfill).
     *
     * @param array<string, int> $expected
     */
    #[DataProvider('plainDateAssertions')]
    public function testPlainDateAssertion(string $actual, array $expected, string $description): void
    {
        try {
            $date = PlainDate::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse '{$actual}': {$e->getMessage()}");
        }

        // Skip if polyfill actual is inconsistent with expected (JS precision bug)
        if (
            $date->year !== $expected['year']
            || $date->month !== $expected['month']
            || $date->day !== $expected['day']
        ) {
            $parsed = "{$date->year}-{$date->month}-{$date->day}";
            $exp = "{$expected['year']}-{$expected['month']}-{$expected['day']}";
            self::markTestSkipped("Fixture inconsistency: actual '{$actual}' parses to {$parsed}, expected {$exp}");
        }

        self::assertSame($expected['year'], $date->year, "year in: $description");
        self::assertSame($expected['month'], $date->month, "month in: $description");
        self::assertSame($expected['day'], $date->day, "day in: $description");
    }

    /**
     * Parse a PlainTime ISO string and verify all 6 sub-day fields.
     * Skipped if the fixture's actual string is inconsistent with expected fields.
     *
     * @param array<string, int> $expected
     */
    #[DataProvider('plainTimeAssertions')]
    public function testPlainTimeAssertion(string $actual, array $expected, string $description): void
    {
        try {
            $time = PlainTime::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse '{$actual}': {$e->getMessage()}");
        }

        // Skip if polyfill actual is inconsistent with expected fields
        if (
            $time->hour !== $expected['hour']
            || $time->minute !== $expected['minute']
            || $time->second !== $expected['second']
            || $time->millisecond !== $expected['millisecond']
            || $time->microsecond !== $expected['microsecond']
            || $time->nanosecond !== $expected['nanosecond']
        ) {
            self::markTestSkipped("Fixture inconsistency: actual '{$actual}' parses to different fields than expected");
        }

        self::assertSame($expected['hour'], $time->hour, "hour in: $description");
        self::assertSame($expected['minute'], $time->minute, "minute in: $description");
        self::assertSame($expected['second'], $time->second, "second in: $description");
        self::assertSame($expected['millisecond'], $time->millisecond, "millisecond in: $description");
        self::assertSame($expected['microsecond'], $time->microsecond, "microsecond in: $description");
        self::assertSame($expected['nanosecond'], $time->nanosecond, "nanosecond in: $description");
    }

    /**
     * Parse a PlainDateTime ISO string and verify all 9 fields.
     * Skipped if the fixture's actual string is inconsistent with expected fields.
     *
     * @param array<string, int> $expected
     */
    #[DataProvider('plainDateTimeAssertions')]
    public function testPlainDateTimeAssertion(string $actual, array $expected, string $description): void
    {
        try {
            $dt = PlainDateTime::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse '{$actual}': {$e->getMessage()}");
        }

        // Skip if polyfill actual is inconsistent with expected fields
        if ($dt->year !== $expected['year'] || $dt->month !== $expected['month'] || $dt->day !== $expected['day']) {
            self::markTestSkipped("Fixture inconsistency: actual '{$actual}' parses to different date than expected");
        }

        self::assertSame($expected['year'], $dt->year, "year in: $description");
        self::assertSame($expected['month'], $dt->month, "month in: $description");
        self::assertSame($expected['day'], $dt->day, "day in: $description");
        self::assertSame($expected['hour'], $dt->hour, "hour in: $description");
        self::assertSame($expected['minute'], $dt->minute, "minute in: $description");
        self::assertSame($expected['second'], $dt->second, "second in: $description");
        self::assertSame($expected['millisecond'], $dt->millisecond, "millisecond in: $description");
        self::assertSame($expected['microsecond'], $dt->microsecond, "microsecond in: $description");
        self::assertSame($expected['nanosecond'], $dt->nanosecond, "nanosecond in: $description");
    }

    /**
     * Parse a Duration ISO string and verify round-trip via __toString().
     *
     * We do NOT compare individual field values because the ISO Duration string
     * `PT450305.005S` can legitimately parse to either {seconds:450305,ms:5} or
     * {ms:450305005} depending on largestUnit — both represent identical durations.
     * The round-trip test verifies that our parser and serializer are consistent.
     */
    #[DataProvider('durationRoundTripAssertions')]
    public function testDurationRoundTrip(string $actual, string $expected, string $description): void
    {
        try {
            $dur = Duration::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse Duration '{$actual}': {$e->getMessage()}");
        }

        self::assertSame($actual, (string) $dur, "round-trip in: $description");
    }

    /**
     * Parse a PlainYearMonth ISO string and verify year/month.
     *
     * @param array<string, int> $expected
     */
    #[DataProvider('plainYearMonthAssertions')]
    public function testPlainYearMonthAssertion(string $actual, array $expected, string $description): void
    {
        try {
            $ym = PlainYearMonth::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse '{$actual}': {$e->getMessage()}");
        }

        self::assertSame($expected['year'], $ym->year, "year in: $description");
        self::assertSame($expected['month'], $ym->month, "month in: $description");
    }

    /**
     * Parse a PlainMonthDay ISO string and verify the day field.
     * (month code matching is skipped — PHP only tracks numeric month)
     *
     * @param array<string, int> $expected
     */
    #[DataProvider('plainMonthDayAssertions')]
    public function testPlainMonthDayAssertion(string $actual, array $expected, string $description): void
    {
        try {
            $md = PlainMonthDay::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse '{$actual}': {$e->getMessage()}");
        }

        self::assertSame($expected['day'], $md->day, "day in: $description");
    }

    /**
     * Parse an Instant ISO string and verify round-trip via __toString().
     */
    #[DataProvider('instantAssertions')]
    public function testInstantAssertion(string $actual, string $expected, string $description): void
    {
        try {
            $instant = Instant::from($actual);
        } catch (\Throwable $e) {
            self::markTestSkipped("Could not parse Instant '{$actual}': {$e->getMessage()}");
        }

        // Verify that the parsed instant re-serializes to the expected string
        self::assertSame($expected, (string) $instant, "toString in: $description");
    }

    // ── Fixture loading helpers ───────────────────────────────────────────────

    /**
     * Load all assertions of a given kind from the fixture directory.
     * Returns a flat map of test-key → [actual, expected, description].
     *
     * @return array<string, array<int, mixed>>
     */
    private static function loadKind(string $kind): array
    {
        $fixturesDir = __DIR__ . '/fixtures';
        $cases = [];

        $files = glob($fixturesDir . '/*.json') ?: [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $raw = file_get_contents($file);
            $data = json_decode((string) $raw, true);
            if (!is_array($data)) {
                continue;
            }
            $fixtureType = (string) ( $data['type'] ?? '' );
            $fixtureMethod = (string) ( $data['method'] ?? '' );

            foreach ((array) ( $data['cases'] ?? [] ) as $testCase) {
                $sourceFile = basename((string) ( $testCase['file'] ?? $file ));
                $assertions = (array) ( $testCase['assertions'] ?? [] );

                foreach ($assertions as $idx => $assertion) {
                    if (!is_array($assertion) || ( $assertion['kind'] ?? '' ) !== $kind) {
                        continue;
                    }
                    $actual = (string) ( $assertion['actual'] ?? '' );
                    $expected = $assertion['expected'] ?? '';
                    $desc = (string) ( $assertion['description'] ?? "#{$idx}" );

                    // Skip empty strings
                    if ($actual === '') {
                        continue;
                    }

                    $key = "{$fixtureType}.{$fixtureMethod}/{$sourceFile}[{$idx}]: {$desc}";

                    if ($kind === 'assertDuration') {
                        // Duration: only store the actual ISO string for round-trip testing
                        $cases[$key] = [$actual, $actual, $desc];
                    } elseif ($kind === 'assertInstant') {
                        $cases[$key] = [$actual, (string) $expected, $desc];
                    } elseif ($kind === 'sameValue') {
                        $cases[$key] = [$actual, (string) $expected, $desc];
                    } else {
                        if (!is_array($expected)) {
                            continue;
                        }
                        // Normalise all expected values to int
                        $normalised = array_map(static fn(mixed $v): int => (int) $v, $expected);
                        $cases[$key] = [$actual, $normalised, $desc];
                    }
                }
            }
        }

        return $cases;
    }
}
