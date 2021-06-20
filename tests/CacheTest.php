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

    public function testConstructorCustomConfiguration(): void
    {
        $cfg = new Configuration([
            'ttl' => 34,
            'driver' => 'null',
            'storages' => [
                'null' => [
                    'class' => NullStorage::class,
                ],
            ]
        ]);

        $l = new Cache($cfg);
        $this->assertInstanceOf(NullStorage::class, $l->getStorage());
        $conf = $this->getPropertyValue(Cache::class, $l, 'config');
        $this->assertInstanceOf(Configuration::class, $conf);
        $this->assertEquals($cfg, $conf);
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
