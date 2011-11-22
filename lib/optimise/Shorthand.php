<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * Shorthand optimisation class
 *
 * Copyright 2005, 2006, 2007 Florian Schmitz
 *
 * This file is part of CSSTidy.
 *
 *   CSSTidy is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or
 *   (at your option) any later version.
 *
 *   CSSTidy is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package CSSTidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2007
 * @author Brett Zamir (brettz9 at yahoo dot com) 2007
 * @author Nikolay Matsievsky (speed at webo dot name) 2009-2010
 * @author Cedric Morin (cedric at yterium dot com) 2010
 * @author Jakub Onderka (acci at acci dot cz) 2011
 */
namespace CSSTidy\Optimise;

use CSSTidy\CSSTidy as Parser;
use CSSTidy\Logger;
use CSSTidy\Configuration;
use CSSTidy\Block;
use CSSTidy\AtBlock;

class Shorthand
{
    /**
     * A list of all shorthand properties that are devided into four properties and/or have four subvalues
     *
     * @todo Are there new ones in CSS3?
     * @see dissolveFourValueShorthands()
     * @see mergeFourValueShorthands()
     * @var array
     */
    public static $shorthands = array(
        'border-color' => array('border-top-color','border-right-color','border-bottom-color','border-left-color'),
        'border-style' => array('border-top-style','border-right-style','border-bottom-style','border-left-style'),
        'border-width' => array('border-top-width','border-right-width','border-bottom-width','border-left-width'),
        'margin' => array('margin-top','margin-right','margin-bottom','margin-left'),
        'padding' => array('padding-top','padding-right','padding-bottom','padding-left'),
        'border-radius' => array('border-radius-top-left', 'border-radius-top-right', 'border-radius-bottom-right', 'border-radius-bottom-left')
    );

    /**
     * @static
     * @var array
     */
    public static $twoValuesShorthand = array(
        'overflow' => array('overflow-x', 'overflow-y'),
        'pause' => array('pause-before', 'pause-after'),
        'rest' => array('rest-before', 'rest-after'),
        'cue' => array('cue-before', 'cue-after'),
    );

    /**
     * Default values for the background properties
     *
     * @todo Possibly property names will change during CSS3 development
     * @see dissolveShortBackground()
     * @see merge_bg()
     * @var array
     */
    public static $backgroundPropDefault = array(
        'background-image' => 'none',
        'background-size' => 'auto',
        'background-repeat' => 'repeat',
        'background-position' => '0 0',
        'background-attachment' => 'scroll',
        'background-clip' => 'border',
        'background-origin' => 'padding',
        'background-color' => 'transparent'
    );

    /**
     * Default values for the font properties
     *
     * @see mergeFonts()
     * @var array
     */
    public static $fontPropDefault = array(
        'font-style' => 'normal',
        'font-variant' => 'normal',
        'font-weight' => 'normal',
        'font-size' => '',
        'line-height' => '',
        'font-family' => '',
    );

    /** @var int */
    protected $optimiseShorthand;

    /**
     * @param int $optimiseShorthand
     */
    public function __construct($optimiseShorthand)
    {
        $this->optimiseShorthand = $optimiseShorthand;
    }

    /**
     * @param Block $block
     */
    public function process(Block $block)
    {
        $this->dissolveShorthands($block);

        $this->mergeFourValueShorthands($block);
        $this->mergeTwoValuesShorthand($block);

        foreach (self::$shorthands as $shorthand => $foo) {
            if (isset($block->properties[$shorthand])) {
                $block->properties[$shorthand] = $shorthand === 'border-radius' ?
                   $this->borderRadiusShorthand($block->properties[$shorthand])  :
                    $this->compressShorthand($block->properties[$shorthand]);
            }
        }

        if ($this->optimiseShorthand >= Configuration::FONT) {
            $this->mergeFont($block);

            if ($this->optimiseShorthand >= Configuration::BACKGROUND) {
                $this->mergeBackground($block);

                if (empty($block->properties)) {
                    unset($block);
                }
            }
        }

        if (isset($block) && $block instanceof AtBlock) {
            foreach ($block->properties as $value) {
                if ($value instanceof Block) {
                    $this->process($value);
                }
            }
        }
    }

    /**
     * @param Block $block
    */
    protected function dissolveShorthands(Block $block)
    {
        if (isset($block->properties['font']) && $this->optimiseShorthand > Configuration::COMMON) {
            $value = $block->properties['font'];
            $block->properties['font'] = '';
            $block->mergeProperties($this->dissolveShortFont($value));
        }

        if (isset($block->properties['background']) && $this->optimiseShorthand > Configuration::FONT) {
            $value = $block->properties['background'];
            $block->properties['background'] = '';
            $block->mergeProperties($this->dissolveShortBackground($value));
        }
    }

    /**
     * Compresses shorthand values. Example: margin:1px 1px 1px 1px -> margin:1px
     * @param string $value
     * @return string
     * @version 1.0
    */
    protected function compressShorthand($value)
    {
        $important = false;
        if (Parser::isImportant($value)) {
            $value = Parser::removeImportant($value, false);
            $important = true;
        }

        $values = Parser::explodeWithoutString(' ', $value);

        return $this->compressShorthandValues($values, $important);
    }


    /**
     * Optimize border-radius property
     *
     * @param string $value
     * @return string
     */
    protected function borderRadiusShorthand($value)
    {
        $important = '';
        if (Parser::isImportant($value)) {
            $value = Parser::removeImportant($value, false);
            $important = '!important';
        }

        $parts = explode('/', $value);

        if (empty($parts)) { // / delimiter in string not found
            return $value;
        }

        if (isset($parts[2])) {
            return $value; // border-radius value can contains only two parts
        }

        foreach ($parts as &$part) {
            $part = $this->compressShorthand(trim($part));
        }

        return implode('/', $parts).$important;
    }

    /**
     * @param array $values
     * @param bool $isImportant
     * @return string
     */
    protected function compressShorthandValues(array $values, $isImportant)
    {
        $important = $isImportant ? '!important' : '';

        switch (count($values)) {
            case 4:
                if ($values[0] == $values[1] && $values[0] == $values[2] && $values[0] == $values[3]) {
                    return $values[0] . $important;
                } else if ($values[1] == $values[3] && $values[0] == $values[2]) {
                    return $values[0] . ' ' . $values[1] . $important;
                } else if ($values[1] == $values[3]) {
                    return $values[0] . ' ' . $values[1] . ' ' . $values[2] . $important;
                }
                break;

            case 3:
                if ($values[0] == $values[1] && $values[0] == $values[2]) {
                    return $values[0] . $important;
                } else if ($values[0] == $values[2]) {
                    return $values[0] . ' ' . $values[1] . $important;
                }
                break;

            case 2:
                if ($values[0] == $values[1]) {
                    return $values[0] . $important;
                }
                break;
        }

        return implode(' ', $values);
    }


    /**
     * Dissolves properties like padding:10px 10px 10px to padding-top:10px;padding-bottom:10px;...
     * @param string $property
     * @param string $value
     * @return array

    protected function dissolveFourValueShorthands($property, $value)
    {
        $shorthands = self::$shorthands[$property];

        $important = '';
        if (Parser::isImportant($value)) {
            $value = Parser::removeImportant($value, false);
            $important = '!important';
        }

        $values = Parser::explodeWithoutString(' ', $value);

        $return = array();
        switch (count($values)) {
            case 4:
                for ($i = 0; $i < 4; $i++) {
                    $return[$shorthands[$i]] = $values[$i] . $important;
                }
                break;

            case 3:
                $return[$shorthands[0]] = $values[0] . $important;
                $return[$shorthands[1]] = $values[1] . $important;
                $return[$shorthands[3]] = $values[1] . $important;
                $return[$shorthands[2]] = $values[2] . $important;
                break;

            case 2:
                for ($i = 0; $i < 4; $i++) {
                    $return[$shorthands[$i]] = $values[$i % 2] . $important;
                }
                break;

            default:
                for ($i = 0; $i < 4; $i++) {
                    $return[$shorthands[$i]] = $values[0] . $important;
                }
                break;
        }

        return $return;
    }*/

    /**
     * Merges Shorthand properties again, the opposite of self::dissolveFourValueShorthands
     * @param Block $block
     */
    protected function mergeFourValueShorthands(Block $block)
    {
        foreach (self::$shorthands as $shorthand => $properties) {
            if (
                isset($block->properties[$properties[0]]) &&
                isset($block->properties[$properties[1]]) &&
                isset($block->properties[$properties[2]]) &&
                isset($block->properties[$properties[3]])
            ) {
                $important = false;
                $values = array();
                foreach ($properties as $property) {
                    $val = $block->properties[$property];
                    if (Parser::isImportant($val)) {
                        $important = true;
                        $values[] = Parser::removeImportant($val, false);
                    } else {
                        $values[] = $val;
                    }
                    unset($block->properties[$property]);
                }

                $block->properties[$shorthand] = $this->compressShorthandValues($values, $important);
            }
        }
    }

    /**
     * Merge two values shorthand
     * Shorthand for merging are defined in self::$twoValuesShorthand
     * Example: overflow-x and overflow-y are merged to overflow shorthand
     * @param Block $block
     * @see self::$twoValuesShorthand
     */
    protected function mergeTwoValuesShorthand(Block $block)
    {
        foreach (self::$twoValuesShorthand as $shorthandProperty => $properties) {
            if (
                isset($block->properties[$properties[0]]) &&
                isset($block->properties[$properties[1]])
            ) {
                $first = $block->properties[$properties[0]];
                $second = $block->properties[$properties[1]];

                if (Parser::isImportant($first) !== Parser::isImportant($second)) {
                    continue;
                }

                $important = Parser::isImportant($first) ? '!important' : '';

                if ($important) {
                    $first = Parser::removeImportant($first, false);
                    $second = Parser::removeImportant($second, false);
                }

                if ($first == $second) {
                    $output = $first . $important;
                } else {
                    $output = "$first $second$important";
                }

                $block->properties[$shorthandProperty] = $output;
                unset($block->properties[$properties[0]], $block->properties[$properties[1]]);
            }
        }
    }


    /**
     * Dissolve background property
     * @param string $str_value
     * @return array
     * @todo full CSS 3 compliance
    */
    protected function dissolveShortBackground($str_value)
    {
        // don't try to explose background gradient !
        if (stripos($str_value, "gradient(") !== false) {
            return array('background' => $str_value);
        }

        static $repeat = array('repeat', 'repeat-x', 'repeat-y', 'no-repeat', 'space');
        static $attachment = array('scroll', 'fixed', 'local');
        static $clip = array('border', 'padding');
        static $origin = array('border', 'padding', 'content');
        static $pos = array('top', 'center', 'bottom', 'left', 'right');

        $return = array(
            'background-image' => null,
            'background-size' => null,
            'background-repeat' => null,
            'background-position' => null,
            'background-attachment' => null,
            'background-clip' => null,
            'background-origin' => null,
            'background-color' => null
        );

        $important = '';
        if (Parser::isImportant($str_value)) {
            $important = ' !important';
            $str_value = Parser::removeImportant($str_value, false);
        }

        $str_value = Parser::explodeWithoutString(',', $str_value);
        foreach ($str_value as $strVal) {
            $have = array(
                'clip' => false,
                'pos' => false,
                'color' => false,
                'bg' => false,
            );

            if (is_array($strVal)) {
                $strVal = $strVal[0];
            }

            $strVal = Parser::explodeWithoutString(' ', trim($strVal));

            foreach ($strVal as $current) {
                if ($have['bg'] === false && (substr($current, 0, 4) === 'url(' || $current === 'none')) {
                    $return['background-image'] .= $current . ',';
                    $have['bg'] = true;
                } else if (in_array($current, $repeat, true)) {
                    $return['background-repeat'] .= $current . ',';
                } else if (in_array($current, $attachment, true)) {
                    $return['background-attachment'] .= $current . ',';
                } else if (in_array($current, $clip, true) && !$have['clip']) {
                    $return['background-clip'] .= $current . ',';
                    $have['clip'] = true;
                } else if (in_array($current, $origin, true)) {
                    $return['background-origin'] .= $current . ',';
                } else if ($current{0} === '(') {
                    $return['background-size'] .= substr($current, 1, -1) . ',';
                } else if (in_array($current, $pos, true) || is_numeric($current{0}) || $current{0} === null || $current{0} === '-' || $current{0} === '.') {
                    $return['background-position'] .= $current . ($have['pos'] ? ',' : ' ');
                    $have['pos'] = true;
                } else if (!$have['color']) {
                    $return['background-color'] .= $current . ',';
                    $have['color'] = true;
                }
            }
        }

        foreach (self::$backgroundPropDefault as $backgroundProperty => $defaultValue) {
            if ($return[$backgroundProperty] !== null) {
                $return[$backgroundProperty] = substr($return[$backgroundProperty], 0, -1) . $important;
            } else {
                $return[$backgroundProperty] = $defaultValue . $important;
            }
        }

        return $return;
    }

    /**
     * Merges all background properties
     * @param Block $block
     * @todo full CSS 3 compliance
     */
    protected function mergeBackground(Block $block)
    {
        $properties = $block->properties;

        // if background properties is here and not empty, don't try anything
        if (isset($properties['background']) && $properties['background']) {
            return;
        }

        // Array with background images to check if BG image exists
        $explodedImage = isset($properties['background-image']) ?
            Parser::explodeWithoutString(',', Parser::removeImportant($block->properties['background-image'])) : array();

        $colorCount = isset($properties['background-color']) ?
            count(Parser::explodeWithoutString(',', $block->properties['background-color'])) : 0;

        // Max number of background images. CSS3 not yet fully implemented
        $numberOfValues = max(count($explodedImage), $colorCount, 1);

        $newBackgroundValue = '';
        $important = '';

        for ($i = 0; $i < $numberOfValues; $i++) {
            foreach (self::$backgroundPropDefault as $property => $defaultValue) {
                // Skip if property does not exist
                if (!isset($block->properties[$property])) {
                    continue;
                }

                $currentValue = $block->properties[$property];
                // skip all optimisation if gradient() somewhere
                if (stripos($currentValue, "gradient(") !== false) {
                    return;
                }

                // Skip some properties if there is no background image
                if ((!isset($explodedImage[$i]) || $explodedImage[$i] === 'none')
                                && ($property === 'background-size' || $property === 'background-position'
                                || $property === 'background-attachment' || $property === 'background-repeat')) {
                    continue;
                }

                // Remove !important
                if (Parser::isImportant($currentValue)) {
                    $important = ' !important';
                    $currentValue = Parser::removeImportant($currentValue, false);
                }

                // Do not add default values
                if ($currentValue === $defaultValue) {
                    continue;
                }

                $temp = Parser::explodeWithoutString(',', $currentValue);

                if (isset($temp[$i])) {
                    if ($property === 'background-size') {
                        $newBackgroundValue .= '(' . $temp[$i] . ') ';
                    } else {
                        $newBackgroundValue .= $temp[$i] . ' ';
                    }
                }
            }

            $newBackgroundValue = trim($newBackgroundValue);
            if ($i != $numberOfValues - 1) {
                $newBackgroundValue .= ',';
            }
        }

        // Delete all background-properties
        foreach (self::$backgroundPropDefault as $property => $foo) {
            unset($block->properties[$property]);
        }

        // Add new background property
        if ($newBackgroundValue !== '') {
            $block->properties['background'] = $newBackgroundValue . $important;
        } else if (isset($block->properties['background'])) {
            $block->properties['background'] = 'none';
        }
    }

    /**
     * Dissolve font property
     * @param string $value
     * @return array
    */
    protected function dissolveShortFont($value)
    {
        static $fontWeight = array('normal', 'bold', 'bolder', 'lighter', 100, 200, 300, 400, 500, 600, 700, 800, 900);
        static $fontVariant = array('normal', 'small-caps');
        static $fontStyle = array('normal', 'italic', 'oblique');

        $important = '';
        if (Parser::isImportant($value)) {
            $important = '!important';
            $value = Parser::removeImportant($value, false);
        }

        $return = array(
            'font-style' => null,
            'font-variant' => null,
            'font-weight' => null,
            'font-size' => null,
            'line-height' => null,
            'font-family' => null
        );

        $have = array(
            'style' => false,
            'variant' => false,
            'weight' => false,
            'size' => false,
        );

        // Detects if font-family consists of several words w/o quotes
        $multiwords = false;

        // Workaround with multiple font-family
        $value = Parser::explodeWithoutString(',', trim($value));

        $beforeColon = array_shift($value);
        $beforeColon = Parser::explodeWithoutString(' ', trim($beforeColon));

        foreach ($beforeColon as $propertyValue) {
            if ($have['weight'] === false && in_array($propertyValue, $fontWeight, true)) {
                $return['font-weight'] = $propertyValue;
                $have['weight'] = true;
            } else if ($have['variant'] === false && in_array($propertyValue, $fontVariant)) {
                $return['font-variant'] = $propertyValue;
                $have['variant'] = true;
            } else if ($have['style'] === false && in_array($propertyValue, $fontStyle)) {
                $return['font-style'] = $propertyValue;
                $have['style'] = true;
            } else if ($have['size'] === false && (is_numeric($propertyValue{0}) || $propertyValue{0} === null || $propertyValue{0} === '.')) {
                $size = Parser::explodeWithoutString('/', trim($propertyValue));
                $return['font-size'] = $size[0];
                if (isset($size[1])) {
                    $return['line-height'] = $size[1];
                } else {
                    $return['line-height'] = ''; // don't add 'normal' !
                }
                $have['size'] = true;
            } else {
                if (isset($return['font-family'])) {
                    $return['font-family'] .= ' ' . $propertyValue;
                    $multiwords = true;
                } else {
                    $return['font-family'] = $propertyValue;
                }
            }
        }
        // add quotes if we have several words in font-family
        if ($multiwords !== false) {
            $return['font-family'] = '"' . $return['font-family'] . '"';
        }

        foreach ($value as $fontFamily) {
            $return['font-family'] .= ',' . trim($fontFamily);
        }

        // Fix for 100 and more font-size
        if ($have['size'] === false && isset($return['font-weight']) &&
                        is_numeric($return['font-weight']{0})) {
            $return['font-size'] = $return['font-weight'];
            unset($return['font-weight']);
        }

        foreach (self::$fontPropDefault as $fontProperty => $defaultValue) {
            if (isset($return[$fontProperty])) {
                $return[$fontProperty] = $return[$fontProperty] . $important;
            } else {
                $return[$fontProperty] = $defaultValue . $important;
            }
        }

        return $return;
    }

    /**
     * Merge font properties into font shorthand
     * @todo: refactor
     * @param Element $block
     */
    protected function mergeFont(Block $block)
    {
        $newFontValue = '';
        $important = '';
        $preserveFontVariant = false;

        // Skip if is font-size not set
        if (isset($block->properties['font-size'])) {
            foreach (self::$fontPropDefault as $fontProperty => $defaultValue) {

                // Skip if property does not exist
                if (!isset($block->properties[$fontProperty])) {
                    continue;
                }

                $currentValue = $block->properties[$fontProperty];

                /**
                 * Skip if default value is used or if font-variant property is not small-caps
                 * @see http://www.w3.org/TR/css3-fonts/#propdef-font
                */
                if ($currentValue === $defaultValue) {
                    continue;
                } else if ($fontProperty === 'font-variant' && $currentValue !== 'small-caps') {
                    $preserveFontVariant = true;
                    continue;
                }

                // Remove !important
                if (Parser::isImportant($currentValue)) {
                    $important = '!important';
                    $currentValue = Parser::removeImportant($currentValue, false);
                }

                $newFontValue .= $currentValue;

                if ($fontProperty === 'font-size' &&
                    isset($block->properties['line-height']) &&
                    $block->properties['line-height'] !== ''
                ) {
                    $newFontValue .= '/';
                } else {
                    $newFontValue .= ' ';
                }
            }

            $newFontValue = trim($newFontValue);

            if ($newFontValue !== '') {
                // Delete all font-properties
                foreach (self::$fontPropDefault as $fontProperty => $defaultValue) {
                    if (!($fontProperty === 'font-variant' && $preserveFontVariant) && $fontProperty !== 'font') {
                        unset($block->properties[$fontProperty]);
                    }
                }

                // Add new font property
                $block->properties['font'] = $newFontValue . $important;
            }
        }
    }
}