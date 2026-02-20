<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use PHPUnit\Framework\TestCase;
use Temporal\Calendar;
use Temporal\GregoryCalendar;
use Temporal\IsoCalendar;
use Temporal\Now;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainMonthDay;
use Temporal\PlainYearMonth;
use Temporal\TimeZone;
use Temporal\ZonedDateTime;
use Temporal\Exception\UnsupportedCalendarException;

/**
 * Tests for API gaps identified by comparing with the TC39 Temporal proposal:
 *  - monthCode property on all date types
 *  - era / eraYear properties (ISO 8601 → null)
 *  - daysInWeek property
 *  - monthsInYear property
 *  - withCalendar() on PlainDate, PlainYearMonth, PlainMonthDay
 *  - Now::plainYearMonthISO() and Now::plainMonthDayISO()
 */
final class ApiGapsTest extends TestCase
{
    // =========================================================================
    // monthCode on PlainDate
    // =========================================================================

    /** @dataProvider monthCodeProvider */
    public function testPlainDateMonthCode(int $month, string $expectedCode): void
    {
        $date = new PlainDate(2024, $month, 15);
        self::assertSame($expectedCode, $date->monthCode);
    }

    /** @return array<int, array{int, string}> */
    public static function monthCodeProvider(): array
    {
        return [
            [1, 'M01'],
            [2, 'M02'],
            [3, 'M03'],
            [4, 'M04'],
            [5, 'M05'],
            [6, 'M06'],
            [7, 'M07'],
            [8, 'M08'],
            [9, 'M09'],
            [10, 'M10'],
            [11, 'M11'],
            [12, 'M12']
        ];
    }

    public function testPlainDateMonthCodeIsSet(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertTrue(isset($date->monthCode));
    }

    // =========================================================================
    // era / eraYear on PlainDate (ISO 8601 → null)
    // =========================================================================

    public function testPlainDateEraIsNullForIso(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertNull($date->era);
    }

    public function testPlainDateEraYearIsNullForIso(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertNull($date->eraYear);
    }

    public function testPlainDateEraIsSet(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertTrue(isset($date->era));
    }

    public function testPlainDateEraYearIsSet(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertTrue(isset($date->eraYear));
    }

    // =========================================================================
    // daysInWeek on PlainDate
    // =========================================================================

    public function testPlainDateDaysInWeek(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertSame(7, $date->daysInWeek);
    }

    public function testPlainDateDaysInWeekIsSet(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertTrue(isset($date->daysInWeek));
    }

    // =========================================================================
    // monthsInYear on PlainDate
    // =========================================================================

    public function testPlainDateMonthsInYear(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertSame(12, $date->monthsInYear);
    }

    public function testPlainDateMonthsInYearIsSet(): void
    {
        $date = new PlainDate(2024, 3, 15);
        self::assertTrue(isset($date->monthsInYear));
    }

    // =========================================================================
    // monthCode on PlainDateTime
    // =========================================================================

    /** @dataProvider plainDateTimeMonthCodeProvider */
    public function testPlainDateTimeMonthCode(int $month, string $expectedCode): void
    {
        $dt = new PlainDateTime(2024, $month, 15, 10, 30);
        self::assertSame($expectedCode, $dt->monthCode);
    }

    /** @return array<int, array{int, string}> */
    public static function plainDateTimeMonthCodeProvider(): array
    {
        return [
            [1, 'M01'],
            [6, 'M06'],
            [12, 'M12']
        ];
    }

    public function testPlainDateTimeEraIsNull(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        self::assertNull($dt->era);
    }

    public function testPlainDateTimeEraYearIsNull(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        self::assertNull($dt->eraYear);
    }

    public function testPlainDateTimeDaysInWeek(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        self::assertSame(7, $dt->daysInWeek);
    }

    public function testPlainDateTimeMonthsInYear(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        self::assertSame(12, $dt->monthsInYear);
    }

    public function testPlainDateTimeIssetNewProperties(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        self::assertTrue(isset($dt->monthCode));
        self::assertTrue(isset($dt->era));
        self::assertTrue(isset($dt->eraYear));
        self::assertTrue(isset($dt->daysInWeek));
        self::assertTrue(isset($dt->monthsInYear));
    }

    // =========================================================================
    // monthCode on ZonedDateTime
    // =========================================================================

    public function testZonedDateTimeMonthCode(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC'); // 1970-01-01
        self::assertSame('M01', $zdt->monthCode);
    }

    public function testZonedDateTimeMonthCodeMarch(): void
    {
        // 2024-03-15T00:00:00Z
        $instant = PlainDate::from('2024-03-15')->toZonedDateTime('UTC')->toInstant();
        $zdt = $instant->toZonedDateTimeISO(TimeZone::from('UTC'));
        self::assertSame('M03', $zdt->monthCode);
    }

    public function testZonedDateTimeEraIsNull(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertNull($zdt->era);
    }

    public function testZonedDateTimeEraYearIsNull(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertNull($zdt->eraYear);
    }

    public function testZonedDateTimeDaysInWeek(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(7, $zdt->daysInWeek);
    }

    public function testZonedDateTimeMonthsInYear(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertSame(12, $zdt->monthsInYear);
    }

    public function testZonedDateTimeIssetNewProperties(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        self::assertTrue(isset($zdt->monthCode));
        self::assertTrue(isset($zdt->era));
        self::assertTrue(isset($zdt->eraYear));
        self::assertTrue(isset($zdt->daysInWeek));
        self::assertTrue(isset($zdt->monthsInYear));
    }

    // =========================================================================
    // monthCode on PlainYearMonth
    // =========================================================================

    /** @dataProvider plainYearMonthCodeProvider */
    public function testPlainYearMonthMonthCode(int $month, string $expectedCode): void
    {
        $ym = new PlainYearMonth(2024, $month);
        self::assertSame($expectedCode, $ym->monthCode);
    }

    /** @return array<int, array{int, string}> */
    public static function plainYearMonthCodeProvider(): array
    {
        return [
            [1, 'M01'],
            [6, 'M06'],
            [12, 'M12']
        ];
    }

    public function testPlainYearMonthEraIsNull(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        self::assertNull($ym->era);
    }

    public function testPlainYearMonthEraYearIsNull(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        self::assertNull($ym->eraYear);
    }

    public function testPlainYearMonthIssetNewProperties(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        self::assertTrue(isset($ym->monthCode));
        self::assertTrue(isset($ym->era));
        self::assertTrue(isset($ym->eraYear));
    }

    // =========================================================================
    // monthCode on PlainMonthDay
    // =========================================================================

    /** @dataProvider plainMonthDayCodeProvider */
    public function testPlainMonthDayMonthCode(int $month, string $expectedCode): void
    {
        $md = new PlainMonthDay($month, 15);
        self::assertSame($expectedCode, $md->monthCode);
    }

    /** @return array<int, array{int, string}> */
    public static function plainMonthDayCodeProvider(): array
    {
        return [
            [1, 'M01'],
            [6, 'M06'],
            [12, 'M12']
        ];
    }

    public function testPlainMonthDayMonthCodeIsSet(): void
    {
        $md = new PlainMonthDay(3, 15);
        self::assertTrue(isset($md->monthCode));
    }

    // =========================================================================
    // GregoryCalendar era properties via PlainDate
    // =========================================================================

    public function testPlainDateWithGregoryCalendarEra(): void
    {
        $date = new PlainDate(2024, 3, 15, GregoryCalendar::instance());
        self::assertSame('ce', $date->era);
    }

    public function testPlainDateWithGregoryCalendarEraYear(): void
    {
        $date = new PlainDate(2024, 3, 15, GregoryCalendar::instance());
        self::assertSame(2024, $date->eraYear);
    }

    public function testPlainDateWithGregoryCalendarBceEra(): void
    {
        $date = new PlainDate(-100, 1, 1, GregoryCalendar::instance());
        self::assertSame('bce', $date->era);
    }

    public function testPlainDateWithGregoryCalendarBceEraYear(): void
    {
        // ISO year 0 = 1 BCE; year -1 = 2 BCE
        $date = new PlainDate(0, 1, 1, GregoryCalendar::instance());
        self::assertSame(1, $date->eraYear);
    }

    public function testPlainDateWithGregoryCalendarMonthCode(): void
    {
        $date = new PlainDate(2024, 6, 15, GregoryCalendar::instance());
        self::assertSame('M06', $date->monthCode);
    }

    // =========================================================================
    // withCalendar() on PlainDate
    // =========================================================================

    public function testPlainDateWithCalendarStringIso(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $same = $date->withCalendar('iso8601');
        self::assertSame('iso8601', $same->calendarId);
        self::assertSame(2024, $same->year);
        self::assertSame(3, $same->month);
        self::assertSame(15, $same->day);
    }

    public function testPlainDateWithCalendarStringGregory(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->withCalendar('gregory');
        self::assertSame('gregory', $result->calendarId);
        self::assertSame(2024, $result->year);
        self::assertSame('ce', $result->era);
    }

    public function testPlainDateWithCalendarProtocol(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $result = $date->withCalendar(GregoryCalendar::instance());
        self::assertSame('gregory', $result->calendarId);
    }

    public function testPlainDateWithCalendarObject(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $cal = Calendar::from('gregory');
        $result = $date->withCalendar($cal);
        self::assertSame('gregory', $result->calendarId);
    }

    public function testPlainDateWithCalendarReturnsSameYearMonthDay(): void
    {
        $date = new PlainDate(2024, 6, 20);
        $result = $date->withCalendar('iso8601');
        self::assertSame(2024, $result->year);
        self::assertSame(6, $result->month);
        self::assertSame(20, $result->day);
    }

    public function testPlainDateWithCalendarThrowsForUnknown(): void
    {
        $date = new PlainDate(2024, 3, 15);
        $this->expectException(\InvalidArgumentException::class);
        $date->withCalendar('unknown-calendar');
    }

    // =========================================================================
    // withCalendar() on PlainYearMonth
    // =========================================================================

    public function testPlainYearMonthWithCalendarIso(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->withCalendar('iso8601');
        self::assertSame(2024, $result->year);
        self::assertSame(3, $result->month);
    }

    public function testPlainYearMonthWithCalendarProtocol(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->withCalendar(IsoCalendar::instance());
        self::assertSame(2024, $result->year);
        self::assertSame(3, $result->month);
    }

    public function testPlainYearMonthWithCalendarObject(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $cal = Calendar::from('iso8601');
        $result = $ym->withCalendar($cal);
        self::assertSame(2024, $result->year);
        self::assertSame(3, $result->month);
    }

    public function testPlainYearMonthWithCalendarThrowsForNonIso(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->expectException(UnsupportedCalendarException::class);
        $ym->withCalendar('gregory');
    }

    // =========================================================================
    // withCalendar() on PlainMonthDay
    // =========================================================================

    public function testPlainMonthDayWithCalendarIso(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->withCalendar('iso8601');
        self::assertSame(3, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testPlainMonthDayWithCalendarProtocol(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->withCalendar(IsoCalendar::instance());
        self::assertSame(3, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testPlainMonthDayWithCalendarObject(): void
    {
        $md = new PlainMonthDay(3, 15);
        $cal = Calendar::from('iso8601');
        $result = $md->withCalendar($cal);
        self::assertSame(3, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testPlainMonthDayWithCalendarThrowsForNonIso(): void
    {
        $md = new PlainMonthDay(3, 15);
        $this->expectException(UnsupportedCalendarException::class);
        $md->withCalendar('buddhist');
    }

    // =========================================================================
    // Now::plainYearMonthISO()
    // =========================================================================

    public function testNowPlainYearMonthISOReturnsInstance(): void
    {
        $ym = Now::plainYearMonthISO();
        self::assertInstanceOf(PlainYearMonth::class, $ym);
    }

    public function testNowPlainYearMonthISOWithUtc(): void
    {
        $ym = Now::plainYearMonthISO('UTC');
        self::assertGreaterThanOrEqual(2020, $ym->year);
        self::assertLessThanOrEqual(2030, $ym->year);
        self::assertGreaterThanOrEqual(1, $ym->month);
        self::assertLessThanOrEqual(12, $ym->month);
    }

    public function testNowPlainYearMonthISOWithTimezoneObject(): void
    {
        $tz = TimeZone::from('UTC');
        $ym = Now::plainYearMonthISO($tz);
        self::assertInstanceOf(PlainYearMonth::class, $ym);
    }

    public function testNowPlainYearMonthISOMatchesZonedDateTime(): void
    {
        $zdt = Now::zonedDateTimeISO('UTC');
        $ym = Now::plainYearMonthISO('UTC');
        // year and month should match (within same second)
        self::assertSame($zdt->year, $ym->year);
        self::assertSame($zdt->month, $ym->month);
    }

    // =========================================================================
    // Now::plainMonthDayISO()
    // =========================================================================

    public function testNowPlainMonthDayISOReturnsInstance(): void
    {
        $md = Now::plainMonthDayISO();
        self::assertInstanceOf(PlainMonthDay::class, $md);
    }

    public function testNowPlainMonthDayISOWithUtc(): void
    {
        $md = Now::plainMonthDayISO('UTC');
        self::assertGreaterThanOrEqual(1, $md->month);
        self::assertLessThanOrEqual(12, $md->month);
        self::assertGreaterThanOrEqual(1, $md->day);
        self::assertLessThanOrEqual(31, $md->day);
    }

    public function testNowPlainMonthDayISOWithTimezoneObject(): void
    {
        $tz = TimeZone::from('UTC');
        $md = Now::plainMonthDayISO($tz);
        self::assertInstanceOf(PlainMonthDay::class, $md);
    }

    public function testNowPlainMonthDayISOMatchesZonedDateTime(): void
    {
        $zdt = Now::zonedDateTimeISO('UTC');
        $md = Now::plainMonthDayISO('UTC');
        // month and day should match (within same second)
        self::assertSame($zdt->month, $md->month);
        self::assertSame($zdt->day, $md->day);
    }
}
