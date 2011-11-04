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

require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Parsed.php';

/**
 * Contains a class for printing CSS code
 *
 * @version 1.0
 */
require_once __DIR__ . '/Output.php';

/**
 * Contains a class for optimising CSS code
 *
 * @version 1.0
 */
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
    public static $whitespace = array(' ',"\n","\t","\r","\x0B");

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
    public static $atRules = array('page' => 'is','font-face' => 'is','charset' => 'iv', 'import' => 'iv','namespace' => 'iv','media' => 'at');


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

        // CSS3 only
        'background-size' => 'CSS3.0',
        'background-origin' => 'CSS3.0',
        'border-radius' => 'CSS3.0',
        'border-image' => 'CSS3.0',
        'border-top-left-radius' => 'CSS3.0',
        'border-top-right-radius' => 'CSS3.0',
        'border-bottom-right-radius' => 'CSS3.0',
        'border-bottom-left-radius' => 'CSS3.0',
        'box-shadow' => 'CSS3.0',
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
     * Parse CSS from $url
     * @param string $url
     * @return Output
     * @throws \Exception
     */
	public function parseFromUrl($url)
    {
        $content = @file_get_contents($url);

        if (!$content) {
            throw new \Exception("Cannot open URL '$url'");
        }

		return $this->parse($content);
	}

	/**
	 * Parses CSS in $string.
	 * @param string $string the CSS code
	 * @return Output
	 */
	public function parse($string)
    {
		// Temporarily set locale to en_US in order to handle floats properly
		$old = @setlocale(LC_ALL, 0);
		@setlocale(LC_ALL, 'C');

		$this->optimise = new Optimise($this->logger, $this->configuration);
        $this->parsed = $parsed = new Parsed($this->configuration, $string);

		$string = str_replace("\r\n", "\n", $string) . ' ';
        
		$currentComment = $currentString = $stringChar = $from = $subValue = $value = $property = $selector = $at = '';
        $quotedString = $strInStr = $invalidAtRule = false;

        /*
         * Possible values:
         * - is = in selector
         * - ip = in property
         * - iv = in value
         * - instr = in string (started at " or ' or ( )
         * - ic = in comment (ignore everything)
         * - at = in @-block
         */
        $status = 'is';
        $subValues = array();

		for ($i = 0, $size = strlen($string); $i < $size; $i++) {
			if ($string{$i} === "\n" || $string{$i} === "\r") {
				$this->logger->incrementLine();
			}

			switch ($status) {
				/* Case in at-block */
				case 'at':
					if ($this->isToken($string, $i)) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*') {
							$status = 'ic';
							++$i;
							$from = 'at';
						} elseif ($string{$i} === '{') {
							$status = 'is';
							$at = $parsed->newMediaSection($at);
							$parsed->addToken(self::AT_START, $at);
						} elseif ($string{$i} === ',') {
							$at = trim($at) . ',';
						} elseif ($string{$i} === '\\') {
							$at .= $this->unicode($string, $i);
						}
						// fix for complicated media, i.e @media screen and (-webkit-min-device-pixel-ratio:0)
						elseif (in_array($string{$i}, array('(', ')', ':'))) {
							$at .= $string{$i};
						}
					} else {
						$lastpos = strlen($at) - 1;
						if (!( (ctype_space($at{$lastpos}) || $this->isToken($at, $lastpos) && $at{$lastpos} === ',') && ctype_space($string{$i}))) {
							$at .= $string{$i};
						}
					}
					break;

				/* Case in-selector */
				case 'is':
					if ($this->isToken($string, $i)) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*' && trim($selector) == '') {
							$status = 'ic';
							++$i;
							$from = 'is';
						} elseif ($string{$i} === '@' && trim($selector) == '') {
							// Check for at-rule
							$invalidAtRule = true;
							foreach (self::$atRules as $name => $type) {
								if (!strcasecmp(substr($string, $i + 1, strlen($name)), $name)) {
									($type === 'at') ? $at = '@' . $name : $selector = '@' . $name;
									$status = $type;
									$i += strlen($name);
									$invalidAtRule = false;
								}
							}

							if ($invalidAtRule) {
								$selector = '@';
								$invalid_at_name = '';
								for ($j = $i + 1; $j < $size; ++$j) {
									if (!ctype_alpha($string{$j})) {
										break;
									}
									$invalid_at_name .= $string{$j};
								}
								$this->logger->log('Invalid @-rule: ' . $invalid_at_name . ' (removed)', 'Warning');
							}
						} elseif (($string{$i} === '"' || $string{$i} === "'")) {
							$currentString = $string{$i};
							$status = 'instr';
							$stringChar = $string{$i};
							$from = 'is';
							/* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
							$quotedString = ($string{$i - 1} === '=');
						} elseif ($invalidAtRule && $string{$i} === ';') {
							$invalidAtRule = false;
							$status = 'is';
						} elseif ($string{$i} === '{') {
							$status = 'ip';
							if($at == '') {
								$at = $parsed->newMediaSection(self::DEFAULT_AT);
							}
							$selector = $parsed->newSelector($at,$selector);
							$parsed->addToken(self::SEL_START, $selector);
						} elseif ($string{$i} === '}') {
							$parsed->addToken(self::AT_END, $at);
							$at = '';
							$selector = '';
							$selectorSeparate = array();
						} elseif ($string{$i} === ',') {
							$selector = trim($selector) . ',';
							$selectorSeparate[] = strlen($selector);
						} elseif ($string{$i} === '\\') {
							$selector .= $this->unicode($string, $i);
						} elseif ($string{$i} === '*' && @in_array($string{$i + 1}, array('.', '#', '[', ':'))) {
							// remove unnecessary universal selector, FS#147
						} else {
							$selector .= $string{$i};
						}
					} else {
						$lastpos = strlen($selector) - 1;
						if ($lastpos == -1 || !( (ctype_space($selector{$lastpos}) || $this->isToken($selector, $lastpos) && $selector{$lastpos} === ',') && ctype_space($string{$i}))) {
							$selector .= $string{$i};
						}
					}
					break;

				/* Case in-property */
				case 'ip':
					if ($this->isToken($string, $i)) {
						if (($string{$i} === ':' || $string{$i} === '=') && $property != '') {
							$status = 'iv';
							if (!$this->configuration->getDiscardInvalidProperties() || $this->propertyIsValid($property)) {
								$property = $parsed->newProperty($at, $selector, $property);
								$parsed->addToken(self::PROPERTY, $property);
							}
						} elseif ($string{$i} === '/' && @$string{$i + 1} === '*' && $property == '') {
							$status = 'ic';
							++$i;
							$from = 'ip';
						} elseif ($string{$i} === '}') {
							$this->explodeSelectors($selector, $at);
							$status = 'is';
							$invalidAtRule = false;
							$parsed->addToken(self::SEL_END, $selector);
							$selector = '';
							$property = '';
						} elseif ($string{$i} === ';') {
							$property = '';
						} elseif ($string{$i} === '\\') {
							$property .= $this->unicode($string, $i);
						}
						// else this is dumb IE a hack, keep it
						elseif ($property == '' && !ctype_space($string{$i})) {
							$property .= $string{$i};
						}
					}
					elseif (!ctype_space($string{$i})) {
						$property .= $string{$i};
					}
					break;

				/* Case in-value */
				case 'iv':
					$pn = (($string{$i} === "\n" || $string{$i} === "\r") && $this->propertyIsNext($string, $i + 1) || $i == strlen($string) - 1);
					if ($this->isToken($string, $i) || $pn) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*') {
							$status = 'ic';
							++$i;
							$from = 'iv';
						} elseif (($string{$i} === '"' || $string{$i} === "'" || $string{$i} === '(')) {
							$currentString = $string{$i};
							$stringChar = ($string{$i} === '(') ? ')' : $string{$i};
							$status = 'instr';
							$from = 'iv';
						} elseif ($string{$i} === ',') {
							$subValue = trim($subValue) . ',';
						} elseif ($string{$i} === '\\') {
							$subValue .= $this->unicode($string, $i);
						} elseif ($string{$i} === ';' || $pn) {
							if ($selector{0} === '@' && isset(self::$atRules[substr($selector, 1)]) && self::$atRules[substr($selector, 1)] === 'iv') {
								/* Add quotes to charset, import, namespace */
								$subValues[] = '"' . trim($subValue) . '"';

								$status = 'is';

								switch ($selector) {
									case '@charset': $parsed->charset = $subValues[0];
										break;
									case '@namespace': $parsed->namespace = implode(' ', $subValues);
										break;
									case '@import': $parsed->import[] = implode(' ', $subValues);
										break;
								}

								$subValues = array();
								$subValue = '';
								$selector = '';
								$selectorSeparate = array();
							} else {
								$status = 'ip';
							}
						} elseif ($string{$i} !== '}') {
							$subValue .= $string{$i};
						}
						if (($string{$i} === '}' || $string{$i} === ';' || $pn) && !empty($selector)) {
							if ($at == '') {
								$at = $parsed->newMediaSection(self::DEFAULT_AT);
							}

							// case settings
							if ($this->configuration->getLowerCaseSelectors()) {
								$selector = strtolower($selector);
							}
							$property = strtolower($property);

							$subValue = $this->optimise->subValue($property, $subValue);
							if ($subValue != '') {
								if (substr($subValue, 0, 6) == 'format') {
									$subValue = str_replace(array('format(', ')'), array('format("', '")'), $subValue);
								}
								$subValues[] = $subValue;
								$subValue = '';
							}

							$value = array_shift($subValues);
							while (!empty($subValues)) {
								$value .= (substr($value, -1, 1) == ',' ? '' : ' ') . array_shift($subValues);
							}

							$value = $this->optimise->value($property, $value);

							$valid = $this->propertyIsValid(rtrim($property)); // Remove right spaces added by Parsed::newProperty
							if ((!$invalidAtRule || $this->configuration->getPreserveCss()) && (!$this->configuration->getDiscardInvalidProperties() || $valid)) {
								$parsed->addProperty($at, $selector, $property, $value);
								$parsed->addToken(self::VALUE, $value);
								$this->optimise->shorthands($parsed, $at, $selector, $property, $value);
							}
							if (!$valid) {
								if ($this->configuration->getDiscardInvalidProperties()) {
									$this->logger->log("Removed invalid property: $property", 'Warning');
								} else {
									$this->logger->log("Invalid property in {$this->configuration->getCssLevel()}: $property", 'Warning');
								}
							}

							$property = '';
							$subValues = array();
							$value = '';
						}
						if ($string{$i} === '}') {
							$this->explodeSelectors($selector, $at);
							$parsed->addToken(self::SEL_END, $selector);
							$status = 'is';
							$invalidAtRule = false;
							$selector = '';
						}
					} elseif (!$pn) {
						$subValue .= $string{$i};

						if (ctype_space($string{$i})) {
							$subValue = $this->optimise->subValue($property, $subValue);
							if ($subValue != '') {
								$subValues[] = $subValue;
								$subValue = '';
							}
						}
					}
					break;

				/* Case in string */
				case 'instr':
					if ($stringChar === ')' && ($string{$i} === '"' || $string{$i} === '\'') && !$strInStr && !self::escaped($string, $i)) {
						$strInStr = true;
					} elseif ($stringChar === ')' && ($string{$i} === '"' || $string{$i} === '\'') && $strInStr && !self::escaped($string, $i)) {
						$strInStr = false;
					}
					$temp_add = $string{$i};					 // ...and no not-escaped backslash at the previous position
					if (($string{$i} === "\n" || $string{$i} === "\r") && !($string{$i - 1} === '\\' && !self::escaped($string, $i - 1))) {
						$temp_add = "\\A ";
						$this->logger->log('Fixed incorrect newline in string', 'Warning');
					}
					// this optimisation remove space in css3 properties (see vendor-prefixed/webkit-gradient.csst)
					#if (!($stringChar === ')' && in_array($string{$i}, $GLOBALS['csstidy']['whitespace']) && !$strInStr)) {
						$currentString .= $temp_add;
					#}
					if ($string{$i} == $stringChar && !self::escaped($string, $i) && !$strInStr) {
						$status = $from;
						if (!preg_match('|[' . implode('', self::$whitespace) . ']|uis', $currentString) && $property !== 'content') {
							if (!$quotedString) {
								if ($stringChar === '"' || $stringChar === '\'') {
									// Temporarily disable this optimization to avoid problems with @charset rule, quote properties, and some attribute selectors...
									// Attribute selectors fixed, added quotes to @chartset, no problems with properties detected. Enabled
									$currentString = substr($currentString, 1, -1);
								} else if (strlen($currentString) > 3 && ($currentString[1] === '"' || $currentString[1] === '\'')) /* () */ {
									$currentString = $currentString[0] . substr($currentString, 2, -2) . substr($currentString, -1);
								}
							} else {
								$quotedString = false;
							}
						}
						if ($from === 'iv') {
							if (!$quotedString) {
								if (strpos($currentString, ',') !== false) {
									// we can on only remove space next to ','
									$currentString = implode(',', array_map('trim', explode(',', $currentString)));
                                }
								// and multiple spaces (too expensive)
								if (strpos($currentString, '  ') !== false) {
									$currentString = preg_replace(",\s+,", " ", $currentString);
                                }
							}
							$subValue .= $currentString;
						} elseif ($from === 'is') {
							$selector .= $currentString;
						}
					}
					break;

				/* Case in-comment */
				case 'ic':
					if ($string{$i} === '*' && $string{$i + 1} === '/') {
						$status = $from;
						$i++;
						$parsed->addToken(self::COMMENT, $currentComment);
						$currentComment = '';
					} else {
						$currentComment .= $string{$i};
					}
					break;
			}
		}

		$this->optimise->postparse($parsed->css);

		@setlocale(LC_ALL, $old); // Set locale back to original setting

		if (!(empty($parsed->css) && empty($parsed->import) && empty($parsed->charset) && empty($parsed->tokens) && empty($parsed->namespace))) {
            return new Output($this->configuration, $this->logger, $string, $parsed);
        } else {
            throw new \Exception("Invalid CSS");
        }
	}

	/**
	 * Explodes selectors
	 * @access private
	 * @version 1.0
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
	 * Parse unicode notations and find a replacement character
	 * @param string $string
	 * @param integer $i
	 * @access private
	 * @return string
	 * @version 1.2
	 */
	protected function unicode(&$string, &$i)
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
			$this->logger->log('Replaced unicode notation: Changed \\' . $add . ' to ' . chr($hexDecAdd), 'Information');
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
			$this->logger->log('Removed unnecessary backslash', 'Information');
		}
		return '';
	}

	/**
	 * Checks if a character is escaped (and returns true if it is)
	 * @param string $string
	 * @param integer $pos
	 * @access public
	 * @return bool
	 * @version 1.02
	 */
	static function escaped($string, $pos)
    {
		return !(@($string{$pos - 1} !== '\\') || self::escaped($string, $pos - 1));
	}

	/**
	 * Checks if $value is !important.
	 * @param string $value
	 * @return bool
	 * @access public
	 * @version 1.0
	 */
	public static function isImportant($value)
    {
		return (!strcasecmp(substr(str_replace(self::$whitespace, '', $value), -10, 10), '!important'));
	}

	/**
	 * Returns a value without !important
	 * @param string $value
	 * @return string
	 * @access public
	 * @version 1.0
	 */
	public static function removeImportant($value)
    {
		if (self::isImportant($value)) {
			$value = trim($value);
			$value = substr($value, 0, -9);
			$value = trim($value);
			$value = substr($value, 0, -1);
			$value = trim($value);
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
		$istring = substr($istring, $pos, strlen($istring) - $pos);
		$pos = strpos($istring, ':');
		if ($pos === false) {
			return false;
		}
		$istring = strtolower(trim(substr($istring, 0, $pos)));
		if (isset(self::$allProperties[$istring])) {
			$this->logger->log('Added semicolon to the end of declaration', 'Warning');
			return true;
		}
		return false;
	}

    /**
	 * Checks if there is a token at the current position
	 * @param string $string
	 * @param integer $i
	 * @access public
	 * @version 1.11
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