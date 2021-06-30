<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use Platine\Cache\Cache;
use Platine\Cache\Configuration;
use Platine\Cache\Exception\CacheException;
use Platine\Cache\Storage\NullStorage;
use Platine\Dev\PlatineTestCase;

/**
 * Cache class tests
 *
 * @group core
 * @group cache
 */
class CacheTest extends PlatineTestCase
{

    public function testConstructorDefault(): void
    {
        $l = new Cache();
        $this->assertInstanceOf(NullStorage::class, $l->getStorage());
    }

    public function testConstructorCustomStorage(): void
    {
        $storage = new NullStorage();

        $l = new Cache($storage);
        $this->assertInstanceOf(NullStorage::class, $l->getStorage());
        $this->assertEquals($storage, $l->getStorage());
    }

    public function testValidateKeyIsEmpty(): void
    {
        $l = new Cache();
        $this->expectException(CacheException::class);

        $this->runPrivateProtectedMethod($l, 'validateKey', array(''));
    }

    public function testValidateKeyReservedChar(): void
    {
         $l = new Cache();
        $this->expectException(CacheException::class);

        $this->runPrivateProtectedMethod($l, 'validateKey', array('ddff@sdf'));
    }

    public function testAll(): void
    {

        $o = new Cache();
        $this->assertFalse($o->clear());
        $this->assertFalse($o->delete('foo'));
        $this->assertFalse($o->get('foo'));
        $this->assertFalse($o->has('foo'));
        $this->assertFalse($o->set('foo', 'bar'));
    }
}
