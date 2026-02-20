<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use Temporal\Exception\DateRangeException;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainTime;

class PlainDateTimeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testBasicConstruction(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(2024, $dt->year);
        $this->assertSame(3, $dt->month);
        $this->assertSame(15, $dt->day);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
        $this->assertSame(0, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testConstructionWithAllFields(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $this->assertSame(45, $dt->second);
        $this->assertSame(123, $dt->millisecond);
        $this->assertSame(456, $dt->microsecond);
        $this->assertSame(789, $dt->nanosecond);
    }

    public function testDefaultsToMidnight(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        $this->assertSame(0, $dt->hour);
        $this->assertSame(0, $dt->minute);
        $this->assertSame(0, $dt->second);
        $this->assertSame(0, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testInvalidMonthThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 13, 1);
    }

    public function testInvalidMonthZeroThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 0, 1);
    }

    public function testInvalidDayThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 2, 30);
    }

    public function testInvalidDayInLeapYearOk(): void
    {
        $dt = new PlainDateTime(2024, 2, 29);
        $this->assertSame(29, $dt->day);
    }

    public function testInvalidDayInNonLeapYearThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2023, 2, 29);
    }

    public function testInvalidHourThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 24);
    }

    public function testInvalidMinuteThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 0, 60);
    }

    public function testInvalidSecondThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 60);
    }

    public function testInvalidMillisecondThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 1000);
    }

    public function testInvalidMicrosecondThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 0, 1000);
    }

    public function testInvalidNanosecondThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 0, 0, 1000);
    }

    public function testNegativeHourThrows(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainDateTime(2024, 3, 15, -1);
    }

    // -------------------------------------------------------------------------
    // from()
    // -------------------------------------------------------------------------

    public function testFromString(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(3, $dt->month);
        $this->assertSame(15, $dt->day);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
    }

    public function testFromStringWithFractionalSeconds(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45.123456789');
        $this->assertSame(45, $dt->second);
        $this->assertSame(123, $dt->millisecond);
        $this->assertSame(456, $dt->microsecond);
        $this->assertSame(789, $dt->nanosecond);
    }

    public function testFromStringWithThreeDigitFraction(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45.500');
        $this->assertSame(500, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testFromStringLowercaseSeparator(): void
    {
        $dt = PlainDateTime::from('2024-03-15t10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(10, $dt->hour);
    }

    public function testFromStringNegativeYear(): void
    {
        $dt = PlainDateTime::from('-001000-06-15T12:00:00');
        $this->assertSame(-1000, $dt->year);
        $this->assertSame(6, $dt->month);
        $this->assertSame(12, $dt->hour);
    }

    public function testFromStringExtendedYear(): void
    {
        $dt = PlainDateTime::from('+010000-01-01T00:00:00');
        $this->assertSame(10000, $dt->year);
    }

    public function testFromArray(): void
    {
        $dt = PlainDateTime::from([
            'year' => 2024,
            'month' => 3,
            'day' => 15,
            'hour' => 10,
            'minute' => 30
        ]);
        $this->assertSame(2024, $dt->year);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
    }

    public function testFromArrayWithAllFields(): void
    {
        $dt = PlainDateTime::from([
            'year' => 2024,
            'month' => 3,
            'day' => 15,
            'hour' => 10,
            'minute' => 30,
            'second' => 45,
            'millisecond' => 100,
            'microsecond' => 200,
            'nanosecond' => 300
        ]);
        $this->assertSame(45, $dt->second);
        $this->assertSame(100, $dt->millisecond);
        $this->assertSame(200, $dt->microsecond);
        $this->assertSame(300, $dt->nanosecond);
    }

    public function testFromPlainDateTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 45);
        $dt2 = PlainDateTime::from($dt1);
        $this->assertTrue($dt1->equals($dt2));
        $this->assertNotSame($dt1, $dt2);
    }

    public function testFromInvalidStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from('not-a-date');
    }

    public function testFromStringMissingTimeSeparatorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from('2024-03-15 10:30:00');
    }

    public function testFromArrayMissingYearThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['month' => 3, 'day' => 15]);
    }

    public function testFromArrayMissingMonthThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['year' => 2024, 'day' => 15]);
    }

    public function testFromArrayMissingDayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['year' => 2024, 'month' => 3]);
    }

    // -------------------------------------------------------------------------
    // toPlainDate() / toPlainTime()
    // -------------------------------------------------------------------------

    public function testToPlainDate(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $date = $dt->toPlainDate();
        $this->assertInstanceOf(PlainDate::class, $date);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testToPlainTime(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $time = $dt->toPlainTime();
        $this->assertInstanceOf(PlainTime::class, $time);
        $this->assertSame(10, $time->hour);
        $this->assertSame(30, $time->minute);
        $this->assertSame(45, $time->second);
        $this->assertSame(123, $time->millisecond);
        $this->assertSame(456, $time->microsecond);
        $this->assertSame(789, $time->nanosecond);
    }

    // -------------------------------------------------------------------------
    // withPlainDate() / withPlainTime()
    // -------------------------------------------------------------------------

    public function testWithPlainDate(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $newDate = new PlainDate(2025, 6, 20);
        $dt2 = $dt->withPlainDate($newDate);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(6, $dt2->month);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(10, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testWithPlainTime(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $newTime = new PlainTime(14, 45, 30, 100, 200, 300);
        $dt2 = $dt->withPlainTime($newTime);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(15, $dt2->day);
        $this->assertSame(14, $dt2->hour);
        $this->assertSame(45, $dt2->minute);
        $this->assertSame(30, $dt2->second);
        $this->assertSame(100, $dt2->millisecond);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithOverrideYear(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['year' => 2025]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(10, $dt2->hour);
    }

    public function testWithOverrideHour(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['hour' => 15]);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(15, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testWithMultipleFields(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['month' => 6, 'day' => 20, 'hour' => 8, 'minute' => 0]);
        $this->assertSame(6, $dt2->month);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(8, $dt2->hour);
        $this->assertSame(0, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    public function testAddDays(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['days' => 5]);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(10, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testAddHours(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['hours' => 3]);
        $this->assertSame(13, $dt2->hour);
        $this->assertSame(15, $dt2->day);
    }

    public function testAddHoursCrossesDay(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 22, 0, 0);
        $dt2 = $dt->add(['hours' => 4]);
        $this->assertSame(16, $dt2->day);
        $this->assertSame(2, $dt2->hour);
    }

    public function testAddHoursCrossesMultipleDays(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['hours' => 48]);
        $this->assertSame(17, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testAddCrossesMonth(): void
    {
        $dt = new PlainDateTime(2024, 1, 31, 10, 0, 0);
        $dt2 = $dt->add(['days' => 1]);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(2, $dt2->month);
        $this->assertSame(1, $dt2->day);
    }

    public function testAddYears(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['years' => 1]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(15, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testAddMonths(): void
    {
        $dt = new PlainDateTime(2024, 11, 15, 10, 0, 0);
        $dt2 = $dt->add(['months' => 3]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(2, $dt2->month);
    }

    public function testAddWeeks(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['weeks' => 2]);
        $this->assertSame(29, $dt2->day);
    }

    public function testAddMinutes(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 45, 0);
        $dt2 = $dt->add(['minutes' => 30]);
        $this->assertSame(11, $dt2->hour);
        $this->assertSame(15, $dt2->minute);
    }

    public function testAddSeconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 59, 50);
        $dt2 = $dt->add(['seconds' => 30]);
        $this->assertSame(11, $dt2->hour);
        $this->assertSame(0, $dt2->minute);
        $this->assertSame(20, $dt2->second);
    }

    public function testAddMilliseconds(): void
    {
        // 900ms + 200ms = 1100ms → 1s + 100ms
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0, 900);
        $dt2 = $dt->add(['milliseconds' => 200]);
        $this->assertSame(100, $dt2->millisecond);
        $this->assertSame(1, $dt2->second);
        // 900ms + 100ms = 1000ms → 1s + 0ms
        $dt3 = new PlainDateTime(2024, 3, 15, 10, 0, 0, 900)->add(['milliseconds' => 100]);
        $this->assertSame(0, $dt3->millisecond);
        $this->assertSame(1, $dt3->second);
    }

    public function testAddNanoseconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0, 0, 0, 999);
        $dt2 = $dt->add(['nanoseconds' => 1]);
        $this->assertSame(0, $dt2->nanosecond);
        $this->assertSame(1, $dt2->microsecond);
    }

    public function testAddMixedDuration(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 23, 30, 0);
        $dt2 = $dt->add(['hours' => 1, 'minutes' => 45]);
        // 23:30 + 1h45m = 25:15 → next day 01:15
        $this->assertSame(16, $dt2->day);
        $this->assertSame(1, $dt2->hour);
        $this->assertSame(15, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // subtract()
    // -------------------------------------------------------------------------

    public function testSubtractDays(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->subtract(['days' => 5]);
        $this->assertSame(10, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testSubtractHoursCrossesDay(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 2, 0, 0);
        $dt2 = $dt->subtract(['hours' => 4]);
        $this->assertSame(14, $dt2->day);
        $this->assertSame(22, $dt2->hour);
    }

    public function testSubtractMonths(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->subtract(['months' => 4]);
        $this->assertSame(2023, $dt2->year);
        $this->assertSame(11, $dt2->month);
    }

    public function testSubtractMinutes(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 10, 0);
        $dt2 = $dt->subtract(['minutes' => 20]);
        $this->assertSame(9, $dt2->hour);
        $this->assertSame(50, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // until() / since()
    // -------------------------------------------------------------------------

    public function testUntilSameDateDifferentTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 14, 30, 0);
        $dur = $dt1->until($dt2);
        $this->assertInstanceOf(Duration::class, $dur);
        $this->assertSame(0, $dur->days);
        $this->assertSame(4, $dur->hours);
        $this->assertSame(30, $dur->minutes);
    }

    public function testUntilDifferentDateSameTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(3, $dur->days);
        $this->assertSame(0, $dur->hours);
    }

    public function testUntilDifferentDateAndTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 14, 30, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(3, $dur->days);
        $this->assertSame(4, $dur->hours);
        $this->assertSame(30, $dur->minutes);
    }

    public function testUntilNegative(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(-3, $dur->days);
    }

    public function testUntilWithTimeBorrow(): void
    {
        // From 10:00 to next day 08:00 → 22 hours
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 16, 8, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(0, $dur->days);
        $this->assertSame(22, $dur->hours);
    }

    public function testSince(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dur = $dt2->since($dt1);
        $this->assertSame(3, $dur->days);
    }

    public function testUntilWithSubseconds(): void
    {
        // dt1 = 10:00:00.000000500, dt2 = 10:00:01.000000000
        // diff = 1,000,000,000 - 500 = 999,999,500 ns
        // → 0 seconds, 999 ms, 999 µs, 500 ns
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0, 0, 0, 500);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 0, 1, 0, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(0, $dur->seconds);
        $this->assertSame(999, $dur->milliseconds);
        $this->assertSame(999, $dur->microseconds);
        $this->assertSame(500, $dur->nanoseconds);
    }

    // -------------------------------------------------------------------------
    // compare() / equals()
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(0, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareLessByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 14, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(-1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareGreaterByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 16, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $this->assertSame(1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareLessByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $this->assertSame(-1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareGreaterByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testEqualsTrue(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertTrue($dt1->equals($dt2));
    }

    public function testEqualsFalseByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 16, 10, 30, 0);
        $this->assertFalse($dt1->equals($dt2));
    }

    public function testEqualsFalseByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 1);
        $this->assertFalse($dt1->equals($dt2));
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringBasic(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame('2024-03-15T10:30:00', (string) $dt);
    }

    public function testToStringMidnight(): void
    {
        $dt = new PlainDateTime(2024, 1, 1);
        $this->assertSame('2024-01-01T00:00:00', (string) $dt);
    }

    public function testToStringWithMilliseconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123);
        $this->assertSame('2024-03-15T10:30:45.123', (string) $dt);
    }

    public function testToStringWithMicroseconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456);
        $this->assertSame('2024-03-15T10:30:45.123456', (string) $dt);
    }

    public function testToStringWithFullFraction(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $this->assertSame('2024-03-15T10:30:45.123456789', (string) $dt);
    }

    public function testToStringNegativeYear(): void
    {
        $dt = new PlainDateTime(-1, 1, 1, 0, 0, 0);
        $this->assertSame('-000001-01-01T00:00:00', (string) $dt);
    }

    public function testToStringLargeYear(): void
    {
        $dt = new PlainDateTime(10000, 1, 1, 0, 0, 0);
        $this->assertSame('+010000-01-01T00:00:00', (string) $dt);
    }

    public function testToStringRoundTrip(): void
    {
        $str = '2024-03-15T10:30:45.123456789';
        $dt = PlainDateTime::from($str);
        $this->assertSame($str, (string) $dt);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function testAddDoesNotMutate(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['days' => 1]);
        $this->assertSame(15, $dt->day);
        $this->assertSame(16, $dt2->day);
    }

    public function testSubtractDoesNotMutate(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->subtract(['hours' => 1]);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(9, $dt2->hour);
    }

    public function testAddDurationObject(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $result = $dt->add(new Duration(hours: 3, minutes: 30));
        $this->assertSame(13, $result->hour);
        $this->assertSame(30, $result->minute);
    }

    public function testSubtractDurationObject(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $result = $dt->subtract(new Duration(hours: 2, minutes: 30));
        $this->assertSame(8, $result->hour);
        $this->assertSame(0, $result->minute);
    }

    public function testAddMonthsOverflowConstrain(): void
    {
        // Jan 31 + 1 month → Feb 28 (constrained, not Feb 31)
        $dt = new PlainDateTime(2023, 1, 31, 12, 0, 0);
        $result = $dt->add(['months' => 1]);
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
        $this->assertSame(12, $result->hour);
    }

    public function testAddMonthsOverflowReject(): void
    {
        $this->expectException(DateRangeException::class);
        $dt = new PlainDateTime(2023, 1, 31, 12, 0, 0);
        $dt->add(['months' => 1], 'reject');
    }

    public function testAddWithExplicitConstrainOverflow(): void
    {
        $dt = new PlainDateTime(2023, 1, 31, 6, 0, 0);
        $result = $dt->add(['months' => 1], 'constrain');
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
    }

    public function testSubtractMonthsOverflowReject(): void
    {
        $this->expectException(DateRangeException::class);
        $dt = new PlainDateTime(2023, 3, 31, 12, 0, 0);
        $dt->subtract(['months' => 1], 'reject');
    }

    public function testAddDurationObjectOverflowConstrain(): void
    {
        $dt = new PlainDateTime(2023, 1, 31, 12, 0, 0);
        $result = $dt->add(new Duration(months: 1));
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->day);
    }

    // -------------------------------------------------------------------------
    // toZonedDateTime
    // -------------------------------------------------------------------------

    public function testToZonedDateTimeWithStringTimezone(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $zdt = $dt->toZonedDateTime('UTC');
        $this->assertInstanceOf(\Temporal\ZonedDateTime::class, $zdt);
        $this->assertSame(2024, $zdt->year);
        $this->assertSame(3, $zdt->month);
        $this->assertSame(15, $zdt->day);
        $this->assertSame(10, $zdt->hour);
        $this->assertSame(30, $zdt->minute);
    }

    public function testToZonedDateTimeWithTimezoneObject(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 0, 0, 0);
        $tz = \Temporal\TimeZone::from('UTC');
        $zdt = $dt->toZonedDateTime($tz);
        $this->assertSame('UTC', (string) $zdt->timeZone);
    }

    public function testToZonedDateTimeEpochNsAtEpoch(): void
    {
        // 1970-01-01T00:00:00 UTC → epoch ns = 0
        $dt = new PlainDateTime(1970, 1, 1, 0, 0, 0);
        $zdt = $dt->toZonedDateTime('UTC');
        $this->assertSame(0, $zdt->epochNanoseconds);
    }

    public function testToZonedDateTimeWithOffsetTimezone(): void
    {
        // 1970-01-01T05:30:00 in +05:30 → epoch ns = 0
        $dt = new PlainDateTime(1970, 1, 1, 5, 30, 0);
        $zdt = $dt->toZonedDateTime('+05:30');
        $this->assertSame(0, $zdt->epochNanoseconds);
    }

    public function testToZonedDateTimePreservesSubSeconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $zdt = $dt->toZonedDateTime('UTC');
        $this->assertSame(45, $zdt->second);
        $this->assertSame(123, $zdt->millisecond);
        $this->assertSame(456, $zdt->microsecond);
        $this->assertSame(789, $zdt->nanosecond);
    }

    public function testToZonedDateTimeRoundTrip(): void
    {
        $dt = new PlainDateTime(2024, 6, 15, 12, 0, 0);
        $zdt = $dt->toZonedDateTime('UTC');
        $dt2 = $zdt->toPlainDateTime();
        $this->assertTrue($dt->equals($dt2));
    }

    // -------------------------------------------------------------------------
    // round()
    // -------------------------------------------------------------------------

    public function testRoundStringShorthand(): void
    {
        // Passing a string is shorthand for smallestUnit with halfExpand mode.
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45);
        $result = $dt->round('hour');
        $this->assertSame('2024-03-15T11:00:00', (string) $result);
    }

    public function testRoundToDayHalfExpandNoonRoundsUp(): void
    {
        // Exactly noon (12:00:00) → halfExpand rounds up to next day.
        $dt = new PlainDateTime(2024, 3, 15, 12, 0, 0);
        $result = $dt->round(['smallestUnit' => 'day']);
        $this->assertSame('2024-03-16T00:00:00', (string) $result);
    }

    public function testRoundToDayHalfExpandJustBeforeNoonStays(): void
    {
        // 11:59:59.999999999 → halfExpand stays at midnight of same day.
        $dt = new PlainDateTime(2024, 3, 15, 11, 59, 59, 999, 999, 999);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-03-15T00:00:00', (string) $result);
    }

    public function testRoundToDayHalfExpandAfternoonRoundsUp(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 12, 30, 0);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-03-16T00:00:00', (string) $result);
    }

    public function testRoundToDayCeilNonMidnightRoundsUp(): void
    {
        // Any time > midnight rounds up.
        $dt = new PlainDateTime(2024, 3, 15, 0, 0, 1);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'ceil']);
        $this->assertSame('2024-03-16T00:00:00', (string) $result);
    }

    public function testRoundToDayCeilMidnightStays(): void
    {
        // Exactly midnight stays at midnight.
        $dt = new PlainDateTime(2024, 3, 15, 0, 0, 0);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'ceil']);
        $this->assertSame('2024-03-15T00:00:00', (string) $result);
    }

    public function testRoundToDayFloorAlwaysMidnight(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 23, 59, 59);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'floor']);
        $this->assertSame('2024-03-15T00:00:00', (string) $result);
    }

    public function testRoundToDayTruncAlwaysMidnight(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 18, 0, 0);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'trunc']);
        $this->assertSame('2024-03-15T00:00:00', (string) $result);
    }

    public function testRoundToHourHalfExpandRoundsUp(): void
    {
        // 10:30:00 → rounds up to 11:00:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-03-15T11:00:00', (string) $result);
    }

    public function testRoundToHourHalfExpandRoundsDown(): void
    {
        // 10:29:59 → rounds down to 10:00:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 29, 59);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-03-15T10:00:00', (string) $result);
    }

    public function testRoundToHourOverflowCarriesToNextDay(): void
    {
        // 23:30:00 rounds up to 24:00:00 → 2024-03-16T00:00:00
        $dt = new PlainDateTime(2024, 3, 15, 23, 30, 0);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-03-16T00:00:00', (string) $result);
    }

    public function testRoundToHourCeil(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 1, 0);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'ceil']);
        $this->assertSame('2024-03-15T11:00:00', (string) $result);
    }

    public function testRoundToHourFloor(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 59, 59);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'floor']);
        $this->assertSame('2024-03-15T10:00:00', (string) $result);
    }

    public function testRoundToMinuteHalfExpand(): void
    {
        // 10:15:30 → round to nearest minute → 10:16:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 15, 30);
        $result = $dt->round('minute');
        $this->assertSame('2024-03-15T10:16:00', (string) $result);
    }

    public function testRoundToMinuteHalfExpandDown(): void
    {
        // 10:15:29 → stays at 10:15:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 15, 29);
        $result = $dt->round('minute');
        $this->assertSame('2024-03-15T10:15:00', (string) $result);
    }

    public function testRoundToSecondHalfExpand(): void
    {
        // 10:15:00.500 → rounds up to 10:15:01
        $dt = new PlainDateTime(2024, 3, 15, 10, 15, 0, 500);
        $result = $dt->round('second');
        $this->assertSame('2024-03-15T10:15:01', (string) $result);
    }

    public function testRoundToSecondHalfExpandDown(): void
    {
        // 10:15:00.499 → stays at 10:15:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 15, 0, 499);
        $result = $dt->round('second');
        $this->assertSame('2024-03-15T10:15:00', (string) $result);
    }

    public function testRoundToMillisecond(): void
    {
        // 500µs rounds up the millisecond
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0, 0, 500, 0);
        $result = $dt->round('millisecond');
        $this->assertSame('2024-03-15T10:00:00.001', (string) $result);
    }

    public function testRoundToNanosecondIsNoop(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 15, 30, 123, 456, 789);
        $result = $dt->round('nanosecond');
        $this->assertSame($dt, $result);
    }

    public function testRoundWithIncrementMinute(): void
    {
        // 10:17:00 rounded to nearest 5 minutes → 10:15:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 17, 0);
        $result = $dt->round(['smallestUnit' => 'minute', 'roundingIncrement' => 5]);
        $this->assertSame('2024-03-15T10:15:00', (string) $result);
    }

    public function testRoundWithIncrementMinuteRoundsUp(): void
    {
        // 10:18:00 rounded to nearest 5 minutes → 10:20:00
        $dt = new PlainDateTime(2024, 3, 15, 10, 18, 0);
        $result = $dt->round(['smallestUnit' => 'minute', 'roundingIncrement' => 5]);
        $this->assertSame('2024-03-15T10:20:00', (string) $result);
    }

    public function testRoundWithIncrementHour(): void
    {
        // 10:00:00 is exactly at midpoint between 8:00 and 12:00 (2h from each).
        // halfExpand rounds up when remainder * 2 >= step, so result is 12:00:00.
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $result = $dt->round(['smallestUnit' => 'hour', 'roundingIncrement' => 4]);
        $this->assertSame('2024-03-15T12:00:00', (string) $result);
    }

    public function testRoundInvalidUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt->round('week');
    }

    public function testRoundMissingSmallestUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt->round(['roundingMode' => 'floor']);
    }

    public function testRoundInvalidModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt->round(['smallestUnit' => 'hour', 'roundingMode' => 'half']);
    }

    public function testRoundBadIncrementThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $dt = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        // 7 does not evenly divide 60 (minutes in hour)
        $dt->round(['smallestUnit' => 'minute', 'roundingIncrement' => 7]);
    }

    public function testRoundDoesNotMutate(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt->round('hour');
        $this->assertSame(30, $dt->minute);
        $this->assertSame(10, $dt->hour);
    }

    public function testRoundDayOverflowAcrossMonth(): void
    {
        // Last day of month at noon → rounds up to first of next month
        $dt = new PlainDateTime(2024, 3, 31, 12, 0, 0);
        $result = $dt->round(['smallestUnit' => 'day', 'roundingMode' => 'halfExpand']);
        $this->assertSame('2024-04-01T00:00:00', (string) $result);
    }

    // -------------------------------------------------------------------------
    // toPlainYearMonth()
    // -------------------------------------------------------------------------

    public function testToPlainYearMonth(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $pym = $dt->toPlainYearMonth();

        $this->assertSame(2024, $pym->year);
        $this->assertSame(3, $pym->month);
    }

    public function testToPlainYearMonthIgnoresTimeAndDay(): void
    {
        $dt1 = new PlainDateTime(2024, 6, 1, 0, 0, 0);
        $dt2 = new PlainDateTime(2024, 6, 30, 23, 59, 59);

        $this->assertTrue($dt1->toPlainYearMonth()->equals($dt2->toPlainYearMonth()));
    }

    // -------------------------------------------------------------------------
    // toPlainMonthDay()
    // -------------------------------------------------------------------------

    public function testToPlainMonthDay(): void
    {
        $dt = new PlainDateTime(2024, 7, 4, 15, 0, 0);
        $pmd = $dt->toPlainMonthDay();

        $this->assertSame(7, $pmd->month);
        $this->assertSame(4, $pmd->day);
    }

    public function testToPlainMonthDayIgnoresYearAndTime(): void
    {
        $dt1 = new PlainDateTime(2020, 2, 29, 0, 0, 0);
        $dt2 = new PlainDateTime(2024, 2, 29, 23, 59, 59);

        $this->assertTrue($dt1->toPlainMonthDay()->equals($dt2->toPlainMonthDay()));
    }

    // -------------------------------------------------------------------------
    // yearOfWeek
    // -------------------------------------------------------------------------

    /** Mid-year date: yearOfWeek equals the calendar year. */
    public function testYearOfWeek_MidYear(): void
    {
        $dt = new PlainDateTime(2024, 6, 15, 12, 0, 0);
        $this->assertSame(2024, $dt->yearOfWeek);
    }

    /** Jan 1, 2021 (Friday) is in week 53 of 2020 → yearOfWeek = 2020. */
    public function testYearOfWeek_EarlyJan(): void
    {
        $dt = new PlainDateTime(2021, 1, 1, 0, 0, 0);
        $this->assertSame(2020, $dt->yearOfWeek);
    }

    // -------------------------------------------------------------------------
    // until() / since() with largestUnit
    // -------------------------------------------------------------------------

    /** Default largestUnit ('day') preserves existing behaviour. */
    public function testUntilDefaultLargestUnit(): void
    {
        $a = new PlainDateTime(2024, 1, 1, 0, 0, 0);
        $b = new PlainDateTime(2024, 1, 10, 6, 0, 0); // 9 days + 6 hours
        $d = $a->until($b);
        $this->assertSame(9, $d->days);
        $this->assertSame(6, $d->hours);
    }

    /** largestUnit = 'month': date part expressed as months + days. */
    public function testUntilLargestUnitMonth(): void
    {
        $a = new PlainDateTime(2024, 1, 15, 10, 0, 0);
        $b = new PlainDateTime(2024, 4, 15, 14, 0, 0); // 3 months + 4 hours
        $d = $a->until($b, ['largestUnit' => 'month']);
        $this->assertSame(0, $d->years);
        $this->assertSame(3, $d->months);
        $this->assertSame(0, $d->days);
        $this->assertSame(4, $d->hours);
    }

    /** largestUnit = 'year': date part expressed as years + months + days. */
    public function testUntilLargestUnitYear(): void
    {
        $a = new PlainDateTime(2020, 3, 15, 0, 0, 0);
        $b = new PlainDateTime(2022, 5, 20, 3, 0, 0); // 2y + 2m + 5d + 3h
        $d = $a->until($b, ['largestUnit' => 'year']);
        $this->assertSame(2, $d->years);
        $this->assertSame(2, $d->months);
        $this->assertSame(5, $d->days);
        $this->assertSame(3, $d->hours);
    }

    /** largestUnit = 'hour': everything collapses to hours + smaller. */
    public function testUntilLargestUnitHour(): void
    {
        $a = new PlainDateTime(2024, 1, 1, 0, 0, 0);
        $b = new PlainDateTime(2024, 1, 2, 6, 30, 0); // 30 hours + 30 minutes
        $d = $a->until($b, ['largestUnit' => 'hour']);
        $this->assertSame(0, $d->days);
        $this->assertSame(30, $d->hours);
        $this->assertSame(30, $d->minutes);
    }

    /** largestUnit = 'minute': everything collapses to minutes + smaller. */
    public function testUntilLargestUnitMinute(): void
    {
        $a = new PlainDateTime(2024, 1, 1, 0, 0, 0);
        $b = new PlainDateTime(2024, 1, 1, 1, 30, 0); // 90 minutes
        $d = $a->until($b, ['largestUnit' => 'minute']);
        $this->assertSame(0, $d->hours);
        $this->assertSame(90, $d->minutes);
    }

    /** Day-borrow with largestUnit = 'month': time offset crosses midnight. */
    public function testUntilMonthDayBorrow(): void
    {
        // 2024-01-15T23:00 to 2024-04-15T01:00 → 3m - 22h = 2m + 30d + 2h
        $a = new PlainDateTime(2024, 1, 15, 23, 0, 0);
        $b = new PlainDateTime(2024, 4, 15, 1, 0, 0);
        $d = $a->until($b, ['largestUnit' => 'month']);
        // Date part: 3 months would overshoot (anchor=Apr15T23, but b=Apr15T01),
        // so borrow → 2 months + some days + 2 hours.
        $this->assertSame(2, $d->months);
        $this->assertSame(2, $d->hours); // 1:00 - 23:00 + 24h = 2h
        $this->assertSame(0, $d->minutes);
    }

    /** since() with largestUnit. */
    public function testSinceLargestUnitMonth(): void
    {
        $a = new PlainDateTime(2024, 1, 15, 10, 0, 0);
        $b = new PlainDateTime(2024, 4, 15, 14, 0, 0);
        $d = $b->since($a, ['largestUnit' => 'month']);
        $this->assertSame(3, $d->months);
        $this->assertSame(4, $d->hours);
    }

    /** Invalid largestUnit throws. */
    public function testUntilInvalidLargestUnit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PlainDateTime(2024, 1, 1, 0, 0, 0)->until(new PlainDateTime(2024, 6, 1, 0, 0, 0), [
            'largestUnit' => 'decade'
        ]);
    }
}
