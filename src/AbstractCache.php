<?php

/**
 * Platine Framework
 *
 * Platine is a lightweight, high-performance, simple and elegant PHP Web framework
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Framework
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
 *  @file AbstractCache.php
 *
 *  The AbstractCache class contains the implementation of common cache features
 *
 *  @package    core
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */


declare(strict_types=1);

namespace Platine\Cache;

use Platine\Cache\Exception\CacheException;

abstract class AbstractCache implements CacheInterface
{

    /**
     * The default time to live for cache data
     * @var integer
     */
    protected int $defaultTtl;

    /**
     * Create new instance
     *
     * @param int $defaultTtl the value of default time to live to use
     */
    public function __construct(int $defaultTtl = 600)
    {
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function get(string $key, $default = null);

    /**
     * {@inheritdoc}
     */
    abstract public function set(string $key, $value, $ttl = null): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function delete(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function clear(): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function has(string $key): bool;

    /**
     * Validate the cache key
     * @param  string $key the key name
     * @return void
     *
     * @throws CacheException if key is invalid
     */
    protected function validateKey(string $key): void
    {
        //PSR-16 reserved caracters
        $reservedPsr16Keys = '/\{|\}|\(|\)|\/|\\\\|\@|\:/u';

        if ($key === '') {
            throw new CacheException('Invalid cache key, can not be null or empty');
        }

        if (preg_match($reservedPsr16Keys, $key, $matches)) {
            throw new CacheException(sprintf('Invalid caracter [%s] in cache key [%s]', $matches[0], $key));
        }
    }

    /**
     * Convert the DateInterval to Unix timestamp
     * @param  \DateInterval $date
     * @return int              the number of second
     */
    protected function convertDateIntervalToSeconds(\DateInterval $date): int
    {
        $ref = new \DateTimeImmutable();
        $time = $ref->add($date);

        return $time->getTimestamp() - $ref->getTimestamp();
    }
}
