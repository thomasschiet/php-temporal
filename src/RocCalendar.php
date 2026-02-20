<?php

declare(strict_types = 1);

namespace Temporal;

use Override;
use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\MissingFieldException;

/**
 * Republic of China (ROC / Minguo) calendar implementation.
 *
 * The ROC calendar epoch is ISO year 1912 (the founding of the Republic of
 * China). ROC year 1 = ISO year 1912.
 *
 * ISO year 2024  → ROC year 113,  era 'roc'
 * ISO year 1912  → ROC year 1,    era 'roc'
 * ISO year 1911  → ROC year 1,    era 'before-roc'
 * ISO year 1     → ROC year 1911, era 'before-roc'
 *
 * The month/day structure is identical to ISO 8601.
 *
 * Corresponds to the "roc" calendar in the TC39 Temporal proposal.
 */
final class RocCalendar implements CalendarProtocol
{
    /** ISO year of the ROC epoch (Republic Year 1). */
    private const EPOCH_ISO_YEAR = 1912;

    private static ?self $instance = null;

    private function __construct()
    {
    }

    /** Return the shared singleton instance. */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — identity
    // -------------------------------------------------------------------------

    #[Override]
    public function getId(): string
    {
        return 'roc';
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field queries
    // -------------------------------------------------------------------------

    /**
     * Return the ROC year.
     *
     * For CE: rocYear = isoYear - 1911  (1912 → 1, 2024 → 113)
     * For before-ROC: rocYear = 1912 - isoYear  (1911 → 1, 1910 → 2, …)
     */
    #[Override]
    public function year(int $isoYear, int $isoMonth, int $isoDay): int
    {
        if ($isoYear >= self::EPOCH_ISO_YEAR) {
            return $isoYear - self::EPOCH_ISO_YEAR + 1;
        }

        return self::EPOCH_ISO_YEAR - $isoYear;
    }

    #[Override]
    public function month(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoMonth;
    }

    #[Override]
    public function monthCode(int $isoYear, int $isoMonth, int $isoDay): string
    {
        return 'M' . str_pad((string) $isoMonth, 2, '0', STR_PAD_LEFT);
    }

    #[Override]
    public function day(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoDay;
    }

    #[Override]
    public function dayOfWeek(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::instance()->dayOfWeek($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function dayOfYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::instance()->dayOfYear($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function weekOfYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return IsoCalendar::instance()->weekOfYear($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function yearOfWeek(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return IsoCalendar::instance()->yearOfWeek($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function daysInWeek(): int
    {
        return 7;
    }

    #[Override]
    public function daysInMonth(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::daysInMonthFor($isoYear, $isoMonth);
    }

    #[Override]
    public function daysInYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::isLeapYear($isoYear) ? 366 : 365;
    }

    #[Override]
    public function monthsInYear(): int
    {
        return 12;
    }

    #[Override]
    public function inLeapYear(int $isoYear, int $isoMonth, int $isoDay): bool
    {
        return IsoCalendar::isLeapYear($isoYear);
    }

    /**
     * Return the era: 'roc' for years >= 1912, 'before-roc' for earlier years.
     */
    #[Override]
    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string
    {
        return $isoYear >= self::EPOCH_ISO_YEAR ? 'roc' : 'before-roc';
    }

    /**
     * Return the era-relative year.
     *
     * ROC:        eraYear = isoYear - 1911  (1912 → 1, 2024 → 113)
     * before-ROC: eraYear = 1912 - isoYear  (1911 → 1, 1910 → 2, …)
     */
    #[Override]
    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        if ($isoYear >= self::EPOCH_ISO_YEAR) {
            return $isoYear - self::EPOCH_ISO_YEAR + 1;
        }

        return self::EPOCH_ISO_YEAR - $isoYear;
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — date arithmetic (ISO-equivalent)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, int> $duration
     */
    #[Override]
    public function dateAdd(PlainDate $date, array $duration, string $overflow): PlainDate
    {
        return IsoCalendar::instance()->dateAdd($date, $duration, $overflow);
    }

    #[Override]
    public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit): Duration
    {
        return IsoCalendar::instance()->dateUntil($one, $two, $largestUnit);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — factory methods
    // -------------------------------------------------------------------------

    /**
     * Create a PlainDate from ROC calendar fields.
     *
     * Accepted field combinations:
     *   - year, month, day                     (proleptic ROC year)
     *   - era ('roc'|'before-roc'), eraYear, month, day
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function dateFromFields(array $fields, string $overflow): PlainDate
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $day = (int) ( $fields['day'] ?? throw MissingFieldException::missingKey('day') );
        $isoYear = $this->resolveIsoYear($fields);

        $maxDay = IsoCalendar::daysInMonthFor($isoYear, $month);

        if ($day > $maxDay) {
            if ($overflow === 'reject') {
                throw DateRangeException::dayRejected($day, $isoYear, $month, $maxDay);
            }

            $day = $maxDay;
        }

        return new PlainDate($isoYear, $month, $day, $this);
    }

    /**
     * Create a PlainYearMonth from ROC calendar fields.
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function yearMonthFromFields(array $fields, string $overflow): PlainYearMonth
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $isoYear = $this->resolveIsoYear($fields);

        return new PlainYearMonth($isoYear, $month);
    }

    /**
     * Create a PlainMonthDay from ROC calendar fields (month, day only).
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function monthDayFromFields(array $fields, string $overflow): PlainMonthDay
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $day = (int) ( $fields['day'] ?? throw MissingFieldException::missingKey('day') );

        return new PlainMonthDay($month, $day);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field helpers
    // -------------------------------------------------------------------------

    /**
     * @param  list<string> $fields
     * @return list<string>
     */
    #[Override]
    public function fields(array $fields): array
    {
        $valid = ['year', 'month', 'day', 'era', 'eraYear'];

        foreach ($fields as $field) {
            if (!in_array($field, $valid, true)) {
                throw InvalidOptionException::unknownCalendarField($field, $valid);
            }
        }

        return array_values($fields);
    }

    /**
     * @param  array<string, int> $fields
     * @param  array<string, int> $additionalFields
     * @return array<string, int>
     */
    #[Override]
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return array_merge($fields, $additionalFields);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the ISO year from a fields array.
     *
     * Accepts either a 'year' key (proleptic ROC year: 1 = 1912, -1 = 1910)
     * or the combination of 'era' ('roc'|'before-roc') and 'eraYear'.
     *
     * @param array<string, mixed> $fields
     */
    private function resolveIsoYear(array $fields): int
    {
        if (isset($fields['era'], $fields['eraYear'])) {
            $era = (string) $fields['era'];
            $eraYear = (int) $fields['eraYear'];

            return match ($era) {
                'roc' => self::EPOCH_ISO_YEAR - 1 + $eraYear,
                'before-roc' => self::EPOCH_ISO_YEAR - $eraYear,
                default => throw InvalidOptionException::unknownCalendarField("era={$era}", ['roc', 'before-roc'])
            };
        }

        $year = (int) ( $fields['year'] ?? throw MissingFieldException::missingKey('year') );

        // Proleptic ROC year: year 1 = ISO 1912, year 2 = ISO 1913, year 0 = ISO 1911, etc.
        return self::EPOCH_ISO_YEAR - 1 + $year;
    }
}
