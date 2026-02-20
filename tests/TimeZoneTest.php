<?php

declare(strict_types=1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Instant;
use Temporal\PlainDateTime;
use Temporal\TimeZone;

final class TimeZoneTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction / from()
    // -------------------------------------------------------------------------

    public function testFromUtcString(): void
    {
        $tz = TimeZone::from('UTC');
        self::assertSame('UTC', (string) $tz);
    }

    public function testFromFixedOffsetPositive(): void
    {
        $tz = TimeZone::from('+05:30');
        self::assertSame('+05:30', (string) $tz);
    }

    public function testFromFixedOffsetNegative(): void
    {
        $tz = TimeZone::from('-08:00');
        self::assertSame('-08:00', (string) $tz);
    }

    public function testFromZeroOffset(): void
    {
        $tz = TimeZone::from('+00:00');
        self::assertSame('+00:00', (string) $tz);
    }

    public function testFromIanaZone(): void
    {
        $tz = TimeZone::from('America/New_York');
        self::assertSame('America/New_York', (string) $tz);
    }

    public function testFromTimeZoneObject(): void
    {
        $tz1 = TimeZone::from('UTC');
        $tz2 = TimeZone::from($tz1);
        self::assertSame('UTC', (string) $tz2);
    }

    public function testFromInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeZone::from('Not/A/Valid/Zone');
    }

    public function testFromEmptyStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeZone::from('');
    }

    // -------------------------------------------------------------------------
    // id property
    // -------------------------------------------------------------------------

    public function testIdProperty(): void
    {
        $tz = TimeZone::from('Europe/London');
        self::assertSame('Europe/London', $tz->id);
    }

    public function testIdPropertyFixedOffset(): void
    {
        $tz = TimeZone::from('+05:30');
        self::assertSame('+05:30', $tz->id);
    }

    // -------------------------------------------------------------------------
    // getOffsetNanosecondsFor()
    // -------------------------------------------------------------------------

    public function testOffsetForUtcIsZero(): void
    {
        $tz      = TimeZone::from('UTC');
        $instant = Instant::fromEpochSeconds(0);
        self::assertSame(0, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForFixedPlus0530(): void
    {
        $tz      = TimeZone::from('+05:30');
        $instant = Instant::fromEpochSeconds(0);
        $expected = (5 * 3600 + 30 * 60) * 1_000_000_000; // 19800000000000
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForFixedMinus0800(): void
    {
        $tz      = TimeZone::from('-08:00');
        $instant = Instant::fromEpochSeconds(0);
        $expected = -8 * 3600 * 1_000_000_000; // -28800000000000
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForNewYorkSummerIsEdtMinus4(): void
    {
        // 2024-07-04T12:00:00Z — America/New_York is in EDT (-4 hours)
        $tz      = TimeZone::from('America/New_York');
        $instant = Instant::from('2024-07-04T12:00:00Z');
        $expected = -4 * 3600 * 1_000_000_000;
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForNewYorkWinterIsEstMinus5(): void
    {
        // 2024-01-15T12:00:00Z — America/New_York is in EST (-5 hours)
        $tz      = TimeZone::from('America/New_York');
        $instant = Instant::from('2024-01-15T12:00:00Z');
        $expected = -5 * 3600 * 1_000_000_000;
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForZeroPlusZero(): void
    {
        $tz      = TimeZone::from('+00:00');
        $instant = Instant::fromEpochSeconds(0);
        self::assertSame(0, $tz->getOffsetNanosecondsFor($instant));
    }

    // -------------------------------------------------------------------------
    // getPlainDateTimeFor()
    // -------------------------------------------------------------------------

    public function testGetPlainDateTimeForUtc(): void
    {
        $tz      = TimeZone::from('UTC');
        // 2021-08-04T12:30:00Z
        $instant = Instant::from('2021-08-04T12:30:00Z');
        $pdt     = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(30, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForFixedOffset(): void
    {
        $tz      = TimeZone::from('+05:30');
        // 2021-08-04T07:00:00Z → local 2021-08-04T12:30:00+05:30
        $instant = Instant::from('2021-08-04T07:00:00Z');
        $pdt     = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(30, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForNegativeOffset(): void
    {
        $tz      = TimeZone::from('-05:00');
        // 2021-08-04T17:00:00Z → local 2021-08-04T12:00:00-05:00
        $instant = Instant::from('2021-08-04T17:00:00Z');
        $pdt     = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(0, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForNewYorkSummer(): void
    {
        $tz      = TimeZone::from('America/New_York');
        // 2024-07-04T16:00:00Z → local 2024-07-04T12:00:00-04:00
        $instant = Instant::from('2024-07-04T16:00:00Z');
        $pdt     = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2024, $pdt->year);
        self::assertSame(7, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
    }

    public function testGetPlainDateTimeWithSubSeconds(): void
    {
        $tz      = TimeZone::from('UTC');
        $instant = Instant::from('2021-01-01T00:00:00.123456789Z');
        $pdt     = $tz->getPlainDateTimeFor($instant);

        self::assertSame(123, $pdt->millisecond);
        self::assertSame(456, $pdt->microsecond);
        self::assertSame(789, $pdt->nanosecond);
    }

    // -------------------------------------------------------------------------
    // getInstantFor()
    // -------------------------------------------------------------------------

    public function testGetInstantForUtc(): void
    {
        $tz  = TimeZone::from('UTC');
        $pdt = PlainDateTime::from('2021-08-04T12:30:00');
        $instant = $tz->getInstantFor($pdt);

        $expected = Instant::from('2021-08-04T12:30:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForFixedOffset(): void
    {
        $tz  = TimeZone::from('+05:30');
        $pdt = PlainDateTime::from('2021-08-04T12:30:00');
        // UTC = 12:30 - 5:30 = 07:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2021-08-04T07:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNegativeOffset(): void
    {
        $tz  = TimeZone::from('-05:00');
        $pdt = PlainDateTime::from('2021-08-04T12:00:00');
        // UTC = 12:00 + 5:00 = 17:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2021-08-04T17:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNewYorkSummer(): void
    {
        $tz  = TimeZone::from('America/New_York');
        $pdt = PlainDateTime::from('2024-07-04T12:00:00');
        // EDT = -4, so UTC = 16:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2024-07-04T16:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNewYorkWinter(): void
    {
        $tz  = TimeZone::from('America/New_York');
        $pdt = PlainDateTime::from('2024-01-15T12:00:00');
        // EST = -5, so UTC = 17:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2024-01-15T17:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    // -------------------------------------------------------------------------
    // equals()
    // -------------------------------------------------------------------------

    public function testEqualsWithSameId(): void
    {
        $a = TimeZone::from('UTC');
        $b = TimeZone::from('UTC');
        self::assertTrue($a->equals($b));
    }

    public function testEqualsWithDifferentId(): void
    {
        $a = TimeZone::from('UTC');
        $b = TimeZone::from('America/New_York');
        self::assertFalse($a->equals($b));
    }

    public function testEqualsFixedOffsets(): void
    {
        $a = TimeZone::from('+05:30');
        $b = TimeZone::from('+05:30');
        self::assertTrue($a->equals($b));
    }

    public function testNotEqualsFixedOffsets(): void
    {
        $a = TimeZone::from('+05:30');
        $b = TimeZone::from('+05:00');
        self::assertFalse($a->equals($b));
    }
}
