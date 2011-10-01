<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Package;

use Composer\Package\Locker;

class LockerTest extends \PHPUnit_Framework_TestCase
{
    public function testIsLocked()
    {
        $json   = $this->createJsonFileMock();
        $locker = new Locker($json, $this->createRepositoryManagerMock());

        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));

        $this->assertTrue($locker->isLocked());
    }

    public function testGetNotLockedPackages()
    {
        $json = $this->createJsonFileMock();
        $repo = $this->createRepositoryManagerMock();

        $locker = new Locker($json, $repo);

        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));

        $this->setExpectedException('LogicException');

        $locker->getLockedPackages();
    }

    public function testGetLockedPackages()
    {
        $json = $this->createJsonFileMock();
        $repo = $this->createRepositoryManagerMock();

        $locker = new Locker($json, $repo);

        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));
        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array(
                array('package' => 'pkg1', 'version' => '1.0.0-beta'),
                array('package' => 'pkg2', 'version' => '0.1.10')
            )));

        $package1 = $this->createPackageMock();
        $package2 = $this->createPackageMock();

        $repo
            ->expects($this->exactly(2))
            ->method('findPackage')
            ->with($this->logicalOr('pkg1', 'pkg2'), $this->logicalOr('1.0.0-beta', '0.1.10'))
            ->will($this->onConsecutiveCalls($package1, $package2));

        $this->assertEquals(array($package1, $package2), $locker->getLockedPackages());
    }

    public function testGetPackagesWithoutRepo()
    {
        $json = $this->createJsonFileMock();
        $repo = $this->createRepositoryManagerMock();

        $locker = new Locker($json, $repo);

        $json
            ->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));
        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array(
                array('package' => 'pkg1', 'version' => '1.0.0-beta'),
                array('package' => 'pkg2', 'version' => '0.1.10')
            )));

        $package1 = $this->createPackageMock();
        $package2 = $this->createPackageMock();

        $repo
            ->expects($this->exactly(2))
            ->method('findPackage')
            ->with($this->logicalOr('pkg1', 'pkg2'), $this->logicalOr('1.0.0-beta', '0.1.10'))
            ->will($this->onConsecutiveCalls($package1, null));

        $this->setExpectedException('LogicException');

        $locker->getLockedPackages();
    }

    public function testLockPackages()
    {
        $json = $this->createJsonFileMock();
        $repo = $this->createRepositoryManagerMock();

        $locker = new Locker($json, $repo);

        $package1 = $this->createPackageMock();
        $package2 = $this->createPackageMock();

        $package1
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pkg1'));
        $package1
            ->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0-beta'));

        $package2
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pkg2'));
        $package2
            ->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('0.1.10'));

        $json
            ->expects($this->once())
            ->method('write')
            ->with(array(
                array('package' => 'pkg1', 'version' => '1.0.0-beta'),
                array('package' => 'pkg2', 'version' => '0.1.10')
            ));

        $locker->lockPackages(array($package1, $package2));
    }

    public function testLockBadPackages()
    {
        $json = $this->createJsonFileMock();
        $repo = $this->createRepositoryManagerMock();

        $locker = new Locker($json, $repo);

        $package1 = $this->createPackageMock();
        $package1
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pkg1'));

        $this->setExpectedException('LogicException');

        $locker->lockPackages(array($package1));
    }

    private function createJsonFileMock()
    {
        return $this->getMockBuilder('Composer\Json\JsonFile')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createRepositoryManagerMock()
    {
        return $this->getMockBuilder('Composer\Repository\RepositoryManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createPackageMock()
    {
        return $this->getMockBuilder('Composer\Package\PackageInterface')
            ->getMock();
    }
}
