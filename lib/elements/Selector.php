<?php
namespace CSSTidy;

class Selector extends Block
{
    /** @var array */
    public $subSelectors = array();

    /**
     * @param string $name
     */
    public function appendSelectorName($name)
    {
        $this->subSelectors[] = $name;
        $this->name .= ',' . $name;
    }
}