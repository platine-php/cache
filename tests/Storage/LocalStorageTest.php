<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use DateInterval;
use org\bovigo\vfs\vfsStream;
use Platine\Cache\Configuration;
use Platine\Cache\Exception\CacheException;
use Platine\Cache\Exception\FilesystemStorageException;
use Platine\Cache\Storage\LocalStorage;
use Platine\Dev\PlatineTestCase;
use Platine\Filesystem\Adapter\Local\LocalAdapter;
use Platine\Filesystem\DirectoryInterface;
use Platine\Filesystem\Filesystem;

/**
 * LocalStorage class tests
 *
 * @group core
 * @group cache
 */
class LocalStorageTest extends PlatineTestCase
{

    protected $vfsRoot;
    protected $vfsCachePath;

    protected function setUp(): void
    {
        parent::setUp();
        //need setup for each test
        $this->vfsRoot = vfsStream::setup();
        $this->vfsCachePath = vfsStream::newDirectory('caches')->at($this->vfsRoot);
    }

    public function testConstructorOne(): void
    {
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $this->vfsCachePath->url(),
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($this->vfsCachePath->url());
        $fs = new Filesystem($adapter);

        $ls = new LocalStorage($fs, $cfg);

        $this->assertInstanceOf(
            DirectoryInterface::class,
            $this->getPropertyValue(LocalStorage::class, $ls, 'directory')
        );

        $this->assertInstanceOf(
            Filesystem::class,
            $this->getPropertyValue(LocalStorage::class, $ls, 'filesystem')
        );

        $this->assertInstanceOf(
            Configuration::class,
            $this->getPropertyValue(LocalStorage::class, $ls, 'config')
        );
    }

    public function testConstructorDirectoryNotFound(): void
    {
        $this->expectException(FilesystemStorageException::class);
        $path = 'path/not/found';
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter(null);
        $fs = new Filesystem($adapter);

        (new LocalStorage($fs, $cfg));
    }

    public function testConstructorDirectoryNotWritable(): void
    {
        $this->expectException(FilesystemStorageException::class);
        $path = $this->vfsCachePath->url();
        $adapter = new LocalAdapter($path);

        chmod($path, 0400);
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $fs = new Filesystem($adapter);

        (new LocalStorage($fs, $cfg));
    }


    public function testGetFilename(): void
    {
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);

        $ls = new LocalStorage($fs, $cfg);

        $key = 'foo';
        $file = $this->runPrivateProtectedMethod($ls, 'getFileName', array($key));

        $this->assertEquals($file, 'cache_' . md5($key) . '.cache');
    }

    public function testGet(): void
    {
        global $mock_filemtime_to_false,
        $mock_filemtime_to_int,
        $mock_file_get_contents_to_false,
        $mock_time_to_zero,
        $mock_unserialize_to_false,
        $mock_file_get_contents_to_data;

        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);

        //Default value
        $this->assertEquals('bar', $ls->get('not_found_key', 'bar'));

        //filemtime return false
        $mock_filemtime_to_false = true;
        $this->assertEquals('bar', $ls->get('not_found_key', 'bar'));

        //Restore
        $mock_filemtime_to_false = false;

        //file is expired
        $mock_filemtime_to_int = true;
        $mock_time = true;
        $this->assertEquals('bar', $ls->get('not_found_key', 'bar'));

        //Restore
        $mock_filemtime_to_int = false;
        $mock_time = false;

        //file_get_contents return false
        $mock_file_get_contents_to_false = true;
        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;
        $this->assertEquals('bar', $ls->get('not_found_key', 'bar'));



        //unserialize return false
        $mock_file_get_contents_to_false = false;
        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;
        $mock_unserialize_to_false = true;
        $mock_file_get_contents_to_data = true;
        $this->assertEquals('bar', $ls->get('not_found_key', 'bar'));
    }

    public function testGetUnserializeFailed(): void
    {
        global $mock_time_to_zero,
        $mock_unserialize_to_false;

        $mock_time_to_zero = true;
        $mock_unserialize_to_false = true;

        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $data = array('foo' => 'bar');

        vfsStream::newFile($filename)
                ->at($this->vfsCachePath)
                ->setContent(serialize($data));

        $ls = new LocalStorage($fs, $cfg);

        $this->assertEquals('bar', $ls->get($key, 'bar'));
    }

    public function testGetReturnCorrectData(): void
    {
        global $mock_time_to_zero;

        $mock_time_to_zero = true;

        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                ->at($this->vfsCachePath)
                ->setContent(serialize($data));

        $ls = new LocalStorage($fs, $cfg);
        $content = $ls->get($key);
        $this->assertEquals($data, $content);
    }

    public function testHas(): void
    {
        global $mock_filemtime_to_int,
        $mock_time_to_zero;


        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;

        //Return correct data
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                ->at($this->vfsCachePath)
                ->setContent(serialize($data));

        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);
        $this->assertTrue($ls->has($key));
    }

    public function testSetSimple(): void
    {
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                ->at($this->vfsCachePath)
                ->setContent(serialize($data));
        $cfg = new Configuration([
            'ttl' => 89,
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);
        $result = $ls->set($key, $data);
        $this->assertTrue($result);
        $this->assertEquals(
            serialize($data),
            file_get_contents($path . DIRECTORY_SEPARATOR . $filename)
        );
    }

    public function testSetInvalidTtl(): void
    {
        $this->expectException(CacheException::class);
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);
        $ls->set('key', 'data', []);
    }

    public function testSetTtlIsDateInterval(): void
    {
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                    ->at($this->vfsCachePath)
                    ->setContent(serialize($data));
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);
        $result = $ls->set($key, $data, new DateInterval('PT4H'));
        $this->assertTrue($result);
        $this->assertEquals(
            serialize($data),
            file_get_contents($path . DIRECTORY_SEPARATOR . $filename)
        );
    }

    public function testDeleteSuccess(): void
    {
        global $mock_filemtime_to_int,
        $mock_time_to_zero;


        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                    ->at($this->vfsCachePath)
                    ->setContent(serialize($data));
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);
        $ls = new LocalStorage($fs, $cfg);

        $content1 = $ls->get($key);
        $this->assertEquals($data, $content1);

        $this->assertTrue($ls->delete($key));

        $content2 = $ls->get($key);
        $this->assertNull($content2);
    }

    public function testDeleteFileNotExist(): void
    {
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);

        $ls = new LocalStorage($fs, $cfg);

        $this->assertTrue($ls->delete('key_not_found'));
    }

    public function testClearSuccess(): void
    {
        $path = $this->vfsCachePath->url();
        $cfg = new Configuration([
            'storages' => [
                'file' => [
                    'path' => $path,
                    'prefix' => 'cache_',
                ],
            ]
        ]);
        $adapter = new LocalAdapter($path);
        $fs = new Filesystem($adapter);

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $data = array('foo' => 'bar');
        vfsStream::newFile($filename)
                ->at($this->vfsCachePath)
                ->setContent(serialize($data));

        $ls = new LocalStorage($fs, $cfg);

        $this->assertTrue($ls->clear());
    }
}
