<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use PHPUnit\Framework\TestCase;
use Temporal\Instant;
use Temporal\Now;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainTime;
use Temporal\TimeZone;
use Temporal\ZonedDateTime;

/**
 * Tests for the Temporal\Now class.
 *
 * Since Now returns the current time (which changes), these tests focus on:
 * - Return types and type safety
 * - Timezone correctness
 * - Rough temporal correctness (values are in a plausible range)
 * - timeZoneId() returns the system timezone
 */
final class NowTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Now::instant()
    // -------------------------------------------------------------------------

    public function testInstantReturnsInstance(): void
    {
        $instant = Now::instant();
        self::assertInstanceOf(Instant::class, $instant);
    }

    public function testInstantEpochSecondsIsReasonable(): void
    {
        // 2020-01-01T00:00:00Z in seconds
        $minSeconds = 1577836800;
        // 2030-01-01T00:00:00Z in seconds
        $maxSeconds = 1893456000;

        $instant = Now::instant();
        self::assertGreaterThan($minSeconds, $instant->epochSeconds);
        self::assertLessThan($maxSeconds, $instant->epochSeconds);
    }

    public function testInstantEpochNanosecondsIsMultipleOfThousand(): void
    {
        // microtime() gives microsecond precision, so ns must be divisible by 1000
        $instant = Now::instant();
        self::assertSame(0, $instant->epochNanoseconds % 1_000);
    }

    public function testTwoInstantCallsAreMonotonicallyNonDecreasing(): void
    {
        $a = Now::instant();
        $b = Now::instant();
        // b >= a (time doesn't go backwards)
        self::assertGreaterThanOrEqual($a->epochNanoseconds, $b->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // Now::timeZoneId()
    // -------------------------------------------------------------------------

    public function testTimeZoneIdReturnsString(): void
    {
        $id = Now::timeZoneId();
        self::assertIsString($id);
        self::assertNotEmpty($id);
    }

    public function testTimeZoneIdMatchesPhpDefaultTimezone(): void
    {
        $phpTz = date_default_timezone_get();
        self::assertSame($phpTz, Now::timeZoneId());
    }

    // -------------------------------------------------------------------------
    // Now::zonedDateTimeISO()
    // -------------------------------------------------------------------------

    public function testZonedDateTimeISOReturnsZonedDateTime(): void
    {
        $zdt = Now::zonedDateTimeISO();
        self::assertInstanceOf(ZonedDateTime::class, $zdt);
    }

    public function testZonedDateTimeISODefaultsToSystemTimezone(): void
    {
        $zdt = Now::zonedDateTimeISO();
        $expectedTz = date_default_timezone_get();
        self::assertSame($expectedTz, (string) $zdt->timeZone);
    }

    public function testZonedDateTimeISOWithExplicitStringTimezone(): void
    {
        $zdt = Now::zonedDateTimeISO('UTC');
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testZonedDateTimeISOWithExplicitTimezoneObject(): void
    {
        $tz = TimeZone::from('America/New_York');
        $zdt = Now::zonedDateTimeISO($tz);
        self::assertSame('America/New_York', (string) $zdt->timeZone);
    }

    public function testZonedDateTimeISOEpochSecondsMatchesInstant(): void
    {
        // The ZonedDateTime's epochSeconds should be close to Now::instant()'s
        $before = Now::instant()->epochSeconds;
        $zdt = Now::zonedDateTimeISO('UTC');
        $after = Now::instant()->epochSeconds;

        self::assertGreaterThanOrEqual($before, $zdt->epochSeconds);
        self::assertLessThanOrEqual($after, $zdt->epochSeconds);
    }

    public function testZonedDateTimeISOUtcHasCorrectYear(): void
    {
        $zdt = Now::zonedDateTimeISO('UTC');
        // Year should be in a plausible range
        self::assertGreaterThanOrEqual(2020, $zdt->year);
        self::assertLessThanOrEqual(2030, $zdt->year);
    }

    // -------------------------------------------------------------------------
    // Now::plainDateTimeISO()
    // -------------------------------------------------------------------------

    public function testPlainDateTimeISOReturnsPlainDateTime(): void
    {
        $pdt = Now::plainDateTimeISO();
        self::assertInstanceOf(PlainDateTime::class, $pdt);
    }

    public function testPlainDateTimeISOWithUtc(): void
    {
        $pdt = Now::plainDateTimeISO('UTC');
        self::assertInstanceOf(PlainDateTime::class, $pdt);
        self::assertGreaterThanOrEqual(2020, $pdt->year);
        self::assertLessThanOrEqual(2030, $pdt->year);
    }

    public function testPlainDateTimeISOHourIsInRange(): void
    {
        $pdt = Now::plainDateTimeISO('UTC');
        self::assertGreaterThanOrEqual(0, $pdt->hour);
        self::assertLessThanOrEqual(23, $pdt->hour);
    }

    // -------------------------------------------------------------------------
    // Now::plainDateISO()
    // -------------------------------------------------------------------------

    public function testPlainDateISOReturnsPlainDate(): void
    {
        $pd = Now::plainDateISO();
        self::assertInstanceOf(PlainDate::class, $pd);
    }

    public function testPlainDateISOWithUtc(): void
    {
        $pd = Now::plainDateISO('UTC');
        self::assertGreaterThanOrEqual(2020, $pd->year);
        self::assertLessThanOrEqual(2030, $pd->year);
        self::assertGreaterThanOrEqual(1, $pd->month);
        self::assertLessThanOrEqual(12, $pd->month);
        self::assertGreaterThanOrEqual(1, $pd->day);
        self::assertLessThanOrEqual(31, $pd->day);
    }

    // -------------------------------------------------------------------------
    // Now::plainTimeISO()
    // -------------------------------------------------------------------------

    public function testPlainTimeISOReturnsPlainTime(): void
    {
        $pt = Now::plainTimeISO();
        self::assertInstanceOf(PlainTime::class, $pt);
    }

    public function testPlainTimeISOWithUtcHasValidFields(): void
    {
        $pt = Now::plainTimeISO('UTC');
        self::assertGreaterThanOrEqual(0, $pt->hour);
        self::assertLessThanOrEqual(23, $pt->hour);
        self::assertGreaterThanOrEqual(0, $pt->minute);
        self::assertLessThanOrEqual(59, $pt->minute);
        self::assertGreaterThanOrEqual(0, $pt->second);
        self::assertLessThanOrEqual(59, $pt->second);
    }

    // -------------------------------------------------------------------------
    // Consistency between Now methods
    // -------------------------------------------------------------------------

    public function testPlainDateTimeMatchesZonedDateTimeComponents(): void
    {
        $tz = 'UTC';
        $zdt = Now::zonedDateTimeISO($tz);
        $pdt = $zdt->toPlainDateTime();

        self::assertSame($zdt->year, $pdt->year);
        self::assertSame($zdt->month, $pdt->month);
        self::assertSame($zdt->day, $pdt->day);
        self::assertSame($zdt->hour, $pdt->hour);
        self::assertSame($zdt->minute, $pdt->minute);
    }

    public function testPlainDateMatchesZonedDateTimeDate(): void
    {
        $tz = 'UTC';
        $zdt = Now::zonedDateTimeISO($tz);
        $pd = $zdt->toPlainDate();

        self::assertSame($zdt->year, $pd->year);
        self::assertSame($zdt->month, $pd->month);
        self::assertSame($zdt->day, $pd->day);
    }
}
