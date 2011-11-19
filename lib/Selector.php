<?php
namespace CSSTidy;

class Selector
{
    /** @var string */
    public $name;

    /** @var string[] */
    public $properties = array();

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addProperty($name, $value)
    {
        while (isset($this->properties[$name])) {
            $name .= ' ';
        }

        $this->properties[$name] = $value;
    }

    /**
     * @param array $properties
     */
    public function mergeProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (
                $value !== '' && (
                !isset($this->properties[$property]) ||
                !CSSTidy::isImportant($this->properties[$property]) ||
                (CSSTidy::isImportant($this->properties[$property]) && CSSTidy::isImportant($value))
            )) {
                $this->properties[$property] = $value;
            }
        }
    }
}