<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use Platine\Cache\Storage\NullStorage;
use Platine\Dev\PlatineTestCase;

/**
 * NullStorage class tests
 *
 * @group core
 * @group cache
 */
class NullStorageTest extends PlatineTestCase
{
    public function testAll(): void
    {
        $o = new NullStorage();
        $this->assertFalse($o->clear());
        $this->assertFalse($o->delete('foo'));
        $this->assertFalse($o->get('foo'));
        $this->assertFalse($o->has('foo'));
        $this->assertFalse($o->set('foo', 'bar'));
    }
}
