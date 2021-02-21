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
 *  @file ApcCache.php
 *
 *  The Cache Driver using APCu extension to manage the cache data
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

class ApcuCache extends AbstractCache
{

    /**
     * {@inheritdoc}
     *
     * Create new instance
     */
    public function __construct(int $defaultTtl = 600)
    {
        if ((!extension_loaded('apcu')) || !((bool) ini_get('apc.enabled'))) {
            throw new CacheException('The cache for APCu driver is not available.'
                            . ' Check if APCu extension is loaded and enabled.');
        }

        parent::__construct($defaultTtl);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $this->validateKey($key);

        $success = false;
        /** @var mixed */
        $data = apcu_fetch($key, $success);

        return $success ? $data : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $this->validateKey($key);

        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        } elseif ($ttl instanceof \DateInterval) {
            $ttl = $this->convertDateIntervalToSeconds($ttl);
        } elseif (!is_int($ttl)) {
            throw new CacheException(sprintf('Invalid cache TTL [%s] ', print_r($ttl, true)));
        }
        /** @var bool */
        return apcu_store($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return apcu_delete($key) === true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        /** @var bool */
        return apcu_clear_cache();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return apcu_exists($key) === true;
    }
}
