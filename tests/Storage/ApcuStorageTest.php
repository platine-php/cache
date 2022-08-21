<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use DateInterval;
use Platine\Cache\Configuration;
use Platine\Cache\Exception\CacheException;
use Platine\Cache\Storage\ApcuStorage;
use Platine\Dev\PlatineTestCase;

/**
 * ApcuStorage class tests
 *
 * @group core
 * @group cache
 */
class ApcuStorageTest extends PlatineTestCase
{
    public function testConstructorExtensionIsNotLoaded(): void
    {
        global $mock_extension_loaded_to_false;

        $mock_extension_loaded_to_false = true;
        $this->expectException(CacheException::class);

        $cfg = $this->getMockInstance(Configuration::class);

        (new ApcuStorage($cfg));
    }

    public function testConstructorExtensionIstLoadedButNotEnabled(): void
    {
        global $mock_extension_loaded_to_true, $mock_ini_get_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_false = true;

        $this->expectException(CacheException::class);
        $cfg = $this->getMockInstance(Configuration::class);

        (new ApcuStorage($cfg));
    }

    public function testGet(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_fetch_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);

        $mock_apcu_fetch_to_false = true;
        //Default value
        $this->assertEquals('bar', $ac->get('not_found_key', 'bar'));

        $mock_apcu_fetch_to_false = false;
        //Return correct data
        $key = uniqid();

        $content = $ac->get($key);
        $this->assertEquals(md5($key), $content);
    }

    public function testHas(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_exists_to_true,
        $mock_apcu_exists_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;

        $cfg = $this->getMockInstance(Configuration::class);

        $key = uniqid();
        $ac = new ApcuStorage($cfg);

        $mock_apcu_exists_to_false = true;

        $this->assertFalse($ac->has($key));

        $mock_apcu_exists_to_false = false;
        $mock_apcu_exists_to_true = true;

        $this->assertTrue($ac->has($key));
    }

    public function testSetSimple(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_store_to_true;

        $key = uniqid();
        $data = array('foo' => 'bar');

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_store_to_true = true;

        $cfg = new Configuration([
            'ttl' => 89,
            'storages' => []
        ]);

        $ac = new ApcuStorage($cfg);
        $result = $ac->set($key, $data);
        $this->assertTrue($result);
    }

    public function testSetInvalidTtl(): void
    {
        global $mock_extension_loaded_to_true, $mock_ini_get_to_true;
        $this->expectException(CacheException::class);
        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);
        $ac->set('key', 'data', []);
    }

    public function testSetTtlIsDateInterval(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_store_to_true;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_store_to_true = true;
        $key = uniqid();

        $data = array('foo' => 'bar');

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);
        $result = $ac->set($key, $data, new DateInterval('PT4H'));
        $this->assertTrue($result);
    }

    public function testSetFailed(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_store_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_store_to_false = true;

        $cfg = new Configuration([
            'ttl' => 89,
            'storages' => []
        ]);

        $ac = new ApcuStorage($cfg);
        $result = $ac->set('key', 'data');
        $this->assertFalse($result);
    }

    public function testDeleteSuccess(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_delete_to_true;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_delete_to_true = true;

        $key = uniqid();

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);

        $this->assertTrue($ac->delete($key));
    }

    public function testDeleteFailed(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_delete_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_delete_to_false = true;

        $key = uniqid();

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);

        $this->assertFalse($ac->delete($key));
    }

    public function testClearFailed(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_clear_cache_to_false;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_clear_cache_to_false = true;

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);

        $this->assertFalse($ac->clear());
    }

    public function testClearSuccess(): void
    {
        global $mock_extension_loaded_to_true,
        $mock_ini_get_to_true,
        $mock_apcu_clear_cache_to_true;

        $mock_extension_loaded_to_true = true;
        $mock_ini_get_to_true = true;
        $mock_apcu_clear_cache_to_true = true;

        $cfg = $this->getMockInstance(Configuration::class);

        $ac = new ApcuStorage($cfg);

        $this->assertTrue($ac->clear());
    }
}
