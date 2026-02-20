---
title: Installation
description: How to install PHP Temporal via Composer.
---

## Requirements

- PHP **8.4** or **8.5**
- Composer 2.x
- No PHP extensions required

## Install via Composer

```bash
composer require php-temporal/php-temporal
```

## Verify the Installation

```php
<?php

declare(strict_types=1);

use Temporal\PlainDate;

$date = PlainDate::from('2025-03-14');
echo $date; // 2025-03-14
```

## Running Tests

The library ships with a full test suite including 4,200+ TC39 reference tests:

```bash
# Core tests
./vendor/bin/phpunit --testsuite Temporal

# TC39 test262 data-driven tests
./vendor/bin/phpunit --testsuite test262
```
