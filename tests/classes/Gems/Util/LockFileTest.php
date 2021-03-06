<?php

/**
 * Description of LockFileTest
 *
 * @author Menno Dekker <menno.dekker@erasmusmc.nl>
 */

namespace Gems\Util;

class LockFileTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Gems_Util_LockFile
     */
    protected $object;
    
    protected $fileName;

    protected function setUp()
    {
        $this->fileName = GEMS_TEST_DIR . '/tmp/locktest.txt';
        $this->object = new \Gems_Util_LockFile($this->fileName);
    }

    protected function tearDown()
    {
        @unlink($this->fileName);
    }

    /**
     * @covers \Gems_Util_LockFile::getLockTime
     * @todo   Implement testGetLockTime().
     */
    public function testGetLockTimeNull()
    {
        $result = $this->object->getLockTime();
        $this->assertNull($result);
    }
    
    /**
     * @covers \Gems_Util_LockFile::getLockTime
     * @todo   Implement testGetLockTime().
     */
    public function testGetLockTimeNotNull()
    {
        $this->object->lock();
        $result = $this->object->getLockTime();
        $this->assertNotNull($result);
    }

    /**
     * @covers \Gems_Util_LockFile::isLocked
     * @todo   Implement testIsLocked().
     */
    public function testIsLocked()
    {
        $this->object->lock();
        $result = $this->object->isLocked();
        $this->assertTrue($result);
    }
    
    /**
     * @covers \Gems_Util_LockFile::isLocked
     * @todo   Implement testIsLocked().
     */
    public function testIsNotLocked()
    {
        $result = $this->object->isLocked();
        $this->assertFalse($result);
    }

    /**
     * @covers \Gems_Util_LockFile::lock
     * @todo   Implement testLock().
     */
    public function testLock()
    {
        $this->object->lock();
        
        $result = file_exists($this->fileName);
        
        $this->assertTrue($result);
    }

    /**
     * @covers \Gems_Util_LockFile::reverse
     * @todo   Implement testReverse().
     */
    public function testReverseLock()
    {
        $this->object->lock();
        $object = $this->object->reverse();
        
        $result = $this->object->isLocked();
        
        $this->assertFalse($result);
    }
    
    /**
     * @covers \Gems_Util_LockFile::reverse
     * @todo   Implement testReverse().
     */
    public function testReverseUnLock()
    {
        $this->object->unlock();
        $object = $this->object->reverse();
        
        $result = $this->object->isLocked();
        
        $this->assertTrue($result);
    }

    /**
     * @covers \Gems_Util_LockFile::unlock
     * @todo   Implement testUnlock().
     */
    public function testUnlock()
    {
        $this->object->lock();
        $this->object->unlock();
        
        $result = file_exists($this->fileName);
        
        $this->assertFalse($result);
    }

}
