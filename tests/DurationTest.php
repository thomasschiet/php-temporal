<?php

declare(strict_types = 1);

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

    // -------------------------------------------------------------------------
    // total() with relativeTo (test262: relativeto-calendar-units-depend-on-relative-date)
    // -------------------------------------------------------------------------

    public function testTotalMonthsWith40DaysFromFeb2020(): void
    {
        // 40 days from Feb 1, 2020 (leap year) = 1 + 11/31 months
        // Feb 2020 has 29 days, March has 31 days
        $d = new Duration(days: 40);
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-02-01']);
        $expected = 1.0 + ( 11.0 / 31.0 );
        $this->assertEqualsWithDelta($expected, $result, 1e-10);
    }

    public function testTotalMonthsWith40DaysFromJan2020(): void
    {
        // 40 days from Jan 1, 2020 (leap year) = 1 + 9/29 months
        // Jan has 31 days, Feb 2020 has 29 days
        $d = new Duration(days: 40);
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-01-01']);
        $expected = 1.0 + ( 9.0 / 29.0 );
        $this->assertEqualsWithDelta($expected, $result, 1e-10);
    }

    public function testTotalMonthsNegative40DaysFromMarch2020(): void
    {
        // -40 days from Mar 1, 2020 → Jan 21, 2020
        // Going back: Mar → Feb (29 days), remaining = 11 days out of Jan's 31
        // = -(1 + 11/31)
        $d = new Duration(days: -40);
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-03-01']);
        $expected = -( 1.0 + ( 11.0 / 31.0 ) );
        $this->assertEqualsWithDelta($expected, $result, 1e-10);
    }

    public function testTotalMonthsNegative40DaysFromApril2020(): void
    {
        // -40 days from Apr 1, 2020 → Feb 21, 2020
        // Going back: Apr → Mar (31 days), remaining = 9 days out of Feb's 29 (leap)
        // = -(1 + 9/29)
        $d = new Duration(days: -40);
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-04-01']);
        $expected = -( 1.0 + ( 9.0 / 29.0 ) );
        $this->assertEqualsWithDelta($expected, $result, 1e-10);
    }

    public function testTotalMonthsZero(): void
    {
        $d = new Duration();
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-01-01']);
        $this->assertSame(0.0, $result);
    }

    public function testTotalMonthsExactlyOneYear(): void
    {
        // 365 days from Jan 1, 2020 (leap year, 366 days) ≈ not exactly 12 months
        // 366 days from Jan 1, 2020 = exactly 12 months
        $d = new Duration(days: 366);
        $result = $d->total(['unit' => 'months', 'relativeTo' => '2020-01-01']);
        $this->assertEqualsWithDelta(12.0, $result, 1e-10);
    }

    public function testTotalYearsWith400DaysFromJan2020(): void
    {
        // 366 days from Jan 1, 2020 = exactly 1 year
        $d = new Duration(days: 366);
        $result = $d->total(['unit' => 'years', 'relativeTo' => '2020-01-01']);
        $this->assertEqualsWithDelta(1.0, $result, 1e-10);
    }

    public function testTotalDaysWithYearMonthDuration(): void
    {
        // P1Y2M = 12 months + 2 months from 2020-01-01
        // 2020-01-01 + 1 year = 2021-01-01 (366 days, leap year)
        // 2021-01-01 + 2 months = 2021-03-01 (59 more days)
        // Total = 366 + 59 = 425 days
        $d = new Duration(years: 1, months: 2);
        $result = $d->total(['unit' => 'days', 'relativeTo' => '2020-01-01']);
        $this->assertSame(425.0, $result);
    }

    public function testTotalCalendarUnitWithoutRelativeToThrows(): void
    {
        $d = new Duration(days: 40);
        $this->expectException(InvalidArgumentException::class);
        $d->total(['unit' => 'months']);
    }

    public function testTotalYearsWithoutRelativeToThrows(): void
    {
        $d = new Duration(days: 365);
        $this->expectException(InvalidArgumentException::class);
        $d->total(['unit' => 'years']);
    }

    public function testTotalStringUnitStillWorks(): void
    {
        // Original string-based API still works
        $d = new Duration(hours: 2, minutes: 30);
        $result = $d->total('hours');
        $this->assertEqualsWithDelta(2.5, $result, 1e-10);
    }

    public function testTotalOptionsArrayForTimeUnit(): void
    {
        // Options-array form also works for time units (no relativeTo needed)
        $d = new Duration(hours: 1, minutes: 30);
        $result = $d->total(['unit' => 'minutes']);
        $this->assertEqualsWithDelta(90.0, $result, 1e-10);
    }

    // -------------------------------------------------------------------------
    // round() with options object (test262: balances-up-to-weeks)
    // -------------------------------------------------------------------------

    public function testRoundOptionsSimpleHalfExpand(): void
    {
        // P1M1D with relativeTo=2024-01-01 → 32 days → ~4.57 weeks → rounds to 5
        $d = new Duration(months: 1, days: 1);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks'
        ]);
        $this->assertSame(0, $result->years);
        $this->assertSame(0, $result->months);
        $this->assertSame(5, $result->weeks);
        $this->assertSame(0, $result->days);
    }

    public function testRoundOptionsWithCeilIncrement6(): void
    {
        // P1M1D → 32 days → 4.57 weeks → ceil(4.57/6)*6 = 6 weeks
        $d = new Duration(months: 1, days: 1);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 6
        ]);
        $this->assertSame(6, $result->weeks);
    }

    public function testRoundOptionsWithCeilIncrement99(): void
    {
        // P1M1D → 32 days → 4.57 weeks → ceil(4.57/99)*99 = 99 weeks
        $d = new Duration(months: 1, days: 1);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 99
        ]);
        $this->assertSame(99, $result->weeks);
    }

    public function testRoundOptionsOneYearOneMonthOneDay(): void
    {
        // P1Y1M1D with relativeTo=2024-01-01 → 2025-02-02 → 398 days
        // 398 / 7 = 56.857 weeks
        // ceil(56.857/57)*57 = 57 weeks
        $d = new Duration(years: 1, months: 1, days: 1);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 57
        ]);
        $this->assertSame(57, $result->weeks);
    }

    public function testRoundOptionsOneYearOneMonthOneDayIncrement99(): void
    {
        // P1Y1M1D → 398 days → 56.857 weeks → ceil(56.857/99)*99 = 99 weeks
        $d = new Duration(years: 1, months: 1, days: 1);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 99
        ]);
        $this->assertSame(99, $result->weeks);
    }

    public function testRoundOptionsPureDays29ToWeeks(): void
    {
        // P29D with relativeTo=2024-01-01 → 4.14 weeks
        // ceil(4.14/5)*5 = 5 weeks
        $d = new Duration(days: 29);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 5
        ]);
        $this->assertSame(5, $result->weeks);
    }

    public function testRoundOptionsPureDays29ToWeeksIncrement8(): void
    {
        // P29D → 4.14 weeks → ceil(4.14/8)*8 = 8 weeks
        $d = new Duration(days: 29);
        $result = $d->round([
            'relativeTo' => '2024-01-01',
            'largestUnit' => 'weeks',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 8
        ]);
        $this->assertSame(8, $result->weeks);
    }

    public function testRoundOptionsRequiresLargestUnitForCalendarWithSubMonthSmallest(): void
    {
        // Duration with months, smallestUnit='weeks', no largestUnit → RangeException
        $d = new Duration(months: 1, days: 1);
        $this->expectException(\RangeException::class);
        $d->round([
            'relativeTo' => '2024-01-01',
            'smallestUnit' => 'weeks',
            'roundingMode' => 'ceil',
            'roundingIncrement' => 99
        ]);
    }

    public function testRoundStringApiStillWorks(): void
    {
        // Original string-based round() API still works
        $d = new Duration(hours: 1, minutes: 45);
        $result = $d->round('hours');
        $this->assertSame(2, $result->hours);
        $this->assertSame(0, $result->minutes);
    }

    // -------------------------------------------------------------------------
    // round() with largestUnit but no relativeTo (balancing time fields)
    // -------------------------------------------------------------------------

    public function testRoundLargestUnitBalances90SecondsToMinutes(): void
    {
        $d = Duration::from('PT90S');
        $result = $d->round(['smallestUnit' => 'second', 'largestUnit' => 'minute']);
        $this->assertSame(1, $result->minutes);
        $this->assertSame(30, $result->seconds);
        $this->assertSame(0, $result->hours);
    }

    public function testRoundLargestUnitBalances3700SecondsToHours(): void
    {
        $d = Duration::from('PT3700S');
        $result = $d->round(['smallestUnit' => 'second', 'largestUnit' => 'hour']);
        $this->assertSame(1, $result->hours);
        $this->assertSame(1, $result->minutes);
        $this->assertSame(40, $result->seconds);
    }

    public function testRoundLargestUnitBalancesWithRounding(): void
    {
        // PT90.6S rounded to nearest second then balanced to minutes
        $d = new Duration(seconds: 90, milliseconds: 600);
        $result = $d->round(['smallestUnit' => 'second', 'largestUnit' => 'minute']);
        $this->assertSame(1, $result->minutes);
        $this->assertSame(31, $result->seconds);
        $this->assertSame(0, $result->milliseconds);
    }

    public function testRoundLargestUnitPreservesDateFields(): void
    {
        // Duration with date fields: only time portion is balanced
        $d = new Duration(days: 1, hours: 90);
        $result = $d->round(['smallestUnit' => 'hour', 'largestUnit' => 'day']);
        // 1 day + 90 hours = 1 day + 3 days + 18 hours = 4 days 18 hours
        $this->assertSame(4, $result->days);
        $this->assertSame(18, $result->hours);
    }

    public function testRoundLargestUnitWeeks(): void
    {
        // 14 days should balance to 2 weeks
        $d = new Duration(days: 14);
        $result = $d->round(['smallestUnit' => 'day', 'largestUnit' => 'week']);
        $this->assertSame(2, $result->weeks);
        $this->assertSame(0, $result->days);
    }

    // -------------------------------------------------------------------------
    // Duration::balance()
    // -------------------------------------------------------------------------

    public function testBalance90SecondsToMinutes(): void
    {
        $d = Duration::from('PT90S');
        $result = $d->balance('minute');
        $this->assertSame(1, $result->minutes);
        $this->assertSame(30, $result->seconds);
        $this->assertSame(0, $result->hours);
        $this->assertSame(0, $result->milliseconds);
    }

    public function testBalance3700SecondsToHours(): void
    {
        $d = Duration::from('PT3700S');
        $result = $d->balance('hour');
        $this->assertSame(1, $result->hours);
        $this->assertSame(1, $result->minutes);
        $this->assertSame(40, $result->seconds);
    }

    public function testBalanceDayToHours(): void
    {
        // 1 day + 2 hours balanced to 'hour' → 26 hours
        $d = new Duration(days: 1, hours: 2);
        $result = $d->balance('hour');
        $this->assertSame(26, $result->hours);
        $this->assertSame(0, $result->days);
    }

    public function testBalanceWeeks(): void
    {
        // 168 hours = 1 week
        $d = new Duration(hours: 168);
        $result = $d->balance('week');
        $this->assertSame(1, $result->weeks);
        $this->assertSame(0, $result->days);
        $this->assertSame(0, $result->hours);
    }

    public function testBalanceWeeksWithRemainder(): void
    {
        // 170 hours = 1 week + 2 hours
        $d = new Duration(hours: 170);
        $result = $d->balance('week');
        $this->assertSame(1, $result->weeks);
        $this->assertSame(0, $result->days);
        $this->assertSame(2, $result->hours);
    }

    public function testBalanceAlreadyBalanced(): void
    {
        $d = new Duration(minutes: 1, seconds: 30);
        $result = $d->balance('minute');
        $this->assertSame(1, $result->minutes);
        $this->assertSame(30, $result->seconds);
    }

    public function testBalanceNegativeDuration(): void
    {
        $d = Duration::from('-PT90S');
        $result = $d->balance('minute');
        $this->assertSame(-1, $result->minutes);
        $this->assertSame(-30, $result->seconds);
    }

    public function testBalanceZeroDuration(): void
    {
        $d = new Duration();
        $result = $d->balance('hour');
        $this->assertSame(0, $result->hours);
        $this->assertSame(0, $result->minutes);
        $this->assertSame(0, $result->seconds);
    }

    public function testBalancePreservesCalendarFields(): void
    {
        // Years and months are preserved; only sub-month fields are balanced
        $d = new Duration(years: 1, months: 2, days: 1, hours: 26);
        $result = $d->balance('day');
        $this->assertSame(1, $result->years);
        $this->assertSame(2, $result->months);
        $this->assertSame(2, $result->days);
        $this->assertSame(2, $result->hours);
    }

    public function testBalanceMissingLargestUnitThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $d = new Duration(seconds: 90);
        $d->balance([]);
    }

    public function testBalanceStringPluralForms(): void
    {
        // Accept 'minutes' (plural) as well as 'minute'
        $d = Duration::from('PT90S');
        $r1 = $d->balance('minute');
        $r2 = $d->balance('minutes');
        $this->assertSame($r1->minutes, $r2->minutes);
        $this->assertSame($r1->seconds, $r2->seconds);
    }
}
