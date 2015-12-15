<?php
/**
 * Tests for Model\Client\ItemManager
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

namespace Model\Test\Client;

class ItemManagerTest extends \Model\Test\AbstractTest
{
    protected static $_tables = array(
        'ClientsAndGroups',
        'DuplicateMacAddresses',
        'SoftwareDefinitions',
        'AudioDevices',
        'Controllers',
        'Cpu',
        'Displays',
        'DisplayControllers',
        'ExtensionSlots',
        'Filesystems',
        'InputDevices',
        'MemorySlots',
        'Modems',
        'MsOfficeProducts',
        'NetworkInterfaces',
        'Ports',
        'Printers',
        'RegistryData',
        'Sim',
        'Software',
        'StorageDevices',
        'VirtualMachines',
    );

    public function testGetItemTypes()
    {
        $this->assertEquals(
            array(
                'audiodevice',
                'controller',
                'cpu',
                'display',
                'displaycontroller',
                'extensionslot',
                'filesystem',
                'inputdevice',
                'memoryslot',
                'modem',
                'msofficeproduct',
                'networkinterface',
                'port',
                'printer',
                'registrydata',
                'sim',
                'software',
                'storagedevice',
                'virtualmachine',
            ),
            $this->_getModel()->getItemTypes()
        );
    }
    public function testGetTableNameInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid item type: invalid');
        $this->_getModel()->getTableName('invalid');
    }

    public function testGetTableInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid item type: invalid');
        $this->_getModel()->getTable('invalid');
    }

    public function getItemsProvider()
    {
        return array(
            array('AudioDevice', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('AudioDevice', null, 'Name', 'desc', array('name2', 'name1'), 'Name'),
            array('AudioDevice', null, null, 'something', array('name2', 'name1'), 'Name'),
            array('audiodevice', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Controller', null, 'id', 'desc', array('name2', 'name1'), 'Name'),
            array('Controller', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('controller', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Cpu', array('Client' => 3), 'id', 'asc', array('type3a', 'type3b'), 'Type'),
            array('Cpu', array('Client' => 4), 'Manufacturer', 'desc', array('type4b', 'type4a'), 'Type'),
            array(
                'Cpu', null, null, 'something', array('type1', 'type2', 'type3a', 'type3b', 'type4a', 'type4b'), 'Type'
            ),
            array('cpu', array('Client' => 2), null, null, array('type2'), 'Type'),
            array('cpu', array('Client' => 3), 'Type', 'asc', array(2, 1), 'NumCores'),
            array('cpu', array('Client' => 4), 'Type', 'asc', array(3, 4), 'NumCores'),
            array('cpu', array('Client' => 42), null, null, array(), 'Type'),
            array('Display', null, 'id', 'asc', array('name1', 'name2'), 'Manufacturer'),
            array('Display', null, 'Type', 'desc', array('name1', 'name2'), 'Manufacturer'),
            array('Display', null, null, 'something', array('name1', 'name2'), 'Manufacturer'),
            array('display', array('Client' => 2), null, null, array('name2'), 'Manufacturer'),
            array('DisplayController', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('DisplayController', null, 'Chipset', 'desc', array('name1', 'name2'), 'Name'),
            array('DisplayController', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('displaycontroller', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('ExtensionSlot', null, 'id', 'desc', array('name2', 'name1'), 'Name'),
            array('ExtensionSlot', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('extensionslot', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Filesystem', null, 'id', 'desc', array('name2', 'name1'), 'Filesystem'),
            array('Filesystem', null, null, 'something', array('name1', 'name2'), 'Filesystem'),
            array('filesystem', array('Client' => 2), null, null, array('name2'), 'Filesystem'),
            array('InputDevice', null, 'id', 'asc', array('name1', 'name2'), 'Description'),
            array('InputDevice', null, 'Description', 'desc', array('name2', 'name1'), 'Description'),
            array('InputDevice', null, null, 'something', array('name2', 'name1'), 'Description'),
            array('inputdevice', array('Client' => 2), null, null, array('name2'), 'Description'),
            array('MemorySlot', null, 'id', 'asc', array('name1', 'name2'), 'Description'),
            array('MemorySlot', null, 'Description', 'desc', array('name2', 'name1'), 'Description'),
            array('MemorySlot', null, null, 'something', array('name2', 'name1'), 'Description'),
            array('memoryslot', array('Client' => 2), null, null, array('name2'), 'Description'),
            array('Modem', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Modem', null, 'Name', 'desc', array('name2', 'name1'), 'Name'),
            array('Modem', null, null, 'something', array('name2', 'name1'), 'Name'),
            array('modem', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('MsOfficeProduct', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('MsOfficeProduct', null, 'Version', 'desc', array('name1', 'name2'), 'Name'),
            array('MsOfficeProduct', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('msofficeproduct', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('MsOfficeProduct', array('Type' => 0), null, null, array('name1'), 'Name'),
            array('MsOfficeProduct', array('Type' => 1), null, null, array('name2'), 'Name'),
            array('NetworkInterface', null, 'id', 'asc', array('1', '0'), 'IsBlacklisted'),
            array('NetworkInterface', null, 'Status', 'desc', array('1', '0'), 'IsBlacklisted'),
            array('NetworkInterface', null, null, 'something', array('1', '0'), 'IsBlacklisted'),
            array('networkinterface', array('Client' => 2), null, null, array('0'), 'IsBlacklisted'),
            array('Port', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Port', null, 'Type', 'desc', array('name1', 'name2'), 'Name'),
            array('Port', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('port', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('Printer', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('Printer', null, 'Port', 'desc', array('name1', 'name2'), 'Name'),
            array('Printer', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('printer', array('Client' => 2), null, null, array('name2'), 'Name'),
            array('RegistryData', null, 'id', 'asc', array('value=data2', 'data', '=data1'), 'Data'),
            array('RegistryData', null, 'Data', 'desc', array('value=data2', 'data', '=data1'), 'Data'),
            array('RegistryData', null, null, 'something', array('=data1', 'value=data2', 'data'), 'Data'),
            array('registrydata', array('Client' => 2), null, null, array('data'), 'Data'),
            array('Sim', null, 'id', 'asc', array('name1', 'name2'), 'OperatorName'),
            array('Sim', null, 'SimSerial', 'desc', array('name1', 'name2'), 'OperatorName'),
            array('Sim', null, null, 'something', array('name1', 'name2'), 'OperatorName'),
            array('sim', array('Client' => 2), null, null, array('name2'), 'OperatorName'),
            array('Software', null, 'id', 'asc', array('name1', 'name2', 'name3', 'name4', ''), 'Name'),
            array('Software', null, 'Version', 'desc', array('name4', 'name3', 'name1', 'name2', ''), 'Name'),
            array('Software', null, null, 'something', array('', 'name1', 'name2', 'name3', 'name4'), 'Name'),
            array('software', array('Client' => 2), null, null, array('name2'), 'Name'),
            array(
                'software',
                array('Software.NotIgnored' => null),
                null,
                null,
                array('name2', 'name3', 'name4'),
                'Name'
            ),
            array(
                'Software',
                array('Client' => 1, 'Software.NotIgnored' => null),
                null,
                null,
                array('name3', 'name4'),
                'Name'
            ),
            array('StorageDevice', null, null, 'something', array('name1', 'name2'), 'Model'),
            array('storagedevice', array('Client' => 2), null, null, array('name2'), 'Model'),
            array('VirtualMachine', null, 'id', 'asc', array('name1', 'name2'), 'Name'),
            array('VirtualMachine', null, 'Type', 'desc', array('name1', 'name2'), 'Name'),
            array('VirtualMachine', null, null, 'something', array('name1', 'name2'), 'Name'),
            array('virtualmachine', array('Client' => 2), null, null, array('name2'), 'Name'),
        );
    }

    /**
     * @dataProvider getItemsProvider
     */
    public function testGetItems($type, $filters, $order, $direction, $result, $keyColumn)
    {
        $model = $this->_getModel();
        $items = $model->getItems($type, $filters, $order, $direction);
        $this->assertInstanceOf('Zend\Db\Resultset\AbstractResultset', $items);
        $items = iterator_to_array($items);
        $this->assertContainsOnlyInstancesOf("Model\\Client\\Item\\$type", $items);
        $this->assertEquals(
            $result,
            array_map(
                function ($element) use ($keyColumn) {
                    return $element[$keyColumn];
                },
                $items
            )
        );
    }

    public function testDeleteItems()
    {
        $model = $this->_getModel();
        $model->deleteItems(1);
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        foreach (static::$_tables as $table) {
            if ($table == 'ClientsAndGroups' or $table == 'DuplicateMacAddresses' or $table == 'SoftwareDefinitions') {
                continue;
            }
            $table = \Library\Application::getService("Database\\Table\\$table")->table;
            $dataSet->addTable($table, "SELECT hardware_id FROM $table");
        }
        $this->assertDataSetsEqual($this->_loadDataSet('DeleteItems'), $dataSet);
    }
}