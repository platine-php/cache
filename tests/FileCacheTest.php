<?php

declare(strict_types=1);

namespace Platine\Test\Cache;

use Platine\Cache\FileCache;
use Platine\Cache\Exception\FileCacheException;
use Platine\Cache\Exception\CacheException;
use org\bovigo\vfs\vfsStream;
use Platine\PlatineTestCase;

/**
 * FileCache class tests
 *
 * @group core
 * @group cache
 */
class FileCacheTest extends PlatineTestCase
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
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $sr = $this->getPrivateProtectedAttribute(FileCache::class, 'savePath');
        $fpr = $this->getPrivateProtectedAttribute(FileCache::class, 'filePrefix');
        $this->assertEquals($path . DIRECTORY_SEPARATOR, $sr->getValue($fc));
        $this->assertEquals('cache_', $fpr->getValue($fc));

        //savePath is empty
        $fc = new FileCache();
        $this->assertNotEmpty($sr->getValue($fc));
    }

    public function testConstructorTwo(): void
    {
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path, 'fooPrefix');
        $sr = $this->getPrivateProtectedAttribute(FileCache::class, 'savePath');
        $fpr = $this->getPrivateProtectedAttribute(FileCache::class, 'filePrefix');
        $this->assertEquals($path . DIRECTORY_SEPARATOR, $sr->getValue($fc));
        $this->assertEquals('fooPrefix', $fpr->getValue($fc));
    }

    public function testConstructorThree(): void
    {
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path, 'fooPrefix', 50);
        $sr = $this->getPrivateProtectedAttribute(FileCache::class, 'savePath');
        $fpr = $this->getPrivateProtectedAttribute(FileCache::class, 'filePrefix');
        $fdttl = $this->getPrivateProtectedAttribute(FileCache::class, 'defaultTtl');
        $this->assertEquals($path . DIRECTORY_SEPARATOR, $sr->getValue($fc));
        $this->assertEquals('fooPrefix', $fpr->getValue($fc));
        $this->assertEquals(50, $fdttl->getValue($fc));
    }

    public function testConstructorDirectoryNotFound(): void
    {
        $this->expectException(FileCacheException::class);
        $fc = new FileCache('/path/not/found/');
    }

    public function testConstructorDirectoryNotWritable(): void
    {
        $this->expectException(FileCacheException::class);
        $path = $this->vfsCachePath->url();
        chmod($path, 0400);
        $fc = new FileCache($path);
    }

    public function testSetGetSavePath(): void
    {
        $path = $this->vfsCachePath->url();

        $fc = new FileCache();
        $fc->setSavePath($path);
        $this->assertEquals($fc->getSavePath(), $path . DIRECTORY_SEPARATOR);
    }

    public function testValidateKeyIsEmpty(): void
    {
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $this->expectException(CacheException::class);

        $this->runPrivateProtectedMethod($fc, 'validateKey', array(''));
    }

    public function testValidateKeyReservedChar(): void
    {
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $this->expectException(CacheException::class);

        $this->runPrivateProtectedMethod($fc, 'validateKey', array('ddff@sdf'));
    }

    public function testGetFilename(): void
    {
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path, '_prefix');

        $key = 'foo';
        $file = $this->runPrivateProtectedMethod($fc, 'getFileName', array($key));

        $this->assertEquals($file, '_prefix' . md5($key) . '.cache');
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
        $fc = new FileCache($path);

        //Default value
        $this->assertEquals('bar', $fc->get('not_found_key', 'bar'));

        //filemtime return false
        $mock_filemtime_to_false = true;
        $this->assertEquals('bar', $fc->get('not_found_key', 'bar'));

        //Restore
        $mock_filemtime_to_false = false;

        //file is expired
        $mock_filemtime_to_int = true;
        $mock_time = true;
        $this->assertEquals('bar', $fc->get('not_found_key', 'bar'));

        //Restore
        $mock_filemtime_to_int = false;
        $mock_time = false;

        //file_get_contents return false
        $mock_file_get_contents_to_false = true;
        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;
        $this->assertEquals('bar', $fc->get('not_found_key', 'bar'));



        //unserialize return false
        $mock_file_get_contents_to_false = false;
        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;
        $mock_unserialize_to_false = true;
        $mock_file_get_contents_to_data = true;
        $this->assertEquals('bar', $fc->get('not_found_key', 'bar'));

        //Restore
        $mock_file_get_contents_to_false = false;
        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;
        $mock_unserialize_to_false = false;
        $mock_file_get_contents_to_data = false;

        //Return correct data
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        $vfsFile = vfsStream::newFile($filename)->at($this->vfsCachePath)->setContent(serialize($data));

        $fc = new FileCache($path);
        $content = $fc->get($key);
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
        $vfsFile = vfsStream::newFile($filename)->at($this->vfsCachePath)->setContent(serialize($data));

        $fc = new FileCache($path);
        $this->assertTrue($fc->has($key));
    }

    public function testSetSimple(): void
    {
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        $vfsFile = vfsStream::newFile($filename)->at($this->vfsCachePath)->setContent(serialize($data));

        $fc = new FileCache($path);
        $result = $fc->set($key, $data);
        $this->assertTrue($result);
        $this->assertEquals(serialize($data), file_get_contents($path . DIRECTORY_SEPARATOR . $filename));
    }

    public function testSetInvalidTtl(): void
    {
        $this->expectException(CacheException::class);
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $fc->set('key', 'data', []);
    }

    public function testSetTtlIsDateInterval(): void
    {
        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        $vfsFile = vfsStream::newFile($filename)
                    ->at($this->vfsCachePath)
                    ->setContent(serialize($data));

        $fc = new FileCache($path);
        $result = $fc->set($key, $data, new \DateInterval('PT4H'));
        $this->assertTrue($result);
        $this->assertEquals(
            serialize($data),
            file_get_contents($path . DIRECTORY_SEPARATOR . $filename)
        );
    }

    public function testSetFilePutContensReturnFalse(): void
    {
        global $mock_file_put_contents_to_false;

        $mock_file_put_contents_to_false = true;
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $result = $fc->set('key', 'data');
        $this->assertFalse($result);
    }

    public function testSetTouchReturnFalse(): void
    {
        global $mock_touch_to_false;

        $mock_touch_to_false = true;
        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);
        $result = $fc->set('key', 'data');
        $this->assertFalse($result);
    }

    public function testDeleteSuccess(): void
    {
        global $mock_filemtime_to_int,
        $mock_time_to_zero;


        $mock_time_to_zero = true;
        $mock_filemtime_to_int = true;

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        $vfsFile = vfsStream::newFile($filename)
                    ->at($this->vfsCachePath)
                    ->setContent(serialize($data));

        $fc = new FileCache($path);

        $content = $fc->get($key);
        $this->assertEquals($data, $content);

        $this->assertTrue($fc->delete($key));

        $content = $fc->get($key);
        $this->assertNull($content);
    }

    public function testDeleteFailed(): void
    {
        global $mock_unlink_to_false;


        $mock_unlink_to_false = true;

        $key = uniqid();
        $filename = 'cache_' . md5($key) . '.cache';
        $path = $this->vfsCachePath->url();
        $data = array('foo' => 'bar');
        $vfsFile = vfsStream::newFile($filename)
                    ->at($this->vfsCachePath)
                    ->setContent(serialize($data));

        $fc = new FileCache($path);

        $this->assertFalse($fc->delete($key));
    }

    public function testDeleteFileNotExist(): void
    {
        $path = $this->vfsCachePath->url();

        $fc = new FileCache($path);

        $this->assertTrue($fc->delete('key_not_found'));
    }

    public function testClearUnlinkReturnFalseDuringOperation(): void
    {
        global $mock_glob,
        $mock_unlink_to_false;


        $mock_glob = true;
        $mock_unlink_to_false = true;


        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);

        $this->assertFalse($fc->clear());
    }

    public function testClearSuccess(): void
    {
        global $mock_glob,
        $mock_unlink_to_true;


        $mock_glob = true;
        $mock_unlink_to_true = true;


        $path = $this->vfsCachePath->url();
        $fc = new FileCache($path);

        $this->assertTrue($fc->clear());
    }
}
