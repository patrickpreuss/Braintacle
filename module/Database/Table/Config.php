<?php
/**
 * "config" table
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

Namespace Database\Table;

/**
 * "config" table
 */
class Config extends \Database\AbstractTable
{
    /**
     * Mapping from option names to internal database identifiers
     * @var array
     */
    protected $_optionMap = array(
        'acceptNonZlib' => 'COMPRESS_TRY_OTHERS',
        'agentWhitelistFile' => 'EXT_USERAGENTS_FILE_PATH',
        'autoDuplicateCriteria' => 'AUTO_DUPLICATE_LVL',
        'communicationServerUri' => 'LOCAL_URI_SERVER',
        'contactInterval' => 'PROLOG_FREQ',
        'defaultAction' => 'BRAINTACLE_DEFAULT_ACTION',
        'defaultActionParam' => 'BRAINTACLE_DEFAULT_ACTION_PARAM',
        'defaultCertificate' => 'BRAINTACLE_DEFAULT_CERTIFICATE',
        'defaultDeleteInterfaces' => 'BRAINTACLE_DEFAULT_DELETE_INTERFACES',
        'defaultDeployError' => 'BRAINTACLE_DEFAULT_DEPLOY_ERROR',
        'defaultDeployGroups' => 'BRAINTACLE_DEFAULT_DEPLOY_GROUPS',
        'defaultDeployNonnotified' => 'BRAINTACLE_DEFAULT_DEPLOY_NONNOTIFIED',
        'defaultDeployNotified' => 'BRAINTACLE_DEFAULT_DEPLOY_NOTIFIED',
        'defaultDeploySuccess' => 'BRAINTACLE_DEFAULT_DEPLOY_SUCCESS',
        'defaultDownloadLocation' => 'BRAINTACLE_DEFAULT_DOWNLOAD_LOCATION',
        'defaultInfoFileLocation' => 'BRAINTACLE_DEFAULT_INFOFILE_LOCATION',
        'defaultMaxFragmentSize' => 'BRAINTACLE_DEFAULT_MAX_FRAGMENT_SIZE',
        'defaultMergeGroups' => 'BRAINTACLE_DEFAULT_MERGE_GROUPS',
        'defaultMergePackages' => 'BRAINTACLE_DEFAULT_MERGE_PACKAGES',
        'defaultMergeCustomFields' => 'BRAINTACLE_DEFAULT_MERGE_USERDEFINED',
        'defaultPackagePriority' => 'BRAINTACLE_DEFAULT_PACKAGE_PRIORITY',
        'defaultPlatform' => 'BRAINTACLE_DEFAULT_PLATFORM',
        'defaultUserActionMessage' => 'BRAINTACLE_DEFAULT_USER_ACTION_MESSAGE',
        'defaultUserActionRequired' => 'BRAINTACLE_DEFAULT_USER_ACTION_REQUIRED',
        'defaultWarn' => 'BRAINTACLE_DEFAULT_WARN',
        'defaultWarnAllowAbort' => 'BRAINTACLE_DEFAULT_WARN_ALLOW_ABORT',
        'defaultWarnAllowDelay' => 'BRAINTACLE_DEFAULT_WARN_ALLOW_DELAY',
        'defaultWarnCountdown' => 'BRAINTACLE_DEFAULT_WARN_COUNTDOWN',
        'defaultWarnMessage' => 'BRAINTACLE_DEFAULT_WARN_MESSAGE',
        'displayBlacklistedSoftware' => 'BRAINTACLE_DISPLAY_BLACKLISTED_SOFTWARE',
        'downloadCycleDelay' => 'DOWNLOAD_CYCLE_LATENCY',
        'downloadFragmentDelay' => 'DOWNLOAD_FRAG_LATENCY',
        'downloadMaxPriority' => 'DOWNLOAD_PERIOD_LENGTH',
        'downloadPeriodDelay' => 'DOWNLOAD_PERIOD_LATENCY',
        'downloadTimeout' => 'DOWNLOAD_TIMEOUT',
        'groupCacheExpirationFuzz' => 'GROUPS_CACHE_OFFSET',
        'groupCacheExpirationInterval' => 'GROUPS_CACHE_REVALIDATE',
        'inspectRegistry' => 'REGISTRY',
        'inventoryFilter' => 'INVENTORY_FILTER_ON',
        'inventoryInterval' => 'FREQUENCY',
        'limitInventory' => 'INVENTORY_FILTER_FLOOD_IP',
        'limitInventoryInterval' => 'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',
        'lockValidity' => 'LOCK_REUSE_TIME',
        'logLevel' => 'LOGLEVEL',
        'logPath' => 'LOGPATH',
        'packageDeployment' => 'DOWNLOAD',
        'packagePath' => 'DOWNLOAD_PACK_DIR',
        'saveDir' => 'OCS_FILES_PATH',
        'saveFormat' => 'OCS_FILES_FORMAT',
        'saveOverwrite' => 'OCS_FILES_OVERWRITE',
        'saveRawData' => 'GENERATE_OCS_FILES',
        'scanAlways' => 'IPDISCOVER_NO_POSTPONE',
        'scanArpDelay' => 'IPDISCOVER_LATENCY',
        'scannerMaxDays' => 'IPDISCOVER_MAX_ALIVE',
        'scannerMinDays' => 'IPDISCOVER_BETTER_THRESHOLD',
        'scannersPerSubnet' => 'IPDISCOVER',
        'scanSnmp' => 'SNMP',
        'schemaVersion' => 'BRAINTACLE_SCHEMA_VERSION',
        'sessionCleanupInterval' => 'SESSION_CLEAN_TIME',
        'sessionRequired' => 'INVENTORY_SESSION_ONLY',
        'sessionValidity' => 'SESSION_VALIDITY_TIME',
        'setGroupPackageStatus' => 'DOWNLOAD_GROUPS_TRACE_EVENTS',
        'trustedNetworksOnly' => 'PROLOG_FILTER_ON',
        'useTransactions' => 'INVENTORY_TRANSACTION',
    );

    /**
     * Options stored in the 'ivalue' column (everything else goes to 'tvalue')
     * @var array
     */
    protected $_iValues = array(
        'acceptNonZlib',
        'agentDeployment',
        'agentUpdate',
        'autoDuplicateCriteria',
        'contactInterval',
        'downloadCycleDelay',
        'downloadFragmentDelay',
        'downloadMaxPriority',
        'downloadPeriodDelay',
        'downloadTimeout',
        'groupCacheExpirationFuzz',
        'groupCacheExpirationInterval',
        'inspectRegistry',
        'inventoryFilter',
        'inventoryInterval',
        'limitInventory',
        'limitInventoryInterval',
        'lockValidity',
        'logLevel',
        'packageDeployment',
        'saveRawData',
        'saveOverwrite',
        'scannersPerSubnet',
        'scannerMinDays',
        'scannerMaxDays',
        'scanAlways',
        'scanArpDelay',
        'scanSnmp',
        'sessionCleanupInterval',
        'sessionRequired',
        'sessionValidity',
        'setGroupPackageStatus',
        'trustedNetworksOnly',
        'useTransactions',
    );

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _postSetSchema()
    {
        $logger = $this->_serviceLocator->get('Library\Logger');

        // If packagePath has not been converted yet, append /download directory
        // with had previously been appended automatically.
        if ($this->get('schemaVersion') < 7) {
            $packagePath = $this->get('packagePath') . '/download';
            $logger->info('Setting packagePath option to ' . $packagePath);
            $this->set('packagePath', $packagePath);
        }

        // If no communication server URI is set, try to generate it from the
        // obsolete Host/Port options
        $this->columns = array();
        $server = array();
        foreach ($this->select("name LIKE 'LOCAL%'") as $option) {
            $server[$option->name] = array(
                'ivalue' => $option->ivalue,
                'tvalue' => $option->tvalue
            );
        }
        if (!isset($server['LOCAL_URI_SERVER']) and isset($server['LOCAL_SERVER'])) {
            $uri = \Zend\Uri\UriFactory::factory('http:');
            $uri->setHost($server['LOCAL_SERVER']['tvalue']);
            if (isset($server['LOCAL_PORT'])) {
                $uri->setPort($server['LOCAL_PORT']['ivalue']);
            }
            $uri->setPath('/ocsinventory');
            $uri = $uri->toString();

            $logger->info(
                'Converting communicationServerUri option to ' . $uri
            );
            $this->insert(
                array(
                    'name' => 'LOCAL_URI_SERVER',
                    'tvalue' => $uri
                )
            );
        }

        // Delete deprecated options, causing the communication server to use
        // (sensible) defaults
        $count = $this->delete(
            array(
                'name' => array(
                    'DEPLOY', // default: 0
                    'ENABLE_GROUPS', // default: 1
                    'INVENTORY_CACHE_ENABLED', // default: 0
                    'INVENTORY_CACHE_KEEP', // unused
                    'INVENTORY_CACHE_REVALIDATE', //unused
                    'INVENTORY_DIFF', // default: 1
                    'INVENTORY_FILTER_ENABLED', // default: 0
                    'INVENTORY_WRITE_DIFF', // default: 1
                    'IPDISCOVER_USE_GROUPS', // default: 1
                    'LOCAL_PORT', // unused
                    'LOCAL_SERVER', // unused
                    'SNMP_INVENTORY_DIFF', // default: 1
                    'TRACE_DELETED', // default: 0
                    'UPDATE', // default: 0
                )
            )
        );
        if ($count) {
            $logger->info("Deleted $count deprecated options, using defaults");
        }
    }

    /**
     * Read option value from the database
     *
     * @param string $option Option name
     * @return mixed Option value or NULL if no value is stored in the database
     */
    public function get($option)
    {
        $name = $this->getDbIdentifier($option);
        $column = $this->_getColumnName($option);
        $this->columns = array($column);
        $row = $this->select(array('name' => $name))->current();
        if ($row) {
            $value = $row[$column];
        } else {
            $value = null;
        }
        return $value;
    }

    /**
     * Write option value to the database
     *
     * @param string $option Option name
     * @param mixed $value Option value
     */
    public function set($option, $value)
    {
        $name = $this->getDbIdentifier($option);
        $column = $this->_getColumnName($option);
        if ($value === false) {
            // Would otherwise be cast to empty string
            $value = 0;
        }
        if ($column == 'ivalue' and !preg_match('/^-?[0-9]+$/', $value)) {
            throw new \InvalidArgumentException(
                sprintf('Tried to set non-integer value "%s" to integer option "%s"', $value, $option)
            );
        }
        $this->columns = array($column);
        $row = $this->select(array('name' => $this->getDbIdentifier($option)))->current();
        if ($row) {
            $oldValue = $row->$column;
            if ($oldValue !== (string) $value) {
                $this->update(
                    array($column => $value),
                    array('name' => $name)
                );
            }
        } else {
            $this->insert(
                array(
                    'name' => $name,
                    $column => $value
                )
            );
        }
    }

    /**
     * Return internal database identifier for given option
     *
     * @param string $option Option name
     * @return string internal database identifier
     * @throws \InvalidArgumentException if $option is not a valid option name
     */
    public function getDbIdentifier($option)
    {
        if (!isset($this->_optionMap[$option])) {
            throw new \InvalidArgumentException('Invalid option: ' . $option);
        }
        return $this->_optionMap[$option];
    }

    /**
     * Get the name of the column that holds the value for the given option
     *
     * @param string $option Option name
     * @return string "ivalue" or "tvalue"
     */
    protected function _getColumnName($option)
    {
        return in_array($option, $this->_iValues) ? 'ivalue' : 'tvalue';
    }
}
