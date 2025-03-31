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
 *  @file ApcuStorage.php
 *
 *  The Cache Storage using APCu extension to manage the cache data
 *
 *  @package    Platine\Cache\Storage
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Cache\Storage;

use DateInterval;
use Platine\Cache\Configuration;
use Platine\Cache\Exception\CacheException;
use Platine\Cache\Storage\AbstractStorage;

/**
 * @class ApcuStorage
 * @package Platine\Cache\Storage
 */
class ApcuStorage extends AbstractStorage
{
    /**
     * {@inheritdoc}
     *
     * Create new instance
     */
    public function __construct(?Configuration $config = null)
    {
        if ((!extension_loaded('apcu')) || !((bool) ini_get('apc.enabled'))) {
            throw new CacheException('The cache for APCu driver is not available.'
                            . ' Check if APCu extension is loaded and enabled.');
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $success = false;
        /** @var mixed */
        $data = apcu_fetch($key, $success);

        return $success ? $data : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        if ($ttl === null) {
            $ttl = $this->config->get('ttl');
        } elseif ($ttl instanceof DateInterval) {
            $ttl = $this->convertDateIntervalToSeconds($ttl);
        }

        /** @var bool */
        return apcu_store($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
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
        return apcu_exists($key) === true;
    }
}
