<?php
namespace CSSTidy;

class SelectorManipulate
{
    /**
     * Merge selectors or at blocks with same name
     * Example: a {color:red} a {font-weight:bold} -> a {color:red;font-weight:bold}
     * @param AtBlock $block
     */
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

    /**
     * Merge selector with same properties
     * Example: a {color:red} b {color:red} -> a,b {color:red}
     * @param AtBlock $block
     */
    public function mergeWithSameProperties(AtBlock $block)
    {
        // Because elements are removed from $block->properties, foreach cannot be used
        reset($block->properties);
        while (($element = current($block->properties))) {
            next($block->properties);
            if (!$element instanceof Block) {
                continue;
            } else if (!$element instanceof Selector) {
                $this->mergeWithSameProperties($element);
                continue;
            }

            $sameSelectors = array();
            foreach ($block->properties as $val) {
                if (!$val instanceof Selector) {
                    continue;
                }

                if ($val->properties == $element->properties && $val !== $element) {
                    $sameSelectors[] = $val;
                }
            }

            if (!empty($sameSelectors)) {
                foreach ($sameSelectors as $sameSelector) {
                    /** @var Selector $element */
                    $element->appendSelectorName($sameSelector->name);
                    $block->removeBlock($sameSelector);
                }
            }
        }
    }

    /**
     * Removes invalid selectors and their corresponding rule-sets as
     * defined by 4.1.7 in REC-CSS2. This is a very rudimentary check
     * and should be replaced by a full-blown parsing algorithm or
     * regular expression
     * @param AtBlock $block
     */
    public function discardInvalid(AtBlock $block)
    {
        foreach ($block->properties as $key => $selector) {
            if ($selector instanceof AtBlock) {
                $this->discardInvalid($selector);
                continue;
            } else if (!$selector instanceof Block) {
                continue;
            }

            $ok = true;
            $selectors = array_map('trim', explode(',', $selector->name));

            foreach ($selectors as $s) {
                $simpleSelectors = preg_split('/\s*[+>~\s]\s*/', $s);
                foreach ($simpleSelectors as $ss) {
                    if ($ss === '') {
                        $ok = false;
                        break 2;
                    }
                    // could also check $ss for internal structure,
                    // but that probably would be too slow
                }
            }

            if (!$ok) {
                unset($block->properties[$key]);
            }
        }
    }

    /**
     * Separate selector for better reability
     * Example: a,b {color:red} -> b {color:red} b {color:red}
     * @param AtBlock $block
     */
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