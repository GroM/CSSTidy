<?php
namespace CSSTidy;

class SelectorManipulate
{
    public function mergeWithSameName(AtBlock $block)
    {
        // Because elements are removed from $block->properties, foreach cannot be used
        reset($block->properties);
        while ($element = current($block->properties)) {
            next($block->properties);
            if (
                !$element instanceof Block ||
                ($element instanceof AtBlock && $element->name === '@font-face') // never merge @font-face
            ) {
                continue;
            }

            /** @var Block $element */
            $sameBlock = $block->getBlockWithSameName($element);
            if ($sameBlock) {
                if ($element instanceof AtBlock) {
                    /** @var AtBlock $element */
                    $element->merge($sameBlock);
                } else {
                    $element->mergeProperties($sameBlock->properties);
                }
                $block->removeBlock($sameBlock);
            }

            if ($element instanceof AtBlock) {
                $this->mergeWithSameName($element);
            }
        }
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