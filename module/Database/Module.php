<?php
/**
 * The Database module
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

use Zend\ModuleManager\Feature;

/**
 * The Database module
 *
 * This module provides a low level interface to the database. It is used by the
 * model classes and for managing the database structure.
 *
 * @codeCoverageIgnore
 */
class Module implements
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\BootstrapListenerInterface,
    Feature\InitProviderInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Library');
        $manager->loadModule('Model');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        // Static configuration part
        $config = array(
            'service_manager' => array(
                'abstract_factories' => array(
                    'Database\Service\AbstractTableFactory',
                ),
                'factories' => array(
                    'Db' => 'Zend\Db\Adapter\AdapterServiceFactory',
                    'Database\Nada' => 'Database\Service\NadaFactory',
                    'Database\SchemaManager' => 'Database\Service\SchemaManagerFactory',
                ),
            ),
        );

        if (\Library\Application::isTest()) {
            // Test setup with in-memory database
            $config['db'] = array('driver' => 'Pdo_Sqlite');
        } else {
            // Merge database configuration from config file
            $configFileContent = \Library\Application::getConfig();
            if (!is_array($configFileContent['database'])) {
                throw new \RuntimeException('Config file has no "database" section');
            }
            $config['db'] = $configFileContent['database'];
        }
        $config['db']['charset'] = 'utf8';

        return $config;
    }

    /**
     * @internal
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Database/' . $path);
    }

    /**
     * @internal
     */
    public function onBootstrap(\Zend\EventManager\EventInterface $e)
    {
        $e->getParam('application')->getServiceManager()->get('Database\Nada')->setTimezone();
    }
}