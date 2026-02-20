<?php

declare(strict_types = 1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a duration of time with date and time components.
 *
 * All non-zero fields must share the same sign (positive or negative).
 * Sub-second precision is stored separately as milliseconds, microseconds,
 * and nanoseconds — matching the JavaScript Temporal API.
 */
final class Duration
{
    /** -1, 0, or 1 */
    public readonly int $sign;

    /** True if every field is zero. */
    public readonly bool $blank;

    public function __construct(
        public readonly int $years = 0,
        public readonly int $months = 0,
        public readonly int $weeks = 0,
        public readonly int $days = 0,
        public readonly int $hours = 0,
        public readonly int $minutes = 0,
        public readonly int $seconds = 0,
        public readonly int $milliseconds = 0,
        public readonly int $microseconds = 0,
        public readonly int $nanoseconds = 0
    ) {
        $pos = $neg = 0;
        foreach ([
            $years,
            $months,
            $weeks,
            $days,
            $hours,
            $minutes,
            $seconds,
            $milliseconds,
            $microseconds,
            $nanoseconds
        ] as $v) {
            if ($v > 0) {
                $pos++;
            } elseif ($v < 0) {
                $neg++;
            }
        }

        if ($pos > 0 && $neg > 0) {
            throw new InvalidArgumentException(
                'Duration fields must all have the same sign; got mixed positive and negative values.'
            );
        }

        $this->sign = $pos > 0 ? 1 : ( $neg > 0 ? -1 : 0 );
        $this->blank = $pos === 0 && $neg === 0;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    public static function from(self|array|string $value): self
    {
        if ($value instanceof self) {
            return new self(
                $value->years,
                $value->months,
                $value->weeks,
                $value->days,
                $value->hours,
                $value->minutes,
                $value->seconds,
                $value->milliseconds,
                $value->microseconds,
                $value->nanoseconds
            );
        }

        if (is_array($value)) {
            return new self(
                years: $value['years'] ?? 0,
                months: $value['months'] ?? 0,
                weeks: $value['weeks'] ?? 0,
                days: $value['days'] ?? 0,
                hours: $value['hours'] ?? 0,
                minutes: $value['minutes'] ?? 0,
                seconds: $value['seconds'] ?? 0,
                milliseconds: $value['milliseconds'] ?? 0,
                microseconds: $value['microseconds'] ?? 0,
                nanoseconds: $value['nanoseconds'] ?? 0
            );
        }

        return self::parse($value);
    }

    // -------------------------------------------------------------------------
    // Derived / computed
    // -------------------------------------------------------------------------

    public function negated(): self
    {
        return new self(
            -$this->years,
            -$this->months,
            -$this->weeks,
            -$this->days,
            -$this->hours,
            -$this->minutes,
            -$this->seconds,
            -$this->milliseconds,
            -$this->microseconds,
            -$this->nanoseconds
        );
    }

    public function abs(): self
    {
        return new self(
            abs($this->years),
            abs($this->months),
            abs($this->weeks),
            abs($this->days),
            abs($this->hours),
            abs($this->minutes),
            abs($this->seconds),
            abs($this->milliseconds),
            abs($this->microseconds),
            abs($this->nanoseconds)
        );
    }

    public function with(array $fields): self
    {
        return new self(
            years: $fields['years'] ?? $this->years,
            months: $fields['months'] ?? $this->months,
            weeks: $fields['weeks'] ?? $this->weeks,
            days: $fields['days'] ?? $this->days,
            hours: $fields['hours'] ?? $this->hours,
            minutes: $fields['minutes'] ?? $this->minutes,
            seconds: $fields['seconds'] ?? $this->seconds,
            milliseconds: $fields['milliseconds'] ?? $this->milliseconds,
            microseconds: $fields['microseconds'] ?? $this->microseconds,
            nanoseconds: $fields['nanoseconds'] ?? $this->nanoseconds
        );
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    public function add(self|array|string $other): self
    {
        $other = self::from($other);

        return new self(
            $this->years + $other->years,
            $this->months + $other->months,
            $this->weeks + $other->weeks,
            $this->days + $other->days,
            $this->hours + $other->hours,
            $this->minutes + $other->minutes,
            $this->seconds + $other->seconds,
            $this->milliseconds + $other->milliseconds,
            $this->microseconds + $other->microseconds,
            $this->nanoseconds + $other->nanoseconds
        );
    }

    public function subtract(self|array|string $other): self
    {
        return $this->add(self::from($other)->negated());
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two durations by their total nanosecond value (time fields only).
     * Returns -1, 0, or 1.
     */
    public static function compare(self|array|string $one, self|array|string $two): int
    {
        $nsOne = self::toTotalNanoseconds(self::from($one));
        $nsTwo = self::toTotalNanoseconds(self::from($two));

        return $nsOne <=> $nsTwo;
    }

    // -------------------------------------------------------------------------
    // Totaling
    // -------------------------------------------------------------------------

    /**
     * Return the total value of this duration expressed in the given unit.
     *
     * Accepts either a unit string or an options array with keys:
     *   - 'unit'       (required) — the target unit
     *   - 'relativeTo' (optional) — a PlainDate, array, or date string;
     *                  required when 'unit' is 'year(s)' or 'month(s)'.
     *
     * Supported units: nanosecond(s) … week(s) without relativeTo;
     * additionally year(s) and month(s) with a PlainDate relativeTo.
     *
     * @param string|array{unit?:string,relativeTo?:PlainDate|array{year:int,month:int,day:int}|string} $unitOrOptions
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    public function total(string|array $unitOrOptions): float
    {
        if (is_string($unitOrOptions)) {
            return $this->totalByUnit($unitOrOptions);
        }

        $unit = $unitOrOptions['unit'] ?? null;

        if ($unit === null) {
            throw new InvalidArgumentException("Duration.total() options must include 'unit'.");
        }

        $relativeTo = $unitOrOptions['relativeTo'] ?? null;

        if ($relativeTo === null) {
            if (in_array($unit, ['year', 'years', 'month', 'months'], true)) {
                throw new InvalidArgumentException(
                    "Duration.total() requires a 'relativeTo' option when unit is '{$unit}'."
                );
            }

            return $this->totalByUnit($unit);
        }

        return $this->totalRelativeTo($unit, PlainDate::from($relativeTo));
    }

    /**
     * Compute the total for a pure time-based unit (no calendar awareness needed).
     */
    private function totalByUnit(string $unit): float
    {
        $ns = (float) self::toTotalNanoseconds($this);

        return match ($unit) {
            'nanosecond', 'nanoseconds' => $ns,
            'microsecond', 'microseconds' => $ns / 1_000,
            'millisecond', 'milliseconds' => $ns / 1_000_000,
            'second', 'seconds' => $ns / 1_000_000_000,
            'minute', 'minutes' => $ns / ( 60 * 1_000_000_000 ),
            'hour', 'hours' => $ns / ( 3_600 * 1_000_000_000 ),
            'day', 'days' => $ns / ( 86_400 * 1_000_000_000 ),
            'week', 'weeks' => $ns / ( 7 * 86_400 * 1_000_000_000 ),
            default => throw new InvalidArgumentException("Unknown or unsupported unit for total(): '{$unit}'.")
        };
    }

    /**
     * Compute the total relative to a reference PlainDate, supporting calendar units.
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function totalRelativeTo(string $unit, PlainDate $relativeTo): float
    {
        // Apply date components to get the end date.
        $endDate = $relativeTo->add([
            'years' => $this->years,
            'months' => $this->months,
            'weeks' => $this->weeks,
            'days' => $this->days
        ]);

        // Time components as nanoseconds (fractional days).
        $timeNs = $this->timeComponentNanoseconds();
        $dateDays = $endDate->toEpochDays() - $relativeTo->toEpochDays();
        $totalDaysFloat = (float) $dateDays + ( $timeNs / ( 86_400.0 * 1_000_000_000.0 ) );

        return match ($unit) {
            'nanosecond', 'nanoseconds' => $totalDaysFloat * 86_400.0 * 1_000_000_000.0,
            'microsecond', 'microseconds' => $totalDaysFloat * 86_400.0 * 1_000_000.0,
            'millisecond', 'milliseconds' => $totalDaysFloat * 86_400.0 * 1_000.0,
            'second', 'seconds' => $totalDaysFloat * 86_400.0,
            'minute', 'minutes' => $totalDaysFloat * 1_440.0,
            'hour', 'hours' => $totalDaysFloat * 24.0,
            'day', 'days' => $totalDaysFloat,
            'week', 'weeks' => $totalDaysFloat / 7.0,
            'month', 'months' => $this->daysToFractionalMonths($totalDaysFloat, $relativeTo),
            'year', 'years' => $this->daysToFractionalYears($totalDaysFloat, $relativeTo),
            default => throw new InvalidArgumentException("Unknown or unsupported unit for total(): '{$unit}'.")
        };
    }

    /**
     * Convert a floating-point day count to fractional months relative to a start date.
     *
     * Uses calendar-aware month lengths: 40 days from Feb 1, 2020 (leap year)
     * = 1 + 11/31 months because February has 29 days and March has 31.
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function daysToFractionalMonths(float $totalDays, PlainDate $relativeTo): float
    {
        if ($totalDays === 0.0) {
            return 0.0;
        }

        $sign = $totalDays > 0.0 ? 1 : -1;
        $months = 0;

        // Advance month by month until the next step would overshoot.
        while (true) {
            $nextDate = $relativeTo->add(['months' => $months + $sign]);
            $nextDays = (float) ( $nextDate->toEpochDays() - $relativeTo->toEpochDays() );

            if ($sign > 0 && $nextDays > $totalDays) {
                break;
            }

            if ($sign < 0 && $nextDays < $totalDays) {
                break;
            }

            $months += $sign;
        }

        $midDate = $relativeTo->add(['months' => $months]);
        $nextDate = $relativeTo->add(['months' => $months + $sign]);
        $midDays = (float) ( $midDate->toEpochDays() - $relativeTo->toEpochDays() );
        $nextDays = (float) ( $nextDate->toEpochDays() - $relativeTo->toEpochDays() );
        $monthLength = abs($nextDays - $midDays);
        $remainingDays = $totalDays - $midDays;

        return $monthLength > 0.0 ? (float) $months + ( $remainingDays / $monthLength ) : (float) $months;
    }

    /**
     * Convert a floating-point day count to fractional years relative to a start date.
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function daysToFractionalYears(float $totalDays, PlainDate $relativeTo): float
    {
        if ($totalDays === 0.0) {
            return 0.0;
        }

        $sign = $totalDays > 0.0 ? 1 : -1;
        $years = 0;

        while (true) {
            $nextDate = $relativeTo->add(['years' => $years + $sign]);
            $nextDays = (float) ( $nextDate->toEpochDays() - $relativeTo->toEpochDays() );

            if ($sign > 0 && $nextDays > $totalDays) {
                break;
            }

            if ($sign < 0 && $nextDays < $totalDays) {
                break;
            }

            $years += $sign;
        }

        $midDate = $relativeTo->add(['years' => $years]);
        $nextDate = $relativeTo->add(['years' => $years + $sign]);
        $midDays = (float) ( $midDate->toEpochDays() - $relativeTo->toEpochDays() );
        $nextDays = (float) ( $nextDate->toEpochDays() - $relativeTo->toEpochDays() );
        $yearLength = abs($nextDays - $midDays);
        $remainingDays = $totalDays - $midDays;

        return $yearLength > 0.0 ? (float) $years + ( $remainingDays / $yearLength ) : (float) $years;
    }

    /**
     * Return the nanosecond total of all time fields (hours … nanoseconds),
     * ignoring any calendar fields (years, months, weeks, days).
     */
    private function timeComponentNanoseconds(): int
    {
        return (
            ( ( ( $this->hours * 3_600 ) + ( $this->minutes * 60 ) + $this->seconds ) * 1_000_000_000 )
            + ( $this->milliseconds * 1_000_000 )
            + ( $this->microseconds * 1_000 )
            + $this->nanoseconds
        );
    }

    // -------------------------------------------------------------------------
    // Rounding
    // -------------------------------------------------------------------------

    /**
     * Round this duration.
     *
     * Accepts either a unit string (half-expand to that unit, no relativeTo),
     * or an options array with keys:
     *   - 'smallestUnit'      (required) — the target smallest unit
     *   - 'largestUnit'       (optional) — the largest unit to balance into
     *   - 'roundingMode'      (optional, default 'halfExpand') — halfExpand|ceil|floor|trunc
     *   - 'roundingIncrement' (optional, default 1) — round to multiples of this
     *   - 'relativeTo'        (optional) — PlainDate, array, or date string;
     *                         required when duration has calendar units and
     *                         smallestUnit is below 'month'
     *
     * @param string|array{smallestUnit?:string,largestUnit?:string,roundingMode?:string,roundingIncrement?:int,relativeTo?:PlainDate|array{year:int,month:int,day:int}|string} $smallestUnitOrOptions
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    public function round(string|array $smallestUnitOrOptions): self
    {
        if (is_string($smallestUnitOrOptions)) {
            return $this->roundSimple($smallestUnitOrOptions);
        }

        $options = $smallestUnitOrOptions;
        $smallestUnit = $options['smallestUnit'] ?? null;

        if ($smallestUnit === null) {
            throw new InvalidArgumentException("round() options must include 'smallestUnit'.");
        }

        $largestUnit = $options['largestUnit'] ?? null;
        $roundingMode = $options['roundingMode'] ?? 'halfExpand';
        $roundingIncrement = $options['roundingIncrement'] ?? 1;
        $relativeTo = $options['relativeTo'] ?? null;

        if ((int) $roundingIncrement < 1) {
            throw new InvalidArgumentException('roundingIncrement must be at least 1.');
        }

        // When the duration has calendar units (years/months) and smallestUnit is
        // a sub-month unit, largestUnit must be specified.
        $hasCalendarUnits = $this->years !== 0 || $this->months !== 0;
        $subMonthUnits = [
            'week',
            'weeks',
            'day',
            'days',
            'hour',
            'hours',
            'minute',
            'minutes',
            'second',
            'seconds',
            'millisecond',
            'milliseconds',
            'microsecond',
            'microseconds',
            'nanosecond',
            'nanoseconds'
        ];

        if ($hasCalendarUnits && in_array($smallestUnit, $subMonthUnits, true) && $largestUnit === null) {
            throw new \RangeException(
                'When the duration has calendar units (years/months), '
                . "'largestUnit' is required when 'smallestUnit' is '{$smallestUnit}'."
            );
        }

        if ($relativeTo === null) {
            if ($largestUnit !== null) {
                return $this->roundWithBalance($smallestUnit, $largestUnit, $roundingMode, (int) $roundingIncrement);
            }

            return $this->roundSimple($smallestUnit);
        }

        return $this->roundWithRelativeTo(
            $smallestUnit,
            $largestUnit,
            $roundingMode,
            (int) $roundingIncrement,
            PlainDate::from($relativeTo)
        );
    }

    /**
     * Balance this duration by redistributing weeks/days/time fields from
     * largestUnit downward, without rounding (i.e. no precision loss).
     *
     * Calendar fields (years, months) are preserved unchanged.  Only the
     * sub-year portion (weeks, days, hours … nanoseconds) is re-expressed
     * starting from largestUnit.
     *
     * @param string|array{largestUnit:string,smallestUnit?:string} $largestUnitOrOptions
     * @throws InvalidArgumentException
     */
    public function balance(string|array $largestUnitOrOptions): self
    {
        if (is_string($largestUnitOrOptions)) {
            $largestUnit = $largestUnitOrOptions;
            $smallestUnit = 'nanosecond';
        } else {
            $largestUnit = $largestUnitOrOptions['largestUnit'] ?? throw new InvalidArgumentException(
                'Missing required option: largestUnit.'
            );
            $smallestUnit = $largestUnitOrOptions['smallestUnit'] ?? 'nanosecond';
        }

        return $this->roundWithBalance($smallestUnit, $largestUnit, 'trunc', 1);
    }

    /**
     * Simple rounding (original behaviour): half-expand to the given unit,
     * zeroing all sub-unit fields. No relativeTo needed.
     */
    private function roundSimple(string $smallestUnit): self
    {
        return match ($smallestUnit) {
            'nanosecond', 'nanoseconds' => $this,
            'microsecond', 'microseconds' => $this->roundToMicroseconds(),
            'millisecond', 'milliseconds' => $this->roundToMilliseconds(),
            'second', 'seconds' => $this->roundToSeconds(),
            'minute', 'minutes' => $this->roundToMinutes(),
            'hour', 'hours' => $this->roundToHours(),
            'day', 'days' => $this->roundToDays(),
            'week', 'weeks' => $this->roundToWeeks(),
            default => throw new InvalidArgumentException("Unknown or unsupported unit for round(): '{$smallestUnit}'.")
        };
    }

    /**
     * Round with a relativeTo date for calendar-aware rounding.
     *
     * When largestUnit === smallestUnit, the entire duration is collapsed
     * into that single unit and rounded. Otherwise the result is balanced
     * from largestUnit down to smallestUnit with rounding applied to the
     * smallestUnit part.
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function roundWithRelativeTo(
        string $smallestUnit,
        ?string $largestUnit,
        string $roundingMode,
        int $roundingIncrement,
        PlainDate $relativeTo
    ): self {
        // Compute total days using the reference date.
        $endDate = $relativeTo->add([
            'years' => $this->years,
            'months' => $this->months,
            'weeks' => $this->weeks,
            'days' => $this->days
        ]);

        $timeNs = $this->timeComponentNanoseconds();
        $dateDays = $endDate->toEpochDays() - $relativeTo->toEpochDays();
        $totalDaysFloat = (float) $dateDays + ( $timeNs / ( 86_400.0 * 1_000_000_000.0 ) );

        // Use largestUnit as the effective unit when both are the same,
        // otherwise default to largestUnit for the conversion.
        $effectiveUnit = $largestUnit ?? $smallestUnit;

        // Convert total days to the effective unit.
        $totalInUnit = match ($effectiveUnit) {
            'week', 'weeks' => $totalDaysFloat / 7.0,
            'day', 'days' => $totalDaysFloat,
            'hour', 'hours' => $totalDaysFloat * 24.0,
            'minute', 'minutes' => $totalDaysFloat * 1_440.0,
            'second', 'seconds' => $totalDaysFloat * 86_400.0,
            'millisecond', 'milliseconds' => $totalDaysFloat * 86_400_000.0,
            'microsecond', 'microseconds' => $totalDaysFloat * 86_400_000_000.0,
            'nanosecond', 'nanoseconds' => $totalDaysFloat * 86_400_000_000_000.0,
            default => throw new InvalidArgumentException(
                "Unsupported largestUnit/smallestUnit for round(): '{$effectiveUnit}'."
            )
        };

        // Apply rounding mode and increment.
        $ratio = $totalInUnit / $roundingIncrement;
        $rounded = $this->applyRoundingMode($ratio, $roundingMode);
        $intRounded = (int) round($rounded * $roundingIncrement);

        // Return a duration expressing the result in the effective unit only.
        // Note: $effectiveUnit is guaranteed to match one of the arms below
        // because the first match already validated the value.
        return match ($effectiveUnit) {
            'week', 'weeks' => new self(weeks: $intRounded),
            'day', 'days' => new self(days: $intRounded),
            'hour', 'hours' => new self(hours: $intRounded),
            'minute', 'minutes' => new self(minutes: $intRounded),
            'second', 'seconds' => new self(seconds: $intRounded),
            'millisecond', 'milliseconds' => new self(milliseconds: $intRounded),
            'microsecond', 'microseconds' => new self(microseconds: $intRounded),
            default => new self(nanoseconds: $intRounded)
        };
    }

    /**
     * Balance the duration's sub-year fields from largestUnit down to smallestUnit,
     * applying the given rounding mode and increment at the smallestUnit level.
     *
     * Calendar fields (years, months) are preserved as-is.
     * Day and time fields are treated as fixed: 1 week = 7 days, 1 day = 24 h.
     *
     * @throws InvalidArgumentException
     */
    private function roundWithBalance(
        string $smallestUnit,
        string $largestUnit,
        string $roundingMode,
        int $roundingIncrement
    ): self {
        $normalize = static fn(string $u): string => match ($u) {
            'years' => 'year',
            'months' => 'month',
            'weeks' => 'week',
            'days' => 'day',
            'hours' => 'hour',
            'minutes' => 'minute',
            'seconds' => 'second',
            'milliseconds' => 'millisecond',
            'microseconds' => 'microsecond',
            'nanoseconds' => 'nanosecond',
            default => $u
        };

        $smallest = $normalize($smallestUnit);
        $largest = $normalize($largestUnit);

        $unitRanks = [
            'nanosecond' => 0,
            'microsecond' => 1,
            'millisecond' => 2,
            'second' => 3,
            'minute' => 4,
            'hour' => 5,
            'day' => 6,
            'week' => 7
        ];

        if (!isset($unitRanks[$smallest])) {
            throw new InvalidArgumentException("Unknown or unsupported unit for round()/balance(): '{$smallest}'.");
        }

        if (!isset($unitRanks[$largest])) {
            throw new InvalidArgumentException("Unknown or unsupported unit for round()/balance(): '{$largest}'.");
        }

        $smallestRank = $unitRanks[$smallest];
        $largestRank = $unitRanks[$largest];

        // Nanoseconds per unit (for the non-calendar hierarchy)
        $nsPerUnit = [
            'week' => 604_800_000_000_000,
            'day' => 86_400_000_000_000,
            'hour' => 3_600_000_000_000,
            'minute' => 60_000_000_000,
            'second' => 1_000_000_000,
            'millisecond' => 1_000_000,
            'microsecond' => 1_000,
            'nanosecond' => 1
        ];

        $sign = $this->sign >= 0 ? 1 : -1;

        // Compute total nanoseconds from ALL sub-year/month fields
        $totalNs =
            ( abs($this->weeks) * 604_800_000_000_000 )
            + ( abs($this->days) * 86_400_000_000_000 )
            + ( abs($this->hours) * 3_600_000_000_000 )
            + ( abs($this->minutes) * 60_000_000_000 )
            + ( abs($this->seconds) * 1_000_000_000 )
            + ( abs($this->milliseconds) * 1_000_000 )
            + ( abs($this->microseconds) * 1_000 )
            + abs($this->nanoseconds);

        // Apply rounding at the smallestUnit level
        $step = $nsPerUnit[$smallest] * $roundingIncrement;
        $totalNs = match ($roundingMode) {
            'halfExpand' => self::halfExpandRoundNs($totalNs, $step),
            'ceil' => (int) ( ceil($totalNs / $step) * $step ),
            'floor', 'trunc' => intdiv($totalNs, $step) * $step,
            default => throw new InvalidArgumentException("Unknown roundingMode: '{$roundingMode}'.")
        };

        // Distribute from largestUnit down to smallestUnit
        $unitOrder = ['week', 'day', 'hour', 'minute', 'second', 'millisecond', 'microsecond', 'nanosecond'];
        $fields = [];
        $remaining = $totalNs;
        $started = false;

        foreach ($unitOrder as $unit) {
            $rank = $unitRanks[$unit];

            if ($rank === $largestRank) {
                $started = true;
            }

            if (!$started) {
                continue;
            }

            if ($rank < $smallestRank) {
                break;
            }

            if ($rank === $smallestRank) {
                $fields[$unit] = intdiv($remaining, $nsPerUnit[$unit]);
                break;
            }

            $fields[$unit] = intdiv($remaining, $nsPerUnit[$unit]);
            $remaining -= $fields[$unit] * $nsPerUnit[$unit];
        }

        return new self(
            years: $this->years,
            months: $this->months,
            weeks: $sign * ( $fields['week'] ?? 0 ),
            days: $sign * ( $fields['day'] ?? 0 ),
            hours: $sign * ( $fields['hour'] ?? 0 ),
            minutes: $sign * ( $fields['minute'] ?? 0 ),
            seconds: $sign * ( $fields['second'] ?? 0 ),
            milliseconds: $sign * ( $fields['millisecond'] ?? 0 ),
            microseconds: $sign * ( $fields['microsecond'] ?? 0 ),
            nanoseconds: $sign * ( $fields['nanosecond'] ?? 0 )
        );
    }

    /** Half-expand rounding for non-negative integers. */
    private static function halfExpandRoundNs(int $ns, int $step): int
    {
        $remainder = $ns % $step;

        return ( $remainder * 2 ) >= $step ? ( intdiv($ns, $step) + 1 ) * $step : intdiv($ns, $step) * $step;
    }

    /**
     * Apply a rounding mode to a ratio value (value already divided by increment).
     */
    private function applyRoundingMode(float $ratio, string $mode): float
    {
        return match ($mode) {
            'halfExpand' => $ratio >= 0 ? floor($ratio + 0.5) : -floor(-$ratio + 0.5),
            'ceil' => ceil($ratio),
            'floor' => floor($ratio),
            'trunc' => $ratio >= 0 ? floor($ratio) : ceil($ratio),
            default => throw new InvalidArgumentException("Unknown roundingMode: '{$mode}'.")
        };
    }

    // -------------------------------------------------------------------------
    // ISO 8601 serialisation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        // Work with absolute values; prepend '-' at the end if negative.
        $yr = abs($this->years);
        $mo = abs($this->months);
        $wk = abs($this->weeks);
        $dy = abs($this->days);
        $hr = abs($this->hours);
        $min = abs($this->minutes);
        $sec = abs($this->seconds);
        $ms = abs($this->milliseconds);
        $us = abs($this->microseconds);
        $ns = abs($this->nanoseconds);

        $result = 'P';

        if ($yr !== 0) {
            $result .= $yr . 'Y';
        }
        if ($mo !== 0) {
            $result .= $mo . 'M';
        }
        if ($wk !== 0) {
            $result .= $wk . 'W';
        }
        if ($dy !== 0) {
            $result .= $dy . 'D';
        }

        $hasTime = $hr || $min || $sec || $ms || $us || $ns;
        $subSecNs = ( $ms * 1_000_000 ) + ( $us * 1_000 ) + $ns;

        if ($hasTime) {
            $result .= 'T';

            if ($hr !== 0) {
                $result .= $hr . 'H';
            }
            if ($min !== 0) {
                $result .= $min . 'M';
            }

            if ($sec !== 0 || $subSecNs !== 0) {
                if ($subSecNs === 0) {
                    $result .= $sec . 'S';
                } else {
                    // Format sub-second part as a 9-digit fraction, stripping trailing zeros.
                    $frac = str_pad((string) $subSecNs, 9, '0', STR_PAD_LEFT);
                    $frac = rtrim($frac, '0');
                    $result .= $sec . '.' . $frac . 'S';
                }
            }
        }

        if ($result === 'P') {
            $result = 'PT0S';
        }

        // Prepend sign for negative durations (PT0S stays unsigned).
        if ($this->sign === -1) {
            $result = '-' . $result;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Convert all time-and-day fields to a total nanosecond count.
     * Weeks and days use fixed 7-day weeks and 24-hour days.
     * Calendar fields (years, months) are ignored.
     */
    private static function toTotalNanoseconds(self $d): int
    {
        return (
            (
                (
                    ( $d->weeks * 7 * 86_400 )
                    + ( $d->days * 86_400 )
                    + ( $d->hours * 3_600 )
                    + ( $d->minutes * 60 )
                    + $d->seconds
                )
                * 1_000_000_000
            )
            + ( $d->milliseconds * 1_000_000 )
            + ( $d->microseconds * 1_000 )
            + $d->nanoseconds
        );
    }

    private function roundToMicroseconds(): self
    {
        $extra = (int) round(abs($this->nanoseconds) / 1_000) * ( $this->sign === -1 ? -1 : 1 );

        return new self(
            $this->years,
            $this->months,
            $this->weeks,
            $this->days,
            $this->hours,
            $this->minutes,
            $this->seconds,
            $this->milliseconds,
            $this->microseconds + $extra,
            0
        );
    }

    private function roundToMilliseconds(): self
    {
        $subNs = ( abs($this->microseconds) * 1_000 ) + abs($this->nanoseconds);
        $extra = (int) round($subNs / 1_000_000) * ( $this->sign === -1 ? -1 : 1 );

        return new self(
            $this->years,
            $this->months,
            $this->weeks,
            $this->days,
            $this->hours,
            $this->minutes,
            $this->seconds,
            $this->milliseconds + $extra,
            0,
            0
        );
    }

    private function roundToSeconds(): self
    {
        $subNs =
            ( abs($this->milliseconds) * 1_000_000 ) + ( abs($this->microseconds) * 1_000 ) + abs($this->nanoseconds);
        $extra = (int) round($subNs / 1_000_000_000) * ( $this->sign === -1 ? -1 : 1 );

        return new self(
            $this->years,
            $this->months,
            $this->weeks,
            $this->days,
            $this->hours,
            $this->minutes,
            $this->seconds + $extra,
            0,
            0,
            0
        );
    }

    private function roundToMinutes(): self
    {
        $subNs =
            ( abs($this->seconds) * 1_000_000_000 )
            + ( abs($this->milliseconds) * 1_000_000 )
            + ( abs($this->microseconds) * 1_000 )
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / ( 60 * 1_000_000_000 )) * ( $this->sign === -1 ? -1 : 1 );

        return new self(
            $this->years,
            $this->months,
            $this->weeks,
            $this->days,
            $this->hours,
            $this->minutes + $extra,
            0,
            0,
            0,
            0
        );
    }

    private function roundToHours(): self
    {
        $subNs =
            ( ( ( abs($this->minutes) * 60 ) + abs($this->seconds) ) * 1_000_000_000 )
            + ( abs($this->milliseconds) * 1_000_000 )
            + ( abs($this->microseconds) * 1_000 )
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / ( 3_600 * 1_000_000_000 )) * ( $this->sign === -1 ? -1 : 1 );

        return new self($this->years, $this->months, $this->weeks, $this->days, $this->hours + $extra, 0, 0, 0, 0, 0);
    }

    private function roundToDays(): self
    {
        $subNs =
            ( ( ( abs($this->hours) * 3_600 ) + ( abs($this->minutes) * 60 ) + abs($this->seconds) ) * 1_000_000_000 )
            + ( abs($this->milliseconds) * 1_000_000 )
            + ( abs($this->microseconds) * 1_000 )
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / ( 86_400 * 1_000_000_000 )) * ( $this->sign === -1 ? -1 : 1 );

        return new self($this->years, $this->months, $this->weeks, $this->days + $extra, 0, 0, 0, 0, 0, 0);
    }

    private function roundToWeeks(): self
    {
        $subNs =
            (
                (
                    ( abs($this->days) * 86_400 )
                    + ( abs($this->hours) * 3_600 )
                    + ( abs($this->minutes) * 60 )
                    + abs($this->seconds)
                )
                * 1_000_000_000
            )
            + ( abs($this->milliseconds) * 1_000_000 )
            + ( abs($this->microseconds) * 1_000 )
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / ( 7 * 86_400 * 1_000_000_000 )) * ( $this->sign === -1 ? -1 : 1 );

        return new self($this->years, $this->months, $this->weeks + $extra, 0, 0, 0, 0, 0, 0, 0);
    }

    // -------------------------------------------------------------------------
    // ISO 8601 parsing
    // -------------------------------------------------------------------------

    private static function parse(string $s): self
    {
        $negative = false;

        if (str_starts_with($s, '-')) {
            $negative = true;
            $s = substr($s, 1);
        } elseif (str_starts_with($s, '+')) {
            $s = substr($s, 1);
        }

        if (!str_starts_with($s, 'P')) {
            throw new InvalidArgumentException("Invalid ISO 8601 duration string: missing 'P' designator in '$s'.");
        }
        $s = substr($s, 1); // strip 'P'

        // Split into date and time portions on 'T'
        $tPos = strpos($s, 'T');
        if ($tPos !== false) {
            $datePart = substr($s, 0, $tPos);
            $timePart = substr($s, $tPos + 1);
        } else {
            $datePart = $s;
            $timePart = '';
        }

        if ($datePart === '' && $timePart === '') {
            throw new InvalidArgumentException("Invalid ISO 8601 duration: empty duration after 'P'.");
        }

        [$years, $months, $weeks, $days] = self::parseDatePart($datePart);
        [$hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds] = self::parseTimePart($timePart);

        if ($negative) {
            $years = -$years;
            $months = -$months;
            $weeks = -$weeks;
            $days = -$days;
            $hours = -$hours;
            $minutes = -$minutes;
            $seconds = -$seconds;
            $milliseconds = -$milliseconds;
            $microseconds = -$microseconds;
            $nanoseconds = -$nanoseconds;
        }

        return new self(
            $years,
            $months,
            $weeks,
            $days,
            $hours,
            $minutes,
            $seconds,
            $milliseconds,
            $microseconds,
            $nanoseconds
        );
    }

    /** @return array{int,int,int,int} [years, months, weeks, days] */
    private static function parseDatePart(string $part): array
    {
        if ($part === '') {
            return [0, 0, 0, 0];
        }

        if (!preg_match('/^(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?$/', $part, $m) || $m[0] === '') {
            throw new InvalidArgumentException("Invalid ISO 8601 duration date part: '$part'.");
        }

        return [
            isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0,
            isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0,
            isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0,
            isset($m[4]) && $m[4] !== '' ? (int) $m[4] : 0
        ];
    }

    /** @return array{int,int,int,int,int,int} [hours, minutes, seconds, ms, us, ns] */
    private static function parseTimePart(string $part): array
    {
        if ($part === '') {
            return [0, 0, 0, 0, 0, 0];
        }

        if (!preg_match('/^(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?$/', $part, $m) || $m[0] === '') {
            throw new InvalidArgumentException("Invalid ISO 8601 duration time part: '$part'.");
        }

        $hours = isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0;
        $minutes = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

        $seconds = $milliseconds = $microseconds = $nanoseconds = 0;

        if (isset($m[3]) && $m[3] !== '') {
            $secStr = $m[3];
            $dotPos = strpos($secStr, '.');

            if ($dotPos !== false) {
                $seconds = (int) substr($secStr, 0, $dotPos);
                $fracRaw = substr($secStr, $dotPos + 1);
                // Normalise to exactly 9 digits (nanosecond resolution).
                $frac9 = str_pad(substr($fracRaw, 0, 9), 9, '0');
                $totalNs = (int) $frac9;

                $milliseconds = intdiv($totalNs, 1_000_000);
                $totalNs -= $milliseconds * 1_000_000;
                $microseconds = intdiv($totalNs, 1_000);
                $nanoseconds = $totalNs % 1_000;
            } else {
                $seconds = (int) $secStr;
            }
        }

        return [$hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds];
    }
}
