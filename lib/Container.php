<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * Simple Dependency injection container
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

/**
 * @property \CSSTidy\Configuration $configuration
 * @property \CSSTidy\Logger $logger
 * @property \CSSTidy\SelectorManipulate $selectorManipulate
 * @property \CSSTidy\Optimise $optimise
 */
class Container
{
    /** @var object[] */
    protected $services = array();

    public function __construct()
    {
        $cont = $this;
        $this->services = array(
            'logger' => function() {
                require_once __DIR__ . '/Logger.php';
                return new Logger;
            },
            'configuration' => function() {
                require_once __DIR__ . '/Configuration.php';
                return new Configuration;
            },
            'selectorManipulate' => function() {
                require_once __DIR__ . '/SelectorManipulate.php';
                return new SelectorManipulate;
            },
            'optimise' => function() use ($cont) {
                require_once __DIR__ . '/Optimise.php';
                return new Optimise($cont->logger, $cont->configuration, $cont->optimiseColor, $cont->optimiseNumber);
            },
            'optimiseColor' => function() use($cont) {
                require_once __DIR__ . '/optimise/Color.php';
                return new \CSSTidy\Optimise\Color($cont->logger, $cont->optimiseNumber);
            },
            'optimiseNumber' => function() use($cont) {
                require_once __DIR__ . '/optimise/Number.php';
                return new \CSSTidy\Optimise\Number($cont->logger, $cont->configuration->getConvertUnit());
            },
        );
    }

    /**
     * @param string $name
     * @return object
     * @throws \Exception
     */
    public function __get($name)
    {
        if (isset($this->services[$name])) {
            return $this->$name = $this->services[$name]();
        }

        throw new \Exception("Service with name '$name' not exists");
    }

    /**
     * @param string $name
     * @param object $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if (!is_object($value)) {
            throw new \Exception("Service '$name' must be object");
        }

        $this->$name = $value;
    }
}