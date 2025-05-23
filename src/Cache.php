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
 *  @file Cache.php
 *
 *  The Cache Manager class
 *
 *  @package    Platine\Cache
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Cache;

use DateInterval;
use Platine\Cache\Exception\CacheException;
use Platine\Cache\Storage\NullStorage;
use Platine\Cache\Storage\StorageInterface;

/**
 * @class Cache
 * @package Platine\Cache
 */
class Cache implements CacheInterface
{
    /**
     * The cache storage to use
     * @var StorageInterface
     */
    protected StorageInterface $storage;

    /**
     * Create new instance
     * @param StorageInterface|null $storage the cache storage to use
     */
    public function __construct(?StorageInterface $storage = null)
    {
        $this->storage = $storage ?? new NullStorage();
    }

    /**
     * Return the storage instance
     * @return StorageInterface
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->storage->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return $this->storage->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        return $this->storage->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->storage->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->validateKey($key);

        return $this->storage->set($key, $value, $ttl);
    }

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
            throw new CacheException(
                'Invalid cache key, can not be null or empty'
            );
        }

        $matches = [];
        if (preg_match($reservedPsr16Keys, $key, $matches)) {
            throw new CacheException(sprintf(
                'Invalid caracter [%s] in cache key [%s]',
                $matches[0],
                $key
            ));
        }
    }
}
