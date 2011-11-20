<?php
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