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
 *  @file FileCache.php
 *
 *  The Cache Driver using filesystem to manage the cache data
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

use Platine\Cache\Exception\FileCacheException;
use Platine\Cache\Exception\CacheException;

class FileCache extends AbstractCache
{

    /**
     * The path to use to save cache files
     * @var string
     */
    protected string $savePath;

    /**
     * The cache file prefix
     * @var string
     */
    protected string $filePrefix;

    /**
     * Create new instance
     *
     * {@inheritdoc}
     *
     * @param string $savePath the path to directory to save cache files
     * @param string $filePrefix the cache file prefix
     */
    public function __construct(string $savePath = '', string $filePrefix = 'cache_', int $defaultTtl = 600)
    {
        parent::__construct($defaultTtl);

        if (empty($savePath)) {
            $savePath = sys_get_temp_dir();
        }

        $this->setSavePath($savePath);
        $this->filePrefix = $filePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $this->validateKey($key);

        $path = $this->savePath . $this->getFileName($key);

        /** @var bool|int */
        $expireAt = @filemtime($path);

        if ($expireAt === false) {
            //file not found
            return $default;
        }

        if (time() >= $expireAt) {
            //file expired
            @unlink($path);
            return $default;
        }

        /** @var string|false */
        $data = @file_get_contents($path);

        if ($data === false) {
            //race condition: file not found
            return $default;
        }

        /** @var bool|string */
        $value = @unserialize($data);


        if ($value === false) {
            //unserialize failed

            return $default;
        }


        return $value;
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
            throw new CacheException(sprintf(
                'Invalid cache TTL value expected null|int|DateInterval but got [%s]',
                gettype($ttl)
            ));
        }
        /** @var int */
        $expireAt = time() + $ttl;

        $path = $this->savePath . $this->getFileName($key);

        if (@file_put_contents($path, serialize($value)) === false) {
            return false;
        }

        if (@touch($path, $expireAt)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        $path = $this->savePath . $this->getFileName($key);

        return !file_exists($path) || @unlink($path);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $success = true;

        $cacheFiles = glob(sprintf('%s%s*', $this->savePath, $this->filePrefix));

        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $file) {
                if (!@unlink($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->get($key, $this) !== $this;
    }

    /**
     * Set save path
     *
     * @param string $savePath
     *
     * @return self
     */
    public function setSavePath(string $savePath): self
    {
        if (!is_dir($savePath)) {
            throw new FileCacheException(sprintf(
                'Cannot use file cache handler, because the directory %s does not exist',
                $savePath
            ));
        }
        if (!is_writable($savePath)) {
            throw new FileCacheException(sprintf(
                'Cannot use file cache handler, because the directory %s is not writable',
                $savePath
            ));
        }
        $this->savePath = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR;

        return $this;
    }

    /**
     * Get save path
     * @return string
     */
    public function getSavePath(): string
    {
        return $this->savePath;
    }

    /**
     * Get cache file name for given key
     * @param  string $key
     * @return string      the filename
     */
    private function getFileName(string $key): string
    {
        return sprintf('%s%s.cache', $this->filePrefix, md5($key));
    }
}
