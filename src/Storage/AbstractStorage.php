<?php

/**
 * Platine Cache
 *
 * Platine Cache is the implementation of PSR 16 simple cache
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Cache
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *  @file AbstractStorage.php
 *
 *  The base storage class that contains the implementation of common features
 *
 *  @package    Platine\Cache\Storage
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Cache\Storage;

use DateInterval;
use DateTimeImmutable;
use Platine\Cache\Configuration;

/**
 * Class AbstractStorage
 * @package Platine\Cache\Storage
 */
abstract class AbstractStorage implements StorageInterface
{

    /**
     * The cache configuration
     * @var Configuration
     */
    protected Configuration $config;

    /**
     * Create new instance
     * @param Configuration|null $config
     */
    public function __construct(?Configuration $config = null)
    {
        $this->config = $config ?? new Configuration([]);
    }

    /**
     * Convert the DateInterval to Unix timestamp
     * @param  DateInterval $date
     * @return int              the number of second
     */
    protected function convertDateIntervalToSeconds(DateInterval $date): int
    {
        $ref = new DateTimeImmutable();
        $time = $ref->add($date);

        return $time->getTimestamp() - $ref->getTimestamp();
    }
}
