<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use Temporal\Instant;
use Temporal\TimeZone;
use Temporal\ZonedDateTime;

final class ZonedDateTimeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromEpochNanoseconds()
    // -------------------------------------------------------------------------

    public function testFromEpochNanosecondsUtc(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(0, $zdt->epochNanoseconds);
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testFromEpochNanosecondsWithTimeZoneObject(): void
    {
        $tz = TimeZone::from('+05:30');
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, $tz);
        self::assertSame('+05:30', (string) $zdt->timeZone);
    }

    public function testEpochZeroInUtcIsJanFirst1970(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(1970, $zdt->year);
        self::assertSame(1, $zdt->month);
        self::assertSame(1, $zdt->day);
        self::assertSame(0, $zdt->hour);
        self::assertSame(0, $zdt->minute);
        self::assertSame(0, $zdt->second);
        self::assertSame(0, $zdt->millisecond);
        self::assertSame(0, $zdt->microsecond);
        self::assertSame(0, $zdt->nanosecond);
    }

    public function testEpochZeroInPlusFiveThirty(): void
    {
        // UTC 1970-01-01T00:00:00Z → +05:30 local = 1970-01-01T05:30:00
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        self::assertSame(1970, $zdt->year);
        self::assertSame(1, $zdt->month);
        self::assertSame(1, $zdt->day);
        self::assertSame(5, $zdt->hour);
        self::assertSame(30, $zdt->minute);
        self::assertSame(0, $zdt->second);
    }

    public function testEpochZeroInMinusFive(): void
    {
        // UTC 1970-01-01T00:00:00Z → -05:00 local = 1969-12-31T19:00:00
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '-05:00');
        self::assertSame(1969, $zdt->year);
        self::assertSame(12, $zdt->month);
        self::assertSame(31, $zdt->day);
        self::assertSame(19, $zdt->hour);
        self::assertSame(0, $zdt->minute);
    }

    // -------------------------------------------------------------------------
    // from() – string parsing
    // -------------------------------------------------------------------------

    public function testFromStringWithBracketTimezone(): void
    {
        // 2021-08-04T12:30:00+00:00[UTC]
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]');
        self::assertSame(2021, $zdt->year);
        self::assertSame(8, $zdt->month);
        self::assertSame(4, $zdt->day);
        self::assertSame(12, $zdt->hour);
        self::assertSame(30, $zdt->minute);
        self::assertSame(0, $zdt->second);
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testFromStringWithZ(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00Z');
        self::assertSame(2021, $zdt->year);
        self::assertSame(12, $zdt->hour);
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testFromStringWithNegativeOffset(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T08:30:00-04:00[America/New_York]');
        self::assertSame(2021, $zdt->year);
        self::assertSame(8, $zdt->month);
        self::assertSame(4, $zdt->day);
        self::assertSame(8, $zdt->hour);
        self::assertSame(30, $zdt->minute);
        self::assertSame('America/New_York', (string) $zdt->timeZone);
    }

    public function testFromStringWithSubSeconds(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00.123456789+00:00[UTC]');
        self::assertSame(123, $zdt->millisecond);
        self::assertSame(456, $zdt->microsecond);
        self::assertSame(789, $zdt->nanosecond);
    }

    public function testFromStringWithFixedOffsetNoName(): void
    {
        // No bracket notation → fixed offset timezone
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+05:30');
        self::assertSame(12, $zdt->hour);
        self::assertSame('+05:30', (string) $zdt->timeZone);
    }

    public function testFromStringInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ZonedDateTime::from('not-a-valid-string');
    }

    public function testFromArray(): void
    {
        $zdt = ZonedDateTime::from([
            'year' => 2021,
            'month' => 8,
            'day' => 4,
            'hour' => 12,
            'minute' => 30,
            'second' => 0,
            'timeZone' => 'UTC'
        ]);
        self::assertSame(2021, $zdt->year);
        self::assertSame(8, $zdt->month);
        self::assertSame(4, $zdt->day);
        self::assertSame(12, $zdt->hour);
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testFromArrayMissingTimeZoneThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ZonedDateTime::from(['year' => 2021, 'month' => 1, 'day' => 1]);
    }

    public function testFromZonedDateTimeObject(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::from($zdt1);
        self::assertSame(0, $zdt2->epochNanoseconds);
        self::assertSame('UTC', (string) $zdt2->timeZone);
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    public function testEpochMilliseconds(): void
    {
        // 1_000_000 ns = 1 ms
        $zdt = ZonedDateTime::fromEpochNanoseconds(1_000_000, 'UTC');
        self::assertSame(1, $zdt->epochMilliseconds);
    }

    public function testEpochSeconds(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(5_000_000_000, 'UTC');
        self::assertSame(5, $zdt->epochSeconds);
    }

    public function testOffsetNanosecondsUtc(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(0, $zdt->offsetNanoseconds);
    }

    public function testOffsetNanosecondsPositive(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        $expected = ( ( 5 * 3600 ) + ( 30 * 60 ) ) * 1_000_000_000;
        self::assertSame($expected, $zdt->offsetNanoseconds);
    }

    public function testOffsetStringUtc(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame('+00:00', $zdt->offset);
    }

    public function testOffsetStringPositive(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        self::assertSame('+05:30', $zdt->offset);
    }

    public function testOffsetStringNegative(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '-08:00');
        self::assertSame('-08:00', $zdt->offset);
    }

    // -------------------------------------------------------------------------
    // Conversion methods
    // -------------------------------------------------------------------------

    public function testToInstant(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        $instant = $zdt->toInstant();
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function testToPlainDate(): void
    {
        // 2021-08-04T12:00:00+05:30
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+05:30');
        $date = $zdt->toPlainDate();
        self::assertSame(2021, $date->year);
        self::assertSame(8, $date->month);
        self::assertSame(4, $date->day);
    }

    public function testToPlainTime(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:45+00:00[UTC]');
        $time = $zdt->toPlainTime();
        self::assertSame(12, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function testToPlainDateTime(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:45.123+00:00[UTC]');
        $pdt = $zdt->toPlainDateTime();
        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(30, $pdt->minute);
        self::assertSame(45, $pdt->second);
        self::assertSame(123, $pdt->millisecond);
    }

    // -------------------------------------------------------------------------
    // withTimeZone()
    // -------------------------------------------------------------------------

    public function testWithTimeZoneKeepsSameInstant(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = $zdt1->withTimeZone('+05:30');

        // Same instant
        self::assertSame($zdt1->epochNanoseconds, $zdt2->epochNanoseconds);
        // Different local time
        self::assertSame(17, $zdt2->hour);
        self::assertSame(30, $zdt2->minute);
        self::assertSame('+05:30', (string) $zdt2->timeZone);
    }

    public function testWithTimeZoneFromObjectToString(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $tz = TimeZone::from('-05:00');
        $zdt2 = $zdt1->withTimeZone($tz);
        self::assertSame(7, $zdt2->hour);
        self::assertSame(0, $zdt2->minute);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithChangesLocalTime(): void
    {
        // Change hour; the instant changes
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = $zdt1->with(['hour' => 15]);
        self::assertSame(15, $zdt2->hour);
        self::assertSame(2021, $zdt2->year);
        // Instant should be 3 hours later
        $diff = $zdt2->epochNanoseconds - $zdt1->epochNanoseconds;
        self::assertSame(3 * 3_600_000_000_000, $diff);
    }

    public function testWithChangesDate(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = $zdt1->with(['day' => 10]);
        self::assertSame(10, $zdt2->day);
        self::assertSame(12, $zdt2->hour); // Same local time
    }

    // -------------------------------------------------------------------------
    // add() and subtract()
    // -------------------------------------------------------------------------

    public function testAddHours(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $result = $zdt->add(new Duration(hours: 3));
        self::assertSame(15, $result->hour);
    }

    public function testAddDays(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $result = $zdt->add(new Duration(days: 5));
        self::assertSame(9, $result->day);
        self::assertSame(12, $result->hour); // Same time of day
    }

    public function testAddMonths(): void
    {
        $zdt = ZonedDateTime::from('2021-01-31T12:00:00+00:00[UTC]');
        $result = $zdt->add(new Duration(months: 1));
        // Jan 31 + 1 month → Feb 28 (constrained)
        self::assertSame(2, $result->month);
        self::assertSame(28, $result->day);
    }

    public function testSubtractHours(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $result = $zdt->subtract(new Duration(hours: 5));
        self::assertSame(7, $result->hour);
    }

    public function testSubtractDays(): void
    {
        $zdt = ZonedDateTime::from('2021-08-10T12:00:00+00:00[UTC]');
        $result = $zdt->subtract(new Duration(days: 5));
        self::assertSame(5, $result->day);
        self::assertSame(12, $result->hour);
    }

    public function testAddFromArray(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $result = $zdt->add(['hours' => 2, 'minutes' => 30]);
        self::assertSame(14, $result->hour);
        self::assertSame(30, $result->minute);
    }

    public function testAddWrapsAroundMidnight(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T22:00:00+00:00[UTC]');
        $result = $zdt->add(new Duration(hours: 4));
        self::assertSame(5, $result->day);
        self::assertSame(2, $result->hour);
    }

    // -------------------------------------------------------------------------
    // until() / since()
    // -------------------------------------------------------------------------

    public function testUntilSameZone(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = ZonedDateTime::from('2021-08-04T15:30:00+00:00[UTC]');
        $dur = $zdt1->until($zdt2);

        self::assertSame(3, $dur->hours);
        self::assertSame(30, $dur->minutes);
    }

    public function testSinceIsReverseOfUntil(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = ZonedDateTime::from('2021-08-04T15:30:00+00:00[UTC]');
        $dur = $zdt2->since($zdt1);

        self::assertSame(3, $dur->hours);
        self::assertSame(30, $dur->minutes);
    }

    public function testUntilAcrossDays(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $zdt2 = ZonedDateTime::from('2021-08-06T12:00:00+00:00[UTC]');
        $dur = $zdt1->until($zdt2);

        // 2 days = 48 hours
        self::assertSame(48, $dur->hours);
    }

    public function testUntilNegative(): void
    {
        $zdt1 = ZonedDateTime::from('2021-08-04T15:30:00+00:00[UTC]');
        $zdt2 = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        $dur = $zdt1->until($zdt2);

        self::assertSame(-3, $dur->hours);
        self::assertSame(-30, $dur->minutes);
    }

    // -------------------------------------------------------------------------
    // compare() / equals()
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        // Same instant, different time zones → equal by instant
        self::assertSame(0, ZonedDateTime::compare($zdt1, $zdt2));
    }

    public function testCompareLess(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(1, 'UTC');
        self::assertSame(-1, ZonedDateTime::compare($zdt1, $zdt2));
    }

    public function testCompareGreater(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(1, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(1, ZonedDateTime::compare($zdt1, $zdt2));
    }

    public function testEqualsTrue(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertTrue($zdt1->equals($zdt2));
    }

    public function testEqualsFalseWhenDifferentTimezone(): void
    {
        // Same instant but different time zones → equals() returns false
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(0, '+05:30');
        self::assertFalse($zdt1->equals($zdt2));
    }

    public function testEqualsFalseWhenDifferentInstant(): void
    {
        $zdt1 = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = ZonedDateTime::fromEpochNanoseconds(1, 'UTC');
        self::assertFalse($zdt1->equals($zdt2));
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringUtc(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]');
        $str = (string) $zdt;
        self::assertSame('2021-08-04T12:30:00+00:00[UTC]', $str);
    }

    public function testToStringWithFixedOffset(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+05:30');
        $str = (string) $zdt;
        self::assertSame('2021-08-04T12:00:00+05:30[+05:30]', $str);
    }

    public function testToStringWithSubSeconds(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00.123456789+00:00[UTC]');
        $str = (string) $zdt;
        self::assertSame('2021-08-04T12:30:00.123456789+00:00[UTC]', $str);
    }

    public function testToStringWithNegativeOffset(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T08:30:00-04:00[America/New_York]');
        $str = (string) $zdt;
        self::assertSame('2021-08-04T08:30:00-04:00[America/New_York]', $str);
    }

    // -------------------------------------------------------------------------
    // Round-trip
    // -------------------------------------------------------------------------

    public function testRoundTripFromEpochNanoseconds(): void
    {
        $ns = 1_628_072_400_000_000_000; // 2021-08-04T11:00:00Z
        $zdt = ZonedDateTime::fromEpochNanoseconds($ns, 'UTC');
        self::assertSame($ns, $zdt->epochNanoseconds);
    }

    public function testRoundTripFromString(): void
    {
        $str = '2021-08-04T12:30:00+00:00[UTC]';
        $zdt = ZonedDateTime::from($str);
        self::assertSame($str, (string) $zdt);
    }

    public function testInstantAndTimeZoneRoundTrip(): void
    {
        $instant = Instant::from('2024-07-04T16:00:00Z');
        $tz = TimeZone::from('America/New_York');
        $zdt = ZonedDateTime::fromEpochNanoseconds($instant->epochNanoseconds, $tz);

        // Local time should be 12:00 EDT
        self::assertSame(12, $zdt->hour);
        self::assertSame(0, $zdt->minute);

        // Convert back to instant
        self::assertSame($instant->epochNanoseconds, $zdt->toInstant()->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // dayOfWeek / dayOfYear computed props
    // -------------------------------------------------------------------------

    public function testDayOfWeek(): void
    {
        // 2021-08-04 is a Wednesday (3)
        $zdt = ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]');
        self::assertSame(3, $zdt->dayOfWeek);
    }

    public function testDaysInMonth(): void
    {
        $zdt = ZonedDateTime::from('2021-02-15T00:00:00+00:00[UTC]');
        self::assertSame(28, $zdt->daysInMonth);
    }

    public function testInLeapYear(): void
    {
        $zdt = ZonedDateTime::from('2024-07-04T00:00:00+00:00[UTC]');
        self::assertTrue($zdt->inLeapYear);
    }

    public function testNotInLeapYear(): void
    {
        $zdt = ZonedDateTime::from('2021-07-04T00:00:00+00:00[UTC]');
        self::assertFalse($zdt->inLeapYear);
    }

    // -------------------------------------------------------------------------
    // round()
    // -------------------------------------------------------------------------

    public function testRoundToHourUp(): void
    {
        // 12:45 → rounds up to 13:00
        $zdt = ZonedDateTime::from('2021-08-04T12:45:00+00:00[UTC]');
        $result = $zdt->round('hour');
        self::assertSame(13, $result->hour);
        self::assertSame(0, $result->minute);
        self::assertSame(0, $result->second);
    }

    public function testRoundToHourDown(): void
    {
        // 12:14 → rounds down to 12:00
        $zdt = ZonedDateTime::from('2021-08-04T12:14:00+00:00[UTC]');
        $result = $zdt->round('hour');
        self::assertSame(12, $result->hour);
        self::assertSame(0, $result->minute);
    }

    public function testRoundToMinuteUp(): void
    {
        // 12:30:45 → rounds up to 12:31:00
        $zdt = ZonedDateTime::from('2021-08-04T12:30:45+00:00[UTC]');
        $result = $zdt->round('minute');
        self::assertSame(12, $result->hour);
        self::assertSame(31, $result->minute);
        self::assertSame(0, $result->second);
    }

    public function testRoundToMinuteDown(): void
    {
        // 12:30:29 → rounds down to 12:30:00
        $zdt = ZonedDateTime::from('2021-08-04T12:30:29+00:00[UTC]');
        $result = $zdt->round('minute');
        self::assertSame(30, $result->minute);
        self::assertSame(0, $result->second);
    }

    public function testRoundToSecondHalfExpand(): void
    {
        // 12:30:00.500 → rounds up to 12:30:01
        $ns = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]')->epochNanoseconds + 500_000_000;
        $zdt = ZonedDateTime::fromEpochNanoseconds($ns, 'UTC');
        $result = $zdt->round('second');
        self::assertSame(1, $result->second);
        self::assertSame(0, $result->millisecond);
    }

    public function testRoundToSecondTrunc(): void
    {
        // 12:30:00.999 with trunc → 12:30:00
        $ns = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]')->epochNanoseconds + 999_000_000;
        $zdt = ZonedDateTime::fromEpochNanoseconds($ns, 'UTC');
        $result = $zdt->round(['smallestUnit' => 'second', 'roundingMode' => 'trunc']);
        self::assertSame(0, $result->second);
        self::assertSame(0, $result->millisecond);
    }

    public function testRoundToMillisecond(): void
    {
        // 12:30:00.0005 → rounds up to 12:30:00.001
        $ns = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]')->epochNanoseconds + 500_000;
        $zdt = ZonedDateTime::fromEpochNanoseconds($ns, 'UTC');
        $result = $zdt->round('millisecond');
        self::assertSame(1, $result->millisecond);
        self::assertSame(0, $result->microsecond);
    }

    public function testRoundToMicrosecond(): void
    {
        // Sub-microsecond precision: 500 ns → rounds up to 1 µs
        $ns = ZonedDateTime::from('2021-08-04T12:30:00+00:00[UTC]')->epochNanoseconds + 500;
        $zdt = ZonedDateTime::fromEpochNanoseconds($ns, 'UTC');
        $result = $zdt->round('microsecond');
        self::assertSame(1, $result->microsecond);
        self::assertSame(0, $result->nanosecond);
    }

    public function testRoundToNanosecondIsNoop(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:30:00.123456789+00:00[UTC]');
        $result = $zdt->round('nanosecond');
        self::assertSame($zdt->epochNanoseconds, $result->epochNanoseconds);
    }

    public function testRoundToDayDown(): void
    {
        // 10:00 (< 12:00) → rounds down to midnight same day
        $zdt = ZonedDateTime::from('2021-08-04T10:00:00+00:00[UTC]');
        $result = $zdt->round('day');
        self::assertSame(2021, $result->year);
        self::assertSame(8, $result->month);
        self::assertSame(4, $result->day);
        self::assertSame(0, $result->hour);
        self::assertSame(0, $result->minute);
    }

    public function testRoundToDayUp(): void
    {
        // 14:00 (> 12:00) → rounds up to midnight next day
        $zdt = ZonedDateTime::from('2021-08-04T14:00:00+00:00[UTC]');
        $result = $zdt->round('day');
        self::assertSame(2021, $result->year);
        self::assertSame(8, $result->month);
        self::assertSame(5, $result->day);
        self::assertSame(0, $result->hour);
    }

    public function testRoundToDayFloor(): void
    {
        // floor always rounds down to start of current day
        $zdt = ZonedDateTime::from('2021-08-04T23:59:59+00:00[UTC]');
        $result = $zdt->round(['smallestUnit' => 'day', 'roundingMode' => 'floor']);
        self::assertSame(4, $result->day);
        self::assertSame(0, $result->hour);
    }

    public function testRoundToDayCeil(): void
    {
        // ceil always rounds up to next day (unless already at midnight)
        $zdt = ZonedDateTime::from('2021-08-04T00:00:01+00:00[UTC]');
        $result = $zdt->round(['smallestUnit' => 'day', 'roundingMode' => 'ceil']);
        self::assertSame(5, $result->day);
        self::assertSame(0, $result->hour);
    }

    public function testRoundToDayAtExactMidnightCeil(): void
    {
        // ceil of exact midnight should stay at midnight (not move to next day)
        $zdt = ZonedDateTime::from('2021-08-04T00:00:00+00:00[UTC]');
        $result = $zdt->round(['smallestUnit' => 'day', 'roundingMode' => 'ceil']);
        self::assertSame(4, $result->day);
        self::assertSame(0, $result->hour);
    }

    public function testRoundWithOptionsArray(): void
    {
        $zdt = ZonedDateTime::from('2021-08-04T12:45:00+00:00[UTC]');
        $result = $zdt->round(['smallestUnit' => 'hour', 'roundingMode' => 'floor']);
        self::assertSame(12, $result->hour);
        self::assertSame(0, $result->minute);
    }

    public function testRoundInvalidUnitThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]')->round('year');
    }

    public function testRoundMissingSmallestUnitThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ZonedDateTime::from('2021-08-04T12:00:00+00:00[UTC]')->round(['roundingMode' => 'floor']);
    }

    public function testRoundCrossesHourBoundary(): void
    {
        // 12:59:45 rounds up to 13:00 (not beyond)
        $zdt = ZonedDateTime::from('2021-08-04T12:59:45+00:00[UTC]');
        $result = $zdt->round('minute');
        self::assertSame(13, $result->hour);
        self::assertSame(0, $result->minute);
    }

    // -------------------------------------------------------------------------
    // DST transitions — America/New_York 2024
    //
    // Spring forward: 2024-03-10T07:00:00Z  (ts=1710054000) -05:00→-04:00
    //   At 2:00 AM EST, clocks jump forward to 3:00 AM EDT (1-hour gap).
    //
    // Fall back:      2024-11-03T06:00:00Z  (ts=1730613600) -04:00→-05:00
    //   At 2:00 AM EDT, clocks fall back to 1:00 AM EST (1-hour fold).
    // -------------------------------------------------------------------------

    public function testOffsetIsEstJustBeforeSpringForward(): void
    {
        // ts=1710053999 → 2024-03-10T01:59:59-05:00 (last second of EST)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1710053999 * 1_000_000_000, 'America/New_York');
        self::assertSame('-05:00', $zdt->offset);
        self::assertSame(1, $zdt->hour);
        self::assertSame(59, $zdt->minute);
        self::assertSame(59, $zdt->second);
    }

    public function testOffsetIsEdtAtSpringForward(): void
    {
        // ts=1710054000 → 2024-03-10T03:00:00-04:00 (first second of EDT)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1710054000 * 1_000_000_000, 'America/New_York');
        self::assertSame('-04:00', $zdt->offset);
        self::assertSame(3, $zdt->hour);
        self::assertSame(0, $zdt->minute);
        self::assertSame(0, $zdt->second);
    }

    public function testOffsetIsEdtJustBeforeFallBack(): void
    {
        // ts=1730613599 → 2024-11-03T01:59:59-04:00 (last second of EDT)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730613599 * 1_000_000_000, 'America/New_York');
        self::assertSame('-04:00', $zdt->offset);
        self::assertSame(1, $zdt->hour);
        self::assertSame(59, $zdt->minute);
        self::assertSame(59, $zdt->second);
    }

    public function testOffsetIsEstAtFallBack(): void
    {
        // ts=1730613600 → 2024-11-03T01:00:00-05:00 (first second of EST after fold)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730613600 * 1_000_000_000, 'America/New_York');
        self::assertSame('-05:00', $zdt->offset);
        self::assertSame(1, $zdt->hour);
        self::assertSame(0, $zdt->minute);
        self::assertSame(0, $zdt->second);
    }

    public function testAddHoursAcrossSpringForward(): void
    {
        // 2024-03-10T01:00:00 EST (ts=1710050400) + 2 hours = 2024-03-10T04:00:00 EDT
        // (absolute time skips over the 2am–3am gap; local time goes 01:00→04:00)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1710050400 * 1_000_000_000, 'America/New_York');
        $result = $zdt->add(['hours' => 2]);
        self::assertSame(4, $result->hour);
        self::assertSame(0, $result->minute);
        self::assertSame('-04:00', $result->offset);
    }

    public function testAddCalendarDayLandingInSpringForwardGap(): void
    {
        // 2024-03-09T02:30:00 EST + 1 day → 2024-03-10T02:30:00 (in the gap)
        // 'compatible' disambiguation pushes past the gap → 03:30 EDT (ts=1710055800)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1709969400 * 1_000_000_000, 'America/New_York');
        self::assertSame(2, $zdt->hour); // 02:30 EST
        $result = $zdt->add(['days' => 1]);
        self::assertSame(3, $result->hour);
        self::assertSame(30, $result->minute);
        self::assertSame('-04:00', $result->offset);
        self::assertSame(1710055800, $result->epochSeconds);
    }

    public function testAdd24HoursVs1DayAcrossSpringForward(): void
    {
        // Adding absolute hours vs. calendar days gives different results when
        // a DST transition falls in between.
        // Start: 2024-03-10T01:00:00 EST (ts=1710050400)
        $start = ZonedDateTime::fromEpochNanoseconds(1710050400 * 1_000_000_000, 'America/New_York');

        // +24 absolute hours → 2024-03-11T02:00:00 EDT (24 hours later in real time)
        $plus24h = $start->add(['hours' => 24]);
        self::assertSame(2, $plus24h->hour);
        self::assertSame('-04:00', $plus24h->offset);

        // +1 calendar day → 2024-03-11T01:00:00 EDT (same local time, 23 hours later)
        $plus1d = $start->add(['days' => 1]);
        self::assertSame(1, $plus1d->hour);
        self::assertSame('-04:00', $plus1d->offset);

        // The two results differ by exactly 1 hour
        $diff = $plus24h->epochNanoseconds - $plus1d->epochNanoseconds;
        self::assertSame(3_600_000_000_000, $diff);
    }

    public function testAddHoursAcrossFallBack(): void
    {
        // 2024-11-03T00:30:00 EDT (ts=1730608200) + 2 hours → 01:30:00 EST (after fold)
        // Absolute time arithmetic crosses the fold: local time goes 00:30→01:30 but
        // the offset changes from -04:00 to -05:00.
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730608200 * 1_000_000_000, 'America/New_York');
        self::assertSame('-04:00', $zdt->offset);
        $result = $zdt->add(['hours' => 2]);
        self::assertSame(1, $result->hour);
        self::assertSame(30, $result->minute);
        self::assertSame('-05:00', $result->offset);
        self::assertSame(1730615400, $result->epochSeconds);
    }

    public function testAddCalendarDayAcrossFallBack(): void
    {
        // 2024-11-02T12:00:00 EDT (ts=1730563200) + 1 day → 2024-11-03T12:00:00 EST
        // The result is 25 absolute hours later because of the fall-back.
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730563200 * 1_000_000_000, 'America/New_York');
        $result = $zdt->add(['days' => 1]);
        self::assertSame(2024, $result->year);
        self::assertSame(11, $result->month);
        self::assertSame(3, $result->day);
        self::assertSame(12, $result->hour);
        self::assertSame(0, $result->minute);
        self::assertSame('-05:00', $result->offset);
        // ts=1730653200 (25h after start, not 24h)
        self::assertSame(1730653200, $result->epochSeconds);
    }

    public function testUntilAcrossSpringForwardIs23Hours(): void
    {
        // 2024-03-10 is only 23 h long (spring forward).
        // From midnight EST (ts=1710046800) to midnight EDT the next day (ts=1710129600).
        $start = ZonedDateTime::fromEpochNanoseconds(1710046800 * 1_000_000_000, 'America/New_York');
        $end = ZonedDateTime::fromEpochNanoseconds(1710129600 * 1_000_000_000, 'America/New_York');
        self::assertSame(0, $start->hour);
        self::assertSame(0, $end->hour);
        $dur = $start->until($end);
        self::assertSame(23, $dur->hours);
        self::assertSame(0, $dur->minutes);
    }

    public function testUntilAcrossFallBackIs25Hours(): void
    {
        // 2024-11-03 is 25 h long (fall back).
        // From midnight EDT (ts=1730606400) to midnight EST the next day (ts=1730696400).
        $start = ZonedDateTime::fromEpochNanoseconds(1730606400 * 1_000_000_000, 'America/New_York');
        $end = ZonedDateTime::fromEpochNanoseconds(1730696400 * 1_000_000_000, 'America/New_York');
        self::assertSame(0, $start->hour);
        self::assertSame(0, $end->hour);
        $dur = $start->until($end);
        self::assertSame(25, $dur->hours);
        self::assertSame(0, $dur->minutes);
    }

    public function testSubtractHoursAcrossSpringForward(): void
    {
        // 2024-03-10T04:00:00 EDT (ts=1710057600) - 2 hours → 01:00:00 EST (before gap)
        $zdt = ZonedDateTime::fromEpochNanoseconds(1710057600 * 1_000_000_000, 'America/New_York');
        self::assertSame('-04:00', $zdt->offset);
        $result = $zdt->subtract(['hours' => 2]);
        self::assertSame(1, $result->hour);
        self::assertSame(0, $result->minute);
        self::assertSame('-05:00', $result->offset);
    }

    public function testSubtractCalendarDayAcrossFallBack(): void
    {
        // 2024-11-03T12:00:00 EST (ts=1730653200) - 1 day → 2024-11-02T12:00:00 EDT
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730653200 * 1_000_000_000, 'America/New_York');
        $result = $zdt->subtract(['days' => 1]);
        self::assertSame(2024, $result->year);
        self::assertSame(11, $result->month);
        self::assertSame(2, $result->day);
        self::assertSame(12, $result->hour);
        self::assertSame('-04:00', $result->offset);
    }

    // -------------------------------------------------------------------------
    // hoursInDay property
    // -------------------------------------------------------------------------

    public function testHoursInDayNormal(): void
    {
        // Regular summer day: 24 hours
        $zdt = ZonedDateTime::from('2024-07-04T12:00:00-04:00[America/New_York]');
        self::assertSame(24, $zdt->hoursInDay);
    }

    public function testHoursInDaySpringForward(): void
    {
        // 2024-03-10: spring-forward day — only 23 hours
        $zdt = ZonedDateTime::fromEpochNanoseconds(1710050400 * 1_000_000_000, 'America/New_York');
        self::assertSame(23, $zdt->hoursInDay);
    }

    public function testHoursInDayFallBack(): void
    {
        // 2024-11-03: fall-back day — 25 hours
        $zdt = ZonedDateTime::fromEpochNanoseconds(1730608200 * 1_000_000_000, 'America/New_York');
        self::assertSame(25, $zdt->hoursInDay);
    }

    public function testHoursInDayUtcIsAlways24(): void
    {
        $zdt = ZonedDateTime::from('2024-03-10T12:00:00+00:00[UTC]');
        self::assertSame(24, $zdt->hoursInDay);
    }

    // -------------------------------------------------------------------------
    // withPlainDate()
    // -------------------------------------------------------------------------

    public function testWithPlainDateReplacesDate(): void
    {
        $zdt = ZonedDateTime::from('2024-03-15T10:30:00+00:00[UTC]');
        $newDate = new \Temporal\PlainDate(2025, 6, 20);
        $result = $zdt->withPlainDate($newDate);

        self::assertSame(2025, $result->year);
        self::assertSame(6, $result->month);
        self::assertSame(20, $result->day);
        // Time preserved
        self::assertSame(10, $result->hour);
        self::assertSame(30, $result->minute);
        // Timezone preserved
        self::assertSame('UTC', $result->timeZone->id);
    }

    public function testWithPlainDatePreservesSubSeconds(): void
    {
        $zdt = ZonedDateTime::from('2024-03-15T10:30:00.123456789+00:00[UTC]');
        $newDate = new \Temporal\PlainDate(2025, 1, 1);
        $result = $zdt->withPlainDate($newDate);

        self::assertSame(123, $result->millisecond);
        self::assertSame(456, $result->microsecond);
        self::assertSame(789, $result->nanosecond);
    }

    // -------------------------------------------------------------------------
    // withPlainTime()
    // -------------------------------------------------------------------------

    public function testWithPlainTimeReplacesTime(): void
    {
        $zdt = ZonedDateTime::from('2024-03-15T10:30:00+00:00[UTC]');
        $newTime = new \Temporal\PlainTime(15, 45, 30);
        $result = $zdt->withPlainTime($newTime);

        // Date preserved
        self::assertSame(2024, $result->year);
        self::assertSame(3, $result->month);
        self::assertSame(15, $result->day);
        // Time replaced
        self::assertSame(15, $result->hour);
        self::assertSame(45, $result->minute);
        self::assertSame(30, $result->second);
    }

    public function testWithPlainTimeNullDefaultsMidnight(): void
    {
        $zdt = ZonedDateTime::from('2024-03-15T10:30:00+00:00[UTC]');
        $result = $zdt->withPlainTime(null);

        self::assertSame(0, $result->hour);
        self::assertSame(0, $result->minute);
        self::assertSame(0, $result->second);
        self::assertSame(0, $result->nanosecond);
    }

    public function testWithPlainTimeNoArgDefaultsMidnight(): void
    {
        $zdt = ZonedDateTime::from('2024-06-01T12:00:00+00:00[UTC]');
        $result = $zdt->withPlainTime();

        self::assertSame(2024, $result->year);
        self::assertSame(6, $result->month);
        self::assertSame(1, $result->day);
        self::assertSame(0, $result->hour);
    }

    // -------------------------------------------------------------------------
    // toPlainYearMonth()
    // -------------------------------------------------------------------------

    public function testToPlainYearMonth(): void
    {
        $zdt = ZonedDateTime::from('2024-07-04T15:00:00+00:00[UTC]');
        $pym = $zdt->toPlainYearMonth();

        self::assertSame(2024, $pym->year);
        self::assertSame(7, $pym->month);
    }

    // -------------------------------------------------------------------------
    // toPlainMonthDay()
    // -------------------------------------------------------------------------

    public function testToPlainMonthDay(): void
    {
        $zdt = ZonedDateTime::from('2024-07-04T15:00:00+00:00[UTC]');
        $pmd = $zdt->toPlainMonthDay();

        self::assertSame(7, $pmd->month);
        self::assertSame(4, $pmd->day);
    }

    // -------------------------------------------------------------------------
    // startOfDay()
    // -------------------------------------------------------------------------

    public function testStartOfDayUtc(): void
    {
        $zdt = ZonedDateTime::from('2024-03-15T10:30:45+00:00[UTC]');
        $start = $zdt->startOfDay();

        self::assertSame(2024, $start->year);
        self::assertSame(3, $start->month);
        self::assertSame(15, $start->day);
        self::assertSame(0, $start->hour);
        self::assertSame(0, $start->minute);
        self::assertSame(0, $start->second);
        self::assertSame('UTC', $start->timeZone->id);
    }

    public function testStartOfDayPreservesTimezone(): void
    {
        $zdt = ZonedDateTime::from('2024-07-04T15:30:00-04:00[America/New_York]');
        $start = $zdt->startOfDay();

        self::assertSame(2024, $start->year);
        self::assertSame(7, $start->month);
        self::assertSame(4, $start->day);
        self::assertSame(0, $start->hour);
        self::assertSame(0, $start->minute);
        self::assertSame('America/New_York', $start->timeZone->id);
    }

    public function testStartOfDayAlreadyAtMidnight(): void
    {
        // 2024-01-01T00:00:00Z is already midnight UTC; startOfDay() should stay at the same instant.
        $zdt = ZonedDateTime::from('2024-01-01T00:00:00+00:00[UTC]');
        $start = $zdt->startOfDay();

        self::assertSame($zdt->epochNanoseconds, $start->epochNanoseconds);
        self::assertSame(0, $start->hour);
        self::assertSame(0, $start->minute);
        self::assertSame(0, $start->second);
    }

    /**
     * On March 31, 1919 in America/Toronto, clocks jumped from 23:30 EST
     * directly to 00:30 EDT. Midnight (00:00) doesn't exist — it falls in
     * the gap. The start of day should be 00:30 EDT, not 01:00 EDT.
     *
     * @see https://github.com/tc39/proposal-temporal/issues/2910
     */
    public function testStartOfDayMidnightInGap(): void
    {
        // Toronto, March 31, 1919: gap from 23:30 (Mar 30) to 00:30 (Mar 31)
        // Transition at UTC 1919-03-31T04:30:00Z
        $zdt = ZonedDateTime::from('1919-03-31T12:00:00-04:00[America/Toronto]');
        $start = $zdt->startOfDay();

        self::assertSame(1919, $start->year);
        self::assertSame(3, $start->month);
        self::assertSame(31, $start->day);
        // Start of day is 00:30, not midnight (which doesn't exist) or 01:00
        self::assertSame(0, $start->hour);
        self::assertSame(30, $start->minute);
        self::assertSame(0, $start->second);
        self::assertSame('America/Toronto', $start->timeZone->id);
    }

    /**
     * hoursInDay should account for the gap correctly when midnight doesn't
     * exist (Toronto, March 31, 1919).
     */
    public function testHoursInDayMidnightInGap(): void
    {
        $zdt = ZonedDateTime::from('1919-03-31T12:00:00-04:00[America/Toronto]');
        // Day starts at 00:30 instead of 00:00, so the day is 30 minutes shorter
        // than a normal 24-hour day: 23.5 hours.
        self::assertSame(23.5, $zdt->hoursInDay);
    }
}
