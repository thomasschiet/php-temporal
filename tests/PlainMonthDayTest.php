<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Exception\DateRangeException;
use Temporal\PlainMonthDay;
use Temporal\PlainDate;

final class PlainMonthDayTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor and basic properties
    // -------------------------------------------------------------------------

    public function testConstructorBasic(): void
    {
        $md = new PlainMonthDay(3, 15);
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    public function testConstructorJanuaryFirst(): void
    {
        $md = new PlainMonthDay(1, 1);
        $this->assertSame(1, $md->month);
        $this->assertSame(1, $md->day);
    }

    public function testConstructorDecember31(): void
    {
        $md = new PlainMonthDay(12, 31);
        $this->assertSame(12, $md->month);
        $this->assertSame(31, $md->day);
    }

    public function testConstructorFebruary29(): void
    {
        // Feb 29 is valid (reference year is a leap year)
        $md = new PlainMonthDay(2, 29);
        $this->assertSame(2, $md->month);
        $this->assertSame(29, $md->day);
    }

    public function testConstructorInvalidMonthZero(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(0, 15);
    }

    public function testConstructorInvalidMonthThirteen(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(13, 15);
    }

    public function testConstructorInvalidDayZero(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(3, 0);
    }

    public function testConstructorInvalidDayTooHigh(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(3, 32);
    }

    public function testConstructorInvalidDayApril31(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(4, 31);
    }

    public function testConstructorInvalidDayFebruary30(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainMonthDay(2, 30);
    }

    // -------------------------------------------------------------------------
    // from() static constructor
    // -------------------------------------------------------------------------

    public function testFromString(): void
    {
        $md = PlainMonthDay::from('--03-15');
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    public function testFromStringJanuary(): void
    {
        $md = PlainMonthDay::from('--01-01');
        $this->assertSame(1, $md->month);
        $this->assertSame(1, $md->day);
    }

    public function testFromStringDecember31(): void
    {
        $md = PlainMonthDay::from('--12-31');
        $this->assertSame(12, $md->month);
        $this->assertSame(31, $md->day);
    }

    public function testFromStringFebruary29(): void
    {
        $md = PlainMonthDay::from('--02-29');
        $this->assertSame(2, $md->month);
        $this->assertSame(29, $md->day);
    }

    public function testFromArray(): void
    {
        $md = PlainMonthDay::from(['month' => 3, 'day' => 15]);
        $this->assertSame(3, $md->month);
        $this->assertSame(15, $md->day);
    }

    public function testFromArrayMissingMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainMonthDay::from(['day' => 15]);
    }

    public function testFromArrayMissingDay(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainMonthDay::from(['month' => 3]);
    }

    public function testFromPlainMonthDay(): void
    {
        $md1 = new PlainMonthDay(3, 15);
        $md2 = PlainMonthDay::from($md1);
        $this->assertSame(3, $md2->month);
        $this->assertSame(15, $md2->day);
        $this->assertNotSame($md1, $md2);
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainMonthDay::from('not-a-month-day');
    }

    public function testFromStringInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainMonthDay::from('03-15'); // Missing leading --
    }

    public function testFromStringInvalidDay(): void
    {
        $this->expectException(DateRangeException::class);
        PlainMonthDay::from('--04-31'); // April only has 30 days
    }

    public function testFromStringInvalidFebruary30(): void
    {
        $this->expectException(DateRangeException::class);
        PlainMonthDay::from('--02-30');
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithMonth(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->with(['month' => 6]);
        $this->assertSame(6, $result->month);
        $this->assertSame(15, $result->day);
    }

    public function testWithDay(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->with(['day' => 20]);
        $this->assertSame(3, $result->month);
        $this->assertSame(20, $result->day);
    }

    public function testWithBoth(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->with(['month' => 6, 'day' => 20]);
        $this->assertSame(6, $result->month);
        $this->assertSame(20, $result->day);
    }

    public function testWithReturnsNewInstance(): void
    {
        $md = new PlainMonthDay(3, 15);
        $result = $md->with(['month' => 6]);
        $this->assertNotSame($md, $result);
        $this->assertSame(3, $md->month); // Original unchanged
    }

    public function testWithInvalidCombination(): void
    {
        $this->expectException(DateRangeException::class);
        $md = new PlainMonthDay(3, 31);
        $md->with(['month' => 4]); // April has only 30 days
    }

    // -------------------------------------------------------------------------
    // equals()
    // -------------------------------------------------------------------------

    public function testEqualsTrue(): void
    {
        $md1 = new PlainMonthDay(3, 15);
        $md2 = new PlainMonthDay(3, 15);
        $this->assertTrue($md1->equals($md2));
    }

    public function testEqualsFalseDifferentDay(): void
    {
        $md1 = new PlainMonthDay(3, 15);
        $md2 = new PlainMonthDay(3, 16);
        $this->assertFalse($md1->equals($md2));
    }

    public function testEqualsFalseDifferentMonth(): void
    {
        $md1 = new PlainMonthDay(3, 15);
        $md2 = new PlainMonthDay(4, 15);
        $this->assertFalse($md1->equals($md2));
    }

    // -------------------------------------------------------------------------
    // toPlainDate()
    // -------------------------------------------------------------------------

    public function testToPlainDate(): void
    {
        $md = new PlainMonthDay(3, 15);
        $date = $md->toPlainDate(2024);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testToPlainDateNegativeYear(): void
    {
        $md = new PlainMonthDay(6, 10);
        $date = $md->toPlainDate(-100);
        $this->assertSame(-100, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(10, $date->day);
    }

    public function testToPlainDateFebruary29InLeapYear(): void
    {
        $md = new PlainMonthDay(2, 29);
        $date = $md->toPlainDate(2024);
        $this->assertSame(2024, $date->year);
        $this->assertSame(2, $date->month);
        $this->assertSame(29, $date->day);
    }

    public function testToPlainDateFebruary29InNonLeapYearThrows(): void
    {
        $this->expectException(DateRangeException::class);
        $md = new PlainMonthDay(2, 29);
        $md->toPlainDate(2023); // Not a leap year
    }

    public function testToPlainDateReturnsPlainDate(): void
    {
        $md = new PlainMonthDay(3, 15);
        $date = $md->toPlainDate(2024);
        $this->assertInstanceOf(PlainDate::class, $date);
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringBasic(): void
    {
        $md = new PlainMonthDay(3, 15);
        $this->assertSame('--03-15', (string) $md);
    }

    public function testToStringJanuaryFirst(): void
    {
        $md = new PlainMonthDay(1, 1);
        $this->assertSame('--01-01', (string) $md);
    }

    public function testToStringDecember31(): void
    {
        $md = new PlainMonthDay(12, 31);
        $this->assertSame('--12-31', (string) $md);
    }

    public function testToStringFebruary29(): void
    {
        $md = new PlainMonthDay(2, 29);
        $this->assertSame('--02-29', (string) $md);
    }

    public function testRoundTripFromString(): void
    {
        $original = '--06-15';
        $md = PlainMonthDay::from($original);
        $this->assertSame($original, (string) $md);
    }
}
