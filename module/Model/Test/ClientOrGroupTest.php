<?php
/**
 * Tests for Model\ClientOrGroup
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Model\Test;

class ClientOrGroupTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('ClientConfig', 'Locks', 'PackageHistory', 'Packages');

    protected $_currentTimestamp;

    /** {@inheritdoc} */
    protected function _loadDataSet($testName = null)
    {
        // Get current time from database as reference point for all operations.
        $this->_currentTimestamp = new \DateTime(
            $this->getConnection()->createQueryTable(
                'current',
                'SELECT CURRENT_TIMESTAMP AS current'
            )->getValue(0, 'current'),
            new \DateTimeZone('UTC')
        );

        $dataSet = parent::_loadDataSet($testName);
        $locks = $dataSet->getTable('locks');
        if ($locks) {
            // Replace offsets with timestamps (current - offset)
            $count = $locks->getRowCount();
            $replacement = new \PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($dataSet);
            for ($i = 0; $i < $count; $i++) {
                $offset = $locks->getValue($i, 'since');
                $interval = new \DateInterval(sprintf('PT%dS', trim($offset, '#')));
                $since = clone $this->_currentTimestamp;
                $since->sub($interval);
                $replacement->addFullReplacement($offset, $since->format('Y-m-d H:i:s'));
            }
            return $replacement;
        } else {
            return $dataSet;
        }
    }

    /**
     * Compare "locks" table with dataset using fuzzy timestamp match
     *
     * @param string $dataSetName Name of dataset file to compare
     */
    public function assertLocksTableEquals($dataSetName)
    {
        $dataSetTable = $this->_loadDataSet($dataSetName)->getTable('locks');
        $queryTable = $this->getConnection()->createQueryTable(
            'locks',
            'SELECT hardware_id, since FROM locks ORDER BY hardware_id'
        );
        $count = $dataSetTable->getRowCount();
        $this->assertEquals($count, $queryTable->getRowCount());
        for ($i = 0; $i < $count; $i++) {
            $dataSetRow = $dataSetTable->getRow($i);
            $queryRow = $queryTable->getRow($i);
            $dataSetDate = new \DateTime($dataSetRow['since']);
            $queryDate = new \DateTime($queryRow['since']);
            $this->assertThat($queryDate->getTimestamp(), $this->equalTo($dataSetDate->getTimestamp(), 1));
            $this->assertEquals($dataSetRow['hardware_id'], $queryRow['hardware_id']);
        }
    }

    /** {@inheritdoc} */
    public function testInterface()
    {
        $this->assertTrue(true); // Test does not apply to this class
    }

    public function testDestructor()
    {
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('unlock'))->getMockForAbstractClass();
        $model->expects($this->once())->method('unlock');
        $model->__destruct();
    }

    public function testDestructorWithNestedLocks()
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')->with('lockValidity')->willReturn(42);

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Database\Table\Locks', true, \Library\Application::getService('Database\Table\Locks')),
                    array('Db', true, \Library\Application::getService('Db')),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 23;

        $model->lock();
        $model->lock();
        $model->__destruct();
        $this->assertLocksTableEquals(null);
    }

    public function lockWithDatabaseTimeProvider()
    {
        return array(
            array(42, 60, true, 'LockNew'),
            array(1, 58, true, 'LockReuse'),
            array(1, 62, false, null),
        );
    }

    /**
     * @dataProvider lockWithDatabaseTimeProvider
     */
    public function testLockWithDatabaseTime($id, $timeout, $success, $dataSetName)
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())->method('__get')->with('lockValidity')->willreturn($timeout);

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Database\Table\Locks', true, \Library\Application::getService('Database\Table\Locks')),
                    array('Db', true, \Library\Application::getService('Db')),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $this->assertSame($success, $model->lock());

        // Reuse $_currentTimestamp before it gets overwritten by _loadDataSet()
        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setAccessible(true);
        if ($success) {
            $this->assertThat(
                $expire->getValue($model)->getTimestamp(),
                $this->equalTo($this->_currentTimestamp->getTimestamp() + $timeout, 1) // fuzzy match
            );
        } else {
            $this->assertNull($expire->getValue($model));
        }

        $this->assertLocksTableEquals($dataSetName);
    }

    public function testLockRaceCondition()
    {
        $sql = $this->getMockBuilder('\Zend\Db\Sql\Sql')->disableOriginalConstructor()->getMock();
        $sql->method('select')->willReturn(new \Zend\Db\Sql\Select);

        $locks = $this->getMockBuilder('Database\Table\Locks')->disableOriginalConstructor()->getMock();
        $locks->method('getSql')->willReturn($sql);
        $locks->method('selectWith')->willReturn(new \ArrayIterator);
        $locks->method('insert')->will($this->throwException(new \RuntimeException('race condition')));

        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Database\Table\Locks', true, $locks),
                    array('Db', true, \Library\Application::getService('Db')),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 42;

        $this->assertFalse($model->lock());
    }

    public function testUnlockWithoutLock()
    {
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'isLocked'))
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('isLocked')->willReturn(false);
        $model->unlock();
    }

    public function testUnlockWithReleasedLock()
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Database\Table\Locks', true, \Library\Application::getService('Database\Table\Locks')),
                    array('Db', true, \Library\Application::getService('Db')),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'isLocked'))
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;

        $current = clone $this->_currentTimestamp;
        $current->add(new \DateInterval('PT10S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setAccessible(true);
        $expire->setValue($model, $current);

        $model->unlock();
        $this->assertLocksTableEquals('Unlock');
        $this->assertNull($expire->getValue($model));
    }

    public function testUnlockWithExpiredLock()
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Db', true, \Library\Application::getService('Db')),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'isLocked'))
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setServiceLocator($serviceManager);

        $current = clone $this->_currentTimestamp;
        $current->sub(new \DateInterval('PT1S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setAccessible(true);
        $expire->setValue($model, $current);

        try {
            $model->unlock();
            $this->fail('Expected exception was not thrown.');
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $this->assertEquals('Lock expired prematurely. Increase lock lifetime.', $e->getMessage());
        }
        $this->assertLocksTableEquals(null);
        $this->assertNull($expire->getValue($model));
    }

    public function testIsLockedFalse()
    {
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $this->assertFalse($model->isLocked());
    }

    public function testIsLockedTrue()
    {
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();

        $expire = new \ReflectionProperty($model, '_lockNestCount');
        $expire->setAccessible(true);
        $expire->setValue($model, 2);

        $this->assertTrue($model->isLocked());
    }

    public function testNestedLocks()
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')->with('lockValidity')->willReturn(42);

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Nada', true, \Library\Application::getService('Database\Nada')),
                    array('Database\Table\Locks', true, \Library\Application::getService('Database\Table\Locks')),
                    array('Db', true, \Library\Application::getService('Db')),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 23;

        $this->assertTrue($model->lock());
        $this->assertTrue($model->lock());
        $model->unlock();
        $this->assertTrue($model->isLocked());
        $model->unlock();
        $this->assertFalse($model->isLocked());
    }

    public function testGetAssignablePackages()
    {
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator(\Library\Application::getService('ServiceManager'));
        $model['Id'] = 1;
        $this->assertEquals(array('package1', 'package3'), $model->getAssignablePackages());
    }

    public function assignPackageProvider()
    {
        return array(
            array('package1', 1, 'AssignPackage'),
            array('package2', 2, null),
        );
    }

    /**
     * @dataProvider assignPackageProvider
     */
    public function testAssignPackage($name, $id, $dataSet)
    {
        $packageManager = $this->getMockBuilder('Model\Package\PackageManager')
                               ->disableOriginalConstructor()
                               ->getMock();
        $packageManager->method('getPackage')
                       ->with($name)
                       ->willReturn(array('Id' => $id));

        $now = $this->getMock('DateTime');
        $now->method('format')->with('D M d H:i:s Y')->willReturn('current timestamp');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array(
                        'Database\Table\ClientConfig',
                        true,
                        \Library\Application::getService('Database\Table\ClientConfig')
                    ),
                    array('Library\Now', true, $now),
                    array('Model\Package\PackageManager', true, $packageManager),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'getAssignablePackages'))
                      ->getMockForAbstractClass();
        $model->method('getAssignablePackages')->willReturn(array('package1', 'package3'));
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;
        $model->assignPackage($name);

        if ($dataSet) {
            $where = 'WHERE hardware_id < 10 ';
        } else {
            $where = '';
        }
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' . $where .
                'ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testRemovePackage()
    {
        $packageManager = $this->getMockBuilder('Model\Package\PackageManager')
                               ->disableOriginalConstructor()
                               ->getMock();
        $packageManager->method('getPackage')
                       ->with('package5')
                       ->willReturn(array('Id' => 5));

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array(
                        'Database\Table\ClientConfig',
                        true,
                        \Library\Application::getService('Database\Table\ClientConfig')
                    ),
                    array('Model\Package\PackageManager', true, $packageManager),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 1;
        $model->removePackage('package5');

        $this->assertTablesEqual(
            $this->_loadDataSet('RemovePackage')->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' .
                'WHERE hardware_id < 10 ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function getConfigProvider()
    {
        return array(
            array(10, 'packageDeployment', 0),
            array(11, 'packageDeployment', null),
            array(10, 'allowScan', 0),
            array(11, 'allowScan', null),
            array(10, 'scanThisNetwork', '192.0.2.0'),
            array(11, 'scanThisNetwork', null),
            array(10, 'scanSnmp', 0),
            array(11, 'scanSnmp', null),
            array(10, 'inventoryInterval', 23),
            array(11, 'inventoryInterval', null),
        );
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetConfig($id, $option, $value)
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array(
                        'Database\Table\ClientConfig',
                        true,
                        \Library\Application::getService('Database\Table\ClientConfig')
                    ),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $this->assertSame($value, $model->getConfig($option));

        $cache = new \ReflectionProperty($model, '_configCache');
        $cache->setAccessible(true);
        $this->assertSame($value, $cache->getValue($model)[$option]);
    }

    public function testGetConfigCached()
    {
        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('__destruct'))->getMockForAbstractClass();
        $model['Id'] = 42;

        $cache = new \ReflectionProperty($model, '_configCache');
        $cache->setAccessible(true);
        $cache->setValue($model, array('option' => 'value'));

        $this->assertEquals('value', $model->getConfig('option'));
    }

    public function setConfigProvider()
    {
        return array(
            array(10, 'inventoryInterval', 'FREQUENCY', null, null, null, 'SetConfigRegularDelete'),
            array(10, 'inventoryInterval', 'FREQUENCY', 42, 23, 42, 'SetConfigRegularUpdate'),
            array(10, 'contactInterval', 'PROLOG_FREQ', 42, null, 42, 'SetConfigRegularInsert'),
            array(10, 'packageDeployment', 'DOWNLOAD', 1, 0, null, 'SetConfigPackageDeploymentEnable'),
            array(10, 'scanSnmp', 'SNMP', 1, 0, null, 'SetConfigScanSnmpEnable'),
            array(10, 'allowScan', 'IPDISCOVER', 1, 0, null, 'SetConfigAllowScanEnable'),
            array(11, 'packageDeployment', 'DOWNLOAD', 0, null, 0, 'SetConfigPackageDeploymentDisable'),
            array(11, 'scanSnmp', 'SNMP', 0, null, 0, 'SetConfigScanSnmpDisable'),
            array(11, 'allowScan', 'IPDISCOVER', 0, null, 0, 'SetConfigAllowScanDisable'),
            array(11, 'scanThisNetwork', 'IPDISCOVER', 'addr', null, 'addr', 'SetConfigScanThisNetworkInsert'),
            array(10, 'scanThisNetwork', 'IPDISCOVER', null, 'addr', null, 'SetConfigScanThisNetworkDelete'),
        );
    }

    /**
     * @dataProvider setConfigProvider
     */
    public function testSetConfig($id, $option, $identifier, $value, $oldValue, $normalizedValue, $dataSet)
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('getDbIdentifier')->with($option)->willReturn($identifier);

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\ClientConfig',
                        true,
                        \Library\Application::getService('Database\Table\ClientConfig')
                    ),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'getConfig'))
                      ->getMockForAbstractClass();
        if ($normalizedValue === null) {
            $model->expects($this->never())->method('getConfig');
        } else {
            $model->expects($this->once())->method('getConfig')->with($option)->willReturn($oldValue);
        }
        $model->setServiceLocator($serviceManager);
        $model['Id'] = $id;

        $model->setConfig($option, $value);
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ' .
                'WHERE hardware_id >= 10 ORDER BY hardware_id, name'
            )
        );

        $cache = new \ReflectionProperty($model, '_configCache');
        $cache->setAccessible(true);
        $this->assertSame($normalizedValue, $cache->getValue($model)[$option]);
    }

    public function testSetConfigUnchanged()
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

        $clientConfig = $this->getMockBuilder('Database\Table\ClientConfig')->disableOriginalConstructor()->getMock();
        $clientConfig->method('getAdapter')->willReturn(\Library\Application::getService('Db'));
        $clientConfig->expects($this->never())->method('insert');
        $clientConfig->expects($this->never())->method('update');
        $clientConfig->expects($this->never())->method('delete');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->will(
            $this->returnValueMap(
                array(
                    array('Database\Table\ClientConfig', true, $clientConfig),
                    array('Model\Config', true, $config),
                )
            )
        );

        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'getConfig'))
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getConfig')->with('inventoryInterval')->willReturn(23);
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 10;

        $model->setConfig('inventoryInterval', '23');

        $cache = new \ReflectionProperty($model, '_configCache');
        $cache->setAccessible(true);
        $this->assertSame(23, $cache->getValue($model)['inventoryInterval']);
    }

    public function getAllConfigProvider()
    {
        return array(
            array(null, 0, 0, 1, 0, 0),
            array(0, null, 0, 0, 1, 0),
            array(0, 0, null, 0, 0, 1),
        );
    }

    /**
     * @dataProvider getAllConfigProvider
     */
    public function testGetAllConfig(
        $packageDeployment,
        $allowScan,
        $scanSnmp,
        $expectedPackageDeployment,
        $expectedAllowScan,
        $expectedScanSnmp
    ) {
        $model = $this->getMockBuilder($this->_getClass())
                      ->setMethods(array('__destruct', 'getConfig'))
                      ->getMockForAbstractClass();
        $model->method('getConfig')->will(
            $this->returnValueMap(
                array(
                    array('contactInterval', 2),
                    array('inventoryInterval', 3),
                    array('packageDeployment', $packageDeployment),
                    array('downloadPeriodDelay', 4),
                    array('downloadCycleDelay', 5),
                    array('downloadFragmentDelay', 6),
                    array('downloadMaxPriority', 7),
                    array('downloadTimeout', 8),
                    array('allowScan', $allowScan),
                    array('scanSnmp', $scanSnmp),
                )
            )
        );
        $this->assertSame(
            array(
                'Agent' => array(
                    'contactInterval' => 2,
                    'inventoryInterval' => 3,
                ),
                'Download' => array(
                    'packageDeployment' => $expectedPackageDeployment,
                    'downloadPeriodDelay' => 4,
                    'downloadCycleDelay' => 5,
                    'downloadFragmentDelay' => 6,
                    'downloadMaxPriority' => 7,
                    'downloadTimeout' => 8,
                ),
                'Scan' => array(
                    'allowScan' => $expectedAllowScan,
                    'scanSnmp' => $expectedScanSnmp,
                ),
            ),
            $model->getAllConfig()
        );
    }
}