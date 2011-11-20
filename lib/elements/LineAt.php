<?php
namespace CSSTidy;

class LineAt
{
    /** @var string */
    public $name;

    /** @var string */
    public $value;

    /**
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString()
    {
        return "@$this->name $this->value;";
    }
}