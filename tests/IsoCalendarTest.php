<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Calendar;
use Temporal\CalendarProtocol;
use Temporal\IsoCalendar;
use Temporal\PlainDate;
use Temporal\PlainMonthDay;
use Temporal\PlainYearMonth;

/**
 * Tests for IsoCalendar and the CalendarProtocol interface.
 */
final class IsoCalendarTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Singleton and interface
    // -------------------------------------------------------------------------

    public function testSingletonReturnsSameInstance(): void
    {
        $a = IsoCalendar::instance();
        $b = IsoCalendar::instance();
        $this->assertSame($a, $b);
    }

    public function testImplementsCalendarProtocol(): void
    {
        $this->assertInstanceOf(CalendarProtocol::class, IsoCalendar::instance());
    }

    public function testGetId(): void
    {
        $this->assertSame('iso8601', IsoCalendar::instance()->getId());
    }

    // -------------------------------------------------------------------------
    // Field identity (ISO calendar: fields pass through unchanged)
    // -------------------------------------------------------------------------

    public function testYearReturnsIsoYear(): void
    {
        $this->assertSame(2024, IsoCalendar::instance()->year(2024, 3, 15));
    }

    public function testMonthReturnsIsoMonth(): void
    {
        $this->assertSame(3, IsoCalendar::instance()->month(2024, 3, 15));
    }

    public function testDayReturnsIsoDay(): void
    {
        $this->assertSame(15, IsoCalendar::instance()->day(2024, 3, 15));
    }

    public function testMonthCodeSingleDigit(): void
    {
        $this->assertSame('M03', IsoCalendar::instance()->monthCode(2024, 3, 15));
    }

    public function testMonthCodeDoubleDigit(): void
    {
        $this->assertSame('M12', IsoCalendar::instance()->monthCode(2024, 12, 1));
    }

    public function testMonthCodeJanuary(): void
    {
        $this->assertSame('M01', IsoCalendar::instance()->monthCode(2024, 1, 1));
    }

    // -------------------------------------------------------------------------
    // Static helpers (public for reuse in temporal types)
    // -------------------------------------------------------------------------

    public function testIsLeapYearDivisibleBy4(): void
    {
        $this->assertTrue(IsoCalendar::isLeapYear(2024));
    }

    public function testIsLeapYearCenturyNotLeap(): void
    {
        $this->assertFalse(IsoCalendar::isLeapYear(1900));
    }

    public function testIsLeapYear400Divisible(): void
    {
        $this->assertTrue(IsoCalendar::isLeapYear(2000));
    }

    public function testIsLeapYearNonLeap(): void
    {
        $this->assertFalse(IsoCalendar::isLeapYear(2023));
    }

    public function testDaysInMonthFor31DayMonths(): void
    {
        foreach ([1, 3, 5, 7, 8, 10, 12] as $month) {
            $this->assertSame(31, IsoCalendar::daysInMonthFor(2024, $month));
        }
    }

    public function testDaysInMonthFor30DayMonths(): void
    {
        foreach ([4, 6, 9, 11] as $month) {
            $this->assertSame(30, IsoCalendar::daysInMonthFor(2024, $month));
        }
    }

    public function testDaysInMonthForFebruaryLeap(): void
    {
        $this->assertSame(29, IsoCalendar::daysInMonthFor(2024, 2));
    }

    public function testDaysInMonthForFebruaryNonLeap(): void
    {
        $this->assertSame(28, IsoCalendar::daysInMonthFor(2023, 2));
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol field queries (take ISO fields)
    // -------------------------------------------------------------------------

    public function testDaysInMonthLeapFebruary(): void
    {
        $this->assertSame(29, IsoCalendar::instance()->daysInMonth(2024, 2, 1));
    }

    public function testDaysInMonthNonLeapFebruary(): void
    {
        $this->assertSame(28, IsoCalendar::instance()->daysInMonth(2023, 2, 1));
    }

    public function testDaysInMonthMarch(): void
    {
        $this->assertSame(31, IsoCalendar::instance()->daysInMonth(2024, 3, 1));
    }

    public function testDaysInYearLeap(): void
    {
        $this->assertSame(366, IsoCalendar::instance()->daysInYear(2024, 1, 1));
    }

    public function testDaysInYearNonLeap(): void
    {
        $this->assertSame(365, IsoCalendar::instance()->daysInYear(2023, 1, 1));
    }

    public function testMonthsInYear(): void
    {
        $this->assertSame(12, IsoCalendar::instance()->monthsInYear());
    }

    public function testInLeapYearTrue(): void
    {
        $this->assertTrue(IsoCalendar::instance()->inLeapYear(2024, 1, 1));
    }

    public function testInLeapYearFalse(): void
    {
        $this->assertFalse(IsoCalendar::instance()->inLeapYear(2023, 1, 1));
    }

    public function testEraReturnsNull(): void
    {
        $this->assertNull(IsoCalendar::instance()->era(2024, 1, 1));
    }

    public function testEraYearReturnsNull(): void
    {
        $this->assertNull(IsoCalendar::instance()->eraYear(2024, 1, 1));
    }

    public function testDaysInWeek(): void
    {
        $this->assertSame(7, IsoCalendar::instance()->daysInWeek());
    }

    public function testDayOfWeekFriday(): void
    {
        // 2024-03-15 is a Friday (5)
        $this->assertSame(5, IsoCalendar::instance()->dayOfWeek(2024, 3, 15));
    }

    public function testDayOfWeekMonday(): void
    {
        // 2024-03-11 is a Monday (1)
        $this->assertSame(1, IsoCalendar::instance()->dayOfWeek(2024, 3, 11));
    }

    public function testDayOfWeekSunday(): void
    {
        // 2024-03-17 is a Sunday (7)
        $this->assertSame(7, IsoCalendar::instance()->dayOfWeek(2024, 3, 17));
    }

    public function testDayOfYear(): void
    {
        // 2024-03-15: Jan(31) + Feb(29 leap) + 15 = 75
        $this->assertSame(75, IsoCalendar::instance()->dayOfYear(2024, 3, 15));
    }

    public function testDayOfYearLeapFeb29(): void
    {
        // 2024-02-29: Jan(31) + 29 = 60
        $this->assertSame(60, IsoCalendar::instance()->dayOfYear(2024, 2, 29));
    }

    public function testWeekOfYear(): void
    {
        $this->assertSame(1, IsoCalendar::instance()->weekOfYear(2024, 1, 1));
    }

    public function testYearOfWeek(): void
    {
        $this->assertSame(2024, IsoCalendar::instance()->yearOfWeek(2024, 3, 15));
    }

    public function testYearOfWeekBoundary(): void
    {
        // 2024-12-30 is in ISO week 1 of 2025
        $this->assertSame(2025, IsoCalendar::instance()->yearOfWeek(2024, 12, 30));
    }

    // -------------------------------------------------------------------------
    // Date arithmetic
    // -------------------------------------------------------------------------

    public function testDateAddDays(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = IsoCalendar::instance()->dateAdd($date, ['days' => 10], 'constrain');
        $this->assertSame('2024-03-25', (string) $result);
    }

    public function testDateAddMonthsConstrain(): void
    {
        // Jan 31 + 1 month → Feb 29 (2024 is leap)
        $date = new PlainDate(2024, 1, 31);
        $result = IsoCalendar::instance()->dateAdd($date, ['months' => 1], 'constrain');
        $this->assertSame('2024-02-29', (string) $result);
    }

    public function testDateAddYears(): void
    {
        $date = new PlainDate(2024, 6, 15);
        $result = IsoCalendar::instance()->dateAdd($date, ['years' => 2], 'constrain');
        $this->assertSame('2026-06-15', (string) $result);
    }

    public function testDateAddWeeks(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = IsoCalendar::instance()->dateAdd($date, ['weeks' => 2], 'constrain');
        $this->assertSame('2024-03-29', (string) $result);
    }

    public function testDateUntilDays(): void
    {
        $one = new PlainDate(2024, 3, 15);
        $two = new PlainDate(2024, 3, 25);
        $duration = IsoCalendar::instance()->dateUntil($one, $two, 'day');
        $this->assertSame(10, $duration->days);
    }

    public function testDateUntilYears(): void
    {
        $one = new PlainDate(2024, 1, 1);
        $two = new PlainDate(2026, 1, 1);
        $duration = IsoCalendar::instance()->dateUntil($one, $two, 'year');
        $this->assertSame(2, $duration->years);
        $this->assertSame(0, $duration->months);
        $this->assertSame(0, $duration->days);
    }

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    public function testDateFromFields(): void
    {
        $date = IsoCalendar::instance()->dateFromFields(['year' => 2024, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testYearMonthFromFields(): void
    {
        $ym = IsoCalendar::instance()->yearMonthFromFields(['year' => 2024, 'month' => 3], 'constrain');
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testMonthDayFromFields(): void
    {
        $md = IsoCalendar::instance()->monthDayFromFields(['month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    // -------------------------------------------------------------------------
    // Field helpers
    // -------------------------------------------------------------------------

    public function testFieldsValidReturnsIndexed(): void
    {
        $result = IsoCalendar::instance()->fields(['year', 'month', 'day']);
        $this->assertSame(['year', 'month', 'day'], $result);
    }

    public function testFieldsSubset(): void
    {
        $result = IsoCalendar::instance()->fields(['month', 'day']);
        $this->assertSame(['month', 'day'], $result);
    }

    public function testFieldsInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IsoCalendar::instance()->fields(['year', 'invalid']);
    }

    public function testMergeFields(): void
    {
        $result = IsoCalendar::instance()->mergeFields(['year' => 2024, 'month' => 1], ['month' => 3, 'day' => 15]);
        $this->assertSame(['year' => 2024, 'month' => 3, 'day' => 15], $result);
    }

    // -------------------------------------------------------------------------
    // Calendar factory returns CalendarProtocol-backed Calendar
    // -------------------------------------------------------------------------

    public function testCalendarFromIso8601HasCorrectId(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame('iso8601', $cal->id);
    }

    public function testCalendarGetProtocol(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertInstanceOf(CalendarProtocol::class, $cal->getProtocol());
    }

    public function testCalendarGetProtocolIsIsoCalendar(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertInstanceOf(IsoCalendar::class, $cal->getProtocol());
    }

    // -------------------------------------------------------------------------
    // PlainDate stores CalendarProtocol and reflects calendarId
    // -------------------------------------------------------------------------

    public function testPlainDateCalendarIdIsIso8601(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame('iso8601', $date->calendarId);
    }
}
