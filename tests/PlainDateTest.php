<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Exception\DateRangeException;
use Temporal\Exception\MissingFieldException;
use Temporal\PlainDate;

class PlainDateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testConstructorBasic(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testConstructorEpoch(): void
    {
        $date = new PlainDate(1970, 1, 1);
        $this->assertSame(1970, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function testConstructorNegativeYear(): void
    {
        $date = new PlainDate(-1, 12, 31);
        $this->assertSame(-1, $date->year);
        $this->assertSame(12, $date->month);
        $this->assertSame(31, $date->day);
    }

    public function testConstructorInvalidMonthZero(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2024, 0, 1);
    }

    public function testConstructorInvalidMonthThirteen(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2024, 13, 1);
    }

    public function testConstructorInvalidDayZero(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2024, 1, 0);
    }

    public function testConstructorInvalidDayTooLarge(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2024, 1, 32);
    }

    public function testConstructorInvalidDayForMonth(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2024, 4, 31); // April has 30 days
    }

    public function testConstructorLeapDay(): void
    {
        $date = new PlainDate(2024, 2, 29);
        $this->assertSame(29, $date->day);
    }

    public function testConstructorInvalidLeapDay(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDate(2023, 2, 29); // 2023 is not a leap year
    }

    // -------------------------------------------------------------------------
    // from() static constructor
    // -------------------------------------------------------------------------

    public function testFromArray(): void
    {
        $date = PlainDate::from(['year' => 2024, 'month' => 6, 'day' => 15]);
        $this->assertSame(2024, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testFromString(): void
    {
        $date = PlainDate::from('2024-03-15');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testFromPlainDate(): void
    {
        $original = new PlainDate(2024, 3, 15);
        $copy = PlainDate::from($original);
        $this->assertSame(2024, $copy->year);
        $this->assertSame(3, $copy->month);
        $this->assertSame(15, $copy->day);
    }

    public function testFromStringNegativeYear(): void
    {
        $date = PlainDate::from('-002023-01-15');
        $this->assertSame(-2023, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(15, $date->day);
    }

    // -------------------------------------------------------------------------
    // toString / toISOString
    // -------------------------------------------------------------------------

    public function testToString(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame('2024-03-15', (string) $date);
    }

    public function testToStringPadsMonthAndDay(): void
    {
        $date = new PlainDate(2024, 1, 5);
        $this->assertSame('2024-01-05', (string) $date);
    }

    public function testToStringNegativeYear(): void
    {
        $date = new PlainDate(-1, 3, 15);
        $this->assertSame('-000001-03-15', (string) $date);
    }

    public function testToStringLargeYear(): void
    {
        $date = new PlainDate(10000, 1, 1);
        $this->assertSame('+010000-01-01', (string) $date);
    }

    /** Year 0 is a valid ISO year and must format as the 4-digit string '0000'. */
    public function testToStringYearZero(): void
    {
        $date = new PlainDate(0, 3, 1);
        $this->assertSame('0000-03-01', (string) $date);
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    public function testDayOfWeekMonday(): void
    {
        $date = new PlainDate(2024, 1, 1); // Monday
        $this->assertSame(1, $date->dayOfWeek);
    }

    public function testDayOfWeekSunday(): void
    {
        $date = new PlainDate(2024, 1, 7); // Sunday
        $this->assertSame(7, $date->dayOfWeek);
    }

    public function testDayOfYearJan1(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $this->assertSame(1, $date->dayOfYear);
    }

    public function testDayOfYearDec31LeapYear(): void
    {
        $date = new PlainDate(2024, 12, 31);
        $this->assertSame(366, $date->dayOfYear);
    }

    public function testDayOfYearDec31NonLeapYear(): void
    {
        $date = new PlainDate(2023, 12, 31);
        $this->assertSame(365, $date->dayOfYear);
    }

    /**
     * February in a leap year must NOT get the leap-day +1 applied —
     * only dates from March 1 onward receive the extra day.
     */
    public function testDayOfYearFebInLeapYear(): void
    {
        // 2024 is a leap year. Feb 15 = 31 (Jan) + 15 = day 46 — NOT 47.
        $date = new PlainDate(2024, 2, 15);
        $this->assertSame(46, $date->dayOfYear);
    }

    public function testDaysInMonthJanuary(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $this->assertSame(31, $date->daysInMonth);
    }

    public function testDaysInMonthFebruaryLeapYear(): void
    {
        $date = new PlainDate(2024, 2, 1);
        $this->assertSame(29, $date->daysInMonth);
    }

    public function testDaysInMonthFebruaryNonLeapYear(): void
    {
        $date = new PlainDate(2023, 2, 1);
        $this->assertSame(28, $date->daysInMonth);
    }

    public function testDaysInYear(): void
    {
        $this->assertSame(366, ( new PlainDate(2024, 1, 1) )->daysInYear);
        $this->assertSame(365, ( new PlainDate(2023, 1, 1) )->daysInYear);
    }

    public function testInLeapYear(): void
    {
        $this->assertTrue(( new PlainDate(2024, 1, 1) )->inLeapYear);
        $this->assertFalse(( new PlainDate(2023, 1, 1) )->inLeapYear);
        $this->assertFalse(( new PlainDate(1900, 1, 1) )->inLeapYear);
        $this->assertTrue(( new PlainDate(2000, 1, 1) )->inLeapYear);
    }

    public function testWeekOfYear(): void
    {
        $date = new PlainDate(2024, 1, 1); // Week 1
        $this->assertSame(1, $date->weekOfYear);
    }

    public function testWeekOfYearLastWeek(): void
    {
        $date = new PlainDate(2024, 12, 28); // Should be week 52
        $this->assertGreaterThanOrEqual(52, $date->weekOfYear);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    public function testAddDays(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $result = $date->add(['days' => 10]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(11, $result->day);
    }

    public function testAddDaysRollsOverMonth(): void
    {
        $date = new PlainDate(2024, 1, 28);
        $result = $date->add(['days' => 5]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(2, $result->month);
        $this->assertSame(2, $result->day);
    }

    public function testAddMonths(): void
    {
        $date = new PlainDate(2024, 1, 15);
        $result = $date->add(['months' => 3]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(4, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testAddMonthsRollsOverYear(): void
    {
        $date = new PlainDate(2024, 11, 15);
        $result = $date->add(['months' => 3]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(2, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testAddYears(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->add(['years' => 2]);
        $this->assertSame(2026, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testAddWeeks(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $result = $date->add(['weeks' => 2]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testAddConstrainsEndOfMonth(): void
    {
        // Jan 31 + 1 month = Feb 29 (2024 is leap year), constrained to last day
        $date = new PlainDate(2024, 1, 31);
        $result = $date->add(['months' => 1]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(2, $result->month);
        $this->assertSame(29, $result->day);
    }

    public function testAddIsImmutable(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $result = $date->add(['days' => 5]);
        $this->assertSame(1, $date->day); // original unchanged
        $this->assertSame(6, $result->day);
    }

    // -------------------------------------------------------------------------
    // subtract()
    // -------------------------------------------------------------------------

    public function testSubtractDays(): void
    {
        $date = new PlainDate(2024, 1, 11);
        $result = $date->subtract(['days' => 10]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(1, $result->day);
    }

    public function testSubtractDaysRollsBackMonth(): void
    {
        $date = new PlainDate(2024, 2, 2);
        $result = $date->subtract(['days' => 5]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(28, $result->day);
    }

    public function testSubtractMonths(): void
    {
        $date = new PlainDate(2024, 4, 15);
        $result = $date->subtract(['months' => 3]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testSubtractYears(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->subtract(['years' => 2]);
        $this->assertSame(2022, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(15, $result->day);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithYear(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->with(['year' => 2025]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testWithMonth(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->with(['month' => 6]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(6, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testWithDay(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->with(['day' => 20]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(20, $result->day);
    }

    // -------------------------------------------------------------------------
    // until() and since()
    // -------------------------------------------------------------------------

    public function testUntilSameDate(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $duration = $date->until(new PlainDate(2024, 1, 1));
        $this->assertSame(0, $duration->days);
    }

    public function testUntilFutureDate(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $duration = $date->until(new PlainDate(2024, 1, 11));
        $this->assertSame(10, $duration->days);
    }

    public function testSincePastDate(): void
    {
        $date = new PlainDate(2024, 1, 11);
        $duration = $date->since(new PlainDate(2024, 1, 1));
        $this->assertSame(10, $duration->days);
    }

    // -------------------------------------------------------------------------
    // compare() and equals()
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        $a = new PlainDate(2024, 3, 15);
        $b = new PlainDate(2024, 3, 15);
        $this->assertSame(0, PlainDate::compare($a, $b));
    }

    public function testCompareLessThan(): void
    {
        $a = new PlainDate(2024, 3, 14);
        $b = new PlainDate(2024, 3, 15);
        $this->assertSame(-1, PlainDate::compare($a, $b));
    }

    public function testCompareGreaterThan(): void
    {
        $a = new PlainDate(2024, 3, 16);
        $b = new PlainDate(2024, 3, 15);
        $this->assertSame(1, PlainDate::compare($a, $b));
    }

    public function testEquals(): void
    {
        $a = new PlainDate(2024, 3, 15);
        $b = new PlainDate(2024, 3, 15);
        $c = new PlainDate(2024, 3, 16);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /** Same month+day but different year must NOT be equal. */
    public function testEqualsYearDiffers(): void
    {
        $a = new PlainDate(2024, 3, 15);
        $b = new PlainDate(2025, 3, 15);
        $this->assertFalse($a->equals($b));
    }

    // -------------------------------------------------------------------------
    // toEpochDays() and fromEpochDays()
    // -------------------------------------------------------------------------

    public function testEpochDaysEpoch(): void
    {
        $date = new PlainDate(1970, 1, 1);
        $this->assertSame(0, $date->toEpochDays());
    }

    public function testEpochDaysPositive(): void
    {
        $date = new PlainDate(1970, 1, 2);
        $this->assertSame(1, $date->toEpochDays());
    }

    public function testEpochDaysNegative(): void
    {
        $date = new PlainDate(1969, 12, 31);
        $this->assertSame(-1, $date->toEpochDays());
    }

    public function testFromEpochDays(): void
    {
        $date = PlainDate::fromEpochDays(0);
        $this->assertSame(1970, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function testFromEpochDaysRoundTrip(): void
    {
        $original = new PlainDate(2024, 6, 15);
        $days = $original->toEpochDays();
        $restored = PlainDate::fromEpochDays($days);
        $this->assertTrue($original->equals($restored));
    }

    // -------------------------------------------------------------------------
    // add() overflow option (test262: constrain-days, overflow-reject)
    // -------------------------------------------------------------------------

    public function testAddMonthOverflowConstrainDefault(): void
    {
        // Jan 31 + 1 month → Feb 28 (common year, constrain by default)
        $date = new PlainDate(2019, 1, 31);
        $result = $date->add(['months' => 1]);
        $this->assertSame(2019, $result->year);
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
    }

    public function testAddMonthOverflowConstrainExplicit(): void
    {
        $date = new PlainDate(2019, 1, 31);
        $result = $date->add(['months' => 1], 'constrain');
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
    }

    public function testAddMonthLeapYearConstrainsFebTo29(): void
    {
        // Jan 31 + 1 month in a leap year → Feb 29
        $date = new PlainDate(2016, 1, 31);
        $result = $date->add(['months' => 1]);
        $this->assertSame(2, $result->month);
        $this->assertSame(29, $result->day);
    }

    public function testAddMonthOverflowRejectThrows(): void
    {
        // Jan 31 + 1 month in common year with reject throws
        $date = new PlainDate(2019, 1, 31);
        $this->expectException(DateRangeException::class);
        $date->add(['months' => 1], 'reject');
    }

    public function testAddMonthLeapYearOverflowRejectThrows(): void
    {
        // Jan 31 + 1 month in non-leap year with reject throws (Feb has 28 days)
        $date = new PlainDate(2023, 1, 31);
        $this->expectException(DateRangeException::class);
        $date->add(['months' => 1], 'reject');
    }

    public function testAddMonthLeapYearOverflowRejectAllowed(): void
    {
        // Jan 31 + 1 month in leap year (Feb has 29 days) with reject is allowed
        // because Jan 31 only needs to fit into 29 days? Actually no —
        // day 31 > 29, so it should still reject in a leap year.
        $date = new PlainDate(2016, 1, 31);
        $this->expectException(DateRangeException::class);
        $date->add(['months' => 1], 'reject');
    }

    public function testAddThreeMonthsConstrainNoOverflow(): void
    {
        // Jan 31 + 3 months → Apr 30 (constrain)
        $date = new PlainDate(2019, 1, 31);
        $result = $date->add(['months' => 3]);
        $this->assertSame(4, $result->month);
        $this->assertSame(30, $result->day);
    }

    public function testAddThreeMonthsRejectThrows(): void
    {
        // Jan 31 + 3 months → Apr 30 would constrain; with reject it throws
        $date = new PlainDate(2019, 1, 31);
        $this->expectException(DateRangeException::class);
        $date->add(['months' => 3], 'reject');
    }

    public function testSubtractMonthOverflowConstrainDefault(): void
    {
        // Mar 31 - 1 month → Feb 28 (common year, constrain)
        $date = new PlainDate(2019, 3, 31);
        $result = $date->subtract(['months' => 1]);
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
    }

    public function testSubtractMonthOverflowRejectThrows(): void
    {
        $date = new PlainDate(2019, 3, 31);
        $this->expectException(DateRangeException::class);
        $date->subtract(['months' => 1], 'reject');
    }

    public function testAddInvalidOverflowThrows(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $this->expectException(InvalidArgumentException::class);
        $date->add(['days' => 1], 'invalid');
    }

    // -------------------------------------------------------------------------
    // Year bounds (test262: overflow-adding-months-to-max-year)
    // -------------------------------------------------------------------------

    public function testConstructMaxBoundary(): void
    {
        // September 13, +275760 is the maximum valid PlainDate
        $date = new PlainDate(275760, 9, 13);
        $this->assertSame(275760, $date->year);
        $this->assertSame(9, $date->month);
        $this->assertSame(13, $date->day);
    }

    public function testConstructMinBoundary(): void
    {
        // April 19, -271821 is the minimum valid PlainDate
        $date = new PlainDate(-271821, 4, 19);
        $this->assertSame(-271821, $date->year);
        $this->assertSame(4, $date->month);
        $this->assertSame(19, $date->day);
    }

    public function testConstructBeyondMaxThrows(): void
    {
        // September 14, +275760 is one day beyond the maximum
        $this->expectException(DateRangeException::class);
        new PlainDate(275760, 9, 14);
    }

    public function testConstructBeyondMinThrows(): void
    {
        // April 18, -271821 is one day before the minimum
        $this->expectException(DateRangeException::class);
        new PlainDate(-271821, 4, 18);
    }

    public function testAddExceedsMaxBoundsThrows(): void
    {
        // Max-year date + large positive duration throws RangeException
        $maxDate = new PlainDate(275760, 1, 1);
        $this->expectException(DateRangeException::class);
        $maxDate->add(['months' => 5432, 'weeks' => 5432]);
    }

    public function testAddExceedsMinBoundsThrows(): void
    {
        // Min-year date + large negative duration throws RangeException
        $minDate = new PlainDate(-271821, 4, 19);
        $this->expectException(DateRangeException::class);
        $minDate->add(['months' => -5432, 'weeks' => -5432]);
    }

    public function testFromEpochDaysMaxBoundary(): void
    {
        $date = PlainDate::fromEpochDays(PlainDate::MAX_EPOCH_DAYS);
        $this->assertSame(275760, $date->year);
        $this->assertSame(9, $date->month);
        $this->assertSame(13, $date->day);
    }

    public function testFromEpochDaysMinBoundary(): void
    {
        $date = PlainDate::fromEpochDays(PlainDate::MIN_EPOCH_DAYS);
        $this->assertSame(-271821, $date->year);
        $this->assertSame(4, $date->month);
        $this->assertSame(19, $date->day);
    }

    public function testFromEpochDaysBeyondMaxThrows(): void
    {
        $this->expectException(DateRangeException::class);
        PlainDate::fromEpochDays(PlainDate::MAX_EPOCH_DAYS + 1);
    }

    public function testFromEpochDaysBeyondMinThrows(): void
    {
        $this->expectException(DateRangeException::class);
        PlainDate::fromEpochDays(PlainDate::MIN_EPOCH_DAYS - 1);
    }

    // -------------------------------------------------------------------------
    // toZonedDateTime
    // -------------------------------------------------------------------------

    public function testToZonedDateTimeWithStringTimezone(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $zdt = $date->toZonedDateTime('UTC');
        $this->assertInstanceOf(\Temporal\ZonedDateTime::class, $zdt);
        $this->assertSame(2024, $zdt->year);
        $this->assertSame(3, $zdt->month);
        $this->assertSame(15, $zdt->day);
    }

    public function testToZonedDateTimeDefaultsMidnight(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $zdt = $date->toZonedDateTime('UTC');
        $this->assertSame(0, $zdt->hour);
        $this->assertSame(0, $zdt->minute);
        $this->assertSame(0, $zdt->second);
    }

    public function testToZonedDateTimeWithTimezoneObject(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $tz = \Temporal\TimeZone::from('UTC');
        $zdt = $date->toZonedDateTime($tz);
        $this->assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testToZonedDateTimeWithPlainTime(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $time = new \Temporal\PlainTime(10, 30, 45);
        $zdt = $date->toZonedDateTime(['timeZone' => 'UTC', 'plainTime' => $time]);
        $this->assertSame(2024, $zdt->year);
        $this->assertSame(3, $zdt->month);
        $this->assertSame(15, $zdt->day);
        $this->assertSame(10, $zdt->hour);
        $this->assertSame(30, $zdt->minute);
        $this->assertSame(45, $zdt->second);
    }

    public function testToZonedDateTimeWithArrayTimezoneOnly(): void
    {
        $date = new PlainDate(2024, 6, 1);
        $zdt = $date->toZonedDateTime(['timeZone' => 'UTC']);
        $this->assertSame(2024, $zdt->year);
        $this->assertSame(6, $zdt->month);
        $this->assertSame(1, $zdt->day);
        $this->assertSame(0, $zdt->hour);
    }

    public function testToZonedDateTimeWithOffsetTimezone(): void
    {
        // 2024-03-15 midnight in +05:30 → epoch ns = (2024-03-15T00:00:00 - 5h30m)
        $date = new PlainDate(2024, 3, 15);
        $zdt = $date->toZonedDateTime('+05:30');
        $this->assertSame(2024, $zdt->year);
        $this->assertSame(3, $zdt->month);
        $this->assertSame(15, $zdt->day);
        $this->assertSame(0, $zdt->hour);
        $this->assertSame(0, $zdt->minute);
    }

    public function testToZonedDateTimeEpochNsIsCorrect(): void
    {
        // 1970-01-01 midnight UTC → epoch ns = 0
        $date = new PlainDate(1970, 1, 1);
        $zdt = $date->toZonedDateTime('UTC');
        $this->assertSame(0, $zdt->epochNanoseconds);
    }

    /** Passing an array without a 'timeZone' key must throw MissingFieldException. */
    public function testToZonedDateTimeMissingTimeZoneKey(): void
    {
        $date = new PlainDate(2024, 6, 1);
        $this->expectException(MissingFieldException::class);
        $_ = $date->toZonedDateTime([]);
    }

    // -------------------------------------------------------------------------
    // toPlainDateTime()
    // -------------------------------------------------------------------------

    public function testToPlainDateTimeWithTime(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $time = new \Temporal\PlainTime(10, 30, 45);
        $pdt = $date->toPlainDateTime($time);

        $this->assertSame(2024, $pdt->year);
        $this->assertSame(3, $pdt->month);
        $this->assertSame(15, $pdt->day);
        $this->assertSame(10, $pdt->hour);
        $this->assertSame(30, $pdt->minute);
        $this->assertSame(45, $pdt->second);
    }

    public function testToPlainDateTimeWithoutTimeDefaultsMidnight(): void
    {
        $date = new PlainDate(2024, 6, 1);
        $pdt = $date->toPlainDateTime();

        $this->assertSame(2024, $pdt->year);
        $this->assertSame(6, $pdt->month);
        $this->assertSame(1, $pdt->day);
        $this->assertSame(0, $pdt->hour);
        $this->assertSame(0, $pdt->minute);
        $this->assertSame(0, $pdt->second);
    }

    public function testToPlainDateTimeWithNullDefaultsMidnight(): void
    {
        $date = new PlainDate(2000, 1, 1);
        $pdt = $date->toPlainDateTime(null);

        $this->assertSame(0, $pdt->hour);
        $this->assertSame(0, $pdt->minute);
        $this->assertSame(0, $pdt->second);
        $this->assertSame(0, $pdt->nanosecond);
    }

    public function testToPlainDateTimePreservesSubSeconds(): void
    {
        $date = new PlainDate(2024, 1, 1);
        $time = new \Temporal\PlainTime(12, 0, 0, 123, 456, 789);
        $pdt = $date->toPlainDateTime($time);

        $this->assertSame(123, $pdt->millisecond);
        $this->assertSame(456, $pdt->microsecond);
        $this->assertSame(789, $pdt->nanosecond);
    }

    // -------------------------------------------------------------------------
    // toPlainYearMonth()
    // -------------------------------------------------------------------------

    public function testToPlainYearMonth(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $pym = $date->toPlainYearMonth();

        $this->assertSame(2024, $pym->year);
        $this->assertSame(3, $pym->month);
    }

    public function testToPlainYearMonthIgnoresDay(): void
    {
        $date1 = new PlainDate(2024, 6, 1);
        $date2 = new PlainDate(2024, 6, 30);

        $this->assertTrue($date1->toPlainYearMonth()->equals($date2->toPlainYearMonth()));
    }

    // -------------------------------------------------------------------------
    // toPlainMonthDay()
    // -------------------------------------------------------------------------

    public function testToPlainMonthDay(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $pmd = $date->toPlainMonthDay();

        $this->assertSame(3, $pmd->month);
        $this->assertSame(15, $pmd->day);
    }

    public function testToPlainMonthDayIgnoresYear(): void
    {
        $date1 = new PlainDate(2020, 2, 29);
        $date2 = new PlainDate(2024, 2, 29);

        $this->assertTrue($date1->toPlainMonthDay()->equals($date2->toPlainMonthDay()));
    }

    // -------------------------------------------------------------------------
    // yearOfWeek
    // -------------------------------------------------------------------------

    /** ISO week year matches calendar year for a mid-year date. */
    public function testYearOfWeekNormalDate(): void
    {
        // 2024-03-15 is in week 11 of 2024 — yearOfWeek matches year.
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(2024, $date->yearOfWeek);
        $this->assertSame(11, $date->weekOfYear);
    }

    /** Dates in early January belonging to the last week of the previous year. */
    public function testYearOfWeekEarlyJanuary(): void
    {
        // 2021-01-01 (Friday) is in week 53 of 2020.
        $date = new PlainDate(2021, 1, 1);
        $this->assertSame(53, $date->weekOfYear);
        $this->assertSame(2020, $date->yearOfWeek);
    }

    /** 2021-01-04 (Monday) is the first day of week 1 of 2021. */
    public function testYearOfWeekFirstWeekOfYear(): void
    {
        $date = new PlainDate(2021, 1, 4);
        $this->assertSame(1, $date->weekOfYear);
        $this->assertSame(2021, $date->yearOfWeek);
    }

    /** Dates in late December belonging to week 1 of the next year. */
    public function testYearOfWeekLateDecember(): void
    {
        // 2020-12-31 (Thursday) is in week 53 of 2020 (not week 1 of 2021).
        // Use a date that wraps to the next year instead.
        // 2019-12-30 (Monday) is in week 1 of 2020.
        $date = new PlainDate(2019, 12, 30);
        $this->assertSame(1, $date->weekOfYear);
        $this->assertSame(2020, $date->yearOfWeek);
    }

    /** 2019-12-29 (Sunday) is in week 52 of 2019 — yearOfWeek matches year. */
    public function testYearOfWeekLateDec_SameYear(): void
    {
        $date = new PlainDate(2019, 12, 29);
        $this->assertSame(52, $date->weekOfYear);
        $this->assertSame(2019, $date->yearOfWeek);
    }

    /**
     * 2011-01-01 (Saturday) falls in week 52 of 2010.
     * yearOfWeek must therefore return 2010 (year - 1), not 2011.
     * This distinguishes the ">= 52" boundary condition from "> 52".
     */
    public function testYearOfWeekJanInWeek52(): void
    {
        $date = new PlainDate(2011, 1, 1);
        $this->assertSame(52, $date->weekOfYear);
        $this->assertSame(2010, $date->yearOfWeek);
    }

    // -------------------------------------------------------------------------
    // until() / since() with largestUnit
    // -------------------------------------------------------------------------

    /** Default behavior (largestUnit = 'day') is unchanged. */
    public function testUntilDefaultLargestUnit(): void
    {
        $a = new PlainDate(2024, 1, 1);
        $b = new PlainDate(2024, 3, 15);
        $d = $a->until($b);
        $this->assertSame(74, $d->days);
        $this->assertSame(0, $d->months);
        $this->assertSame(0, $d->years);
    }

    /** largestUnit = 'week' breaks days into weeks + remainder days. */
    public function testUntilLargestUnitWeek(): void
    {
        $a = new PlainDate(2024, 1, 1);
        $b = new PlainDate(2024, 1, 22); // 21 days = 3 weeks exactly
        $d = $a->until($b, ['largestUnit' => 'week']);
        $this->assertSame(3, $d->weeks);
        $this->assertSame(0, $d->days);
    }

    public function testUntilLargestUnitWeekWithRemainder(): void
    {
        $a = new PlainDate(2024, 1, 1);
        $b = new PlainDate(2024, 1, 25); // 24 days = 3 weeks + 3 days
        $d = $a->until($b, ['largestUnit' => 'week']);
        $this->assertSame(3, $d->weeks);
        $this->assertSame(3, $d->days);
    }

    /** largestUnit = 'month' returns months + remainder days. */
    public function testUntilLargestUnitMonth(): void
    {
        $a = new PlainDate(2024, 1, 15);
        $b = new PlainDate(2024, 4, 15); // exactly 3 months
        $d = $a->until($b, ['largestUnit' => 'month']);
        $this->assertSame(0, $d->years);
        $this->assertSame(3, $d->months);
        $this->assertSame(0, $d->days);
    }

    public function testUntilLargestUnitMonthWithRemainder(): void
    {
        $a = new PlainDate(2024, 1, 15);
        $b = new PlainDate(2024, 4, 20); // 3 months + 5 days
        $d = $a->until($b, ['largestUnit' => 'month']);
        $this->assertSame(3, $d->months);
        $this->assertSame(5, $d->days);
    }

    /** largestUnit = 'year' returns years + months + remainder days. */
    public function testUntilLargestUnitYear(): void
    {
        $a = new PlainDate(2020, 3, 15);
        $b = new PlainDate(2022, 5, 20); // 2 years + 2 months + 5 days
        $d = $a->until($b, ['largestUnit' => 'year']);
        $this->assertSame(2, $d->years);
        $this->assertSame(2, $d->months);
        $this->assertSame(5, $d->days);
    }

    /** Negative duration when other is before this. */
    public function testUntilLargestUnitNegative(): void
    {
        $a = new PlainDate(2024, 4, 15);
        $b = new PlainDate(2024, 1, 10); // b is before a
        $d = $a->until($b, ['largestUnit' => 'month']);
        $this->assertSame(-3, $d->months);
        $this->assertSame(-5, $d->days);
    }

    /** since() is the reverse of until(). */
    public function testSinceLargestUnitMonth(): void
    {
        $a = new PlainDate(2024, 1, 15);
        $b = new PlainDate(2024, 4, 15);
        $d = $b->since($a, ['largestUnit' => 'month']);
        $this->assertSame(3, $d->months);
        $this->assertSame(0, $d->days);
    }

    /** Accepts unit string directly instead of options array. */
    public function testUntilLargestUnitStringArg(): void
    {
        $a = new PlainDate(2024, 1, 1);
        $b = new PlainDate(2024, 4, 1); // 3 months
        $d = $a->until($b, 'month');
        $this->assertSame(3, $d->months);
        $this->assertSame(0, $d->days);
    }

    /** Invalid largestUnit throws. */
    public function testUntilInvalidLargestUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PlainDate(2024, 1, 1)->until(new PlainDate(2024, 6, 1), ['largestUnit' => 'hour']);
    }

    // -------------------------------------------------------------------------
    // test262: PlainDate.prototype.add/basic.js — comprehensive data matrix
    // 62 cases covering leap years, month-end constraints, cross-year boundaries
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{string, array<string,int>, string}>
     */
    public static function addDataProvider(): array
    {
        return [
            // leap year Feb 29 constraining
            'leap-p1y-constrain' => ['2020-02-29', ['years' => 1], '2021-02-28'],
            'leap-p4y-preserves' => ['2020-02-29', ['years' => 4], '2024-02-29'],
            // simple year
            'simple-p1y' => ['2021-07-16', ['years' => 1], '2022-07-16'],
            // 5-month additions
            '5m-no-overflow' => ['2021-07-16', ['months' => 5], '2021-12-16'],
            '5m-crosses-year' => ['2021-08-16', ['months' => 5], '2022-01-16'],
            '5m-31-to-31' => ['2021-10-31', ['months' => 5], '2022-03-31'],
            '5m-30-to-28' => ['2021-09-30', ['months' => 5], '2022-02-28'],
            '5m-30-to-29-leap' => ['2019-09-30', ['months' => 5], '2020-02-29'],
            '5m-01-to-01' => ['2019-10-01', ['months' => 5], '2020-03-01'],
            // 1y2m combos
            '1y2m-basic' => ['2021-07-16', ['years' => 1, 'months' => 2], '2022-09-16'],
            '1y2m-30day' => ['2021-11-30', ['years' => 1, 'months' => 2], '2023-01-30'],
            '1y2m-31-to-28' => ['2021-12-31', ['years' => 1, 'months' => 2], '2023-02-28'],
            '1y2m-31-to-29-leap' => ['2022-12-31', ['years' => 1, 'months' => 2], '2024-02-29'],
            // 1y4d combos
            '1y4d-basic' => ['2021-07-16', ['years' => 1, 'days' => 4], '2022-07-20'],
            '1y4d-feb-overflow' => ['2021-02-27', ['years' => 1, 'days' => 4], '2022-03-03'],
            '1y4d-leap-no-overflow' => ['2023-02-27', ['years' => 1, 'days' => 4], '2024-03-02'],
            '1y4d-cross-year' => ['2021-12-30', ['years' => 1, 'days' => 4], '2023-01-03'],
            '1y4d-jul30' => ['2021-07-30', ['years' => 1, 'days' => 4], '2022-08-03'],
            '1y4d-jun30' => ['2021-06-30', ['years' => 1, 'days' => 4], '2022-07-04'],
            // 1y2m4d combos
            '1y2m4d-basic' => ['2021-07-16', ['years' => 1, 'months' => 2, 'days' => 4], '2022-09-20'],
            '1y2m4d-feb27' => ['2021-02-27', ['years' => 1, 'months' => 2, 'days' => 4], '2022-05-01'],
            '1y2m4d-feb26' => ['2021-02-26', ['years' => 1, 'months' => 2, 'days' => 4], '2022-04-30'],
            '1y2m4d-leap-feb26' => ['2023-02-26', ['years' => 1, 'months' => 2, 'days' => 4], '2024-04-30'],
            '1y2m4d-dec30' => ['2021-12-30', ['years' => 1, 'months' => 2, 'days' => 4], '2023-03-04'],
            '1y2m4d-jul30' => ['2021-07-30', ['years' => 1, 'months' => 2, 'days' => 4], '2022-10-04'],
            '1y2m4d-jun30' => ['2021-06-30', ['years' => 1, 'months' => 2, 'days' => 4], '2022-09-03'],
            // 10-day additions
            '10d-basic' => ['2021-07-16', ['days' => 10], '2021-07-26'],
            '10d-crosses-month' => ['2021-07-26', ['days' => 10], '2021-08-05'],
            '10d-crosses-year' => ['2021-12-26', ['days' => 10], '2022-01-05'],
            '10d-feb-leap' => ['2020-02-26', ['days' => 10], '2020-03-07'],
            '10d-feb-nonleap' => ['2021-02-26', ['days' => 10], '2021-03-08'],
            '10d-leap-exact' => ['2020-02-19', ['days' => 10], '2020-02-29'],
            '10d-nonleap-march' => ['2021-02-19', ['days' => 10], '2021-03-01'],
            // 1-week additions
            '1w-basic' => ['2021-02-19', ['weeks' => 1], '2021-02-26'],
            '1w-crosses-month' => ['2021-02-27', ['weeks' => 1], '2021-03-06'],
            '1w-crosses-leap-month' => ['2020-02-27', ['weeks' => 1], '2020-03-05'],
            '1w-dec31' => ['2021-12-24', ['weeks' => 1], '2021-12-31'],
            '1w-crosses-year' => ['2021-12-27', ['weeks' => 1], '2022-01-03'],
            '1w-jan27' => ['2021-01-27', ['weeks' => 1], '2021-02-03'],
            '1w-jun27' => ['2021-06-27', ['weeks' => 1], '2021-07-04'],
            '1w-jul27' => ['2021-07-27', ['weeks' => 1], '2021-08-03'],
            // 6-week additions
            '6w-feb19' => ['2021-02-19', ['weeks' => 6], '2021-04-02'],
            '6w-feb27' => ['2021-02-27', ['weeks' => 6], '2021-04-10'],
            '6w-leap-feb27' => ['2020-02-27', ['weeks' => 6], '2020-04-09'],
            '6w-dec24' => ['2021-12-24', ['weeks' => 6], '2022-02-04'],
            '6w-dec27' => ['2021-12-27', ['weeks' => 6], '2022-02-07'],
            '6w-jan27' => ['2021-01-27', ['weeks' => 6], '2021-03-10'],
            '6w-jun27' => ['2021-06-27', ['weeks' => 6], '2021-08-08'],
            '6w-jul27' => ['2021-07-27', ['weeks' => 6], '2021-09-07'],
            // 2w3d additions
            '2w3d-leap-feb29' => ['2020-02-29', ['weeks' => 2, 'days' => 3], '2020-03-17'],
            '2w3d-leap-feb28' => ['2020-02-28', ['weeks' => 2, 'days' => 3], '2020-03-16'],
            '2w3d-nonleap-feb28' => ['2021-02-28', ['weeks' => 2, 'days' => 3], '2021-03-17'],
            '2w3d-dec28' => ['2020-12-28', ['weeks' => 2, 'days' => 3], '2021-01-14'],
            // 1y2w additions
            '1y2w-leap-feb29' => ['2020-02-29', ['years' => 1, 'weeks' => 2], '2021-03-14'],
            '1y2w-leap-feb28' => ['2020-02-28', ['years' => 1, 'weeks' => 2], '2021-03-14'],
            '1y2w-nonleap-feb28' => ['2021-02-28', ['years' => 1, 'weeks' => 2], '2022-03-14'],
            '1y2w-dec28' => ['2020-12-28', ['years' => 1, 'weeks' => 2], '2022-01-11'],
            // 2m3w additions
            '2m3w-leap-feb29' => ['2020-02-29', ['months' => 2, 'weeks' => 3], '2020-05-20'],
            '2m3w-leap-feb28' => ['2020-02-28', ['months' => 2, 'weeks' => 3], '2020-05-19'],
            '2m3w-nonleap-feb28' => ['2021-02-28', ['months' => 2, 'weeks' => 3], '2021-05-19'],
            '2m3w-dec28' => ['2020-12-28', ['months' => 2, 'weeks' => 3], '2021-03-21'],
            '2m3w-dec28-nonleap' => ['2019-12-28', ['months' => 2, 'weeks' => 3], '2020-03-20'],
            '2m3w-oct28' => ['2019-10-28', ['months' => 2, 'weeks' => 3], '2020-01-18'],
            '2m3w-oct31' => ['2019-10-31', ['months' => 2, 'weeks' => 3], '2020-01-21']
        ];
    }

    /**
     * @param array<string,int> $duration
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('addDataProvider')]
    public function testAddMatrix(string $start, array $duration, string $expected): void
    {
        $date = PlainDate::from($start);
        $result = $date->add($duration);
        $this->assertSame($expected, (string) $result);
    }
}
