<?php

declare(strict_types=1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
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
        $this->expectException(InvalidArgumentException::class);
        new PlainDate(2024, 0, 1);
    }

    public function testConstructorInvalidMonthThirteen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDate(2024, 13, 1);
    }

    public function testConstructorInvalidDayZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDate(2024, 1, 0);
    }

    public function testConstructorInvalidDayTooLarge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDate(2024, 1, 32);
    }

    public function testConstructorInvalidDayForMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDate(2024, 4, 31); // April has 30 days
    }

    public function testConstructorLeapDay(): void
    {
        $date = new PlainDate(2024, 2, 29);
        $this->assertSame(29, $date->day);
    }

    public function testConstructorInvalidLeapDay(): void
    {
        $this->expectException(InvalidArgumentException::class);
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
        $this->assertSame(366, (new PlainDate(2024, 1, 1))->daysInYear);
        $this->assertSame(365, (new PlainDate(2023, 1, 1))->daysInYear);
    }

    public function testInLeapYear(): void
    {
        $this->assertTrue((new PlainDate(2024, 1, 1))->inLeapYear);
        $this->assertFalse((new PlainDate(2023, 1, 1))->inLeapYear);
        $this->assertFalse((new PlainDate(1900, 1, 1))->inLeapYear);
        $this->assertTrue((new PlainDate(2000, 1, 1))->inLeapYear);
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
}
