<?php
/**
 * Client
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

namespace Model\Client;

/**
 * Client
 *
 * Additional virtual properties from searched items are provided by filters on
 * an item in the form "Type.Property".
 *
 * @property integer $Id primary key
 * @property string $IdString client-generated ID (name + timestamp, like 'NAME-2009-04-27-15-52-37')
 * @property string $Name Name
 * @property string $Type Type (Desktop, Notebook...) as reported by BIOS
 * @property string $Manufacturer system manufacturer
 * @property string $Model system model
 * @property string $Serial serial number
 * @property string $AssetTag asset tag
 * @property integer $CpuClock CPU clock in MHz
 * @property integer $CpuCores total number of CPUs/cores
 * @property string $CpuType CPU manufacturer and model
 * @property \DateTime $InventoryDate timestamp of last inventory
 * @property \DateTime $LastContactDate timestamp of last agent contact (may be newer than InventoryDate)
 * @property integer $PhysicalMemory Amount of RAM as reported by OS. May be lower than actual RAM.
 * @property integer $SwapMemory Amount of swap space in use
 * @property string $BiosManufacturer BIOS manufacturer
 * @property string $BiosVersion BIOS version
 * @property string $BiosDate BIOS date (no unified format, not parseable)
 * @property string $DnsDomain DNS domain name (UNIX clients only)
 * @property string $DnsServer IP Address of DNS server
 * @property string $DefaultGateway default gateway
 * @property string $OsName OS name
 * @property string $OsVersionNumber internal OS version number
 * @property string $OsVersionString OS version (Service pack, kernel version etc...)
 * @property string $OsComment OS comment
 * @property string $UserAgent user agent identification string
 * @property string $UserName user logged in at time of inventory
 * @property string $Uuid UUID (typically provided by BIOS)
 * @property \Model\Client\WindowsInstallation $Windows Windows installation info, NULL for non-Windows systems
 * @property \Model\Client\CustomFields $CustomFields custom fields
 * @property bool $IsSerialBlacklisted TRUE if the serial is ignored for detection of duplicates
 * @property bool $IsAssetTagBlacklisted TRUE if the asset tag is ignored for detection of duplicates
 * @property \Model\Client\Item\AudioDevice[] $AudioDevice audio devices
 * @property \Model\Client\Item\Controller[] $Controller controllers
 * @property \Model\Client\Item\Cpu[] $Cpu CPUs (newer UNIX clients only)
 * @property \Model\Client\Item\Display[] $Display displays
 * @property \Model\Client\Item\DisplayController[] $DisplayController display controllers
 * @property \Model\Client\Item\ExtensionSlot[] $ExtensionSlot extension slots
 * @property \Model\Client\Item\Filesystem[] $Filesystem filesystems
 * @property \Model\Client\Item\InputDevice[] $InputDevice input devices
 * @property \Model\Client\Item\MemorySlot[] $MemorySlot memory slots
 * @property \Model\Client\Item\Modem[] $Modem modems
 * @property \Model\Client\Item\MsOfficeProduct[] $MsOfficeProduct MS Office products
 * @property \Model\Client\Item\NetworkInterface[] $NetworkInterface network interfaces
 * @property \Model\Client\Item\Port[] $Port ports
 * @property \Model\Client\Item\Printer[] $Printer printers
 * @property \Model\Client\Item\RegistryData[] $RegistryData registry data
 * @property \Model\Client\Item\Sim[] $Sim SIM (Android clients only)
 * @property \Model\Client\Item\Software[] $Software software
 * @property \Model\Client\Item\StorageDevice[] $StorageDevice storage devices
 * @property \Model\Client\Item\VirtualMachine[] $VirtualMachine virtual machines
 * @property string $Package.Status package status (supplied by filter)
 * @property integer $Membership group membership type (supplied by filter)
 * @property string $Registry.* registry search result (supplied by filter)
 */
class Client extends \Model\ClientOrGroup
{
    /**
     * Value denoting automatic group membership, i.e. from a group query
     */
    const MEMBERSHIP_AUTOMATIC = 0;

    /**
     * Value denoting explicit group membership
     */
    const MEMBERSHIP_ALWAYS = 1;

    /**
     * Value denoting that the client is excluded from a group
     */
    const MEMBERSHIP_NEVER = 2;

    /**
     * Value denoting either MEMBERSHIP_ALWAYS or MEMBERSHIP_NEVER - only as argument for getGroupMemberships()
     */
    const MEMBERSHIP_MANUAL = -1;

    /**
     * Value denoting any membership value - only as argument for getGroupMemberships()
     */
    const MEMBERSHIP_ANY = -2;

    /**
     * Cache for getDefaultConfig() results
     * @var array
     */
    protected $_configDefault = array();

    /**
     * Cache for getEffectiveConfig() results
     * @var array
     */
    protected $_configEffective = array();

    /**
     * Cache for getGroups() result
     * @var \Model\Group\Group[]
     */
    protected $_groups;

    /** {@inheritdoc} */
    public function offsetGet($index)
    {
        if ($this->offsetExists($index)) {
            $value = parent::offsetGet($index);
        } elseif (strpos($index, 'Registry.') === 0) {
            $value = $this['Registry.Content'];
        } else {
            // Virtual properties from database queries
            switch ($index) {
                case 'Windows':
                    $windowsInstallations = $this->serviceLocator->get('Database\Table\WindowsInstallations');
                    $select = $windowsInstallations->getSql()->select();
                    $select->columns(
                        array(
                            'workgroup',
                            'user_domain',
                            'company',
                            'owner',
                            'product_key',
                            'product_id',
                            'manual_product_key',
                            'cpu_architecture',
                        )
                    );
                    $select->where(array('client_id' => $this['Id']));
                    $value = $windowsInstallations->selectWith($select)->current() ?: null;
                    break;
                case 'CustomFields':
                    $value = $this->serviceLocator->get('Model\Client\CustomFieldManager')->read($this['Id']);
                    break;
                case 'IsSerialBlacklisted':
                    $duplicateSerials = $this->serviceLocator->get('Database\Table\DuplicateSerials');
                    $value = (bool) $duplicateSerials->select(array('serial' => $this['Serial']))->count();
                    break;
                case 'IsAssetTagBlacklisted':
                    $duplicateAssetTags = $this->serviceLocator->get('Database\Table\DuplicateAssetTags');
                    $value = (bool) $duplicateAssetTags->select(array('assettag' => $this['AssetTag']))->count();
                    break;
                default:
                    $value = $this->getItems($index);
            }
            // Cache result
            $this->offsetSet($index, $value);
        }
        return $value;
    }

    /** {@inheritdoc} */
    public function getDefaultConfig($option)
    {
        $id = $this['Id'];
        if (array_key_exists($option, $this->_configDefault)) {
            return $this->_configDefault[$option];
        }

        $config = $this->serviceLocator->get('Model\Config');

        // Get non-NULL values from groups
        $groupValues = array();
        foreach ($this->getGroups() as $group) {
            $groupValue = $group->getConfig($option);
            if ($groupValue !== null) {
                $groupValues[] = $groupValue;
            }
        }

        $value = null;
        switch ($option) {
            case 'inventoryInterval':
                $value = $config->inventoryInterval;
                // Special values 0 and -1 always take precedence if
                // configured globally. Otherwise use smallest value from
                // groups if defined.
                if ($value >= 1 and !empty($groupValues)) {
                    $value = min($groupValues);
                }
                break;
            case 'contactInterval':
            case 'downloadMaxPriority':
            case 'downloadTimeout':
                // Get smallest value from groups
                if ($groupValues) {
                    $value = min($groupValues);
                }
                break;
            case 'downloadPeriodDelay':
            case 'downloadCycleDelay':
            case 'downloadFragmentDelay':
                // Get largest value from groups
                if ($groupValues) {
                    $value = max($groupValues);
                }
                break;
            case 'packageDeployment':
            case 'scanSnmp':
                // 0 if global setting or any group setting is 0, otherwise 1.
                if (in_array(0, $groupValues)) {
                    $value = 0;
                } else {
                    $value = $config->$option;
                }
                break;
            case 'allowScan':
                // 0 scanning is disabled globally or any group setting is 0, otherwise 1.
                if (in_array(0, $groupValues)) {
                    $value = 0;
                } else {
                    // Limit result to 1
                    $value = min($config->scannersPerSubnet, 1);
                }
                break;
        }
        if ($value === null) {
            // Fall back to global value
            $value = $config->$option;
        }

        $this->_configDefault[$option] = $value;
        return $value;
    }

    /** {@inheritdoc} */
    public function getAllConfig()
    {
        $config = parent::getAllConfig();
        $config['Scan']['scanThisNetwork'] = $this->getConfig('scanThisNetwork');
        return $config;
    }

    /**
     * Get effective configuration value
     *
     * This method returns the effective setting for an option. It is determined
     * from this client's individual setting, the global setting and/or all
     * groups of which the client is a member. The exact rules are:
     *
     * - packageDeployment, allowScan and scanSnmp return 0 if the setting is
     *   disabled either globally, for any group or for the client, otherwise 1.
     * - For inventoryInterval, if the global setting is one of the special
     *   values 0 or -1, this setting is returned. Otherwise, return the
     *   smallest value of the group and client setting. If this is undefined,
     *   use global setting.
     * - contactInterval, downloadMaxPriority and downloadTimeout evaluate (in
     *   that order): the client setting, the smallest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - downloadPeriodDelay, downloadCycleDelay, downloadFragmentDelay evaluate
     *   (in that order): the client setting, the largest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - For any other setting, the client's configured value is evaluated via
     *   getConfig().
     *
     * @param string $option Option name
     * @return mixed Effective value or NULL
     */
    public function getEffectiveConfig($option)
    {
        $id = $this['Id'];
        if (array_key_exists($option, $this->_configEffective)) {
            return $this->_configEffective[$option];
        }

        switch ($option) {
            case 'inventoryInterval':
                $globalValue = $this->serviceLocator->get('Model\Config')->inventoryInterval;
                // Special global values 0 and -1 always take precedence.
                if ($globalValue <= 0) {
                    $value = $globalValue;
                } else {
                    // Get smallest value of client and group settings
                    $value = $this->getConfig('inventoryInterval');
                    foreach ($this->getGroups() as $group) {
                        $groupValue = $group->getConfig('inventoryInterval');
                        if ($value === null or ($groupValue !== null and $groupValue < $value)) {
                            $value = $groupValue;
                        }
                    }
                    // Fall back to global default if not set anywhere else
                    if ($value === null) {
                        $value = $globalValue;
                    }
                }
                break;
            case 'contactInterval':
            case 'downloadPeriodDelay':
            case 'downloadCycleDelay':
            case 'downloadFragmentDelay':
            case 'downloadMaxPriority':
            case 'downloadTimeout':
                // Client value takes precedence.
                $value = $this->getConfig($option);
                if ($value === null) {
                    $value = $this->getDefaultConfig($option);
                }
                break;
            case 'packageDeployment':
            case 'allowScan':
            case 'scanSnmp':
                // If default is 0, return 0.
                // Otherwise override default if explicitly disabled.
                $default = $this->getDefaultConfig($option);
                if ($default and $this->getConfig($option) === 0) {
                    $value = 0;
                } else {
                    $value = $default;
                }
                break;
            default:
                $value = $this->getConfig($option);
        }

        $this->_configEffective[$option] = $value;
        return $value;
    }

    /**
     * Get package assignments
     *
     * @param string $order Package assignment property to sort by, default: PackageName
     * @param string $direction asc|desc, default: asc
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model\Package\Assignment
     */
    public function getPackageAssignments($order = 'PackageName', $direction = 'asc')
    {
        $hydrator = new \Zend\Stdlib\Hydrator\ArraySerializable;
        $hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'name' => 'PackageName',
                    'tvalue' => 'Status',
                    'comments' => 'Timestamp',
                )
            )
        );
        $hydrator->addStrategy(
            'Timestamp',
            new \Zend\Stdlib\Hydrator\Strategy\DateTimeFormatterStrategy(
                \Model\Package\Assignment::DATEFORMAT
            )
        );

        $sql = $this->serviceLocator->get('Database\Table\ClientConfig')->getSql();
        $select = $sql->select();
        $select->columns(array('tvalue', 'comments'))
               ->join(
                   'download_available',
                   'download_available.fileid = devices.ivalue',
                   array('name'),
                   \Zend\Db\Sql\Select::JOIN_INNER
               )
               ->where(array('hardware_id' => $this['Id'], 'devices.name' => 'DOWNLOAD'))
               ->order(array($hydrator->extractName($order) => $direction));

        $resultSet = new \Zend\Db\ResultSet\HydratingResultSet(
            $hydrator,
            clone $this->serviceLocator->get('Model\Package\Assignment')
        );
        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());

        return $resultSet;
    }

    /**
     * Get package IDs from download history
     *
     * @return array Package IDs (creation timestamps)
     */
    public function getDownloadedPackageIds()
    {
        $packageHistory = $this->serviceLocator->get('Database\Table\PackageHistory');
        $select = $packageHistory->getSql()->select();
        $select->columns(array('pkg_id'))
               ->where(array('hardware_id' => $this['Id']))
               ->order('pkg_id');
        return array_column($packageHistory->selectWith($select)->toArray(), 'pkg_id');
    }

    /**
     * Get all items of given type
     *
     * @param string $type Item type
     * @param string $order Property to sort by. Default: item-specific
     * @param string $direction Sorting direction (asc|desc)
     * @param array $filters Extra filters for ItemManager::getItems()
     * @return \Zend\Db\ResultSet\AbstractResultSet
     */
    public function getItems($type, $order = null, $direction = null, $filters = array())
    {
        $filters['Client'] = $this['Id'];
        return $this->serviceLocator->get('Model\Client\ItemManager')->getItems(
            $type,
            $filters,
            $order,
            $direction
        );
    }

    /**
     * Set group memberships
     *
     * Groups which are not present in $newGroups remain unchanged. The keys can
     * be either the integer ID or the name of the group.
     *
     * @param array $newMemberships New group memberships (integer|string => Type). Unknown groups are ignored.
     */
    public function setGroupMemberships($newMemberships)
    {
        $groupMemberships = $this->serviceLocator->get('Database\Table\GroupMemberships');

        // Build lookup tables
        $groupsById = array();
        $groupsByName = array();
        foreach ($this->serviceLocator->get('Model\Group\GroupManager')->getGroups() as $group) {
            $groupsById[$group['Id']] = $group;
            $groupsByName[$group['Name']] = $group;
        }
        $oldMemberships = $this->getGroupMemberships(self::MEMBERSHIP_ANY);

        $resetCache = false;
        foreach ($newMemberships as $groupKey => $newMembership) {
            if (is_int($groupKey)) {
                $group = @$groupsById[$groupKey];
            } else {
                $group = @$groupsByName[$groupKey];
            }
            if (!$group) {
                continue; // Ignore unknown groups
            }

            $groupId = $group['Id'];
            if (isset($oldMemberships[$groupId])) {
                $oldMembership = $oldMemberships[$groupId];
            } else {
                $oldMembership = null;
            }
            switch ($newMembership) {
                case self::MEMBERSHIP_AUTOMATIC:
                    if ($oldMembership === self::MEMBERSHIP_ALWAYS or
                        $oldMembership === self::MEMBERSHIP_NEVER
                    ) {
                        // Delete manual membership and update group cache
                        // because the client may be a candidate for automatic
                        // membership.
                        $groupMemberships->delete(
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                            )
                        );
                        $group->update(true);
                        $resetCache = true;
                    }
                    break;
                case self::MEMBERSHIP_ALWAYS:
                case self::MEMBERSHIP_NEVER:
                    if ($oldMembership === null) {
                        $groupMemberships->insert(
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                                'static' => $newMembership,
                            )
                        );
                        $resetCache = true;
                    } elseif ($oldMembership !== $newMembership) {
                        $groupMemberships->update(
                            array(
                                'static' => $newMembership
                            ),
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                            )
                        );
                        $resetCache = true;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid membership type: ' . $newMembership);
            }
        }

        if ($resetCache) {
            $this->_groups = null;
        }
    }

    /**
     * Retrieve group membership information
     *
     * @param integer $membershipType Membership type (one of the MEMBERSHIP_* constants)
     * @return array Group ID => membership type
     * @throws \InvalidArgumentException if $membershipType is invalid
     */
    public function getGroupMemberships($membershipType)
    {
        $groupMemberships = $this->serviceLocator->get('Database\Table\GroupMemberships');
        $select = $groupMemberships->getSql()->select();
        $select->columns(array('group_id', 'static'));

        switch ($membershipType) {
            case self::MEMBERSHIP_ANY:
                break;
            case self::MEMBERSHIP_MANUAL:
                $select->where(
                    new \Zend\Db\Sql\Predicate\Operator('static', '!=', self::MEMBERSHIP_AUTOMATIC)
                );
                break;
            case self::MEMBERSHIP_AUTOMATIC:
            case self::MEMBERSHIP_ALWAYS:
            case self::MEMBERSHIP_NEVER:
                $select->where(array('static' => $membershipType));
                break;
            default:
                throw new \InvalidArgumentException("Bad value for membership: $membershipType");
        }
        $select->where(array('hardware_id' => $this['Id']));

        $this->serviceLocator->get('Model\Group\GroupManager')->updateCache();

        $result = array();
        foreach ($groupMemberships->selectWith($select) as $row) {
            $result[(integer) $row['group_id']] = (integer) $row['static'];
        }
        return $result;
    }

    /**
     * Get groups of which this client is a member
     *
     * Result gets cached.
     *
     * @return \Model\Group\Group[]
     */
    public function getGroups()
    {
        if ($this->_groups === null) {
            $this->_groups = iterator_to_array(
                $this->serviceLocator->get('Model\Group\GroupManager')->getGroups('Member', $this['Id'])
            );
        }
        return $this->_groups;
    }
    /**
     * Set values for custom fields
     *
     * @param array $values Field name => Value
     */
    public function setCustomFields($values)
    {
        $this->serviceLocator->get('Model\Client\CustomFieldManager')->write($this['Id'], $values);
    }

    /**
     * Export to DOM document
     *
     * @return \Protocol\Message\InventoryRequest
     */
    public function toDomDocument()
    {
        $document = clone $this->serviceLocator->get('Protocol\Message\InventoryRequest');
        $document->loadClient($this, $this->serviceLocator);
        return $document;
    }
}