<?php

declare(strict_types=1);

namespace Temporal\Tests;

use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use InvalidArgumentException;

class DurationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorDefaults(): void
    {
        $d = new Duration();
        $this->assertSame(0, $d->years);
        $this->assertSame(0, $d->months);
        $this->assertSame(0, $d->weeks);
        $this->assertSame(0, $d->days);
        $this->assertSame(0, $d->hours);
        $this->assertSame(0, $d->minutes);
        $this->assertSame(0, $d->seconds);
        $this->assertSame(0, $d->milliseconds);
        $this->assertSame(0, $d->microseconds);
        $this->assertSame(0, $d->nanoseconds);
    }

    public function testConstructorAllFields(): void
    {
        $d = new Duration(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $this->assertSame(1, $d->years);
        $this->assertSame(2, $d->months);
        $this->assertSame(3, $d->weeks);
        $this->assertSame(4, $d->days);
        $this->assertSame(5, $d->hours);
        $this->assertSame(6, $d->minutes);
        $this->assertSame(7, $d->seconds);
        $this->assertSame(8, $d->milliseconds);
        $this->assertSame(9, $d->microseconds);
        $this->assertSame(10, $d->nanoseconds);
    }

    public function testConstructorNegativeFields(): void
    {
        $d = new Duration(-1, -2, 0, -4);
        $this->assertSame(-1, $d->years);
        $this->assertSame(-2, $d->months);
        $this->assertSame(0, $d->weeks);
        $this->assertSame(-4, $d->days);
    }

    public function testConstructorMixedSignsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Duration(1, 0, 0, -1);
    }

    public function testConstructorMixedSignsPositiveNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Duration(-1, 0, 0, 0, 1);
    }

    // -------------------------------------------------------------------------
    // from() static constructor
    // -------------------------------------------------------------------------

    public function testFromDuration(): void
    {
        $d1 = new Duration(1, 2, 3);
        $d2 = Duration::from($d1);
        $this->assertSame(1, $d2->years);
        $this->assertSame(2, $d2->months);
        $this->assertSame(3, $d2->weeks);
    }

    public function testFromArray(): void
    {
        $d = Duration::from(['years' => 2, 'months' => 6, 'days' => 15]);
        $this->assertSame(2, $d->years);
        $this->assertSame(6, $d->months);
        $this->assertSame(15, $d->days);
        $this->assertSame(0, $d->hours);
    }

    public function testFromStringYearsOnly(): void
    {
        $d = Duration::from('P2Y');
        $this->assertSame(2, $d->years);
        $this->assertSame(0, $d->months);
    }

    public function testFromStringMonthsOnly(): void
    {
        $d = Duration::from('P6M');
        $this->assertSame(6, $d->months);
    }

    public function testFromStringWeeksOnly(): void
    {
        $d = Duration::from('P3W');
        $this->assertSame(3, $d->weeks);
    }

    public function testFromStringDaysOnly(): void
    {
        $d = Duration::from('P10D');
        $this->assertSame(10, $d->days);
    }

    public function testFromStringHoursOnly(): void
    {
        $d = Duration::from('PT5H');
        $this->assertSame(5, $d->hours);
    }

    public function testFromStringMinutesOnly(): void
    {
        $d = Duration::from('PT30M');
        $this->assertSame(30, $d->minutes);
    }

    public function testFromStringSecondsOnly(): void
    {
        $d = Duration::from('PT45S');
        $this->assertSame(45, $d->seconds);
    }

    public function testFromStringFull(): void
    {
        $d = Duration::from('P1Y2M3W4DT5H6M7S');
        $this->assertSame(1, $d->years);
        $this->assertSame(2, $d->months);
        $this->assertSame(3, $d->weeks);
        $this->assertSame(4, $d->days);
        $this->assertSame(5, $d->hours);
        $this->assertSame(6, $d->minutes);
        $this->assertSame(7, $d->seconds);
    }

    public function testFromStringNegative(): void
    {
        $d = Duration::from('-P1Y2M');
        $this->assertSame(-1, $d->years);
        $this->assertSame(-2, $d->months);
    }

    public function testFromStringFractionalSeconds(): void
    {
        // PT1.5S means 1 second 500 milliseconds
        $d = Duration::from('PT1.5S');
        $this->assertSame(1, $d->seconds);
        $this->assertSame(500, $d->milliseconds);
    }

    public function testFromStringFractionalSecondsNanoseconds(): void
    {
        // PT0.000000001S means 1 nanosecond
        $d = Duration::from('PT0.000000001S');
        $this->assertSame(0, $d->seconds);
        $this->assertSame(0, $d->milliseconds);
        $this->assertSame(0, $d->microseconds);
        $this->assertSame(1, $d->nanoseconds);
    }

    public function testFromStringZero(): void
    {
        $d = Duration::from('PT0S');
        $this->assertTrue($d->blank);
    }

    public function testFromStringInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::from('not-a-duration');
    }

    public function testFromStringMissingPThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::from('1Y2M');
    }

    // -------------------------------------------------------------------------
    // sign and blank properties
    // -------------------------------------------------------------------------

    public function testSignPositive(): void
    {
        $d = new Duration(1, 2, 3);
        $this->assertSame(1, $d->sign);
    }

    public function testSignNegative(): void
    {
        $d = new Duration(-1, -2);
        $this->assertSame(-1, $d->sign);
    }

    public function testSignZero(): void
    {
        $d = new Duration();
        $this->assertSame(0, $d->sign);
    }

    public function testBlankTrue(): void
    {
        $d = new Duration();
        $this->assertTrue($d->blank);
    }

    public function testBlankFalse(): void
    {
        $d = new Duration(0, 0, 0, 0, 0, 0, 0, 0, 0, 1);
        $this->assertFalse($d->blank);
    }

    // -------------------------------------------------------------------------
    // negated()
    // -------------------------------------------------------------------------

    public function testNegated(): void
    {
        $d = new Duration(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $n = $d->negated();
        $this->assertSame(-1, $n->years);
        $this->assertSame(-2, $n->months);
        $this->assertSame(-3, $n->weeks);
        $this->assertSame(-4, $n->days);
        $this->assertSame(-5, $n->hours);
        $this->assertSame(-6, $n->minutes);
        $this->assertSame(-7, $n->seconds);
        $this->assertSame(-8, $n->milliseconds);
        $this->assertSame(-9, $n->microseconds);
        $this->assertSame(-10, $n->nanoseconds);
    }

    public function testNegatedOfNegative(): void
    {
        $d = new Duration(-3, -6);
        $n = $d->negated();
        $this->assertSame(3, $n->years);
        $this->assertSame(6, $n->months);
    }

    public function testNegatedOfZeroIsZero(): void
    {
        $d = new Duration();
        $n = $d->negated();
        $this->assertTrue($n->blank);
    }

    // -------------------------------------------------------------------------
    // abs()
    // -------------------------------------------------------------------------

    public function testAbsPositive(): void
    {
        $d = new Duration(1, 2, 3);
        $a = $d->abs();
        $this->assertSame(1, $a->years);
        $this->assertSame(2, $a->months);
    }

    public function testAbsNegative(): void
    {
        $d = new Duration(-1, -2, -3);
        $a = $d->abs();
        $this->assertSame(1, $a->years);
        $this->assertSame(2, $a->months);
        $this->assertSame(3, $a->weeks);
    }

    public function testAbsZero(): void
    {
        $d = new Duration();
        $a = $d->abs();
        $this->assertTrue($a->blank);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWith(): void
    {
        $d = new Duration(1, 2, 3, 4);
        $d2 = $d->with(['years' => 10, 'days' => 20]);
        $this->assertSame(10, $d2->years);
        $this->assertSame(2, $d2->months);
        $this->assertSame(3, $d2->weeks);
        $this->assertSame(20, $d2->days);
    }

    public function testWithImmutable(): void
    {
        $d = new Duration(1, 2, 3);
        $d2 = $d->with(['years' => 5]);
        $this->assertSame(1, $d->years); // original unchanged
        $this->assertSame(5, $d2->years);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    public function testAddDurations(): void
    {
        $d1 = new Duration(1, 2, 0, 3);
        $d2 = new Duration(0, 1, 0, 2);
        $result = $d1->add($d2);
        $this->assertSame(1, $result->years);
        $this->assertSame(3, $result->months);
        $this->assertSame(5, $result->days);
    }

    public function testAddDurationFromArray(): void
    {
        $d = new Duration(0, 0, 0, 5);
        $result = $d->add(['days' => 3, 'hours' => 2]);
        $this->assertSame(8, $result->days);
        $this->assertSame(2, $result->hours);
    }

    public function testAddDurationFromString(): void
    {
        $d = new Duration(1);
        $result = $d->add('P1Y');
        $this->assertSame(2, $result->years);
    }

    // -------------------------------------------------------------------------
    // subtract()
    // -------------------------------------------------------------------------

    public function testSubtractDurations(): void
    {
        $d1 = new Duration(3, 6);
        $d2 = new Duration(1, 2);
        $result = $d1->subtract($d2);
        $this->assertSame(2, $result->years);
        $this->assertSame(4, $result->months);
    }

    public function testSubtractDurationFromArray(): void
    {
        $d = new Duration(0, 0, 0, 10);
        $result = $d->subtract(['days' => 3]);
        $this->assertSame(7, $result->days);
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringZero(): void
    {
        $d = new Duration();
        $this->assertSame('PT0S', (string) $d);
    }

    public function testToStringYearsOnly(): void
    {
        $d = new Duration(years: 2);
        $this->assertSame('P2Y', (string) $d);
    }

    public function testToStringMonthsOnly(): void
    {
        $d = new Duration(months: 6);
        $this->assertSame('P6M', (string) $d);
    }

    public function testToStringWeeksOnly(): void
    {
        $d = new Duration(weeks: 3);
        $this->assertSame('P3W', (string) $d);
    }

    public function testToStringDaysOnly(): void
    {
        $d = new Duration(days: 10);
        $this->assertSame('P10D', (string) $d);
    }

    public function testToStringHoursOnly(): void
    {
        $d = new Duration(hours: 5);
        $this->assertSame('PT5H', (string) $d);
    }

    public function testToStringMinutesOnly(): void
    {
        $d = new Duration(minutes: 30);
        $this->assertSame('PT30M', (string) $d);
    }

    public function testToStringSecondsOnly(): void
    {
        $d = new Duration(seconds: 45);
        $this->assertSame('PT45S', (string) $d);
    }

    public function testToStringFull(): void
    {
        $d = new Duration(1, 2, 3, 4, 5, 6, 7);
        $this->assertSame('P1Y2M3W4DT5H6M7S', (string) $d);
    }

    public function testToStringNegative(): void
    {
        $d = new Duration(-1, -2);
        $this->assertSame('-P1Y2M', (string) $d);
    }

    public function testToStringWithMilliseconds(): void
    {
        $d = new Duration(seconds: 1, milliseconds: 500);
        $this->assertSame('PT1.5S', (string) $d);
    }

    public function testToStringWithMicroseconds(): void
    {
        $d = new Duration(seconds: 1, microseconds: 500);
        $this->assertSame('PT1.0005S', (string) $d);
    }

    public function testToStringWithNanoseconds(): void
    {
        $d = new Duration(seconds: 1, nanoseconds: 500);
        $this->assertSame('PT1.0000005S', (string) $d);
    }

    public function testToStringMillisecondsOnly(): void
    {
        $d = new Duration(milliseconds: 100);
        $this->assertSame('PT0.1S', (string) $d);
    }

    public function testToStringSubSecondAllComponents(): void
    {
        // 1ms + 1us + 1ns = 001_001_001 nanoseconds
        $d = new Duration(seconds: 2, milliseconds: 1, microseconds: 1, nanoseconds: 1);
        $this->assertSame('PT2.001001001S', (string) $d);
    }

    public function testToStringNegativeWithTime(): void
    {
        $d = new Duration(hours: -1, minutes: -30);
        $this->assertSame('-PT1H30M', (string) $d);
    }

    // -------------------------------------------------------------------------
    // compare() static method
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        // Simple time-only comparison (no calendar ambiguity)
        $d1 = new Duration(hours: 2);
        $d2 = new Duration(minutes: 120);
        // Both represent 2 hours = 7200 seconds
        $this->assertSame(0, Duration::compare($d1, $d2));
    }

    public function testCompareLessThan(): void
    {
        $d1 = new Duration(hours: 1);
        $d2 = new Duration(hours: 2);
        $this->assertSame(-1, Duration::compare($d1, $d2));
    }

    public function testCompareGreaterThan(): void
    {
        $d1 = new Duration(hours: 3);
        $d2 = new Duration(hours: 2);
        $this->assertSame(1, Duration::compare($d1, $d2));
    }

    // -------------------------------------------------------------------------
    // total()
    // -------------------------------------------------------------------------

    public function testTotalSeconds(): void
    {
        $d = new Duration(hours: 1, minutes: 30, seconds: 15);
        // 3600 + 1800 + 15 = 5415
        $this->assertSame(5415.0, $d->total('seconds'));
    }

    public function testTotalMinutes(): void
    {
        $d = new Duration(hours: 2, minutes: 30);
        // 150 minutes
        $this->assertSame(150.0, $d->total('minutes'));
    }

    public function testTotalHours(): void
    {
        $d = new Duration(hours: 2, minutes: 30);
        // 2.5 hours
        $this->assertSame(2.5, $d->total('hours'));
    }

    public function testTotalMilliseconds(): void
    {
        $d = new Duration(seconds: 1, milliseconds: 500);
        $this->assertSame(1500.0, $d->total('milliseconds'));
    }

    public function testTotalMicroseconds(): void
    {
        $d = new Duration(milliseconds: 1, microseconds: 500);
        $this->assertSame(1500.0, $d->total('microseconds'));
    }

    public function testTotalNanoseconds(): void
    {
        $d = new Duration(microseconds: 1, nanoseconds: 500);
        $this->assertSame(1500.0, $d->total('nanoseconds'));
    }

    public function testTotalDays(): void
    {
        $d = new Duration(days: 2, hours: 12);
        // 2.5 days
        $this->assertSame(2.5, $d->total('days'));
    }

    public function testTotalWeeks(): void
    {
        $d = new Duration(weeks: 1, days: 7);
        // 2 weeks
        $this->assertSame(2.0, $d->total('weeks'));
    }

    public function testTotalInvalidUnitThrows(): void
    {
        $d = new Duration(hours: 1);
        $this->expectException(InvalidArgumentException::class);
        $d->total('centuries');
    }

    // -------------------------------------------------------------------------
    // round()
    // -------------------------------------------------------------------------

    public function testRoundToSeconds(): void
    {
        $d = new Duration(seconds: 1, milliseconds: 600);
        $rounded = $d->round('seconds');
        $this->assertSame(2, $rounded->seconds);
        $this->assertSame(0, $rounded->milliseconds);
    }

    public function testRoundToSecondsDown(): void
    {
        $d = new Duration(seconds: 1, milliseconds: 400);
        $rounded = $d->round('seconds');
        $this->assertSame(1, $rounded->seconds);
        $this->assertSame(0, $rounded->milliseconds);
    }

    public function testRoundToMinutes(): void
    {
        $d = new Duration(minutes: 1, seconds: 45);
        $rounded = $d->round('minutes');
        $this->assertSame(2, $rounded->minutes);
        $this->assertSame(0, $rounded->seconds);
    }

    public function testRoundToMinutesDown(): void
    {
        $d = new Duration(minutes: 1, seconds: 20);
        $rounded = $d->round('minutes');
        $this->assertSame(1, $rounded->minutes);
        $this->assertSame(0, $rounded->seconds);
    }

    public function testRoundToHours(): void
    {
        $d = new Duration(hours: 1, minutes: 40);
        $rounded = $d->round('hours');
        $this->assertSame(2, $rounded->hours);
        $this->assertSame(0, $rounded->minutes);
    }

    public function testRoundToMilliseconds(): void
    {
        $d = new Duration(milliseconds: 1, microseconds: 600);
        $rounded = $d->round('milliseconds');
        $this->assertSame(2, $rounded->milliseconds);
        $this->assertSame(0, $rounded->microseconds);
    }

    public function testRoundNegative(): void
    {
        $d = new Duration(seconds: -1, milliseconds: -600);
        $rounded = $d->round('seconds');
        $this->assertSame(-2, $rounded->seconds);
        $this->assertSame(0, $rounded->milliseconds);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function testNegatedReturnsNewInstance(): void
    {
        $d = new Duration(1, 2);
        $n = $d->negated();
        $this->assertNotSame($d, $n);
    }

    public function testAbsReturnsNewInstance(): void
    {
        $d = new Duration(-1, -2);
        $a = $d->abs();
        $this->assertNotSame($d, $a);
    }

    public function testAddReturnsNewInstance(): void
    {
        $d1 = new Duration(1);
        $d2 = new Duration(2);
        $result = $d1->add($d2);
        $this->assertNotSame($d1, $result);
        $this->assertNotSame($d2, $result);
    }
}
