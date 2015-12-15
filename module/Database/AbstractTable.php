<?php
/**
 * Base class for table objects
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

namespace Database;

/**
 * Base class for table objects
 *
 * Table objects should be pulled from the service manager which provides the
 * Database\Table\ClassName services which will create and set up object
 * instances.
 */
abstract class AbstractTable extends \Zend\Db\TableGateway\AbstractTableGateway
{
    /**
     * Service manager
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Hydrator
     * @var \Zend\Stdlib\Hydrator\AbstractHydrator
     */
    protected $_hydrator;

    /**
     * Constructor
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator Service manager instance
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
        if (!$this->table) {
            // If not set explicitly, derive table name from class name.
            // Uppercase letters cause an underscore to be inserted, except at
            // the beginning of the string.
            $this->table = strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $this->_getClassName()));
        }
        $this->adapter = $serviceLocator->get('Db');
    }

    /**
     * Get hydrator suitable for bridging with model
     *
     * @return \Zend\Stdlib\Hydrator\AbstractHydrator|null
     */
    public function getHydrator()
    {
        return $this->_hydrator;
    }

    /**
     * Helper method to get class name without namespace
     * @internal
     * @return string Class name
     * @codeCoverageIgnore
     */
    protected function _getClassName()
    {
        return substr(get_class($this), strrpos(get_class($this), '\\') + 1);
    }

    /**
     * Create or update table according to schema file
     *
     * The schema file is located in ./data/ClassName.json and contains all
     * information required to create or alter the table.
     * @codeCoverageIgnore
     */
    public function setSchema()
    {
        $logger = $this->_serviceLocator->get('Library\Logger');
        $schema = \Zend\Config\Factory::fromFile(
            Module::getPath('data/Tables/' . $this->_getClassName() . '.json')
        );
        $database = $this->_serviceLocator->get('Database\Nada');

        $this->_preSetSchema($logger, $schema, $database);
        \Database\SchemaManager::setSchema($logger, $schema, $database);
        $this->_postSetSchema($logger, $schema, $database);
    }

    /**
     * Hook to be called before creating/altering table schema
     *
     * @param \Zend\Log\Logger $logger Logger instance
     * @param array $schema Parsed table schema
     * @param \Nada_Database $database Database object
     * @codeCoverageIgnore
     */
    protected function _preSetSchema($logger, $schema, $database)
    {
    }

    /**
     * Hook to be called after creating/altering table schema
     *
     * @param \Zend\Log\Logger $logger Logger instance
     * @param array $schema Parsed table schema
     * @param \Nada_Database $database Database object
     * @codeCoverageIgnore
     */
    protected function _postSetSchema($logger, $schema, $database)
    {
    }

    /**
     * Drop a column if it exists
     *
     * @param \Zend\Log\Logger $logger Logger instance
     * @param \Nada_Database $database Database object
     * @param string $column column name
     * @codeCoverageIgnore
     */
    protected function _dropColumnIfExists($logger, $database, $column)
    {
        $tables = $database->getTables();
        if (isset($tables[$this->table])) {
            $table = $tables[$this->table];
            $columns = $table->getColumns();
            if (isset($columns[$column])) {
                $logger->info("Dropping column $this->table.$column...");
                $table->dropColumn($column);
                $logger->info('done.');
            }
        }
    }

    /**
     * Fetch a single column as a flat array
     *
     * @param string $name Column name
     * @return array
     */
    public function fetchCol($name)
    {
        $select = $this->sql->select();
        $select->columns(array($name), false);
        $resultSet = $this->selectWith($select);

        // Map column name to corresponding result key
        if ($resultSet instanceof \Zend\Db\ResultSet\HydratingResultSet) {
            $hydrator = $resultSet->getHydrator();
            if ($hydrator instanceof \Zend\Stdlib\Hydrator\AbstractHydrator) {
                $name = $hydrator->hydrateName($name);
            }
        }

        $col = array();
        foreach ($resultSet as $row) {
            $col[] = $row[$name];
        }
        return $col;
    }
}