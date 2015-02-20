<?php
/**
 * Factory for ClientController
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

namespace Console\Service;

/**
 * Factory for ClientController
 */
class ClientControllerFactory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * @internal
     */
    public function createService(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $serviceManager = $serviceLocator->getServiceLocator();
        $config = $serviceManager->get('Model\Config');
        return new \Console\Controller\ClientController(
            $serviceManager->get('Model\Computer\Computer'),
            $serviceManager->get('Model\Group\GroupManager'),
            $serviceManager->get('FormElementManager'),
            $config,
            new \Library\InventoryUploader($config)
        );
    }
}
