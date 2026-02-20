---
title: Overflow Handling
description: How PHP Temporal handles arithmetic that produces out-of-range field values.
---

When arithmetic produces a date with an out-of-range field (e.g. adding one month to January 31), PHP Temporal lets you control the behaviour with an **overflow** option.

## The `overflow` Option

The `add()` and `subtract()` methods on `PlainDate` and `PlainDateTime` accept an optional second parameter:

| Value | Behaviour |
|-------|-----------|
| `'constrain'` | *(default)* Clamp the result to the nearest valid value |
| `'reject'` | Throw an `ArithmeticError` if the result is out of range |

## Example: Month End Arithmetic

January has 31 days; adding one month yields February, which has at most 29 days.

```php
use Temporal\PlainDate;

$jan31 = new PlainDate(2025, 1, 31);

// constrain (default): clamps to last day of February
$result = $jan31->add(['months' => 1], 'constrain');
echo $result; // 2025-02-28

// reject: throws ArithmeticError
try {
    $jan31->add(['months' => 1], 'reject');
} catch (\ArithmeticError $e) {
    echo $e->getMessage(); // day 31 is out of range for 2025-02
}
```

## Constrain Behaviour Details

`constrain` clamps the final value to the **last valid day** of the resulting month:

```php
$date = new PlainDate(2024, 1, 31); // 2024 is a leap year
$date->add(['months' => 1]); // 2024-02-29 (Feb has 29 days in 2024)

$date = new PlainDate(2025, 1, 31); // 2025 is not a leap year
$date->add(['months' => 1]); // 2025-02-28
```

## `with()` Overflow

The `with()` method also accepts an `overflow` field inside the options array:

```php
$date = new PlainDate(2025, 2, 1);

// constrain: Feb 30 → Feb 28
$result = $date->with(['day' => 30], 'constrain');
echo $result; // 2025-02-28

// reject: throws for invalid day
$date->with(['day' => 30], 'reject'); // ArithmeticError
```

## Time Fields Never Overflow

Time fields (`hour`, `minute`, `second`, etc.) always wrap around at midnight when using `PlainTime::add()` / `PlainTime::subtract()`. There is no overflow option for time arithmetic.
