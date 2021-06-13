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
 *  @file Configuration.php
 *
 *  The Cache Configuration class
 *
 *  @package    Platine\Cache
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Cache;

use Platine\Stdlib\Config\AbstractConfiguration;

/**
 * Class Configuration
 * @package Platine\Cache
 */
class Configuration extends AbstractConfiguration
{

    /**
     * The default time to live for cache data
     * @var int
     */
    protected int $ttl = 300;

    /**
     * The path to use to save cache files
     * @var string
     */
    protected string $fileSavePath = '';

    /**
     * The cache file prefix
     * @var string
     */
    protected string $filePrefix = 'cache_';

    /**
     * Return the cache time to live
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Return the file storage path
     * @return string
     */
    public function getFileSavePath(): string
    {
        return $this->fileSavePath;
    }

    /**
     * Return the cache file prefix
     * @return string
     */
    public function getFilePrefix(): string
    {
        return $this->filePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules(): array
    {
        return [
            'ttl' => 'integer',
            'fileSavePath' => 'string',
            'filePrefix' => 'string',
        ];
    }
}
