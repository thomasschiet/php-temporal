---
title: Immutability
description: All PHP Temporal types are fully immutable — every operation returns a new instance.
---

All types in PHP Temporal are **fully immutable**. Every method that would modify a value instead returns a new instance with the requested change applied. The original object is never mutated.

## Why Immutability Matters

Mutable date objects are a common source of bugs:

```php
// BAD — PHP's DateTimeImmutable name is misleading for DateTime:
$dt = new DateTime('2025-03-14');
$later = $dt->modify('+1 month'); // $dt and $later are the SAME object!
echo $dt->format('Y-m-d');    // 2025-04-14 — oops, mutated!
echo $later->format('Y-m-d'); // 2025-04-14
```

PHP Temporal is different:

```php
// GOOD — PHP Temporal is truly immutable:
$date = new \Temporal\PlainDate(2025, 3, 14);
$later = $date->add(['months' => 1]);

echo $date;  // 2025-03-14  — unchanged
echo $later; // 2025-04-14  — new instance
```

## All Properties are Readonly

Every property on every type is declared `readonly`. You can read them freely but cannot assign to them:

```php
$date = new \Temporal\PlainDate(2025, 3, 14);
echo $date->year;  // 2025
echo $date->month; // 3
echo $date->day;   // 14

// $date->year = 2026; // TypeError — readonly property
```

## Safe to Share

Because instances are immutable, they are safe to pass anywhere without fear of mutation:

```php
function firstOfMonth(\Temporal\PlainDate $date): \Temporal\PlainDate
{
    return $date->with(['day' => 1]);
}

$original = \Temporal\PlainDate::from('2025-03-14');
$first    = firstOfMonth($original);

echo $original; // 2025-03-14  — still March 14th
echo $first;    // 2025-03-01  — new instance
```

## The `with()` Method

Every type exposes a `with()` method that returns a copy with specific fields changed:

```php
$time = new \Temporal\PlainTime(9, 30, 0);
$noon = $time->with(['hour' => 12]);

echo $time; // 09:30:00
echo $noon; // 12:30:00 — same minute and second, new hour
```
