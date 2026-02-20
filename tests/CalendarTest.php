<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Calendar;
use Temporal\Duration;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainMonthDay;
use Temporal\PlainYearMonth;
use Temporal\ZonedDateTime;

/**
 * Tests for Calendar — the ISO 8601 calendar implementation.
 */
final class CalendarTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testFromIso8601(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame('iso8601', $cal->id);
    }

    public function testFromIso8601CaseInsensitive(): void
    {
        $cal = Calendar::from('ISO8601');
        $this->assertSame('iso8601', $cal->id);
    }

    public function testFromCalendarCopies(): void
    {
        $cal1 = Calendar::from('iso8601');
        $cal2 = Calendar::from($cal1);
        $this->assertSame('iso8601', $cal2->id);
    }

    public function testFromUnknownCalendarThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Calendar::from('hebrew');
    }

    public function testFromGregorianThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Calendar::from('gregorian');
    }

    // -------------------------------------------------------------------------
    // Properties and toString
    // -------------------------------------------------------------------------

    public function testIdProperty(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame('iso8601', $cal->id);
    }

    public function testToString(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame('iso8601', (string) $cal);
    }

    public function testEquals(): void
    {
        $cal1 = Calendar::from('iso8601');
        $cal2 = Calendar::from('iso8601');
        $this->assertTrue($cal1->equals($cal2));
    }

    // -------------------------------------------------------------------------
    // dateFromFields
    // -------------------------------------------------------------------------

    public function testDateFromFields(): void
    {
        $cal = Calendar::from('iso8601');
        $date = $cal->dateFromFields(['year' => 2024, 'month' => 3, 'day' => 15]);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testDateFromFieldsLeapDay(): void
    {
        $cal = Calendar::from('iso8601');
        $date = $cal->dateFromFields(['year' => 2000, 'month' => 2, 'day' => 29]);
        $this->assertSame(2000, $date->year);
        $this->assertSame(2, $date->month);
        $this->assertSame(29, $date->day);
    }

    public function testDateFromFieldsMissingYearThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->dateFromFields(['month' => 3, 'day' => 15]);
    }

    public function testDateFromFieldsMissingMonthThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->dateFromFields(['year' => 2024, 'day' => 15]);
    }

    public function testDateFromFieldsMissingDayThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->dateFromFields(['year' => 2024, 'month' => 3]);
    }

    public function testDateFromFieldsInvalidOverflowThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->dateFromFields(['year' => 2024, 'month' => 3, 'day' => 15], 'invalid');
    }

    // -------------------------------------------------------------------------
    // yearMonthFromFields
    // -------------------------------------------------------------------------

    public function testYearMonthFromFields(): void
    {
        $cal = Calendar::from('iso8601');
        $ym = $cal->yearMonthFromFields(['year' => 2024, 'month' => 3]);
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testYearMonthFromFieldsMissingYearThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->yearMonthFromFields(['month' => 3]);
    }

    public function testYearMonthFromFieldsMissingMonthThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->yearMonthFromFields(['year' => 2024]);
    }

    public function testYearMonthFromFieldsInvalidOverflowThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->yearMonthFromFields(['year' => 2024, 'month' => 3], 'invalid');
    }

    // -------------------------------------------------------------------------
    // monthDayFromFields
    // -------------------------------------------------------------------------

    public function testMonthDayFromFields(): void
    {
        $cal = Calendar::from('iso8601');
        $md = $cal->monthDayFromFields(['month' => 3, 'day' => 15]);
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    public function testMonthDayFromFieldsMissingMonthThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->monthDayFromFields(['day' => 15]);
    }

    public function testMonthDayFromFieldsMissingDayThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->monthDayFromFields(['month' => 3]);
    }

    public function testMonthDayFromFieldsInvalidOverflowThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->monthDayFromFields(['month' => 3, 'day' => 15], 'invalid');
    }

    // -------------------------------------------------------------------------
    // dateAdd
    // -------------------------------------------------------------------------

    public function testDateAdd(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $duration = new Duration(days: 10);
        $result = $cal->dateAdd($date, $duration);
        $this->assertSame('2024-03-25', (string) $result);
    }

    public function testDateAddMonths(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 1, 31);
        $duration = new Duration(months: 1);
        $result = $cal->dateAdd($date, $duration);
        // Jan 31 + 1 month constrains to Feb 29 (2024 is leap year)
        $this->assertSame('2024-02-29', (string) $result);
    }

    public function testDateAddYears(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 6, 15);
        $duration = new Duration(years: 2);
        $result = $cal->dateAdd($date, $duration);
        $this->assertSame('2026-06-15', (string) $result);
    }

    public function testDateAddNegative(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $duration = new Duration(days: -5);
        $result = $cal->dateAdd($date, $duration);
        $this->assertSame('2024-03-10', (string) $result);
    }

    public function testDateAddWeeks(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $duration = new Duration(weeks: 2);
        $result = $cal->dateAdd($date, $duration);
        // 2 weeks = 14 days → 2024-03-29
        $this->assertSame('2024-03-29', (string) $result);
    }

    public function testDateAddInvalidOverflowThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $duration = new Duration(days: 1);
        $this->expectException(InvalidArgumentException::class);
        $cal->dateAdd($date, $duration, 'invalid');
    }

    // -------------------------------------------------------------------------
    // dateUntil
    // -------------------------------------------------------------------------

    public function testDateUntil(): void
    {
        $cal = Calendar::from('iso8601');
        $one = new PlainDate(2024, 3, 15);
        $two = new PlainDate(2024, 3, 25);
        $duration = $cal->dateUntil($one, $two);
        $this->assertSame(10, $duration->days);
    }

    public function testDateUntilNegative(): void
    {
        $cal = Calendar::from('iso8601');
        $one = new PlainDate(2024, 3, 25);
        $two = new PlainDate(2024, 3, 15);
        $duration = $cal->dateUntil($one, $two);
        $this->assertSame(-10, $duration->days);
    }

    public function testDateUntilSameDate(): void
    {
        $cal = Calendar::from('iso8601');
        $one = new PlainDate(2024, 3, 15);
        $two = new PlainDate(2024, 3, 15);
        $duration = $cal->dateUntil($one, $two);
        $this->assertTrue($duration->blank);
    }

    // -------------------------------------------------------------------------
    // fields and mergeFields
    // -------------------------------------------------------------------------

    public function testFields(): void
    {
        $cal = Calendar::from('iso8601');
        $result = $cal->fields(['year', 'month', 'day']);
        $this->assertSame(['year', 'month', 'day'], $result);
    }

    public function testFieldsSubset(): void
    {
        $cal = Calendar::from('iso8601');
        $result = $cal->fields(['month', 'day']);
        $this->assertSame(['month', 'day'], $result);
    }

    public function testFieldsInvalidThrows(): void
    {
        $cal = Calendar::from('iso8601');
        $this->expectException(InvalidArgumentException::class);
        $cal->fields(['year', 'invalid']);
    }

    public function testFieldsReturnsIndexedArray(): void
    {
        $cal = Calendar::from('iso8601');
        // Pass array with non-sequential integer keys; result must be re-indexed.
        $result = $cal->fields([2 => 'year', 5 => 'month']);
        $this->assertSame([0 => 'year', 1 => 'month'], $result);
    }

    public function testMergeFields(): void
    {
        $cal = Calendar::from('iso8601');
        $base = ['year' => 2024, 'month' => 1];
        $override = ['month' => 3, 'day' => 15];
        $result = $cal->mergeFields($base, $override);
        $this->assertSame(['year' => 2024, 'month' => 3, 'day' => 15], $result);
    }

    public function testMergeFieldsAdditionalTakesPrecedence(): void
    {
        $cal = Calendar::from('iso8601');
        $base = ['year' => 2024, 'month' => 1, 'day' => 1];
        $override = ['year' => 2025, 'month' => 6];
        $result = $cal->mergeFields($base, $override);
        $this->assertSame(2025, $result['year']);
        $this->assertSame(6, $result['month']);
        $this->assertSame(1, $result['day']);
    }

    // -------------------------------------------------------------------------
    // Computed property methods
    // -------------------------------------------------------------------------

    public function testYear(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(2024, $cal->year($date));
    }

    public function testMonth(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(3, $cal->month($date));
    }

    public function testMonthCodeSingleDigit(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame('M03', $cal->monthCode($date));
    }

    public function testMonthCodeDoubleDigit(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 12, 1);
        $this->assertSame('M12', $cal->monthCode($date));
    }

    public function testDay(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(15, $cal->day($date));
    }

    public function testDayOfWeek(): void
    {
        $cal = Calendar::from('iso8601');
        // 2024-03-15 is a Friday (5)
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame(5, $cal->dayOfWeek($date));
    }

    public function testDayOfYear(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 15);
        // Jan: 31, Feb: 29 (leap), Mar: 15 = 75
        $this->assertSame(75, $cal->dayOfYear($date));
    }

    public function testWeekOfYear(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 1, 1);
        $this->assertSame(1, $cal->weekOfYear($date));
    }

    public function testDaysInWeek(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame(7, $cal->daysInWeek());
    }

    public function testDaysInMonthRegular(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 3, 1);
        $this->assertSame(31, $cal->daysInMonth($date));
    }

    public function testDaysInMonthLeapFebruary(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 2, 1);
        $this->assertSame(29, $cal->daysInMonth($date));
    }

    public function testDaysInMonthNonLeapFebruary(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2023, 2, 1);
        $this->assertSame(28, $cal->daysInMonth($date));
    }

    public function testDaysInYearLeap(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 1, 1);
        $this->assertSame(366, $cal->daysInYear($date));
    }

    public function testDaysInYearNonLeap(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2023, 1, 1);
        $this->assertSame(365, $cal->daysInYear($date));
    }

    public function testMonthsInYear(): void
    {
        $cal = Calendar::from('iso8601');
        $this->assertSame(12, $cal->monthsInYear());
    }

    public function testInLeapYearTrue(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 6, 15);
        $this->assertTrue($cal->inLeapYear($date));
    }

    public function testInLeapYearFalse(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2023, 6, 15);
        $this->assertFalse($cal->inLeapYear($date));
    }

    public function testEraReturnsNull(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 6, 15);
        $this->assertNull($cal->era($date));
    }

    public function testEraYearReturnsNull(): void
    {
        $cal = Calendar::from('iso8601');
        $date = new PlainDate(2024, 6, 15);
        $this->assertNull($cal->eraYear($date));
    }

    // -------------------------------------------------------------------------
    // Methods work with PlainDateTime too
    // -------------------------------------------------------------------------

    public function testYearFromPlainDateTime(): void
    {
        $cal = Calendar::from('iso8601');
        $dt = new PlainDateTime(2024, 3, 15, 10, 30);
        $this->assertSame(2024, $cal->year($dt));
    }

    public function testMonthCodeFromPlainDateTime(): void
    {
        $cal = Calendar::from('iso8601');
        $dt = new PlainDateTime(2024, 3, 15, 10, 30);
        $this->assertSame('M03', $cal->monthCode($dt));
    }

    public function testDayOfWeekFromPlainDateTime(): void
    {
        $cal = Calendar::from('iso8601');
        // 2024-03-15 is Friday = 5
        $dt = new PlainDateTime(2024, 3, 15, 10, 30);
        $this->assertSame(5, $cal->dayOfWeek($dt));
    }

    // -------------------------------------------------------------------------
    // Methods work with PlainYearMonth too
    // -------------------------------------------------------------------------

    public function testYearFromPlainYearMonth(): void
    {
        $cal = Calendar::from('iso8601');
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame(2024, $cal->year($ym));
    }

    public function testDaysInMonthFromPlainYearMonth(): void
    {
        $cal = Calendar::from('iso8601');
        $ym = new PlainYearMonth(2024, 2);
        $this->assertSame(29, $cal->daysInMonth($ym));
    }

    public function testInLeapYearFromPlainYearMonth(): void
    {
        $cal = Calendar::from('iso8601');
        $ym = new PlainYearMonth(2024, 1);
        $this->assertTrue($cal->inLeapYear($ym));
    }

    // -------------------------------------------------------------------------
    // Methods work with PlainMonthDay too
    // -------------------------------------------------------------------------

    public function testMonthFromPlainMonthDay(): void
    {
        $cal = Calendar::from('iso8601');
        $md = new PlainMonthDay(3, 15);
        $this->assertSame(3, $cal->month($md));
    }

    public function testDayFromPlainMonthDay(): void
    {
        $cal = Calendar::from('iso8601');
        $md = new PlainMonthDay(3, 15);
        $this->assertSame(15, $cal->day($md));
    }

    public function testMonthCodeFromPlainMonthDay(): void
    {
        $cal = Calendar::from('iso8601');
        $md = new PlainMonthDay(3, 15);
        $this->assertSame('M03', $cal->monthCode($md));
    }

    // -------------------------------------------------------------------------
    // calendarId on existing types
    // -------------------------------------------------------------------------

    public function testPlainDateCalendarId(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $this->assertSame('iso8601', $date->calendarId);
    }

    public function testPlainDateTimeCalendarId(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30);
        $this->assertSame('iso8601', $dt->calendarId);
    }

    public function testPlainYearMonthCalendarId(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame('iso8601', $ym->calendarId);
    }

    public function testPlainMonthDayCalendarId(): void
    {
        $md = new PlainMonthDay(3, 15);
        $this->assertSame('iso8601', $md->calendarId);
    }

    public function testZonedDateTimeCalendarId(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $this->assertSame('iso8601', $zdt->calendarId);
    }
}
