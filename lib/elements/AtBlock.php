<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * AtBlock element
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

class AtBlock extends Block
{
    /**
     * @param Block $block
     * @return Block
     */
    public function addBlock(Block $block)
    {
        $name = '!' . $block->name;

        while (isset($this->properties[$name])) {
            $name .= ' ';
        }

        return $this->properties[$name] = $block;
    }

    /**
     * @param Block $block
     * @return bool true if block was removed
     */
    public function removeBlock(Block $block)
    {
        $name = '!' . $block->name;

        while (isset($this->properties[$name])) {
            if ($this->properties[$name] === $block) {
                unset($this->properties[$name]);
                return true;
            }
            $name .= ' ';
        }

        return false;
    }

    /**
     * @param Block $block
     * @return Block|bool
     */
    public function getBlockWithSameName(Block $block)
    {
        $name = '!' . $block->name;

        while (isset($this->properties[$name])) {
            $sameBlock = $this->properties[$name];
            if (
                $sameBlock !== $block &&
                $sameBlock instanceof $block &&
                $sameBlock->name === $block->name
            ) {
                return $sameBlock;
            }
            $name .= ' ';
        }

        return false;
    }

    /**
     * @param Block $block
     */
    public function merge(Block $block)
    {
        foreach ($block->properties as $key => $value) {
            if ($value instanceof Block) {
                $this->addBlock($value);
            } else if ($value instanceof LineAt) {
                $this->addLineAt($value);
            } else {
                $this->addProperty($key, $value);
            }
        }
    }

    /**
     * @param LineAt $lineAt
     */
    public function addLineAt(LineAt $lineAt)
    {
        $this->properties[] = $lineAt;
    }
}