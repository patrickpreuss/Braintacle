<?php
/**
 * "software_definitions" table
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Table;

/**
 * "software_definitions" table
 */
class SoftwareDefinitions extends \Database\AbstractTable
{
    /**
     * Migrate accepted software from old table structure?
     * @var bool
     */
    protected $_migrateAccepted;

    /**
     * Migrate ignored software from old table structure?
     * @var bool
     */
    protected $_migrateIgnored;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'software_definitions';
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _preSetSchema($logger, $schema, $database)
    {
        $tables = $database->getTableNames();
        $tableExists = in_array('software_definitions', $tables);
        $this->_migrateAccepted = (!$tableExists and in_array('dico_soft', $tables));
        $this->_migrateIgnored = (!$tableExists and in_array('dico_ignored', $tables));
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _postSetSchema($logger, $schema, $database)
    {
        if ($this->_migrateAccepted) {
            $logger->info('Migrating accepted software definitions');
            $this->adapter->query(
                'INSERT INTO software_definitions (name, display) SELECT extracted, TRUE FROM dico_soft',
                \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            );
            $logger->info('done.');
        }
        if ($this->_migrateIgnored) {
            $logger->info('Migrating ignored software definitions');
            $query = <<<'EOT'
                INSERT INTO software_definitions (name, display)
                SELECT extracted, FALSE FROM dico_ignored WHERE extracted NOT IN(SELECT name FROM software_definitions)
EOT;
            $this->adapter->query($query, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
            $logger->info('done.');
        }
    }
}