<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Exception\DateRangeException;
use Temporal\PlainYearMonth;
use Temporal\Duration;

final class PlainYearMonthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor and basic properties
    // -------------------------------------------------------------------------

    public function testConstructorBasic(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testConstructorNegativeYear(): void
    {
        $ym = new PlainYearMonth(-100, 6);
        $this->assertSame(-100, $ym->year);
        $this->assertSame(6, $ym->month);
    }

    public function testConstructorJanuary(): void
    {
        $ym = new PlainYearMonth(2024, 1);
        $this->assertSame(1, $ym->month);
    }

    public function testConstructorDecember(): void
    {
        $ym = new PlainYearMonth(2024, 12);
        $this->assertSame(12, $ym->month);
    }

    public function testConstructorInvalidMonthZero(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainYearMonth(2024, 0);
    }

    public function testConstructorInvalidMonthThirteen(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainYearMonth(2024, 13);
    }

    public function testConstructorInvalidMonthNegative(): void
    {
        $this->expectException(DateRangeException::class);
        new PlainYearMonth(2024, -1);
    }

    // -------------------------------------------------------------------------
    // from() static constructor
    // -------------------------------------------------------------------------

    public function testFromString(): void
    {
        $ym = PlainYearMonth::from('2024-03');
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testFromStringLeadingZeroMonth(): void
    {
        $ym = PlainYearMonth::from('2024-01');
        $this->assertSame(1, $ym->month);
    }

    public function testFromStringDecember(): void
    {
        $ym = PlainYearMonth::from('2024-12');
        $this->assertSame(12, $ym->month);
    }

    public function testFromStringNegativeYear(): void
    {
        $ym = PlainYearMonth::from('-000100-06');
        $this->assertSame(-100, $ym->year);
        $this->assertSame(6, $ym->month);
    }

    public function testFromStringExtendedPositiveYear(): void
    {
        $ym = PlainYearMonth::from('+012345-06');
        $this->assertSame(12345, $ym->year);
        $this->assertSame(6, $ym->month);
    }

    public function testFromArray(): void
    {
        $ym = PlainYearMonth::from(['year' => 2024, 'month' => 3]);
        $this->assertSame(2024, $ym->year);
        $this->assertSame(3, $ym->month);
    }

    public function testFromArrayMissingYear(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainYearMonth::from(['month' => 3]);
    }

    public function testFromArrayMissingMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainYearMonth::from(['year' => 2024]);
    }

    public function testFromPlainYearMonth(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = PlainYearMonth::from($ym1);
        $this->assertSame(2024, $ym2->year);
        $this->assertSame(3, $ym2->month);
        $this->assertNotSame($ym1, $ym2);
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainYearMonth::from('not-a-year-month');
    }

    public function testFromStringInvalidMonth(): void
    {
        $this->expectException(DateRangeException::class);
        PlainYearMonth::from('2024-13');
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    public function testDaysInMonthMarch(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame(31, $ym->daysInMonth);
    }

    public function testDaysInMonthApril(): void
    {
        $ym = new PlainYearMonth(2024, 4);
        $this->assertSame(30, $ym->daysInMonth);
    }

    public function testDaysInMonthFebruaryLeapYear(): void
    {
        $ym = new PlainYearMonth(2024, 2);
        $this->assertSame(29, $ym->daysInMonth);
    }

    public function testDaysInMonthFebruaryNonLeapYear(): void
    {
        $ym = new PlainYearMonth(2023, 2);
        $this->assertSame(28, $ym->daysInMonth);
    }

    public function testDaysInYearLeapYear(): void
    {
        $ym = new PlainYearMonth(2024, 6);
        $this->assertSame(366, $ym->daysInYear);
    }

    public function testDaysInYearNonLeapYear(): void
    {
        $ym = new PlainYearMonth(2023, 6);
        $this->assertSame(365, $ym->daysInYear);
    }

    public function testInLeapYearTrue(): void
    {
        $ym = new PlainYearMonth(2024, 1);
        $this->assertTrue($ym->inLeapYear);
    }

    public function testInLeapYearFalse(): void
    {
        $ym = new PlainYearMonth(2023, 1);
        $this->assertFalse($ym->inLeapYear);
    }

    public function testMonthsInYear(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame(12, $ym->monthsInYear);
    }

    public function testUndefinedPropertyThrows(): void
    {
        $this->expectException(\Error::class);
        $ym = new PlainYearMonth(2024, 3);
        /** @phpstan-ignore-next-line */
        $_ = $ym->nonExistentProperty;
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithYear(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->with(['year' => 2025]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(3, $result->month);
    }

    public function testWithMonth(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->with(['month' => 6]);
        $this->assertSame(2024, $result->year);
        $this->assertSame(6, $result->month);
    }

    public function testWithBoth(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->with(['year' => 2025, 'month' => 6]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(6, $result->month);
    }

    public function testWithReturnsNewInstance(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->with(['year' => 2025]);
        $this->assertNotSame($ym, $result);
        $this->assertSame(2024, $ym->year); // Original unchanged
    }

    // -------------------------------------------------------------------------
    // add() / subtract()
    // -------------------------------------------------------------------------

    public function testAddMonths(): void
    {
        $ym = new PlainYearMonth(2024, 10);
        $result = $ym->add(['months' => 3]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(1, $result->month);
    }

    public function testAddYears(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->add(['years' => 2]);
        $this->assertSame(2026, $result->year);
        $this->assertSame(3, $result->month);
    }

    public function testAddYearsAndMonths(): void
    {
        $ym = new PlainYearMonth(2024, 11);
        $result = $ym->add(['years' => 1, 'months' => 3]);
        $this->assertSame(2026, $result->year);
        $this->assertSame(2, $result->month);
    }

    public function testAddNegativeMonths(): void
    {
        $ym = new PlainYearMonth(2024, 2);
        $result = $ym->add(['months' => -3]);
        $this->assertSame(2023, $result->year);
        $this->assertSame(11, $result->month);
    }

    public function testSubtractMonths(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->subtract(['months' => 5]);
        $this->assertSame(2023, $result->year);
        $this->assertSame(10, $result->month);
    }

    public function testSubtractYears(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $result = $ym->subtract(['years' => 3]);
        $this->assertSame(2021, $result->year);
        $this->assertSame(3, $result->month);
    }

    public function testAddDuration(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $duration = new Duration(years: 1, months: 2);
        $result = $ym->add($duration);
        $this->assertSame(2025, $result->year);
        $this->assertSame(5, $result->month);
    }

    public function testSubtractDuration(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $duration = new Duration(months: 4);
        $result = $ym->subtract($duration);
        $this->assertSame(2023, $result->year);
        $this->assertSame(11, $result->month);
    }

    // -------------------------------------------------------------------------
    // until() / since()
    // -------------------------------------------------------------------------

    public function testUntilSameMonth(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 3);
        $d = $ym1->until($ym2);
        $this->assertSame(0, $d->years);
        $this->assertSame(0, $d->months);
    }

    public function testUntilLaterMonth(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 6);
        $d = $ym1->until($ym2);
        $this->assertSame(0, $d->years);
        $this->assertSame(3, $d->months);
    }

    public function testUntilLaterYear(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2026, 5);
        $d = $ym1->until($ym2);
        $this->assertSame(2, $d->years);
        $this->assertSame(2, $d->months);
    }

    public function testUntilEarlierMonth(): void
    {
        $ym1 = new PlainYearMonth(2024, 6);
        $ym2 = new PlainYearMonth(2024, 3);
        $d = $ym1->until($ym2);
        $this->assertSame(0, $d->years);
        $this->assertSame(-3, $d->months);
    }

    public function testSince(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 6);
        $d = $ym2->since($ym1);
        $this->assertSame(0, $d->years);
        $this->assertSame(3, $d->months);
    }

    public function testUntilAcrossYearBoundary(): void
    {
        $ym1 = new PlainYearMonth(2023, 11);
        $ym2 = new PlainYearMonth(2024, 2);
        $d = $ym1->until($ym2);
        $this->assertSame(0, $d->years);
        $this->assertSame(3, $d->months);
    }

    // -------------------------------------------------------------------------
    // compare() and equals()
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 3);
        $this->assertSame(0, PlainYearMonth::compare($ym1, $ym2));
    }

    public function testCompareLater(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 6);
        $this->assertSame(-1, PlainYearMonth::compare($ym1, $ym2));
    }

    public function testCompareEarlier(): void
    {
        $ym1 = new PlainYearMonth(2024, 6);
        $ym2 = new PlainYearMonth(2024, 3);
        $this->assertSame(1, PlainYearMonth::compare($ym1, $ym2));
    }

    public function testCompareDifferentYears(): void
    {
        $ym1 = new PlainYearMonth(2023, 12);
        $ym2 = new PlainYearMonth(2024, 1);
        $this->assertSame(-1, PlainYearMonth::compare($ym1, $ym2));
    }

    public function testEqualsTrue(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 3);
        $this->assertTrue($ym1->equals($ym2));
    }

    public function testEqualsFalseDifferentMonth(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2024, 4);
        $this->assertFalse($ym1->equals($ym2));
    }

    public function testEqualsFalseDifferentYear(): void
    {
        $ym1 = new PlainYearMonth(2024, 3);
        $ym2 = new PlainYearMonth(2025, 3);
        $this->assertFalse($ym1->equals($ym2));
    }

    // -------------------------------------------------------------------------
    // toPlainDate()
    // -------------------------------------------------------------------------

    public function testToPlainDate(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $date = $ym->toPlainDate(15);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testToPlainDateFirstDay(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $date = $ym->toPlainDate(1);
        $this->assertSame(1, $date->day);
    }

    public function testToPlainDateLastDay(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $date = $ym->toPlainDate(31);
        $this->assertSame(31, $date->day);
    }

    public function testToPlainDateInvalidDay(): void
    {
        $this->expectException(DateRangeException::class);
        $ym = new PlainYearMonth(2024, 3);
        $ym->toPlainDate(32);
    }

    public function testToPlainDateFebruaryLeap(): void
    {
        $ym = new PlainYearMonth(2024, 2);
        $date = $ym->toPlainDate(29);
        $this->assertSame(29, $date->day);
    }

    public function testToPlainDateFebruaryNonLeapInvalid(): void
    {
        $this->expectException(DateRangeException::class);
        $ym = new PlainYearMonth(2023, 2);
        $ym->toPlainDate(29);
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringBasic(): void
    {
        $ym = new PlainYearMonth(2024, 3);
        $this->assertSame('2024-03', (string) $ym);
    }

    public function testToStringJanuary(): void
    {
        $ym = new PlainYearMonth(2024, 1);
        $this->assertSame('2024-01', (string) $ym);
    }

    public function testToStringDecember(): void
    {
        $ym = new PlainYearMonth(2024, 12);
        $this->assertSame('2024-12', (string) $ym);
    }

    public function testToStringNegativeYear(): void
    {
        $ym = new PlainYearMonth(-100, 6);
        $this->assertSame('-000100-06', (string) $ym);
    }

    public function testToStringExtendedYear(): void
    {
        $ym = new PlainYearMonth(12345, 6);
        $this->assertSame('+012345-06', (string) $ym);
    }

    public function testToStringFourDigitYear(): void
    {
        $ym = new PlainYearMonth(2024, 6);
        $this->assertSame('2024-06', (string) $ym);
    }

    public function testRoundTripFromString(): void
    {
        $original = '2024-06';
        $ym = PlainYearMonth::from($original);
        $this->assertSame($original, (string) $ym);
    }
}
