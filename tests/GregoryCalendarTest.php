<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\BuddhistCalendar;
use Temporal\Calendar;
use Temporal\CalendarProtocol;
use Temporal\GregoryCalendar;
use Temporal\IsoCalendar;
use Temporal\PlainDate;

/**
 * Tests for GregoryCalendar and BuddhistCalendar implementations.
 */
final class GregoryCalendarTest extends TestCase
{
    // =========================================================================
    // GregoryCalendar — singleton and interface
    // =========================================================================

    public function testGregorySingletonReturnsSameInstance(): void
    {
        $a = GregoryCalendar::instance();
        $b = GregoryCalendar::instance();
        $this->assertSame($a, $b);
    }

    public function testGregoryImplementsCalendarProtocol(): void
    {
        $this->assertInstanceOf(CalendarProtocol::class, GregoryCalendar::instance());
    }

    public function testGregoryGetId(): void
    {
        $this->assertSame('gregory', GregoryCalendar::instance()->getId());
    }

    // =========================================================================
    // GregoryCalendar — field pass-through (CE dates match ISO)
    // =========================================================================

    public function testGregoryYearCE(): void
    {
        $this->assertSame(2024, GregoryCalendar::instance()->year(2024, 3, 15));
    }

    public function testGregoryMonthPassThrough(): void
    {
        $this->assertSame(3, GregoryCalendar::instance()->month(2024, 3, 15));
    }

    public function testGregoryDayPassThrough(): void
    {
        $this->assertSame(15, GregoryCalendar::instance()->day(2024, 3, 15));
    }

    public function testGregoryMonthCode(): void
    {
        $this->assertSame('M03', GregoryCalendar::instance()->monthCode(2024, 3, 15));
    }

    public function testGregoryMonthCodeDecember(): void
    {
        $this->assertSame('M12', GregoryCalendar::instance()->monthCode(2024, 12, 1));
    }

    // =========================================================================
    // GregoryCalendar — era for CE dates
    // =========================================================================

    public function testGregoryEraForCEYear(): void
    {
        $this->assertSame('ce', GregoryCalendar::instance()->era(2024, 1, 1));
    }

    public function testGregoryEraForYear1(): void
    {
        $this->assertSame('ce', GregoryCalendar::instance()->era(1, 1, 1));
    }

    public function testGregoryEraYearForCEYear(): void
    {
        $this->assertSame(2024, GregoryCalendar::instance()->eraYear(2024, 3, 15));
    }

    public function testGregoryEraYearForYear1(): void
    {
        $this->assertSame(1, GregoryCalendar::instance()->eraYear(1, 1, 1));
    }

    // =========================================================================
    // GregoryCalendar — era for BCE dates
    // =========================================================================

    public function testGregoryEraForIsoYear0(): void
    {
        // ISO year 0 = 1 BCE
        $this->assertSame('bce', GregoryCalendar::instance()->era(0, 1, 1));
    }

    public function testGregoryEraForNegativeIsoYear(): void
    {
        // ISO year -1 = 2 BCE
        $this->assertSame('bce', GregoryCalendar::instance()->era(-1, 1, 1));
    }

    public function testGregoryEraYearForIsoYear0(): void
    {
        // ISO year 0 → BCE era-year 1  (1 - 0 = 1)
        $this->assertSame(1, GregoryCalendar::instance()->eraYear(0, 1, 1));
    }

    public function testGregoryEraYearForIsoYearMinus1(): void
    {
        // ISO year -1 → BCE era-year 2  (1 - (-1) = 2)
        $this->assertSame(2, GregoryCalendar::instance()->eraYear(-1, 1, 1));
    }

    public function testGregoryEraYearForIsoYearMinus99(): void
    {
        // ISO year -99 → BCE era-year 100
        $this->assertSame(100, GregoryCalendar::instance()->eraYear(-99, 1, 1));
    }

    // =========================================================================
    // GregoryCalendar — week / day-of-year / inLeapYear (delegates to ISO)
    // =========================================================================

    public function testGregoryDayOfWeekDelegatesToIso(): void
    {
        // 2024-03-15 is a Friday (5)
        $this->assertSame(
            IsoCalendar::instance()->dayOfWeek(2024, 3, 15),
            GregoryCalendar::instance()->dayOfWeek(2024, 3, 15)
        );
    }

    public function testGregoryDayOfYearDelegatesToIso(): void
    {
        $this->assertSame(
            IsoCalendar::instance()->dayOfYear(2024, 3, 15),
            GregoryCalendar::instance()->dayOfYear(2024, 3, 15)
        );
    }

    public function testGregoryWeekOfYearDelegatesToIso(): void
    {
        $this->assertSame(
            IsoCalendar::instance()->weekOfYear(2024, 3, 15),
            GregoryCalendar::instance()->weekOfYear(2024, 3, 15)
        );
    }

    public function testGregoryInLeapYearDelegatesToIso(): void
    {
        $this->assertTrue(GregoryCalendar::instance()->inLeapYear(2024, 2, 29));
        $this->assertFalse(GregoryCalendar::instance()->inLeapYear(2023, 1, 1));
    }

    public function testGregoryDaysInMonthDelegatesToIso(): void
    {
        $this->assertSame(29, GregoryCalendar::instance()->daysInMonth(2024, 2, 1));
        $this->assertSame(28, GregoryCalendar::instance()->daysInMonth(2023, 2, 1));
        $this->assertSame(31, GregoryCalendar::instance()->daysInMonth(2024, 1, 1));
    }

    public function testGregoryDaysInYear(): void
    {
        $this->assertSame(366, GregoryCalendar::instance()->daysInYear(2024, 1, 1));
        $this->assertSame(365, GregoryCalendar::instance()->daysInYear(2023, 1, 1));
    }

    public function testGregoryMonthsInYear(): void
    {
        $this->assertSame(12, GregoryCalendar::instance()->monthsInYear());
    }

    public function testGregoryDaysInWeek(): void
    {
        $this->assertSame(7, GregoryCalendar::instance()->daysInWeek());
    }

    // =========================================================================
    // GregoryCalendar — dateFromFields with year
    // =========================================================================

    public function testGregoryDateFromFieldsWithYear(): void
    {
        $cal = GregoryCalendar::instance();
        $date = $cal->dateFromFields(['year' => 2024, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
        $this->assertSame('gregory', $date->calendarId);
    }

    public function testGregoryDateFromFieldsWithEraYearCE(): void
    {
        $cal = GregoryCalendar::instance();
        $date = $cal->dateFromFields(['era' => 'ce', 'eraYear' => 2024, 'month' => 6, 'day' => 1], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(1, $date->day);
    }

    public function testGregoryDateFromFieldsWithEraYearBCE(): void
    {
        $cal = GregoryCalendar::instance();
        // era=bce, eraYear=1 → ISO year 0
        $date = $cal->dateFromFields(['era' => 'bce', 'eraYear' => 1, 'month' => 1, 'day' => 1], 'constrain');
        $this->assertSame(0, $date->year);
        $this->assertSame('gregory', $date->calendarId);
    }

    public function testGregoryDateFromFieldsWithEraYearBCE2(): void
    {
        $cal = GregoryCalendar::instance();
        // era=bce, eraYear=2 → ISO year -1
        $date = $cal->dateFromFields(['era' => 'bce', 'eraYear' => 2, 'month' => 1, 'day' => 1], 'constrain');
        $this->assertSame(-1, $date->year);
    }

    public function testGregoryDateFromFieldsConstrainsDay(): void
    {
        $cal = GregoryCalendar::instance();
        // Jan 31 + constrain → keeps day at 31 (valid)
        // Feb 30 constrained → Feb 28/29
        $date = $cal->dateFromFields(['year' => 2023, 'month' => 2, 'day' => 30], 'constrain');
        $this->assertSame(28, $date->day);
    }

    public function testGregoryDateFromFieldsRejectsInvalidDay(): void
    {
        $this->expectException(\RangeException::class);
        GregoryCalendar::instance()->dateFromFields(['year' => 2023, 'month' => 2, 'day' => 30], 'reject');
    }

    public function testGregoryDateFromFieldsUnknownEraThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GregoryCalendar::instance()->dateFromFields([
            'era' => 'ad',
            'eraYear' => 2024,
            'month' => 1,
            'day' => 1
        ], 'constrain');
    }

    // =========================================================================
    // GregoryCalendar — yearMonthFromFields and monthDayFromFields
    // =========================================================================

    public function testGregoryYearMonthFromFields(): void
    {
        $ym = GregoryCalendar::instance()->yearMonthFromFields(['year' => 2024, 'month' => 3], 'constrain');
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testGregoryYearMonthFromFieldsWithEra(): void
    {
        $ym = GregoryCalendar::instance()->yearMonthFromFields([
            'era' => 'bce',
            'eraYear' => 1,
            'month' => 6
        ], 'constrain');
        $this->assertSame(0, $ym->year);
        $this->assertSame(6, $ym->month);
    }

    public function testGregoryMonthDayFromFields(): void
    {
        $md = GregoryCalendar::instance()->monthDayFromFields(['month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    // =========================================================================
    // GregoryCalendar — fields() and mergeFields()
    // =========================================================================

    public function testGregoryFieldsAcceptsEraFields(): void
    {
        $result = GregoryCalendar::instance()->fields(['year', 'month', 'day', 'era', 'eraYear']);
        $this->assertSame(['year', 'month', 'day', 'era', 'eraYear'], $result);
    }

    public function testGregoryFieldsRejectsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GregoryCalendar::instance()->fields(['year', 'invalid']);
    }

    public function testGregoryMergeFields(): void
    {
        $result = GregoryCalendar::instance()->mergeFields(['year' => 2024, 'month' => 1], ['month' => 3, 'day' => 15]);
        $this->assertSame(['year' => 2024, 'month' => 3, 'day' => 15], $result);
    }

    // =========================================================================
    // GregoryCalendar — date arithmetic (delegates to ISO)
    // =========================================================================

    public function testGregoryDateAddDays(): void
    {
        $date = new PlainDate(2024, 3, 15, GregoryCalendar::instance());
        $result = GregoryCalendar::instance()->dateAdd($date, ['days' => 10], 'constrain');
        $this->assertSame(2024, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(25, $result->day);
    }

    public function testGregoryDateUntilDays(): void
    {
        $one = new PlainDate(2024, 3, 15);
        $two = new PlainDate(2024, 3, 25);
        $duration = GregoryCalendar::instance()->dateUntil($one, $two, 'day');
        $this->assertSame(10, $duration->days);
    }

    // =========================================================================
    // GregoryCalendar — Calendar::from() integration
    // =========================================================================

    public function testCalendarFromGregoryHasCorrectId(): void
    {
        $cal = Calendar::from('gregory');
        $this->assertSame('gregory', $cal->id);
    }

    public function testCalendarFromGregoryGetProtocol(): void
    {
        $cal = Calendar::from('gregory');
        $this->assertInstanceOf(GregoryCalendar::class, $cal->getProtocol());
    }

    public function testCalendarFromGregoryEra(): void
    {
        $cal = Calendar::from('gregory');
        $date = new PlainDate(2024, 6, 15, GregoryCalendar::instance());
        $this->assertSame('ce', $cal->era($date));
    }

    public function testCalendarFromGregoryEraYear(): void
    {
        $cal = Calendar::from('gregory');
        $date = new PlainDate(2024, 6, 15, GregoryCalendar::instance());
        $this->assertSame(2024, $cal->eraYear($date));
    }

    public function testCalendarFromGregoryBCEEra(): void
    {
        $cal = Calendar::from('gregory');
        $date = new PlainDate(0, 6, 15, GregoryCalendar::instance());
        $this->assertSame('bce', $cal->era($date));
        $this->assertSame(1, $cal->eraYear($date));
    }

    // =========================================================================
    // GregoryCalendar — PlainDate with gregory calendar
    // =========================================================================

    public function testPlainDateWithGregoryCalendarId(): void
    {
        $date = new PlainDate(2024, 3, 15, GregoryCalendar::instance());
        $this->assertSame('gregory', $date->calendarId);
    }

    public function testPlainDateWithGregoryToString(): void
    {
        $date = new PlainDate(2024, 3, 15, GregoryCalendar::instance());
        $this->assertSame('2024-03-15[u-ca=gregory]', (string) $date);
    }

    // =========================================================================
    // BuddhistCalendar — singleton and interface
    // =========================================================================

    public function testBuddhistSingletonReturnsSameInstance(): void
    {
        $a = BuddhistCalendar::instance();
        $b = BuddhistCalendar::instance();
        $this->assertSame($a, $b);
    }

    public function testBuddhistImplementsCalendarProtocol(): void
    {
        $this->assertInstanceOf(CalendarProtocol::class, BuddhistCalendar::instance());
    }

    public function testBuddhistGetId(): void
    {
        $this->assertSame('buddhist', BuddhistCalendar::instance()->getId());
    }

    // =========================================================================
    // BuddhistCalendar — year offset
    // =========================================================================

    public function testBuddhistYearOffset(): void
    {
        // 2024 CE = 2567 BE
        $this->assertSame(2567, BuddhistCalendar::instance()->year(2024, 1, 1));
    }

    public function testBuddhistYearOffset1(): void
    {
        // ISO year 1 = Buddhist year 544
        $this->assertSame(544, BuddhistCalendar::instance()->year(1, 1, 1));
    }

    public function testBuddhistYearOffset0(): void
    {
        // ISO year 0 = Buddhist year 543
        $this->assertSame(543, BuddhistCalendar::instance()->year(0, 1, 1));
    }

    public function testBuddhistYearOffsetNegative(): void
    {
        // ISO year -1 = Buddhist year 542
        $this->assertSame(542, BuddhistCalendar::instance()->year(-1, 1, 1));
    }

    // =========================================================================
    // BuddhistCalendar — era
    // =========================================================================

    public function testBuddhistEraIsAlwaysBe(): void
    {
        $this->assertSame('be', BuddhistCalendar::instance()->era(2024, 1, 1));
        $this->assertSame('be', BuddhistCalendar::instance()->era(0, 1, 1));
        $this->assertSame('be', BuddhistCalendar::instance()->era(-100, 1, 1));
    }

    public function testBuddhistEraYear(): void
    {
        // 2024 CE → era-year 2567
        $this->assertSame(2567, BuddhistCalendar::instance()->eraYear(2024, 3, 15));
    }

    // =========================================================================
    // BuddhistCalendar — month/day pass-through
    // =========================================================================

    public function testBuddhistMonthPassThrough(): void
    {
        $this->assertSame(3, BuddhistCalendar::instance()->month(2024, 3, 15));
    }

    public function testBuddhistDayPassThrough(): void
    {
        $this->assertSame(15, BuddhistCalendar::instance()->day(2024, 3, 15));
    }

    public function testBuddhistMonthCode(): void
    {
        $this->assertSame('M03', BuddhistCalendar::instance()->monthCode(2024, 3, 15));
    }

    // =========================================================================
    // BuddhistCalendar — delegates to ISO for structural fields
    // =========================================================================

    public function testBuddhistDaysInMonthLeapFebruary(): void
    {
        $this->assertSame(29, BuddhistCalendar::instance()->daysInMonth(2024, 2, 1));
    }

    public function testBuddhistDaysInMonthNonLeapFebruary(): void
    {
        $this->assertSame(28, BuddhistCalendar::instance()->daysInMonth(2023, 2, 1));
    }

    public function testBuddhistInLeapYear(): void
    {
        $this->assertTrue(BuddhistCalendar::instance()->inLeapYear(2024, 1, 1));
        $this->assertFalse(BuddhistCalendar::instance()->inLeapYear(2023, 1, 1));
    }

    public function testBuddhistMonthsInYear(): void
    {
        $this->assertSame(12, BuddhistCalendar::instance()->monthsInYear());
    }

    public function testBuddhistDaysInWeek(): void
    {
        $this->assertSame(7, BuddhistCalendar::instance()->daysInWeek());
    }

    public function testBuddhistDayOfWeekDelegatesToIso(): void
    {
        $this->assertSame(
            IsoCalendar::instance()->dayOfWeek(2024, 3, 15),
            BuddhistCalendar::instance()->dayOfWeek(2024, 3, 15)
        );
    }

    // =========================================================================
    // BuddhistCalendar — dateFromFields
    // =========================================================================

    public function testBuddhistDateFromFields(): void
    {
        // Buddhist year 2567 = ISO 2024
        $date = BuddhistCalendar::instance()->dateFromFields(['year' => 2567, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
        $this->assertSame('buddhist', $date->calendarId);
    }

    public function testBuddhistDateFromFieldsConstrainsDay(): void
    {
        // BE 2566 = ISO 2023; Feb has 28 days
        $date = BuddhistCalendar::instance()->dateFromFields(['year' => 2566, 'month' => 2, 'day' => 30], 'constrain');
        $this->assertSame(28, $date->day);
    }

    public function testBuddhistDateFromFieldsRejectsInvalidDay(): void
    {
        $this->expectException(\RangeException::class);
        BuddhistCalendar::instance()->dateFromFields(['year' => 2566, 'month' => 2, 'day' => 30], 'reject');
    }

    // =========================================================================
    // BuddhistCalendar — yearMonthFromFields and monthDayFromFields
    // =========================================================================

    public function testBuddhistYearMonthFromFields(): void
    {
        $ym = BuddhistCalendar::instance()->yearMonthFromFields(['year' => 2567, 'month' => 3], 'constrain');
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testBuddhistMonthDayFromFields(): void
    {
        $md = BuddhistCalendar::instance()->monthDayFromFields(['month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    // =========================================================================
    // BuddhistCalendar — fields() and mergeFields()
    // =========================================================================

    public function testBuddhistFieldsAcceptsStandardFields(): void
    {
        $result = BuddhistCalendar::instance()->fields(['year', 'month', 'day']);
        $this->assertSame(['year', 'month', 'day'], $result);
    }

    public function testBuddhistFieldsRejectsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BuddhistCalendar::instance()->fields(['year', 'era']);
    }

    public function testBuddhistMergeFields(): void
    {
        $result = BuddhistCalendar::instance()->mergeFields(['year' => 2567, 'month' => 1], ['month' => 3]);
        $this->assertSame(['year' => 2567, 'month' => 3], $result);
    }

    // =========================================================================
    // BuddhistCalendar — Calendar::from() integration
    // =========================================================================

    public function testCalendarFromBuddhistHasCorrectId(): void
    {
        $cal = Calendar::from('buddhist');
        $this->assertSame('buddhist', $cal->id);
    }

    public function testCalendarFromBuddhistGetProtocol(): void
    {
        $cal = Calendar::from('buddhist');
        $this->assertInstanceOf(BuddhistCalendar::class, $cal->getProtocol());
    }

    public function testCalendarFromBuddhistEra(): void
    {
        $cal = Calendar::from('buddhist');
        $date = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $this->assertSame('be', $cal->era($date));
    }

    public function testCalendarFromBuddhistEraYear(): void
    {
        $cal = Calendar::from('buddhist');
        $date = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $this->assertSame(2567, $cal->eraYear($date));
    }

    // =========================================================================
    // BuddhistCalendar — PlainDate with buddhist calendar
    // =========================================================================

    public function testPlainDateWithBuddhistCalendarId(): void
    {
        $date = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $this->assertSame('buddhist', $date->calendarId);
    }

    public function testPlainDateWithBuddhistToString(): void
    {
        $date = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $this->assertSame('2024-03-15[u-ca=buddhist]', (string) $date);
    }

    // =========================================================================
    // BuddhistCalendar — date arithmetic
    // =========================================================================

    public function testBuddhistDateAddDays(): void
    {
        $date = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $result = BuddhistCalendar::instance()->dateAdd($date, ['days' => 10], 'constrain');
        $this->assertSame(2024, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(25, $result->day);
    }

    public function testBuddhistDateUntilDays(): void
    {
        $one = new PlainDate(2024, 3, 15, BuddhistCalendar::instance());
        $two = new PlainDate(2024, 3, 25, BuddhistCalendar::instance());
        $duration = BuddhistCalendar::instance()->dateUntil($one, $two, 'day');
        $this->assertSame(10, $duration->days);
    }

    // =========================================================================
    // Calendar::from() — unsupported calendar still throws
    // =========================================================================

    public function testCalendarFromUnsupportedThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Calendar::from('hebrew');
    }

    public function testCalendarFromCaseInsensitiveGregory(): void
    {
        $cal = Calendar::from('Gregory');
        $this->assertSame('gregory', $cal->id);
    }

    public function testCalendarFromCaseInsensitiveBuddhist(): void
    {
        $cal = Calendar::from('Buddhist');
        $this->assertSame('buddhist', $cal->id);
    }
}
