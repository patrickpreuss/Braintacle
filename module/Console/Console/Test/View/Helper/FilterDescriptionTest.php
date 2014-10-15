<?php
/**
 * Tests for the FilterDescription helper
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

namespace Console\Test\View\Helper;

/**
 * Tests for the FilterDescription helper
 */
class FilterDescriptionTest extends \Library\Test\View\Helper\AbstractTest
{
    public function testInterfaceInSubnet()
    {
        // Escaped characters should not occur, but are theoretically possible.
        $this->assertEquals(
            "42 computers with an interface in network &#039;&gt;192.0.2.0/24&#039;",
            $this->_getHelper()->__invoke(
                array('NetworkInterface.Subnet', 'NetworkInterface.Netmask'),
                array('>192.0.2.0', '255.255.255.0'),
                42
            )
        );
    }

    public function testPackageNonnotified()
    {
        $this->assertEquals(
            "42 computers waiting for notification of package &#039;&gt;Name&#039;",
            $this->_getHelper()->__invoke('PackageNonnotified', '>Name', 42)
        );
    }

    public function testPackageSuccess()
    {
        $this->assertEquals(
            "42 computers with package &#039;&gt;Name&#039; successfully deployed",
            $this->_getHelper()->__invoke('PackageSuccess', '>Name', 42)
        );
    }

    public function testPackageNotified()
    {
        $this->assertEquals(
            "42 computers with deployment of package &#039;&gt;Name&#039; in progress",
            $this->_getHelper()->__invoke('PackageNotified', '>Name', 42)
        );
    }

    public function testPackageError()
    {
        $this->assertEquals(
            "42 computers where deployment of package &#039;&gt;Name&#039; failed",
            $this->_getHelper()->__invoke('PackageError', '>Name', 42)
        );
    }

    public function testSoftware()
    {
        $this->assertEquals(
            "42 computers where software &#039;&gt;Name&#039; is installed",
            $this->_getHelper()->__invoke('Software', '>Name', 42)
        );
    }

    public function testManualProductKey()
    {
        $this->assertEquals(
            '42 computers with manually entered product key',
            $this->_getHelper()->__invoke('Windows.ManualProductKey', 'dummy', 42)
        );
    }

    public function testInvalidArrayFilter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No description available for this set of multiple filters'
        );
        $this->_getHelper()->__invoke(array('NetworkInterface.Subnet'), null, 42);
    }

    public function testInvalidStringFilter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No description available for filter invalid'
        );
        $this->_getHelper()->__invoke('invalid', null, 42);
    }
}
