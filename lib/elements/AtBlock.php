<?php
namespace CSSTidy;

class AtBlock extends Block
{
    /** @var int */
    public static $mergeSelectors;

    /**
     * @param Element $block
     * @return Element
     */
    public function addBlock(Block $block)
    {
        $name = '!' . $block->name;

        // Never merge @font-face at rule
        if ($block->name === '@font-face') {
            while (isset($this->properties[$name])) {
                $name .= ' ';
            }
        } else if (self::$mergeSelectors === Configuration::MERGE_SELECTORS) {
            if (isset($this->properties[$name])) {
                $this->properties[$name]->mergeProperties($block->properties);
                return $this->properties[$name];
            }
        } else {
            if (isset($this->properties[$name])) {
                end($this->properties);
                if (key($this->properties)  === $name) {
                    $this->properties[$name]->mergeProperties($block->properties);
                    return $this->properties[$name];
                }

                $name .= ' ';

                while (isset($this->properties[$name])) {
                    $name .= ' ';
                }
            }
        }

        return $this->properties[$name] = $block;
    }

    /**
     * @param Element $block
     */
    public function removeBlock(Block $block)
    {
        foreach ($this->properties as $key => $value)
        {
            if ($block === $value) {
                unset($this->properties[$key]);
                break;
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