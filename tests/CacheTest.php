<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use Platine\Cache\FileCache;
use Platine\Cache\Cache;
use Platine\PlatineTestCase;

/**
 * Cache class tests
 *
 * @group core
 * @group cache
 */
class CacheTest extends PlatineTestCase
{

    public function testConstructorAndCallMethods(): void
    {
        $fileCache = $this->getMockBuilder(FileCache::class)
                ->setMethods(array('get'))
                ->getMock();

        $l = new Cache($fileCache);
        $l->get('key');
        $mr = $this->getPrivateProtectedAttribute(Cache::class, 'handler');
        $this->assertInstanceOf(FileCache::class, $mr->getValue($l));
    }
}
