<?php
namespace CSSTidy\Optimise;
use CSSTidy\Logger;

class Number
{
    /**
     * All CSS units (CSS 3 units included)
     *
     * @see http://www.w3.org/TR/css3-values/
     * @see compressNumbers()
     * @static
     * @var array
     */
    public static $units = array(
        // Absolute lengths
        'px','in','cm','mm','pt','pc',
        // Relative lengths
        '%','em','rem','ex','ch','vw','vh','vm',
        // Angle
        'deg','grad','rad','turn',
        // Time
        'ms','s',
        // Frequency
        'khz','hz',
        // Layout-specific
        'fr', 'gr',
        // Resolution
        'dpi','dpcm','dppx',
        // Speech
        'db', 'st'
    );

    /**
     * Properties that allow <color> as value
     *
     * @todo CSS3 properties
     * @see compressNumbers();
     * @static
     * @var array
     */
    public static $colorValues = array(
        'background-color' => true,
        'border-color' => true,
        'border-top-color' => true,
        'border-right-color' => true,
        'border-bottom-color' => true,
        'border-left-color' => true,
        'color' => true,
        'outline-color' => true
    );

    /**
     * Properties that need a value with unit
     *
     * @todo CSS3 properties
     * @todo Background property realy need unit?
     * @see compressNumbers();
     * @static
     * @var array
     */
    public static $unitValues = array (
        'background', 'background-position', 'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-width', 'border-top-width', 'border-right-width', 'border-left-width', 'border-bottom-width', 'bottom',
        'border-spacing', 'font-size', 'height', 'left', 'margin', 'margin-top', 'margin-right', 'margin-bottom',
        'margin-left', 'max-height', 'max-width', 'min-height', 'min-width', 'outline', 'outline-width', 'padding',
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'right', 'top', 'text-indent',
        'letter-spacing', 'word-spacing', 'width'
    );


    /** @var \CSSTidy\Logger */
    protected $logger;

    /** @var bool */
    protected $convertUnit = false;

    public function __construct(Logger $logger, $convertUnit)
    {
        $this->logger = $logger;
        $this->convertUnit = $convertUnit;
    }

    /**
     * Compresses numbers (ie. 1.0 becomes 1 or 1.100 becomes 1.1 )
     * @param string $property
     * @param string $subValue
     * @return string
     */
    public function optimise($property, $subValue)
    {
        // for font:1em/1em sans-serif...;
        if ($property === 'font') {
            $parts = explode('/', $subValue);
        } else {
            $parts = array($subValue);
        }

        foreach ($parts as &$part) {
            // if we are not dealing with a number at this point, do not optimise anything
            $number = $this->analyse($part);
            if ($number === false) {
                return $subValue;
            }

            // Fix colors without # character
            if (isset(self::$colorValues[$property])) {
                if ($this->checkHexValue($part)) {
                    $part = '#' . $part;
                    continue;
                } else {
                    $this->logger->log("Invalid color value '$part' for property '$property'", Logger::ERROR);
                }
            }

            if (abs($number[0]) > 0) {
                if ($number[1] === '' && in_array($property, self::$unitValues, true)) {
                    $number[1] = 'px';
                    $this->logger->log("Fixed invalid number: Added 'px' unit to '$part'", Logger::WARNING);
                }
            } else if ($number[1] !== '') {
                $this->logger->log("Optimised number: Removed unit '{$number[1]}' from '{$part}'", Logger::INFORMATION);
                $number[1] = '';
            }

            $part = $number[0] . $number[1];
        }

        return (isset($parts[1]) ? $parts[0] . '/' . $parts[1] : $parts[0]);
    }

    /**
     * Checks if a given string is a CSS valid number. If it is,
     * an array containing the value and unit is returned
     * @param string $string
     * @return array ('unit' if unit is found or '' if no unit exists, number value) or false if no number
     */
    protected function analyse($string)
    {
        // most simple checks first
        if (!isset($string{0}) || $string{0} === '#' || ctype_alpha($string{0})) {
            return false;
        } else if ($string === '0') {
            return array(0, '');
        } else if (!preg_match('~([-]?([0-9]*\.[0-9]+|[0-9]+))(.*)~si', $string, $matches)) {
            return false; // Value is not a number
        }

        list(, $value, , $unit) = $matches;

        if ($value === '') {
            return false;
        }

        $value = $optimisedValue = trim($value);
        $unit = $optimisedUnit = strtolower(trim($unit));

        if ($unit !== '' && !in_array($unit, self::$units)) {
            return false; // Unit is not supported
        }

        if ($this->convertUnit) {
            list($optimisedValue, $optimisedUnit) = $this->unitConvert($value, $unit);
        }

        $optimisedValue = $this->compressNumber($optimisedValue);

        if ($optimisedUnit !== $unit) {
            $this->logger->log("Optimised number: Converted from '{$value}{$unit}' to '{$optimisedValue}{$optimisedUnit}'", Logger::INFORMATION);
        } else if ($optimisedValue != $value) {
            $this->logger->log("Optimised number: Optimised from '{$value}{$unit}' to '{$optimisedValue}{$optimisedUnit}'", Logger::INFORMATION);
        }

        return array($optimisedValue, $optimisedUnit);
    }

    /**
     * Removes 0 from decimal number between -1 - 1
     * Example: 0.3 -> .3; -0.3 -> -.3
     * @param string $string
     * @return string without any non numeric character
     */
    public function compressNumber($string)
    {
        $float = floatval($string);
        if (abs($float) > 0 && abs($float) < 1) {
            if ($float < 0) {
                return '-' . ltrim(substr($float, 1), '0');
            } else {
                return ltrim($float, '0');
            }
        }

        return $float;
    }

    /**
     * Convert unit to greather with shorter value
     *
     * For example 100px is converted to 75pt and 10mm to 1cm
     *
     * @see http://www.w3.org/TR/css3-values/#absolute-lengths
     * @param string $value
     * @param string $unit
     * @return array [value, unit]
     */
    protected function unitConvert($value, $unit)
    {
        $convert = array(
            // Absolute lengths
            'px' => array(0.75, 'pt'),
            'pt' => array(1/12, 'pc'),
            'mm' => array(6/25.4,'pc'),
            'pc' => array(2.54/6, 'cm'),
            'cm' => array(1/2.54, 'in'),

            // Frequency
            'hz' => array(0.001, 'khz'),

            // Angle, radians are ugly
            'grad' => array(0.9, 'deg'),
            //'deg' => array(1/360, 'turn'), // turn unit is not supported by major browser

            // Time
            'ms' => array(0.001, 's'),
        );

        $options = array($unit => $value);
        while (isset($convert[$unit])) {
            list($coefficient , $unit) = $convert[$unit];
            $options[$unit] = $value *= $coefficient;
        }

        // Find smaller string with unit
        $smaller = 0;
        $smallerUnit = '';
        foreach ($options as $unit => $value) {
            $current = strlen($value . $unit);
            if ($current < $smaller || $smaller === 0) {
                $smaller = $current;
                $smallerUnit = $unit;
            }
        }

        return array($options[$smallerUnit], $smallerUnit);
    }

    /**
     * Check is string is valid 3 or 6 character color value without # character
     * @param string $string HEX color value
     * @return bool
     */
    protected function checkHexValue($string)
    {
        $size = strlen($string);

        if ($size !== 3 && $size !== 6) {
            return false;
        }

        return ctype_xdigit($string);
    }
}