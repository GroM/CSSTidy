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
require_once __DIR__ . '/Parsed.php';
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
        DEFAULT_AT = 41;

    /**
     * All whitespace allowed in CSS
     *
     * @static
     * @var array
     * @version 1.0
     */
    public static $whitespace = array(' ', "\n", "\t", "\x0B");

    /**
     * All CSS tokens used by csstidy
     *
     * @var string
     * @static
     * @version 1.0
     */
    public static $tokensList = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';


    /**
     * Available at-rules
     *
     * @static
     * @var array
     * @version 1.0
     */
    public static $atRules = array(
        '@page' => 'is',
        '@font-face' => 'is',
        '@charset' => 'iv',
        '@import' => 'iv',
        '@namespace' => 'iv',
        '@media' => 'at',
        '@keyframes' => 'at',
        '@-moz-keyframes' => 'at', // vendor prefixed
        '@-webkit-keyframes' => 'at',
        //'@font-feature-values ' => 'at', // Not fully supported yet
    );


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
    );

    /** @var \CSSTidy\Optimise */
    private $optimise;

    /** @var \CSSTidy\Logger */
    public $logger;

    /** @var \CSSTidy\Parsed */
    protected $parsed;

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
        $this->parsed = $parsed = new Parsed($this->configuration, $string);

        // Normalize new line characters
        $string = str_replace(array("\r\n", "\r"), array("\n", "\n"), $string) . ' ';

        // Initialize variables
        $preserveCss = $this->configuration->getPreserveCss();
        $currentComment = $currentString = $stringChar = $from = $subValue = $value = $property = $selector = $at = '';
        $quotedString = $invalidAtRule = false;
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
        $subValues = array();

        for ($i = 0, $size = strlen($string); $i < $size; $i++) {
            $current = $string{$i};

            if ($current === "\n") {
                $this->logger->incrementLine();
            }

            switch ($status) {
                /* Case in-selector */
                case 'is':
                    if ($this->isToken($string, $i)) {
                        if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*' && trim($selector) == '') {
                            $status = 'ic';
                            ++$i;
                            $from = 'is';
                        } elseif ($current === '@' && trim($selector) == '') {
                            // Check for at-rule
                            $invalidAtRule = true;
                            foreach (self::$atRules as $name => $type) {
                                if (!substr_compare($string, $name, $i, strlen($name), true)) {
                                    ($type === 'at') ? $at = $name : $selector = $name;
                                    $status = $type;
                                    $i += strlen($name) - 1;
                                    $invalidAtRule = false;
                                }
                            }

                            if ($invalidAtRule) {
                                $selector = '@';
                                $invalidAtName = '';
                                for ($j = $i + 1; $j < $size; ++$j) {
                                    if (!ctype_alpha($string{$j})) {
                                        break;
                                    }
                                    $invalidAtName .= $string{$j};
                                }
                                $this->logger->log("Invalid @-rule: $invalidAtName (removed)", Logger::WARNING);
                            }
                        } elseif ($current === '"' || $current === "'") {
                            $currentString = $current;
                            $status = 'instr';
                            $stringChar = $current;
                            $from = 'is';
                            /* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
                            $quotedString = ($string{$i - 1} === '=');
                        } elseif ($invalidAtRule && $current === ';') {
                            $invalidAtRule = false;
                            $status = 'is';
                        } elseif ($current === '{') {
                            $status = 'ip';
                            if (!$preserveCss) {
                                if ($at === '') {
                                    $at = $parsed->newMediaSection(self::DEFAULT_AT);
                                }
                                $selector = $parsed->newSelector($at, $selector);
                                $parsed->addToken(self::SEL_START, $selector);
                            }
                        } elseif ($current === '}') {
                            if (!$preserveCss) $parsed->addToken(self::AT_END, $at);
                            $at = $selector = '';
                            $selectorSeparate = array();
                        } elseif ($current === ',') {
                            $selector = trim($selector) . ',';
                            $selectorSeparate[] = strlen($selector);
                        } elseif ($current === '\\') {
                            $selector .= $this->unicode($string, $i);
                        } elseif ($current === '*' && isset($string{$i + 1}) && in_array($string{$i + 1}, array('.', '#', '[', ':'))) {
                            // remove unnecessary universal selector, FS#147
                        } else {
                            $selector .= $current;
                        }
                    } else {
                        if (!isset($selector{0})) {
                            $selector .= $current;
                        } else {
                            if (!(ctype_space($current) && (($last = substr($selector, -1)) === ',' || ctype_space($last)))) {
                                $selector .= $current;
                            }
                        }
                    }
                    break;

                /* Case in-property */
                case 'ip':
                    if ($this->isToken($string, $i)) {
                        if (($current === ':' || $current === '=') && $property != '') {
                            $status = 'iv';
                            if (!$preserveCss && (!$this->configuration->getDiscardInvalidProperties() || $this->propertyIsValid($property))) {
                                $property = $parsed->newProperty($at, $selector, $property);
                                $parsed->addToken(self::PROPERTY, $property);
                            }
                        } elseif ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*' && $property == '') {
                            $status = 'ic';
                            ++$i;
                            $from = 'ip';
                        } elseif ($current === '}') {
                            $this->explodeSelectors($selector, $at);
                            $status = 'is';
                            $invalidAtRule = false;
                            if (!$preserveCss) $parsed->addToken(self::SEL_END, $selector);
                            $selector = $property = '';
                        } elseif ($current === ';') {
                            $property = '';
                        } elseif ($current === '\\') {
                            $property .= $this->unicode($string, $i);
                        }
                        // else this is dumb IE a hack, keep it
                        elseif ($property == '' && !ctype_space($current)) {
                            $property .= $current;
                        }
                    }
                    elseif (!ctype_space($current)) {
                        $property .= $current;
                    }
                    break;

                /* Case in-value */
                case 'iv':
                    $pn = ($current === "\n" && $this->propertyIsNext($string, $i + 1) || $i === $size - 1);
                    if ($this->isToken($string, $i) || $pn) {
                        if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            $status = 'ic';
                            ++$i;
                            $from = 'iv';
                        } elseif ($current === '"' || $current === "'") {
                            $currentString = $current;
                            $status = 'instr';
                            $stringChar = $current;
                            $from = 'iv';
                        } elseif ($current === '(') {
                            $subValue .= $current;
                            $bracketCount = 1;
                            $status = 'inbrck';
                        } elseif ($current === ',') {
                            if (trim($subValue) != '') {
                                $subValues[] = trim($subValue);
                                $subValues[] = ',';
                                $subValue = '';
                            }
                        } elseif ($current === '\\') {
                            $subValue .= $this->unicode($string, $i);
                        } elseif ($current === ';' || $pn) {
                            if ($selector{0} === '@' && isset(self::$atRules[$selector]) && self::$atRules[$selector] === 'iv') {
                                $subValues[] = trim($subValue);

                                $status = 'is';

                                if ($selector === '@namespace' && isset($subValues[1])) {
                                    $stringPosition = 1;
                                    $subValues[0] = ' ' . $subValues[0];
                                } else {
                                    $stringPosition = 0;
                                }

                                if (substr_compare($subValues[$stringPosition], 'url(', 0, 4) !== 0) {
                                    $subValues[$stringPosition] = '"' . $subValues[$stringPosition] . '"';
                                }

                                switch ($selector) {
                                    case '@charset':
                                        $parsed->charset = $subValues[0];
                                        if (!empty($parsed->css) || !empty($parsed->import) || !empty($parsed->namespace)) {
                                            $this->logger->log("@charset must be before anything", Logger::WARNING);
                                        }
                                        break;

                                    case '@namespace':
                                        $parsed->namespace[] = implode(' ', $subValues);
                                        if (!empty($parsed->css)) {
                                            $this->logger->log("@namespace must be before selectors", Logger::WARNING);
                                        }
                                        break;

                                    case '@import':
                                        $parsed->import[] = $this->mergeSubValues(null, $subValues);
                                        if (!empty($parsed->css)) {
                                            $this->logger->log("@import must be before anything selectors", Logger::WARNING);
                                        } else if (!empty($at)) {
                                            $this->logger->log("@import cannot be inside @media", Logger::WARNING);
                                        }

                                        break;
                                }

                                $subValues = $selectorSeparate = array();
                                $subValue = $selector = '';
                            } else {
                                $status = 'ip';
                            }
                        } elseif ($current !== '}') {
                            $subValue .= $current;
                        }

                        if (($current === '}' || $current === ';' || $pn) && !empty($selector)) {
                            if ($at == '' && !$preserveCss) {
                                $at = $parsed->newMediaSection(self::DEFAULT_AT);
                            }

                            $property = strtolower($property);

                            if (trim($subValue) != '') {
                                $subValues[] = trim($subValue);
                                $subValue = '';
                            }

                            foreach ($subValues as &$sbv) {
                                if (strncmp($sbv, 'format(', 7) == 0) {
                                    // format() value must be inside quotes
                                    $sbv = str_replace(array('format(', ')'), array('format("', '")'), $sbv);
                                }
                            }

                            $value = $this->mergeSubValues($property, $subValues);
                            $value = $this->optimise->value($property, $value);

                            $valid = $this->propertyIsValid(rtrim($property)); // Remove right spaces added by Parsed::newProperty
                            if ((!$invalidAtRule || $preserveCss) && (!$this->configuration->getDiscardInvalidProperties() || $valid)) {
                                if (!$preserveCss) {
                                    $parsed->addProperty($at, $selector, $property, $value);
                                    $parsed->addToken(self::VALUE, $value);
                                }
                                $this->optimise->shorthands($parsed, $at, $selector, $property, $value);
                            }
                            if (!$valid) {
                                if ($this->configuration->getDiscardInvalidProperties()) {
                                    $this->logger->log("Removed invalid property: $property", Logger::WARNING);
                                } else {
                                    $this->logger->log("Invalid property in {$this->configuration->getCssLevel()}: $property", Logger::WARNING);
                                }
                            }

                            $property = $value = '';
                            $subValues = array();
                        }
                        if ($current === '}') {
                            $this->explodeSelectors($selector, $at);
                            if (!$preserveCss) $parsed->addToken(self::SEL_END, $selector);
                            $status = 'is';
                            $invalidAtRule = false;
                            $selector = '';
                        }
                    } elseif (!$pn) {
                        $subValue .= $current;

                        if (ctype_space($current)) {
                            if (trim($subValue) != '') {
                                $subValues[] = trim($subValue);
                                $subValue = '';
                            }
                        }
                    }
                    break;

                /* Case data in bracket */
                case 'inbrck':
                    if (strpos("\"'() ,\n", $current) !== false) {
                        if (($current === '"' || $current === '\'') && !self::escaped($string, $i)) {
                            $status = 'instr';
                            $from = 'inbrck';
                            $currentString = $current;
                            $stringChar = $current;
                            continue;
                        } else if ($current === '(') {
                            ++$bracketCount;
                        } else if ($current === ')' && --$bracketCount === 0) {
                            $status = 'iv'; // Go back to 'in value' parser
                        } else if (($current === ' ' || $current === "\n") && (substr($subValue, -1) === ' ' || substr($subValue, -1) === ',')) {
                            continue; // Remove multiple spaces and space after ','
                        } else if ($current === ',' && substr($subValue, -1) === ' ') {
                            $subValue = substr($subValue, 0, -1); // Remove space before ','
                        } else if ($current === "\n") {
                            $current = ' '; // Change new line character to normal space
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
                        $status = $from;
                        if ($property !== 'content' && $property !== 'quotes' && !$quotedString) {
                            $currentString = $this->removeQuotes($currentString, $from);
                        }
                        if ($from === 'iv' || $from === 'inbrck') {
                            $subValue .= $currentString;
                        } elseif ($from === 'is') {
                            $selector .= $currentString;
                        }
                        $quotedString = false;
                    }
                    break;

                /* Case in-comment */
                case 'ic':
                    if ($current === '*' && $string{$i + 1} === '/') {
                        $status = $from;
                        $i++;
                        if (!$preserveCss) $parsed->addToken(self::COMMENT, $currentComment);
                        $currentComment = '';
                    } else {
                        $currentComment .= $current;
                    }
                    break;

                /* Case in at-block */
                case 'at':
                    if ($this->isToken($string, $i)) {
                        if ($current === '/' && isset($string{$i + 1}) && $string{$i + 1} === '*') {
                            $status = 'ic';
                            ++$i;
                            $from = 'at';
                        } elseif ($current === '{') {
                            $status = 'is';
                            if (!$preserveCss) {
                                $at = $parsed->newMediaSection($at);
                                $parsed->addToken(self::AT_START, $at);
                            }
                        } elseif ($current === ',') {
                            $at = trim($at) . ',';
                        } elseif ($current === '\\') {
                            $at .= $this->unicode($string, $i);
                        }
                        // fix for complicated media, i.e @media screen and (-webkit-min-device-pixel-ratio:0)
                        elseif (in_array($current, array('(', ')', ':'))) {
                            $at .= $current;
                        }
                    } else {
                        $lastpos = strlen($at) - 1;
                        if (!( (ctype_space($at{$lastpos}) || $this->isToken($at, $lastpos) && $at{$lastpos} === ',') && ctype_space($current))) {
                            $at .= $current;
                        }
                    }
                    break;
            }
        }

        $this->optimise->postparse($parsed->css);

        @setlocale(LC_ALL, $old); // Set locale back to original setting

        if (!(empty($parsed->css) && empty($parsed->import) && empty($parsed->charset) && empty($parsed->tokens) && empty($parsed->namespace))) {
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
     * @param string $at
     */
    protected function explodeSelectors($selector, $at)
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
                foreach ($newSelectors as $selector) {
                    if (isset($this->parsed->css[$at][$selector])) {
                        $this->parsed->mergeCssBlocks($at, $selector, $this->parsed->css[$at][$selector]);
                    }
                }
                unset($this->parsed->css[$at][$selector]);
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
     * @todo Check all possible bugs
     * @param string $string
     * @param string $from
     * @return mixed
     */
    protected function removeQuotes($string, $from)
    {
        if (preg_match('|[' . implode('', self::$whitespace) . ']|uis', $string)) { // If string contains whitespace
            if (strpos($string, '"') === false) { // Convert all possible single quote to double quote
                return '"' . substr($string, 1, -1) . '"';
            }
            return $string;
        }

        if ($from === 'inbrck' && (strpos($string, '(') !== false || strpos($string, ')') !== false)) {
            return $string;
        }

        return substr($string, 1, -1);
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

        while ($i < strlen($string) && (ctype_xdigit($string{$i}) || ctype_space($string{$i})) && strlen($add) < 6) {
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
            $add = trim('\\' . $add);
        }

        if (@ctype_xdigit($string{$i + 1}) && ctype_space($string{$i})
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
        return isset($value{9}) && substr_compare(str_replace(self::$whitespace, '', $value), '!important', -10, 10, true) === 0;
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
     * Checks if the next word in a string from pos is a CSS property
     * @param string $istring
     * @param integer $pos
     * @return bool
     * @access private
     * @version 1.2
     */
    protected function propertyIsNext($istring, $pos)
    {
        $istring = substr($istring, $pos);
        $pos = strpos($istring, ':');
        if ($pos === false) {
            return false;
        }
        $istring = strtolower(trim(substr($istring, 0, $pos)));
        if (isset(self::$allProperties[$istring])) {
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
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }
}