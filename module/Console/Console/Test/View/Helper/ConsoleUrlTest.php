<?php
/**
 * Tests for the ConsoleUrl helper
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

namespace Console\Test\View\Helper;

/**
 * Tests for the ConsoleUrl helper
 */
class ConsoleUrlTest extends \Library\Test\View\Helper\AbstractTest
{
    /**
     * Tests for the __invoke() method
     */
    public function testInvokable()
    {
        $helper = $this->_getHelper();

        $this->assertEquals(
            '/console/computer/index/',
            $helper('computer', 'index')
        );

        $params = array('param1' => 'value1');
        $this->assertEquals(
            '/console/computer/index/?param1=value1',
            $helper('computer', 'index', $params)
        );

        $params['param2'] = 'value2';
        $this->assertEquals(
            '/console/computer/index/?param1=value1&param2=value2',
            $helper('computer', 'index', $params)
        );
    }
}
