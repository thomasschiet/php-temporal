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
use Temporal\JapaneseCalendar;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainMonthDay;
use Temporal\PlainYearMonth;
use Temporal\RocCalendar;
use Temporal\TimeZone;
use Temporal\ZonedDateTime;

/**
 * Tests for CalendarProtocol support in PlainDateTime and ZonedDateTime,
 * and for the RocCalendar and JapaneseCalendar implementations.
 */
final class CalendarSupportTest extends TestCase
{
    // =========================================================================
    // PlainDateTime — CalendarProtocol support
    // =========================================================================

    public function testPlainDateTimeDefaultCalendarIsIso(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15);
        $this->assertSame('iso8601', $pdt->calendarId);
    }

    public function testPlainDateTimeWithGregoryCalendar(): void
    {
        $pdt = new PlainDateTime(1, 1, 1, 0, 0, 0, 0, 0, 0, GregoryCalendar::instance());
        $this->assertSame('gregory', $pdt->calendarId);
        $this->assertSame('ce', $pdt->era);
        $this->assertSame(1, $pdt->eraYear);
    }

    public function testPlainDateTimeWithCalendarMethod(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15);
        $pdt2 = $pdt->withCalendar('gregory');
        $this->assertSame('gregory', $pdt2->calendarId);
        $this->assertSame(2024, $pdt2->year);
        $this->assertSame(3, $pdt2->month);
        $this->assertSame(15, $pdt2->day);
    }

    public function testPlainDateTimeWithCalendarProtocolInstance(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15);
        $pdt2 = $pdt->withCalendar(GregoryCalendar::instance());
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeWithCalendarCalendarInstance(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15);
        $cal = Calendar::from('gregory');
        $pdt2 = $pdt->withCalendar($cal);
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeGetCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15, 0, 0, 0, 0, 0, 0, GregoryCalendar::instance());
        $this->assertSame(GregoryCalendar::instance(), $pdt->getCalendar());
    }

    public function testPlainDateTimeCalendarPreservedInWith(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $pdt2 = $pdt->with(['month' => 6]);
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeCalendarPreservedInAdd(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $pdt2 = $pdt->add(['days' => 1]);
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeCalendarPreservedInSubtract(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $pdt2 = $pdt->subtract(['days' => 1]);
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeCalendarPreservedInRound(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15, 12)->withCalendar('gregory');
        $pdt2 = $pdt->round('day');
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeCalendarPreservedInWithPlainTime(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $pdt2 = $pdt->withPlainTime(new \Temporal\PlainTime(12, 0, 0));
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeWithPlainDateTakesCalendarFromDate(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $buddhistDate = new PlainDate(2024, 6, 1)->withCalendar('buddhist');
        $pdt2 = $pdt->withPlainDate($buddhistDate);
        $this->assertSame('buddhist', $pdt2->calendarId);
    }

    public function testPlainDateTimeFromPreservesCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $pdt2 = PlainDateTime::from($pdt);
        $this->assertSame('gregory', $pdt2->calendarId);
    }

    public function testPlainDateTimeToStringAppendCalendarForNonIso(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15, 12, 30)->withCalendar('gregory');
        $str = (string) $pdt;
        $this->assertStringContainsString('[u-ca=gregory]', $str);
    }

    public function testPlainDateTimeToStringNoAnnotationForIso(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15);
        $this->assertStringNotContainsString('[u-ca=', (string) $pdt);
    }

    public function testPlainDateTimeFromStringParsesCalendarAnnotation(): void
    {
        $pdt = PlainDateTime::from('2024-03-15T12:30:00[u-ca=gregory]');
        $this->assertSame('gregory', $pdt->calendarId);
    }

    public function testPlainDateTimeFromStringUnknownCalendarFallsBackToIso(): void
    {
        $pdt = PlainDateTime::from('2024-03-15T12:30:00[u-ca=unknown-cal]');
        $this->assertSame('iso8601', $pdt->calendarId);
    }

    public function testPlainDateTimeToPlainDatePreservesCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $date = $pdt->toPlainDate();
        $this->assertSame('gregory', $date->calendarId);
    }

    public function testPlainDateTimeGetISOFieldsCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('gregory');
        $fields = $pdt->getISOFields();
        $this->assertSame('gregory', $fields['calendar']);
    }

    public function testPlainDateTimeBceEraFields(): void
    {
        $pdt = new PlainDateTime(0, 1, 1, 0, 0, 0, 0, 0, 0, GregoryCalendar::instance());
        $this->assertSame('bce', $pdt->era);
        $this->assertSame(1, $pdt->eraYear);
    }

    // =========================================================================
    // ZonedDateTime — CalendarProtocol support
    // =========================================================================

    public function testZonedDateTimeDefaultCalendarIsIso(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $this->assertSame('iso8601', $zdt->calendarId);
    }

    public function testZonedDateTimeWithCalendar(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $zdt2 = $zdt->withCalendar('gregory');
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeCalendarPreservedInFrom(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $zdt2 = ZonedDateTime::from($zdt);
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeCalendarPreservedInWithTimeZone(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $zdt2 = $zdt->withTimeZone('America/New_York');
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeCalendarPreservedInAdd(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $zdt2 = $zdt->add(['days' => 1]);
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeCalendarPreservedInRound(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $zdt2 = $zdt->round('hour');
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeCalendarPreservedInWithPlainTime(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $zdt2 = $zdt->withPlainTime();
        $this->assertSame('gregory', $zdt2->calendarId);
    }

    public function testZonedDateTimeToPlainDateTimePreservesCalendar(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $pdt = $zdt->toPlainDateTime();
        $this->assertSame('gregory', $pdt->calendarId);
    }

    public function testZonedDateTimeToPlainDatePreservesCalendar(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $date = $zdt->toPlainDate();
        $this->assertSame('gregory', $date->calendarId);
    }

    public function testZonedDateTimeEraFieldWithGregoryCalendar(): void
    {
        // 1970-01-01 UTC with Gregory calendar
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $this->assertSame('ce', $zdt->era);
        $this->assertSame(1970, $zdt->eraYear);
    }

    public function testZonedDateTimeGetISOFieldsCalendar(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $fields = $zdt->getISOFields();
        $this->assertSame('gregory', $fields['calendar']);
    }

    public function testZonedDateTimeToStringAppendCalendarForNonIso(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $str = (string) $zdt;
        $this->assertStringContainsString('[u-ca=gregory]', $str);
    }

    public function testZonedDateTimeToStringNoAnnotationForIso(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC');
        $this->assertStringNotContainsString('[u-ca=', (string) $zdt);
    }

    public function testZonedDateTimeFromStringParsesCalendarAnnotation(): void
    {
        $zdt = ZonedDateTime::from('1970-01-01T00:00:00Z[UTC][u-ca=gregory]');
        $this->assertSame('gregory', $zdt->calendarId);
    }

    public function testZonedDateTimeFromStringUnknownCalendarFallsBackToIso(): void
    {
        $zdt = ZonedDateTime::from('1970-01-01T00:00:00Z[UTC][u-ca=unknown-xyz]');
        $this->assertSame('iso8601', $zdt->calendarId);
    }

    public function testZonedDateTimeGetCalendar(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $this->assertSame(GregoryCalendar::instance(), $zdt->getCalendar());
    }

    public function testZonedDateTimeWithPlainDateTakesCalendarFromDate(): void
    {
        $zdt = ZonedDateTime::fromEpochNanoseconds(0, 'UTC', GregoryCalendar::instance());
        $buddhistDate = new PlainDate(2024, 6, 1)->withCalendar('buddhist');
        $zdt2 = $zdt->withPlainDate($buddhistDate);
        $this->assertSame('buddhist', $zdt2->calendarId);
    }

    public function testPlainDateToZonedDateTimePreservesCalendar(): void
    {
        $date = new PlainDate(2024, 3, 15)->withCalendar('gregory');
        $zdt = $date->toZonedDateTime('UTC');
        $this->assertSame('gregory', $zdt->calendarId);
    }

    public function testPlainDateToPlainDateTimePreservesCalendar(): void
    {
        $date = new PlainDate(2024, 3, 15)->withCalendar('gregory');
        $pdt = $date->toPlainDateTime();
        $this->assertSame('gregory', $pdt->calendarId);
    }

    // =========================================================================
    // RocCalendar — singleton and interface
    // =========================================================================

    public function testRocSingletonReturnsSameInstance(): void
    {
        $a = RocCalendar::instance();
        $b = RocCalendar::instance();
        $this->assertSame($a, $b);
    }

    public function testRocImplementsCalendarProtocol(): void
    {
        $this->assertInstanceOf(CalendarProtocol::class, RocCalendar::instance());
    }

    public function testRocGetId(): void
    {
        $this->assertSame('roc', RocCalendar::instance()->getId());
    }

    // =========================================================================
    // RocCalendar — year and era
    // =========================================================================

    public function testRocYear2024IsYear113(): void
    {
        $this->assertSame(113, RocCalendar::instance()->year(2024, 1, 1));
    }

    public function testRocYear1912IsYear1(): void
    {
        $this->assertSame(1, RocCalendar::instance()->year(1912, 1, 1));
    }

    public function testRocYear1911IsBeforeRoc(): void
    {
        $this->assertSame('before-roc', RocCalendar::instance()->era(1911, 12, 31));
        $this->assertSame(1, RocCalendar::instance()->eraYear(1911, 12, 31));
    }

    public function testRocYear1910IsBeforeRocYear2(): void
    {
        $this->assertSame(2, RocCalendar::instance()->eraYear(1910, 1, 1));
    }

    public function testRocEraForCEYear(): void
    {
        $this->assertSame('roc', RocCalendar::instance()->era(2024, 3, 15));
    }

    public function testRocEraYearFor2024(): void
    {
        $this->assertSame(113, RocCalendar::instance()->eraYear(2024, 3, 15));
    }

    public function testRocMonthPassThrough(): void
    {
        $this->assertSame(3, RocCalendar::instance()->month(2024, 3, 15));
    }

    public function testRocDayPassThrough(): void
    {
        $this->assertSame(15, RocCalendar::instance()->day(2024, 3, 15));
    }

    public function testRocMonthCode(): void
    {
        $this->assertSame('M03', RocCalendar::instance()->monthCode(2024, 3, 15));
    }

    public function testRocDaysInMonth(): void
    {
        $this->assertSame(29, RocCalendar::instance()->daysInMonth(2024, 2, 1)); // 2024 is leap
        $this->assertSame(28, RocCalendar::instance()->daysInMonth(2023, 2, 1)); // 2023 not leap
    }

    public function testRocInLeapYear(): void
    {
        $this->assertTrue(RocCalendar::instance()->inLeapYear(2024, 1, 1));
        $this->assertFalse(RocCalendar::instance()->inLeapYear(2023, 1, 1));
    }

    public function testRocMonthsInYear(): void
    {
        $this->assertSame(12, RocCalendar::instance()->monthsInYear());
    }

    public function testRocDaysInWeek(): void
    {
        $this->assertSame(7, RocCalendar::instance()->daysInWeek());
    }

    // =========================================================================
    // RocCalendar — factory methods
    // =========================================================================

    public function testRocDateFromFieldsWithYear(): void
    {
        $roc = RocCalendar::instance();
        // ROC year 113, month 3, day 15 = ISO 2024-03-15
        $date = $roc->dateFromFields(['year' => 113, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testRocDateFromFieldsWithEra(): void
    {
        $roc = RocCalendar::instance();
        $date = $roc->dateFromFields(['era' => 'roc', 'eraYear' => 113, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
    }

    public function testRocDateFromFieldsBeforeRocEra(): void
    {
        $roc = RocCalendar::instance();
        // before-roc year 1 = ISO 1911
        $date = $roc->dateFromFields(['era' => 'before-roc', 'eraYear' => 1, 'month' => 1, 'day' => 1], 'constrain');
        $this->assertSame(1911, $date->year);
    }

    public function testRocDateFromFieldsOverflowConstrain(): void
    {
        $roc = RocCalendar::instance();
        $date = $roc->dateFromFields(['year' => 113, 'month' => 2, 'day' => 30], 'constrain');
        $this->assertSame(29, $date->day); // 2024 is leap → Feb has 29 days
    }

    public function testRocCalendarFromCalendarFactory(): void
    {
        $cal = Calendar::from('roc');
        $this->assertSame('roc', $cal->id);
    }

    public function testPlainDateWithRocCalendar(): void
    {
        $date = new PlainDate(2024, 3, 15)->withCalendar('roc');
        $this->assertSame('roc', $date->calendarId);
        $this->assertSame('roc', $date->era);
        $this->assertSame(113, $date->eraYear);
    }

    public function testPlainDateTimeWithRocCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('roc');
        $this->assertSame('roc', $pdt->calendarId);
        $this->assertSame(113, $pdt->eraYear);
    }

    public function testRocToStringIncludesAnnotation(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('roc');
        $this->assertStringContainsString('[u-ca=roc]', (string) $pdt);
    }

    // =========================================================================
    // JapaneseCalendar — singleton and interface
    // =========================================================================

    public function testJapaneseSingletonReturnsSameInstance(): void
    {
        $a = JapaneseCalendar::instance();
        $b = JapaneseCalendar::instance();
        $this->assertSame($a, $b);
    }

    public function testJapaneseImplementsCalendarProtocol(): void
    {
        $this->assertInstanceOf(CalendarProtocol::class, JapaneseCalendar::instance());
    }

    public function testJapaneseGetId(): void
    {
        $this->assertSame('japanese', JapaneseCalendar::instance()->getId());
    }

    // =========================================================================
    // JapaneseCalendar — era assignment
    // =========================================================================

    public function testJapaneseReiwa2024(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('reiwa', $jp->era(2024, 3, 15));
        $this->assertSame(6, $jp->eraYear(2024, 3, 15)); // Reiwa 1 = 2019 → 2024-2019+1=6
    }

    public function testJapaneseReiwaStartDate(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('reiwa', $jp->era(2019, 5, 1));
        $this->assertSame(1, $jp->eraYear(2019, 5, 1));
    }

    public function testJapaneseLastDayHeisei(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('heisei', $jp->era(2019, 4, 30));
        $this->assertSame(31, $jp->eraYear(2019, 4, 30)); // Heisei 1=1989 → 2019-1989+1=31
    }

    public function testJapaneseHeisei1989(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('heisei', $jp->era(1989, 1, 8));
        $this->assertSame(1, $jp->eraYear(1989, 1, 8));
    }

    public function testJapaneseLastDayShowa(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('showa', $jp->era(1989, 1, 7));
        $this->assertSame(64, $jp->eraYear(1989, 1, 7)); // Showa 1=1926 → 1989-1926+1=64
    }

    public function testJapaneseShowa1(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('showa', $jp->era(1926, 12, 25));
        $this->assertSame(1, $jp->eraYear(1926, 12, 25));
    }

    public function testJapaneseTaisho1(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('taisho', $jp->era(1912, 7, 30));
        $this->assertSame(1, $jp->eraYear(1912, 7, 30));
    }

    public function testJapaneseMeiji1(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('meiji', $jp->era(1868, 1, 1));
        $this->assertSame(1, $jp->eraYear(1868, 1, 1));
    }

    public function testJapaneseBeforeMeijiFallback(): void
    {
        $jp = JapaneseCalendar::instance();
        $this->assertSame('japanese', $jp->era(1867, 12, 31));
    }

    // =========================================================================
    // JapaneseCalendar — field pass-through
    // =========================================================================

    public function testJapaneseMonthPassThrough(): void
    {
        $this->assertSame(3, JapaneseCalendar::instance()->month(2024, 3, 15));
    }

    public function testJapaneseDayPassThrough(): void
    {
        $this->assertSame(15, JapaneseCalendar::instance()->day(2024, 3, 15));
    }

    public function testJapaneseMonthCode(): void
    {
        $this->assertSame('M03', JapaneseCalendar::instance()->monthCode(2024, 3, 15));
    }

    public function testJapaneseDaysInMonth(): void
    {
        $this->assertSame(29, JapaneseCalendar::instance()->daysInMonth(2024, 2, 1));
    }

    public function testJapaneseInLeapYear(): void
    {
        $this->assertTrue(JapaneseCalendar::instance()->inLeapYear(2024, 1, 1));
        $this->assertFalse(JapaneseCalendar::instance()->inLeapYear(2023, 1, 1));
    }

    public function testJapaneseMonthsInYear(): void
    {
        $this->assertSame(12, JapaneseCalendar::instance()->monthsInYear());
    }

    // =========================================================================
    // JapaneseCalendar — factory methods
    // =========================================================================

    public function testJapaneseDateFromFieldsWithEra(): void
    {
        $jp = JapaneseCalendar::instance();
        // Reiwa 6, month 3, day 15 = ISO 2024-03-15
        $date = $jp->dateFromFields(['era' => 'reiwa', 'eraYear' => 6, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testJapaneseDateFromFieldsWithYear(): void
    {
        $jp = JapaneseCalendar::instance();
        $date = $jp->dateFromFields(['year' => 2024, 'month' => 3, 'day' => 15], 'constrain');
        $this->assertSame(2024, $date->year);
    }

    public function testJapaneseDateFromFieldsShowa(): void
    {
        $jp = JapaneseCalendar::instance();
        // Showa 1 = 1926
        $date = $jp->dateFromFields(['era' => 'showa', 'eraYear' => 1, 'month' => 12, 'day' => 25], 'constrain');
        $this->assertSame(1926, $date->year);
    }

    public function testJapaneseDateFromFieldsOverflowConstrain(): void
    {
        $jp = JapaneseCalendar::instance();
        $date = $jp->dateFromFields(['era' => 'reiwa', 'eraYear' => 6, 'month' => 2, 'day' => 30], 'constrain');
        $this->assertSame(29, $date->day); // 2024 is leap
    }

    public function testJapaneseCalendarFromCalendarFactory(): void
    {
        $cal = Calendar::from('japanese');
        $this->assertSame('japanese', $cal->id);
    }

    public function testPlainDateWithJapaneseCalendar(): void
    {
        $date = new PlainDate(2024, 3, 15)->withCalendar('japanese');
        $this->assertSame('japanese', $date->calendarId);
        $this->assertSame('reiwa', $date->era);
        $this->assertSame(6, $date->eraYear);
    }

    public function testPlainDateTimeWithJapaneseCalendar(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('japanese');
        $this->assertSame('japanese', $pdt->calendarId);
        $this->assertSame('reiwa', $pdt->era);
    }

    public function testJapaneseToStringIncludesAnnotation(): void
    {
        $pdt = new PlainDateTime(2024, 3, 15)->withCalendar('japanese');
        $this->assertStringContainsString('[u-ca=japanese]', (string) $pdt);
    }

    // =========================================================================
    // Calendar::from() — new calendars
    // =========================================================================

    public function testCalendarFromRoc(): void
    {
        $cal = Calendar::from('roc');
        $this->assertSame('roc', $cal->id);
        $this->assertInstanceOf(RocCalendar::class, $cal->getProtocol());
    }

    public function testCalendarFromJapanese(): void
    {
        $cal = Calendar::from('japanese');
        $this->assertSame('japanese', $cal->id);
        $this->assertInstanceOf(JapaneseCalendar::class, $cal->getProtocol());
    }

    public function testCalendarFromRocCaseInsensitive(): void
    {
        $cal = Calendar::from('ROC');
        $this->assertSame('roc', $cal->id);
    }

    public function testCalendarFromJapaneseCaseInsensitive(): void
    {
        $cal = Calendar::from('JAPANESE');
        $this->assertSame('japanese', $cal->id);
    }
}
