<?php
namespace CSSTidy;

class SelectorManipulate
{
    public function merge()
    {

    }

    public function separate(AtBlock $block)
    {
        foreach ($block->properties as $element) {
            if (!$element instanceof Block) {
                continue;
            } else if ($element instanceof AtBlock) {
                $this->separate($element);
                continue;
            }

            /** @var Selector $element */
            if (count($element->subSelectors) <= 1) {
                continue;
            }

            foreach ($element->subSelectors as $subSelector) {
                $newSelector = new Selector($subSelector);
                $newSelector->properties = $element->properties;
                $block->addBlock($newSelector);
            }

            $block->removeBlock($element);
        }
    }
}