<?php
namespace CSSTidy;

class AtBlock extends Selector
{
    /** @var int */
    public static $mergeSelectors;

    /**
     * @param Selector $selector
     * @return Selector
     */
    public function addSelector(Selector $selector)
    {
        $name = '!' . $selector->name;

        // Never merge @font-face at rule
        if ($selector->name === '@font-face') {
            while (isset($this->properties[$name])) {
                $name .= ' ';
            }
        } else if (self::$mergeSelectors === Configuration::MERGE_SELECTORS) {
            if (isset($this->properties[$name])) {
                $this->properties[$name]->mergeProperties($selector->properties);
                return $this->properties[$name];
            }
        } else {
            if (isset($this->properties[$name])) {
                end($this->properties);
                if (key($this->properties)  === $name) {
                    $this->properties[$name]->mergeProperties($selector->properties);
                    return $this->properties[$name];
                }

                $name .= ' ';

                while (isset($this->properties[$name])) {
                    $name .= ' ';
                }
            }
        }

        return $this->properties[$name] = $selector;
    }

    /**
     * @param Selector $selector
     */
    public function removeSelector(Selector $selector)
    {
        foreach ($this->properties as $key => $value)
        {
            if ($selector === $value) {
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