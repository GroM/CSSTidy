<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * CSS Parser class
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
 * @package csstidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2007
 * @author Brett Zamir (brettz9 at yahoo dot com) 2007
 * @author Nikolay Matsievsky (speed at webo dot name) 2009-2010
 * @author Cedric Morin (cedric at yterium dot com) 2010
 * @author Jakub Onderka (acci at acci dot cz) 2011
 */
namespace CSSTidy;

require_once __DIR__ . '/Template.php';
require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/elements/Parsed.php';
require_once __DIR__ . '/Output.php';
require_once __DIR__ . '/Optimise.php';

/**
 * CSS Parser class
 *

 * This class represents a CSS parser which reads CSS code and saves it in an array.
 * In opposite to most other CSS parsers, it does not use regular expressions and
 * thus has full CSS2 support and a higher reliability.
 * Additional to that it applies some optimisations and fixes to the CSS code.
 * An online version should be available here: http://cdburnerxp.se/cssparse/css_optimiser.php
 * @package csstidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2006
 * @version 1.3.1
 */
class CSSTidy
{
    const AT_START = 1,
        AT_END = 2,
        SEL_START = 3,
        SEL_END = 4,
        PROPERTY = 5,
        VALUE = 6,
        COMMENT = 7,
        LINE_AT = 8;
    /**
     * All whitespace allowed in CSS without '\r', because is changed to '\n' before parsing
     * @static
     * @var string
     */
    public static $whitespace = " \n\t\x0B\x0C";

    /**
     * Array is generated from self::$whitespace
     * @static
     * @var array
     */
    public static $whitespaceArray = array();

    /**
     * All CSS tokens used by csstidy
     *
     * @var string
     * @static
     * @version 1.0
     */
    public static $tokensList = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';

    /**
     * All properties, value contains comma separated CSS supported versions
     *
     * @static
     * @var array
     */
    public static $allProperties = array(
        'background' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'background-color' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'background-image' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'background-repeat' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'background-attachment' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'background-position' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-top' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-right' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-bottom' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-left' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-color' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-top-color' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-bottom-color' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-left-color' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-right-color' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-style' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-top-style' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-right-style' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-left-style' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-bottom-style' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-top-width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-right-width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-left-width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-bottom-width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'border-collapse' => 'CSS2.0,CSS2.1,CSS3.0',
        'border-spacing' => 'CSS2.0,CSS2.1,CSS3.0',
        'bottom' => 'CSS2.0,CSS2.1,CSS3.0',
        'caption-side' => 'CSS2.0,CSS2.1,CSS3.0',
        'content' => 'CSS2.0,CSS2.1,CSS3.0',
        'clear' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'clip' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'color' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'counter-reset' => 'CSS2.0,CSS2.1,CSS3.0',
        'counter-increment' => 'CSS2.0,CSS2.1,CSS3.0',
        'cursor' => 'CSS2.0,CSS2.1,CSS3.0',
        'empty-cells' => 'CSS2.0,CSS2.1,CSS3.0',
        'display' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'direction' => 'CSS2.0,CSS2.1,CSS3.0',
        'float' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font-family' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font-style' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font-variant' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font-weight' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'font-stretch' => 'CSS2.0,CSS3.0',
        'font-size-adjust' => 'CSS2.0,CSS3.0',
        'font-size' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'height' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'left' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'line-height' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'list-style' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'list-style-type' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'list-style-image' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'list-style-position' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'margin' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'margin-top' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'margin-right' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'margin-bottom' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'margin-left' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'marks' => 'CSS1.0,CSS2.0,CSS3.0',
        'marker-offset' => 'CSS2.0,CSS3.0',
        'max-height' => 'CSS2.0,CSS2.1,CSS3.0',
        'max-width' => 'CSS2.0,CSS2.1,CSS3.0',
        'min-height' => 'CSS2.0,CSS2.1,CSS3.0',
        'min-width' => 'CSS2.0,CSS2.1,CSS3.0',
        'overflow' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'orphans' => 'CSS2.0,CSS2.1,CSS3.0',
        'outline' => 'CSS2.0,CSS2.1,CSS3.0',
        'outline-width' => 'CSS2.0,CSS2.1,CSS3.0',
        'outline-style' => 'CSS2.0,CSS2.1,CSS3.0',
        'outline-color' => 'CSS2.0,CSS2.1,CSS3.0',
        'padding' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'padding-top' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'padding-right' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'padding-bottom' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'padding-left' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'page-break-before' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'page-break-after' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'page-break-inside' => 'CSS2.0,CSS2.1,CSS3.0',
        'page' => 'CSS2.0,CSS3.0',
        'position' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'quotes' => 'CSS2.0,CSS2.1,CSS3.0',
        'right' => 'CSS2.0,CSS2.1,CSS3.0',
        'size' => 'CSS1.0,CSS2.0,CSS3.0',
        'speak-header' => 'CSS2.0,CSS2.1,CSS3.0',
        'table-layout' => 'CSS2.0,CSS2.1,CSS3.0',
        'top' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'text-indent' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'text-align' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'text-decoration' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'text-shadow' => 'CSS2.0,CSS3.0',
        'letter-spacing' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'word-spacing' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'text-transform' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'white-space' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'unicode-bidi' => 'CSS2.0,CSS2.1,CSS3.0',
        'vertical-align' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'visibility' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'width' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'widows' => 'CSS2.0,CSS2.1,CSS3.0',
        'z-index' => 'CSS1.0,CSS2.0,CSS2.1,CSS3.0',
        'volume' => 'CSS2.0,CSS2.1,CSS3.0',
        'speak' => 'CSS2.0,CSS2.1,CSS3.0',
        'pause' => 'CSS2.0,CSS2.1,CSS3.0',
        'pause-before' => 'CSS2.0,CSS2.1,CSS3.0',
        'pause-after' => 'CSS2.0,CSS2.1,CSS3.0',
        'cue' => 'CSS2.0,CSS2.1,CSS3.0',
        'cue-before' => 'CSS2.0,CSS2.1,CSS3.0',
        'cue-after' => 'CSS2.0,CSS2.1,CSS3.0',
        'play-during' => 'CSS2.0,CSS2.1,CSS3.0',
        'azimuth' => 'CSS2.0,CSS2.1,CSS3.0',
        'elevation' => 'CSS2.0,CSS2.1,CSS3.0',
        'speech-rate' => 'CSS2.0,CSS2.1,CSS3.0',
        'voice-family' => 'CSS2.0,CSS2.1,CSS3.0',
        'pitch' => 'CSS2.0,CSS2.1,CSS3.0',
        'pitch-range' => 'CSS2.0,CSS2.1,CSS3.0',
        'stress' => 'CSS2.0,CSS2.1,CSS3.0',
        'richness' => 'CSS2.0,CSS2.1,CSS3.0',
        'speak-punctuation' => 'CSS2.0,CSS2.1,CSS3.0',
        'speak-numeral' => 'CSS2.0,CSS2.1,CSS3.0',

        // CSS3 properties
        // Animation module
        'animation-timing-function' => 'CSS3.0',
        'animation-name' => 'CSS3.0',
        'animation-duration' => 'CSS3.0',
        'animation-iteration-count' => 'CSS3.0',
        'animation-direction' => 'CSS3.0',
        'animation-play-state' => 'CSS3.0',
        'animation-delay' => 'CSS3.0',
        'animation' => 'CSS3.0',
        // Backgrounds
        'background-size' => 'CSS3.0',
        'background-origin' => 'CSS3.0',
        'border-radius' => 'CSS3.0',
        'border-top-right-radius' => 'CSS3.0',
        'border-bottom-right-radius' => 'CSS3.0',
        'border-bottom-left-radius' => 'CSS3.0',
        'border-top-left-radius' => 'CSS3.0',
        'border-image' => 'CSS3.0',
        'border-top-left-radius' => 'CSS3.0',
        'border-top-right-radius' => 'CSS3.0',
        'border-bottom-right-radius' => 'CSS3.0',
        'border-bottom-left-radius' => 'CSS3.0',
        'box-shadow' => 'CSS3.0',
        // Font module
        'src' => 'CSS3.0', // inside @font-face
        'font-variant-east-asian' => 'CSS3.0',
        'font-variant-numeric' => 'CSS3.0',
        'font-variant-ligatures' => 'CSS3.0',
        'font-feature-settings' => 'CSS3.0',
        'font-language-override' => 'CSS3.0',
        'font-kerning' => 'CSS3.0',
        // Color Module
        'opacity' => 'CSS3.0',
        // Box module
        'overflow-x' => 'CSS3.0',
        'overflow-y' => 'CSS3.0',
        // UI module
        'pointer-events' => 'CSS3.0',
        'user-select' => 'CSS3.0',
        // Images
        'image-rendering' => 'CSS3.0',
        'image-resolution' => 'CSS3.0',
        'image-orientation' => 'CSS3.0',
        // Transform
        'transform' => 'CSS3.0',
        'transform-origin' => 'CSS3.0',
        'transform-style' => 'CSS3.0',
        'perspective' => 'CSS3.0',
        'perspective-origin' => 'CSS3.0',
        'backface-visibility' => 'CSS3.0',
        // Transition
        'transition' => 'CSS3.0',
        'transition-delay' => 'CSS3.0',
        'transition-duration' => 'CSS3.0',
        'transition-property' => 'CSS3.0',
        'transition-timing-function' => 'CSS3.0',
        // Speech
        'voice-pitch' => 'CSS3.0',
    );

    /** @var \CSSTidy\Optimise */
    private $optimise;

    /** @var \CSSTidy\Logger */
    public $logger;

    /** @var \CSSTidy\Configuration */
    public $configuration;

    /** @var string */
    private static $version = '1.4';

    /**
     * Saves the position of , in selectors
     * @var array
     */
    private $selectorSeparate = array();


    /**
     * @param Configuration|null $configuration
     */
    public function __construct(Configuration $configuration = null)
    {
        $this->configuration = $configuration ?: new Configuration();
        $this->logger = new Logger;

        // Prepare array of all CSS whitespaces
        self::$whitespaceArray = str_split(self::$whitespace);
    }

    /**
     * @param string $string
     * @return Output
     * @throws \Exception
     */
    public function parse($string)
    {
        $original = $string;
        // Temporarily set locale to en_US in order to handle floats properly
        $old = @setlocale(LC_ALL, 0);
        @setlocale(LC_ALL, 'C');

        $this->optimise = new Optimise($this->logger, $this->configuration);
        $parsed = new Parsed($this->configuration, $string);

        // Normalize new line characters
        $string = str_replace(array("\r\n", "\r"), array("\n", "\n"), $string) . ' ';

        // Initialize variables
        $currentComment = $currentString = $stringChar = $subValue = $value = $property = $selector = '';
        $quotedString = false;
        $bracketCount = 0;

        /*
         * Possible values:
         * - is = in selector
         * - ip = in property
         * - iv = in value
         * - instr = in string (started at " or ')
         * - inbrck = in bracket (started by ()
         * - ic = in comment (ignore everything)
         * - at = in @-block
         */
        $status = 'is';
        $subValues = $from = array();

        $stack = array($parsed);

        for ($i = 0, $size = strlen($string); $i < $size; $i++) {
            $current = $string{$i};

            if ($current === "\n") {
                $this->logger->incrementLine();
            }

            switch ($status) {
                /* Case in-selector */
                case 'is':
                    if ($this->isToken($string, $i)) {
                        if ($current === '{') {
                            $status = 'ip';
                            $from[] = 'is';
                            $selector = trim($selector);
                            $stack[] = end($stack)->addBlock(new Selector($selector));
                            $parsed->addToken(self::SEL_START, $selector);
                        } else if ($current === ',') {
                            $selector = trim($selector) . ',';
                            $this->selectorSeparate[] = strlen($selector);
                        } else if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            ++$i;
                            $status = 'ic';
                            $from[] = 'is';
                        } else if ($current === '@' && trim($selector) == '') {
                            $status = 'at';
                        } else if ($current === '"' || $current === "'") {
                            $currentString = $stringChar = $current;
                            $status = 'instr';
                            $from[] = 'is';
                            /* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
                            $quotedString = ($string{$i - 1} === '=');
                        } else if ($current === '}') {
                            if (array_pop($stack) instanceof AtBlock) {
                                $parsed->addToken(self::AT_END);
                            } else {
                                $parsed->addToken(self::SEL_END);
                            }
                            $selector = '';
                            $this->selectorSeparate = array();
                        } else if ($current === '\\') {
                            $selector .= $this->unicode($string, $i);
                        } else if ($current === '*' && isset($string{$i + 1}) && in_array($string{$i + 1}, array('.', '#', '[', ':'))) {
                            // remove unnecessary universal selector, FS#147
                        } else {
                            $selector .= $current;
                        }
                    } else {
                        $last = strcspn($string, self::$tokensList . self::$whitespace, $i);
                        if ($last !== 0) {
                            $selector .= substr($string, $i, $last);
                            $i += $last - 1;
                        } else if (
                            !isset($selector{0}) ||
                            !(($last = substr($selector, -1)) === ',' || ctype_space($last))
                        ) {
                            $selector .= $current;
                        }
                    }
                    break;

                /* Case in-property */
                case 'ip':
                    if ($this->isToken($string, $i)) {
                        if (($current === ':' || $current === '=') && isset($property{0})) {
                            $status = 'iv';
                            $from[] = 'ip';
                            if (!$this->configuration->getDiscardInvalidProperties() || $this->propertyIsValid($property)) {
                                $parsed->addToken(self::PROPERTY, trim($property));
                            }
                        } else if ($current === '}') {
                            $this->explodeSelectors($selector, $stack);
                            $status = array_pop($from);
                            if (array_pop($stack) instanceof AtBlock) {
                                $parsed->addToken(self::AT_END);
                            } else {
                                $parsed->addToken(self::SEL_END);
                            }
                            $selector = $property = '';
                        } else if ($current === '@') {
                            $status = 'at';
                        } else if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            ++$i;
                            $status = 'ic';
                            $from[] = 'ip';
                        } else if ($current === ';') {
                            $property = '';
                        } else if ($current === '\\') {
                            $property .= $this->unicode($string, $i);
                        } else if ($property == '' && !ctype_space($current)) {
                            $property .= $current;
                        }
                    } else {
                        $last = strcspn($string, self::$tokensList . self::$whitespace, $i);
                        if ($last !== 0) {
                            $property .= substr($string, $i, $last);
                            $i += $last - 1;
                        } else if (!ctype_space($current)) {
                            $property .= $current;
                        }
                    }
                    break;

                /* Case in-value */
                case 'iv':
                    $pn = ($current === "\n" && $this->propertyIsNext($string, $i + 1) || $i === $size - 1);
                    if ($this->isToken($string, $i) || $pn) {
                        if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            ++$i;
                            $status = 'ic';
                            $from[] = 'iv';
                        } else if ($current === '"' || $current === "'") {
                            $currentString = $stringChar = $current;
                            $status = 'instr';
                            $from[] = 'iv';
                        } else if ($current === '(') {
                            $subValue .= $current;
                            $bracketCount = 1;
                            $status = 'inbrck';
                            $from[] = 'iv';
                        } else if ($current === ',') {
                            if (($trimmed = trim($subValue, self::$whitespace)) !== '') {
                                $subValues[] = $trimmed;
                                $subValues[] = ',';
                                $subValue = '';
                            }
                        } else if ($current === '\\') {
                            $subValue .= $this->unicode($string, $i);
                        } else if ($current === ';' || $pn) {
                            $status = array_pop($from);
                        } else if ($current !== '}') {
                            $subValue .= $current;
                        }

                        if (($current === '}' || $current === ';' || $pn) && !empty($selector)) {
                            $property = strtolower($property);

                            if (($trimmed = trim($subValue, self::$whitespace)) !== '') {
                                $subValues[] = $trimmed;
                                $subValue = '';
                            }

                            $value = $this->mergeSubValues($property, $subValues);
                            $value = $this->optimise->value($property, $value);

                            $valid = $this->propertyIsValid(rtrim($property)); // Remove right spaces added by Parsed::newProperty
                            if (!$this->configuration->getDiscardInvalidProperties() || $valid) {
                                end($stack)->addProperty($property, $value);
                                $parsed->addToken(self::VALUE, $value);
                            }
                            if (!$valid) {
                                if ($this->configuration->getDiscardInvalidProperties()) {
                                    $this->logger->log("Removed invalid property: $property", Logger::WARNING);
                                } else {
                                    $this->logger->log(
                                        "Invalid property in {$this->configuration->getCssLevel()}: $property",
                                        Logger::WARNING
                                    );
                                }
                            }

                            $property = $value = '';
                            $subValues = array();
                        }
                        if ($current === '}') {
                            $this->explodeSelectors($selector, $stack);
                            $parsed->addToken(self::SEL_END);
                            array_pop($stack);

                            array_pop($from);
                            $status = array_pop($from);
                            $selector = '';
                        }
                    } else if (!$pn) {
                        $last = strcspn($string, self::$tokensList . self::$whitespace, $i);
                        if ($last !== 0) {
                            $subValue .= substr($string, $i, $last);
                            $i += $last - 1;
                        } else if (ctype_space($current)) {
                            if (($trimmed = trim($subValue, self::$whitespace)) !== '') {
                                $subValues[] = $trimmed;
                                $subValue = '';
                            }
                        } else {
                            $subValue .= $current;
                        }
                    }
                    break;

                /* Case data in bracket */
                case 'inbrck':
                    if (strpos("\"'() ,\n" . self::$whitespace, $current) !== false && !self::escaped($string, $i)) {
                        if (($current === '"' || $current === '\'') && !self::escaped($string, $i)) {
                            $status = 'instr';
                            $from[] = 'inbrck';
                            $currentString = $stringChar = $current;
                            continue;
                        } else if ($current === '(') {
                            ++$bracketCount;
                        } else if ($current === ')' && --$bracketCount === 0) {
                            $status = array_pop($from); // Go back to prev parser
                        } else if ($current === "\n") {
                            $current = ' '; // Change new line character to normal space
                        }

                        if (
                            strpos(self::$whitespace, $current) !== false &&
                            in_array(substr($subValue, -1), array(' ', ',', '('))
                        ) {
                            continue; // Remove multiple spaces and space after token
                        } else if (($current === ',' || $current === ')') && substr($subValue, -1) === ' ') {
                            $subValue = substr($subValue, 0, -1); // Remove space before ',' or ')'
                        }
                    }

                    $subValue .= $current;
                    break;

                /* Case in string */
                case 'instr':
                    // ...and no not-escaped backslash at the previous position
                    if ($current === "\n" && !($string{$i - 1} === '\\' && !self::escaped($string, $i - 1))) {
                        $current = "\\A ";
                        $this->logger->log('Fixed incorrect newline in string', Logger::WARNING);
                    }

                    $currentString .= $current;

                    if ($current === $stringChar && !self::escaped($string, $i)) {
                        $status = array_pop($from);
                        if ($property !== 'content' && $property !== 'quotes' && !$quotedString) {
                            $currentString = self::removeQuotes($currentString, $status === 'ibrck');
                        } else {
                            $currentString = self::normalizeQuotes($currentString);
                        }
                        if ($status === 'is') {
                            $selector .= $currentString;
                        } else {
                            $subValue .= $currentString;
                        }
                        $quotedString = false;
                    }
                    break;

                /* Case in-comment */
                case 'ic':
                    if ($current === '*' && $string{$i + 1} === '/') {
                        $status = array_pop($from);
                        $i++;
                        $parsed->addToken(self::COMMENT, $currentComment);
                        $currentComment = '';
                    } else {
                        $currentComment .= $current;
                    }
                    break;

                /* Case in at rule */
                case 'at':
                    if ($this->isToken($string, $i)) {
                        if ($current === '"' || $current === '\'') {
                            $status = 'instr';
                            $from[] = 'at';
                            $quotedString = true;
                            $currentString = $stringChar = $current;
                        } else if ($current === '(') {
                            $subValue .= $current;
                            $status = 'inbrck';
                            $bracketCount = 1;
                            $from[] = 'at';
                        } else if ($current === ';') {
                            $subValues[] = $subValue;
                            $this->processAtRule($subValues, $stack);
                            $subValues = array();
                            $subValue = '';
                            $status = 'is';
                        } else if ($current === ',') {
                            $subValues[] = $subValue;
                            $subValues[] = ',';
                            $subValue = '';
                        } else if ($current === '{') {
                            $subValues[] = $subValue;
                            $data = '@' . rtrim($this->mergeSubValues(null, $subValues));

                            $status = $this->nextParserInAtRule($string, $i);
                            if ($status === 'ip') {
                                $selector = $data;
                            } else {
                                $status = 'is';
                            }
                            $from[] = 'is';

                            $parsed->addToken(self::AT_START, $data);
                            $stack[] = end($stack)->addBlock(new AtBlock($data));

                            $subValues = array();
                            $subValue = '';
                        } else if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            $status = 'ic';
                            ++$i;
                            $from[] = 'at';
                        } else if ($current === '\\') {
                            $subValue .= $this->unicode($string, $i);
                        } else {
                            $subValue .= $current;
                        }
                    } else if (ctype_space($current)) {
                        if (trim($subValue) !== '') {
                            $subValues[] = $subValue;
                            $subValue = '';
                        }
                    } else {
                        $subValue .= $current;
                    }
                    break;
            }
        }

        $this->optimise->postparse($parsed);
        @setlocale(LC_ALL, $old); // Set locale back to original setting

        if (!(empty($parsed->properties) && empty($parsed->import) && empty($parsed->charset) && empty($parsed->tokens) && empty($parsed->namespace))) {
            return new Output($this->configuration, $this->logger, $original, $parsed);
        } else {
            throw new \Exception("Invalid CSS");
        }

    }

    /**
     * @param string $string
     * @param string $fileDirectory
     * @return string
     */
    public function mergeImports($string, $fileDirectory = '')
    {
        preg_match_all('~@import[ \t\r\n\f]*(url\()?("[^\n\r\f\\"]+"|\'[^\n\r\f\\"]+\')(\))?([^;]*);~si', $string, $matches);

        $notResolvedImports = array();
        foreach ($matches[2] as $i => $fileName) {
            $importRule = $matches[0][$i];

            if (trim($matches[4][$i]) !== '') {
                $notResolvedImports[] = $importRule;
                continue; // Import is for other media
            }

            $fileName = trim($fileName, " \t\r\n\f'\"");

            $content = file_get_contents($fileDirectory . $fileName);

            $string = str_replace($importRule, $content ? $content : '', $string);

            if (!$content) {
                $notResolvedImports[] = $importRule;
                $this->logger->log("Import file {$fileDirectory}{$fileName} not found", Logger::WARNING);
            }
        }

        return implode("\n", $notResolvedImports) . $string;
    }

    /**
     * Explodes selectors
     * @param string $selector
     * @param array $top
     */
    protected function explodeSelectors($selector, array $top)
    {
        // Explode multiple selectors
        if ($this->configuration->getMergeSelectors() === Configuration::SEPARATE_SELECTORS) {
            $newSelectors = array();
            $lastPosition = 0;
            $this->selectorSeparate[] = strlen($selector);

            $lastSelectorSeparateKey = count($this->selectorSeparate) - 1;
            foreach ($this->selectorSeparate as $num => $pos) {
                if ($num === $lastSelectorSeparateKey) {
                    ++$pos;
                }

                $newSelectors[] = substr($selector, $lastPosition, $pos - $lastPosition - 1);
                $lastPosition = $pos;
            }

            if (count($newSelectors) > 1) {
                $selectorObj = end($top);
                $parentSelectorObj = prev($top);
                foreach ($newSelectors as $newSelector) {
                    $newSelectorObj = clone $selectorObj;
                    $newSelectorObj->name = $newSelector;
                    $parentSelectorObj->addBlock($newSelectorObj);
                }
                $parentSelectorObj->removeBlock($selectorObj);
                end($top);
            }
        }

        $this->selectorSeparate = array();
    }

    /**
     * @param string $property
     * @param array $subValues
     * @return string
     */
    protected function mergeSubValues($property, array $subValues)
    {
        $prev = false;
        $output = '';

        foreach ($subValues as $subValue) {
            if (strncmp($subValue, 'format(', 7) === 0) {
                // format() value must be inside quotes
                $subValue = str_replace(array('format(', ')'), array('format("', '")'), $subValue);
            }

            if ($subValue === ',') {
                $prev = true;
            } else if (!$prev) {
                $subValue = $this->optimise->subValue($property, $subValue);
                $output .= ' ';
            } else {
                $subValue = $this->optimise->subValue($property, $subValue);
                $prev = false;
            }
            $output .= $subValue;
        }

        return ltrim($output, ' ');
    }

    /**
     * Process charset, namespace or import at rule
     * @param array $subValues
     */
    protected function processAtRule(array $subValues, array $stack)
    {
        /** @var Parsed $parsed */
        $parsed = $stack[0];
        $rule = strtolower(array_shift($subValues));

        switch ($rule) {
            case 'charset':
                $parsed->charset = $subValues[0];
                if (!empty($parsed->properties) || !empty($parsed->import) || !empty($parsed->namespace)) {
                    $this->logger->log("@charset must be before anything", Logger::WARNING);
                }
                break;

            case 'namespace':
                if (isset($subValues[1])) {
                    $subValues[0] = ' ' . $subValues[0];
                }

                $parsed->namespace[] = implode(' ', $subValues);
                if (!empty($parsed->properties)) {
                    $this->logger->log("@namespace must be before selectors", Logger::WARNING);
                }
                break;

            case 'import':
                $parsed->import[] = $this->mergeSubValues(null, $subValues);
                if (!empty($parsed->properties)) {
                    $this->logger->log("@import must be before anything selectors", Logger::WARNING);
                } else if (isset($stack[1])) {
                    $this->logger->log("@import cannot be inside @media", Logger::WARNING);
                }
                break;

            default:
                $data = $this->mergeSubValues(null, $subValues);
                $lineAt = new LineAt($rule, $data);
                $parsed->addToken(self::LINE_AT, $lineAt->__toString());
                end($stack)->addLineAt($lineAt);
                break;
        }
    }

    /**
     * @param string $string
     * @param int $i
     * @return string Parser section name
    */
    protected function nextParserInAtRule($string, $i)
    {
        ++$i;
        $nextColon = strpos($string, ':', $i);

        if ($nextColon === false) {
            return 'is';
        }

        $nextCurlyBracket = strpos($string, '{', $i);

        if ($nextCurlyBracket === false) {
            return 'ip';
        }

        while (self::escaped($string, $nextColon)) {
            $nextColon = strpos($string, ':', $nextColon);
        }

        while (self::escaped($string, $nextCurlyBracket)) {
            $nextCurlyBracket = strpos($string, '{', $i);
        }

        return $nextColon > $nextCurlyBracket ? 'is' : 'ip';
    }

    /**
     * Parse unicode notations and find a replacement character
     * @param string $string
     * @param integer $i
     * @return string
     */
    protected function unicode($string, &$i)
    {
        ++$i;
        $add = '';
        $replaced = false;

        while (isset($string{$i}) && (ctype_xdigit($string{$i}) || ctype_space($string{$i})) && !isset($add{6})) {
            $add .= $string{$i};

            if (ctype_space($string{$i})) {
                break;
            }
            $i++;
        }

        $hexDecAdd = hexdec($add);
        if ($hexDecAdd > 47 && $hexDecAdd < 58 || $hexDecAdd > 64 && $hexDecAdd < 91 || $hexDecAdd > 96 && $hexDecAdd < 123) {
            $this->logger->log('Replaced unicode notation: Changed \\' . $add . ' to ' . chr($hexDecAdd), Logger::INFORMATION);
            $add = chr($hexDecAdd);
            $replaced = true;
        } else {
            $add = $add === ' ' ? '\\' . $add : trim('\\' . $add);
        }

        if (isset($string{$i + 1}) && ctype_xdigit($string{$i + 1}) && ctype_space($string{$i})
                        && !$replaced || !ctype_space($string{$i})) {
            $i--;
        }

        if ($add !== '\\' || !$this->configuration->getRemoveBackSlash() || strpos(self::$tokensList, $string{$i + 1}) !== false) {
            return $add;
        }

        if ($add === '\\') {
            $this->logger->log('Removed unnecessary backslash', Logger::INFORMATION);
        }
        return '';
    }

    /**
     * Checks if the next word in a string from pos is a CSS property
     * @param string $string
     * @param integer $pos
     * @return bool
     * @access private
     * @version 1.2
     */
    protected function propertyIsNext($string, $pos)
    {
        $string = substr($string, $pos);
        $string = strstr($string, ':', true);

        if ($string === false) {
            return false;
        }

        $string = strtolower(trim($string));

        if (isset(self::$allProperties[$string])) {
            $this->logger->log('Added semicolon to the end of declaration', Logger::WARNING);
            return true;
        }

        return false;
    }

    /**
     * Checks if there is a token at the current position
     * @param string $string
     * @param integer $i
     * @return bool
     */
    protected function isToken($string, $i)
    {
        return (strpos(self::$tokensList, $string{$i}) !== false && !self::escaped($string, $i));
    }

    /**
     * Checks if a property is valid
     * @param string $property
     * @return bool;
     * @access public
     * @version 1.0
     */
    protected function propertyIsValid($property)
    {
        return (isset(self::$allProperties[$property]) &&
            strpos(self::$allProperties[$property], $this->configuration->getCssLevel()) !== false);
    }

    /**
     * Checks if a character is escaped (and returns true if it is)
     * @param string $string
     * @param integer $pos
     * @return bool
     */
    public static function escaped($string, $pos)
    {
        return !((!isset($string{$pos - 1}) || $string{$pos - 1} !== '\\') || self::escaped($string, $pos - 1));
    }

    /**
     * Checks if $value is !important.
     * @param string $value
     * @return bool
     */
    public static function isImportant($value)
    {
        return isset($value{9}) && substr_compare(str_replace(self::$whitespaceArray, '', $value), '!important', -10, 10, true) === 0;
    }

    /**
     * Returns a value without !important
     * @param string $value
     * @param bool $check Check if important in value exists
     * @return string
     */
    public static function removeImportant($value, $check = true)
    {
        if (!$check || self::isImportant($value)) {
            $value = trim($value);
            $value = substr($value, 0, -9);
            $value = rtrim($value);
            $value = substr($value, 0, -1);
            $value = rtrim($value);
            return $value;
        }

        return $value;
    }

    /**
     * @todo Check all possible bugs
     * @param string $string
     * @param bool $bracketInsideStringAllowed
     * @return mixed
     */
    public static function removeQuotes($string, $bracketInsideStringAllowed = true)
    {
        if (preg_match('|[' . self::$whitespace . ']|uis', $string)) { // If string contains whitespace
            return self::normalizeQuotes($string);
        }

        if (!$bracketInsideStringAllowed && (strpos($string, '(') !== false || strpos($string, ')') !== false)) {
            return $string;
        }

        return substr($string, 1, -1);
    }

    /**
     * Convert all possible single quote to double quote
     * @param string $string
     * @return string
     */
    public static function normalizeQuotes($string)
    {
        if (strpos($string, '"') === false) {
            return '"' . substr($string, 1, -1) . '"';
        }

        return $string;
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }
}