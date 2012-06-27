<?php
/**
 * Validate string to be a path to a readable file
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Library
 */
/**
 * Validate string to be a path to a readable file
 * @package Library
 */
class Braintacle_Validate_DirectoryWritable extends Zend_Validate_Abstract
{
    const FILE = 'file';
    const READABLE = 'readable';

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates = array(
        self::FILE => "'%value%' is not a file or inaccessible",
        self::READABLE => "File '%value%' is not readable",
    );

    /**
     * Returns true if $value is a file and readable
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if (is_file($value)) {
            if (is_readable($value)) {
                return true;
            } else {
                $this->_error(self::READABLE);
                return false;
            }
        } else {
            $this->_error(self::FILE);
            return false;
        }
    }

}