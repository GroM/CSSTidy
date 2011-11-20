<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * Abstract block element
 *
 * Copyright 2005, 2006, 2007 Florian Schmitz
 *
 * This file is part of CSSTidy.
 *
 *   CSSTidy is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or
 *   (at your option) any later version.
 *
 *   CSSTidy is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package CSSTidy
 * @author Jakub Onderka (acci at acci dot cz) 2011
 */
namespace CSSTidy;

abstract class Block
{
    /** @var string */
    public $name;

    /** @var string[] */
    public $properties = array();

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addProperty($name, $value)
    {
        while (isset($this->properties[$name])) {
            $name .= ' ';
        }

        $this->properties[$name] = $value;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $name => $value) {
            $this->addProperty($name, $value);
        }
    }

    /**
     * @param array $properties
     */
    public function mergeProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (
                $value !== '' && (
                !isset($this->properties[$property]) ||
                !CSSTidy::isImportant($this->properties[$property]) ||
                (CSSTidy::isImportant($this->properties[$property]) && CSSTidy::isImportant($value))
            )) {
                $this->properties[$property] = $value;
            }
        }
    }
}