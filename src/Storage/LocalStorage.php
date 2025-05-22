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
 *  @file LocalStorage.php
 *
 *  The Cache Driver using file system to manage the cache data
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
use Platine\Cache\Exception\FilesystemStorageException;
use Platine\Filesystem\DirectoryInterface;
use Platine\Filesystem\FileInterface;
use Platine\Filesystem\Filesystem;
use Platine\Stdlib\Helper\Path;
use Platine\Stdlib\Helper\Str;


/**
 * @class LocalStorage
 * @package Platine\Cache\Storage
 */
class LocalStorage extends AbstractStorage
{
     /**
     * The directory to use to save cache files
     * @var DirectoryInterface
     */
    protected DirectoryInterface $directory;

    /**
     * The file system instance
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * Create new instance
     *
     * {@inheritdoc}
     */
    public function __construct(Filesystem $filesystem, ?Configuration $config = null)
    {
        parent::__construct($config);
        $this->filesystem = $filesystem;

        $filePath = Path::normalizePathDS($this->config->get('storages.file.path'), true);
        $directory = $filesystem->directory($filePath);
        if ($directory->exists() === false || $directory->isWritable() === false) {
            throw new FilesystemStorageException(sprintf(
                'Cannot use file cache handler, because the directory %s does '
                    . 'not exist or is not writable',
                $filePath
            ));
        }

        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getCacheFile($key);

        if (!$file->exists() || $file->getMtime() <= time()) {
            return $default;
        }

        $data = $file->read();

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
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        if ($ttl === null) {
            $ttl = $this->config->get('ttl');
        } elseif ($ttl instanceof DateInterval) {
            $ttl = $this->convertDateIntervalToSeconds($ttl);
        }


        /** @var int */
        $expireAt = time() + $ttl;
        $file = $this->getCacheFile($key);
        $file->write(serialize($value));
        $file->touch($expireAt);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if ($file->exists()) {
            $file->delete();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $files = $this->directory->read(DirectoryInterface::FILE);
        foreach ($files as /** @var FileInterface $file */ $file) {
            if (
                Str::startsWith(
                    $this->config->get('storages.file.prefix'),
                    $file->getName()
                )
            ) {
                $file->delete();
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Return the file cache
     * @param string $key
     * @return FileInterface
     */
    protected function getCacheFile(string $key): FileInterface
    {
        $filename = $this->getFileName($key);
        $file = $this->filesystem->file(
            $this->directory->getPath() . DIRECTORY_SEPARATOR . $filename
        );

        return $file;
    }

    /**
     * Get cache file name for given key
     * @param  string $key
     * @return string      the filename
     */
    private function getFileName(string $key): string
    {
        $cleanKey = preg_replace('/[^A-Za-z0-9\.]+/', '_', $key);
        return sprintf(
            '%s%s.cache',
            $this->config->get('storages.file.prefix'),
            $cleanKey
        );
    }
}
