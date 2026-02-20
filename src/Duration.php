<?php

declare(strict_types=1);

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
        public readonly int $nanoseconds = 0,
    ) {
        $pos = $neg = 0;
        foreach (
            [$years, $months, $weeks, $days, $hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds]
            as $v
        ) {
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

        $this->sign  = $pos > 0 ? 1 : ($neg > 0 ? -1 : 0);
        $this->blank = $pos === 0 && $neg === 0;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    public static function from(self|array|string $value): self
    {
        if ($value instanceof self) {
            return new self(
                $value->years, $value->months, $value->weeks, $value->days,
                $value->hours, $value->minutes, $value->seconds,
                $value->milliseconds, $value->microseconds, $value->nanoseconds,
            );
        }

        if (is_array($value)) {
            return new self(
                years:        $value['years']        ?? 0,
                months:       $value['months']       ?? 0,
                weeks:        $value['weeks']        ?? 0,
                days:         $value['days']         ?? 0,
                hours:        $value['hours']        ?? 0,
                minutes:      $value['minutes']      ?? 0,
                seconds:      $value['seconds']      ?? 0,
                milliseconds: $value['milliseconds'] ?? 0,
                microseconds: $value['microseconds'] ?? 0,
                nanoseconds:  $value['nanoseconds']  ?? 0,
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
            -$this->years, -$this->months, -$this->weeks, -$this->days,
            -$this->hours, -$this->minutes, -$this->seconds,
            -$this->milliseconds, -$this->microseconds, -$this->nanoseconds,
        );
    }

    public function abs(): self
    {
        return new self(
            abs($this->years), abs($this->months), abs($this->weeks), abs($this->days),
            abs($this->hours), abs($this->minutes), abs($this->seconds),
            abs($this->milliseconds), abs($this->microseconds), abs($this->nanoseconds),
        );
    }

    public function with(array $fields): self
    {
        return new self(
            years:        $fields['years']        ?? $this->years,
            months:       $fields['months']       ?? $this->months,
            weeks:        $fields['weeks']        ?? $this->weeks,
            days:         $fields['days']         ?? $this->days,
            hours:        $fields['hours']        ?? $this->hours,
            minutes:      $fields['minutes']      ?? $this->minutes,
            seconds:      $fields['seconds']      ?? $this->seconds,
            milliseconds: $fields['milliseconds'] ?? $this->milliseconds,
            microseconds: $fields['microseconds'] ?? $this->microseconds,
            nanoseconds:  $fields['nanoseconds']  ?? $this->nanoseconds,
        );
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    public function add(self|array|string $other): self
    {
        $other = self::from($other);

        return new self(
            $this->years        + $other->years,
            $this->months       + $other->months,
            $this->weeks        + $other->weeks,
            $this->days         + $other->days,
            $this->hours        + $other->hours,
            $this->minutes      + $other->minutes,
            $this->seconds      + $other->seconds,
            $this->milliseconds + $other->milliseconds,
            $this->microseconds + $other->microseconds,
            $this->nanoseconds  + $other->nanoseconds,
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
     * Supported units: nanosecond(s), microsecond(s), millisecond(s),
     * second(s), minute(s), hour(s), day(s), week(s).
     *
     * Calendar units (months, years) are not supported and will throw.
     */
    public function total(string $unit): float
    {
        $ns = (float) self::toTotalNanoseconds($this);

        return match ($unit) {
            'nanosecond',  'nanoseconds'  => $ns,
            'microsecond', 'microseconds' => $ns / 1_000,
            'millisecond', 'milliseconds' => $ns / 1_000_000,
            'second',      'seconds'      => $ns / 1_000_000_000,
            'minute',      'minutes'      => $ns / (60 * 1_000_000_000),
            'hour',        'hours'        => $ns / (3_600 * 1_000_000_000),
            'day',         'days'         => $ns / (86_400 * 1_000_000_000),
            'week',        'weeks'        => $ns / (7 * 86_400 * 1_000_000_000),
            default => throw new InvalidArgumentException(
                "Unknown or unsupported unit for total(): '$unit'."
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Rounding
    // -------------------------------------------------------------------------

    /**
     * Round this duration to the given smallest unit using half-expand
     * (round half away from zero). Returns a new Duration with all
     * sub-unit fields zeroed.
     */
    public function round(string $smallestUnit): self
    {
        return match ($smallestUnit) {
            'nanosecond',  'nanoseconds'  => $this,
            'microsecond', 'microseconds' => $this->roundToMicroseconds(),
            'millisecond', 'milliseconds' => $this->roundToMilliseconds(),
            'second',      'seconds'      => $this->roundToSeconds(),
            'minute',      'minutes'      => $this->roundToMinutes(),
            'hour',        'hours'        => $this->roundToHours(),
            'day',         'days'         => $this->roundToDays(),
            'week',        'weeks'        => $this->roundToWeeks(),
            default => throw new InvalidArgumentException(
                "Unknown or unsupported unit for round(): '$smallestUnit'."
            ),
        };
    }

    // -------------------------------------------------------------------------
    // ISO 8601 serialisation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        // Work with absolute values; prepend '-' at the end if negative.
        $yr  = abs($this->years);
        $mo  = abs($this->months);
        $wk  = abs($this->weeks);
        $dy  = abs($this->days);
        $hr  = abs($this->hours);
        $min = abs($this->minutes);
        $sec = abs($this->seconds);
        $ms  = abs($this->milliseconds);
        $us  = abs($this->microseconds);
        $ns  = abs($this->nanoseconds);

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

        $hasTime    = $hr || $min || $sec || $ms || $us || $ns;
        $subSecNs   = $ms * 1_000_000 + $us * 1_000 + $ns;

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
                    $frac    = str_pad((string) $subSecNs, 9, '0', STR_PAD_LEFT);
                    $frac    = rtrim($frac, '0');
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
            $d->weeks * 7 * 86_400
            + $d->days * 86_400
            + $d->hours * 3_600
            + $d->minutes * 60
            + $d->seconds
        ) * 1_000_000_000
            + $d->milliseconds * 1_000_000
            + $d->microseconds * 1_000
            + $d->nanoseconds;
    }

    private function roundToMicroseconds(): self
    {
        $extra = (int) round(abs($this->nanoseconds) / 1_000) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days,
            $this->hours, $this->minutes, $this->seconds,
            $this->milliseconds, $this->microseconds + $extra, 0,
        );
    }

    private function roundToMilliseconds(): self
    {
        $subNs = abs($this->microseconds) * 1_000 + abs($this->nanoseconds);
        $extra = (int) round($subNs / 1_000_000) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days,
            $this->hours, $this->minutes, $this->seconds,
            $this->milliseconds + $extra, 0, 0,
        );
    }

    private function roundToSeconds(): self
    {
        $subNs = abs($this->milliseconds) * 1_000_000
            + abs($this->microseconds) * 1_000
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / 1_000_000_000) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days,
            $this->hours, $this->minutes, $this->seconds + $extra, 0, 0, 0,
        );
    }

    private function roundToMinutes(): self
    {
        $subNs = (abs($this->seconds) * 1_000_000_000)
            + abs($this->milliseconds) * 1_000_000
            + abs($this->microseconds) * 1_000
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / (60 * 1_000_000_000)) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days,
            $this->hours, $this->minutes + $extra, 0, 0, 0, 0,
        );
    }

    private function roundToHours(): self
    {
        $subNs = (abs($this->minutes) * 60 + abs($this->seconds)) * 1_000_000_000
            + abs($this->milliseconds) * 1_000_000
            + abs($this->microseconds) * 1_000
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / (3_600 * 1_000_000_000)) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days,
            $this->hours + $extra, 0, 0, 0, 0, 0,
        );
    }

    private function roundToDays(): self
    {
        $subNs = (abs($this->hours) * 3_600 + abs($this->minutes) * 60 + abs($this->seconds)) * 1_000_000_000
            + abs($this->milliseconds) * 1_000_000
            + abs($this->microseconds) * 1_000
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / (86_400 * 1_000_000_000)) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks, $this->days + $extra,
            0, 0, 0, 0, 0, 0,
        );
    }

    private function roundToWeeks(): self
    {
        $subNs = (abs($this->days) * 86_400 + abs($this->hours) * 3_600 + abs($this->minutes) * 60 + abs($this->seconds)) * 1_000_000_000
            + abs($this->milliseconds) * 1_000_000
            + abs($this->microseconds) * 1_000
            + abs($this->nanoseconds);
        $extra = (int) round($subNs / (7 * 86_400 * 1_000_000_000)) * ($this->sign === -1 ? -1 : 1);

        return new self(
            $this->years, $this->months, $this->weeks + $extra,
            0, 0, 0, 0, 0, 0, 0,
        );
    }

    // -------------------------------------------------------------------------
    // ISO 8601 parsing
    // -------------------------------------------------------------------------

    private static function parse(string $s): self
    {
        $negative = false;

        if (str_starts_with($s, '-')) {
            $negative = true;
            $s        = substr($s, 1);
        } elseif (str_starts_with($s, '+')) {
            $s = substr($s, 1);
        }

        if (!str_starts_with($s, 'P')) {
            throw new InvalidArgumentException(
                "Invalid ISO 8601 duration string: missing 'P' designator in '$s'."
            );
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
            throw new InvalidArgumentException(
                "Invalid ISO 8601 duration: empty duration after 'P'."
            );
        }

        [$years, $months, $weeks, $days] = self::parseDatePart($datePart);
        [$hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds] = self::parseTimePart($timePart);

        if ($negative) {
            $years        = -$years;
            $months       = -$months;
            $weeks        = -$weeks;
            $days         = -$days;
            $hours        = -$hours;
            $minutes      = -$minutes;
            $seconds      = -$seconds;
            $milliseconds = -$milliseconds;
            $microseconds = -$microseconds;
            $nanoseconds  = -$nanoseconds;
        }

        return new self($years, $months, $weeks, $days, $hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds);
    }

    /** @return array{int,int,int,int} [years, months, weeks, days] */
    private static function parseDatePart(string $part): array
    {
        if ($part === '') {
            return [0, 0, 0, 0];
        }

        if (!preg_match('/^(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?$/', $part, $m) || $m[0] === '') {
            throw new InvalidArgumentException(
                "Invalid ISO 8601 duration date part: '$part'."
            );
        }

        return [
            isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0,
            isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0,
            isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0,
            isset($m[4]) && $m[4] !== '' ? (int) $m[4] : 0,
        ];
    }

    /** @return array{int,int,int,int,int,int} [hours, minutes, seconds, ms, us, ns] */
    private static function parseTimePart(string $part): array
    {
        if ($part === '') {
            return [0, 0, 0, 0, 0, 0];
        }

        if (!preg_match('/^(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?$/', $part, $m) || $m[0] === '') {
            throw new InvalidArgumentException(
                "Invalid ISO 8601 duration time part: '$part'."
            );
        }

        $hours   = isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0;
        $minutes = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

        $seconds = $milliseconds = $microseconds = $nanoseconds = 0;

        if (isset($m[3]) && $m[3] !== '') {
            $secStr = $m[3];
            $dotPos = strpos($secStr, '.');

            if ($dotPos !== false) {
                $seconds = (int) substr($secStr, 0, $dotPos);
                $fracRaw = substr($secStr, $dotPos + 1);
                // Normalise to exactly 9 digits (nanosecond resolution).
                $frac9   = str_pad(substr($fracRaw, 0, 9), 9, '0');
                $totalNs = (int) $frac9;

                $milliseconds  = intdiv($totalNs, 1_000_000);
                $totalNs      -= $milliseconds * 1_000_000;
                $microseconds  = intdiv($totalNs, 1_000);
                $nanoseconds   = $totalNs % 1_000;
            } else {
                $seconds = (int) $secStr;
            }
        }

        return [$hours, $minutes, $seconds, $milliseconds, $microseconds, $nanoseconds];
    }
}
