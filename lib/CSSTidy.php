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

        // CSS3
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

	/**
	 * Optimiser class
	 * @var Optimise
	 */
	private $optimise;
    /**
	 * @var Logger
	 */
	public $logger;
    /**
     * @var Parsed
     */
    protected $parsed;
    /*
	 * Contains the version of csstidy
	 * @var string
	 */
	private static $version = '1.4';

	/**
	 * Saves the parser-status.
	 *
	 * Possible values:
	 * - is = in selector
	 * - ip = in property
	 * - iv = in value
	 * - instr = in string (started at " or ' or ( )
	 * - ic = in comment (ignore everything)
	 * - at = in @-block
	 *
	 * @var string
	 */
	private $status = 'is';
	/**
	 * Saves the current at rule (@media)
	 * @var string
	 */
	private $at = '';
	/**
	 * Saves the current selector
	 * @var string
	 */
	private $selector = '';
	/**
	 * Saves the current property
	 * @var string
	 */
	private $property = '';
	/**
	 * Saves the position of , in selectors
	 * @var array
	 */
	private $sel_separate = array();
	/**
	 * Saves the current value
	 * @var string
	 */
	private $value = '';
	/**
	 * Saves the current sub-value
	 *
	 * Example for a subvalue:
	 * background:url(foo.png) red no-repeat;
	 * "url(foo.png)", "red", and  "no-repeat" are subvalues,
	 * seperated by whitespace
	 * @var string
	 */
	private $sub_value = '';
	/**
	 * Array which saves all subvalues for a property.
	 * @var array
	 * @see sub_value
	 */
	private $sub_value_arr = array();
	/**
	 * Saves the char which opened the last string
	 * @var string
	 * @access private
	 */
	private $str_char = '';
	private $cur_string = '';
	/**
	 * Status from which the parser switched to ic or instr
	 * @var string
	 * @access private
	 */
	private $from = '';
	/**
	 * Variable needed to manage string-in-strings, for example url("foo.png")
	 * @var string
	 * @access private
	 */
	private $str_in_str = false;
	/**
	 * =true if in invalid at-rule
	 * @var bool
	 * @access private
	 */
	private $invalid_at = false;

    /**
     * @var Configuration
     */
    public $configuration;
	/**
	 * Marks if we need to leave quotes for a string
	 * @var string
	 * @access private
	 */
	private $quoted_string = false;

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
		$cur_comment = '';

		for ($i = 0, $size = strlen($string); $i < $size; $i++) {
			if ($string{$i} === "\n" || $string{$i} === "\r") {
				$this->logger->incrementLine();
			}

			switch ($this->status) {
				/* Case in at-block */
				case 'at':
					if ($this->isToken($string, $i)) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*') {
							$this->status = 'ic';
							++$i;
							$this->from = 'at';
						} elseif ($string{$i} === '{') {
							$this->status = 'is';
							$this->at = $this->parsed->newMediaSection($this->at);
							$parsed->addToken(self::AT_START, $this->at);
						} elseif ($string{$i} === ',') {
							$this->at = trim($this->at) . ',';
						} elseif ($string{$i} === '\\') {
							$this->at .= $this->unicode($string, $i);
						}
						// fix for complicated media, i.e @media screen and (-webkit-min-device-pixel-ratio:0)
						elseif (in_array($string{$i}, array('(', ')', ':'))) {
							$this->at .= $string{$i};
						}
					} else {
						$lastpos = strlen($this->at) - 1;
						if (!( (ctype_space($this->at{$lastpos}) || $this->isToken($this->at, $lastpos) && $this->at{$lastpos} === ',') && ctype_space($string{$i}))) {
							$this->at .= $string{$i};
						}
					}
					break;

				/* Case in-selector */
				case 'is':
					if ($this->isToken($string, $i)) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*' && trim($this->selector) == '') {
							$this->status = 'ic';
							++$i;
							$this->from = 'is';
						} elseif ($string{$i} === '@' && trim($this->selector) == '') {
							// Check for at-rule
							$this->invalid_at = true;
							foreach (self::$atRules as $name => $type) {
								if (!strcasecmp(substr($string, $i + 1, strlen($name)), $name)) {
									($type === 'at') ? $this->at = '@' . $name : $this->selector = '@' . $name;
									$this->status = $type;
									$i += strlen($name);
									$this->invalid_at = false;
								}
							}

							if ($this->invalid_at) {
								$this->selector = '@';
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
							$this->cur_string = $string{$i};
							$this->status = 'instr';
							$this->str_char = $string{$i};
							$this->from = 'is';
							/* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
							$this->quoted_string = ($string{$i - 1} == '=' );
						} elseif ($this->invalid_at && $string{$i} === ';') {
							$this->invalid_at = false;
							$this->status = 'is';
						} elseif ($string{$i} === '{') {
							$this->status = 'ip';
							if($this->at == '') {
								$this->at = $this->parsed->newMediaSection(self::DEFAULT_AT);
							}
							$this->selector = $this->parsed->newSelector($this->at,$this->selector);
							$parsed->addToken(self::SEL_START, $this->selector);
						} elseif ($string{$i} === '}') {
							$parsed->addToken(self::AT_END, $this->at);
							$this->at = '';
							$this->selector = '';
							$this->sel_separate = array();
						} elseif ($string{$i} === ',') {
							$this->selector = trim($this->selector) . ',';
							$this->sel_separate[] = strlen($this->selector);
						} elseif ($string{$i} === '\\') {
							$this->selector .= $this->unicode($string, $i);
						} elseif ($string{$i} === '*' && @in_array($string{$i + 1}, array('.', '#', '[', ':'))) {
							// remove unnecessary universal selector, FS#147
						} else {
							$this->selector .= $string{$i};
						}
					} else {
						$lastpos = strlen($this->selector) - 1;
						if ($lastpos == -1 || !( (ctype_space($this->selector{$lastpos}) || $this->isToken($this->selector, $lastpos) && $this->selector{$lastpos} === ',') && ctype_space($string{$i}))) {
							$this->selector .= $string{$i};
						}
					}
					break;

				/* Case in-property */
				case 'ip':
					if ($this->isToken($string, $i)) {
						if (($string{$i} === ':' || $string{$i} === '=') && $this->property != '') {
							$this->status = 'iv';
							if (!$this->configuration->discardInvalidProperties || $this->propertyIsValid($this->property)) {
								$this->property = $this->parsed->newProperty($this->at,$this->selector,$this->property);
								$parsed->addToken(self::PROPERTY, $this->property);
							}
						} elseif ($string{$i} === '/' && @$string{$i + 1} === '*' && $this->property == '') {
							$this->status = 'ic';
							++$i;
							$this->from = 'ip';
						} elseif ($string{$i} === '}') {
							$this->explodeSelectors();
							$this->status = 'is';
							$this->invalid_at = false;
							$parsed->addToken(self::SEL_END, $this->selector);
							$this->selector = '';
							$this->property = '';
						} elseif ($string{$i} === ';') {
							$this->property = '';
						} elseif ($string{$i} === '\\') {
							$this->property .= $this->unicode($string, $i);
						}
						// else this is dumb IE a hack, keep it
						elseif ($this->property == '' && !ctype_space($string{$i})) {
							$this->property .= $string{$i};
						}
					}
					elseif (!ctype_space($string{$i})) {
						$this->property .= $string{$i};
					}
					break;

				/* Case in-value */
				case 'iv':
					$pn = (($string{$i} === "\n" || $string{$i} === "\r") && $this->propertyIsNext($string, $i + 1) || $i == strlen($string) - 1);
					if ($this->isToken($string, $i) || $pn) {
						if ($string{$i} === '/' && @$string{$i + 1} === '*') {
							$this->status = 'ic';
							++$i;
							$this->from = 'iv';
						} elseif (($string{$i} === '"' || $string{$i} === "'" || $string{$i} === '(')) {
							$this->cur_string = $string{$i};
							$this->str_char = ($string{$i} === '(') ? ')' : $string{$i};
							$this->status = 'instr';
							$this->from = 'iv';
						} elseif ($string{$i} === ',') {
							$this->sub_value = trim($this->sub_value) . ',';
						} elseif ($string{$i} === '\\') {
							$this->sub_value .= $this->unicode($string, $i);
						} elseif ($string{$i} === ';' || $pn) {
							if ($this->selector{0} === '@' && isset(self::$atRules[substr($this->selector, 1)]) && self::$atRules[substr($this->selector, 1)] === 'iv') {
								/* Add quotes to charset, import, namespace */
								$this->sub_value_arr[] = '"' . trim($this->sub_value) . '"';

								$this->status = 'is';

								switch ($this->selector) {
									case '@charset': $parsed->charset = $this->sub_value_arr[0];
										break;
									case '@namespace': $parsed->namespace = implode(' ', $this->sub_value_arr);
										break;
									case '@import': $parsed->import[] = implode(' ', $this->sub_value_arr);
										break;
								}

								$this->sub_value_arr = array();
								$this->sub_value = '';
								$this->selector = '';
								$this->sel_separate = array();
							} else {
								$this->status = 'ip';
							}
						} elseif ($string{$i} !== '}') {
							$this->sub_value .= $string{$i};
						}
						if (($string{$i} === '}' || $string{$i} === ';' || $pn) && !empty($this->selector)) {
							if ($this->at == '') {
								$this->at = $this->parsed->newMediaSection(self::DEFAULT_AT);
							}

							// case settings
							if ($this->configuration->lowerCaseSelectors) {
								$this->selector = strtolower($this->selector);
							}
							$this->property = strtolower($this->property);

							$this->sub_value = $this->optimise->subValue($this->property, $this->sub_value);
							if ($this->sub_value != '') {
								if (substr($this->sub_value, 0, 6) == 'format') {
									$this->sub_value = str_replace(array('format(', ')'), array('format("', '")'), $this->sub_value);
								}
								$this->sub_value_arr[] = $this->sub_value;
								$this->sub_value = '';
							}

							$this->value = array_shift($this->sub_value_arr);
							while (!empty($this->sub_value_arr)) {
								$this->value .= (substr($this->value, -1, 1) == ',' ? '' : ' ') . array_shift($this->sub_value_arr);
							}

							$this->value = $this->optimise->value($this->property, $this->value);

							$valid = $this->propertyIsValid($this->property);
							if ((!$this->invalid_at || $this->configuration->preserveCss) && (!$this->configuration->discardInvalidProperties || $valid)) {
								$this->parsed->addProperty($this->at, $this->selector, $this->property, $this->value);
								$parsed->addToken(self::VALUE, $this->value);
								$this->optimise->shorthands($parsed, $this->at, $this->selector, $this->property, $this->value);
							}
							if (!$valid) {
								if ($this->configuration->discardInvalidProperties) {
									$this->logger->log('Removed invalid property: ' . $this->property, 'Warning');
								} else {
									$this->logger->log('Invalid property in ' . $this->configuration->cssLevel . ': ' . $this->property, 'Warning');
								}
							}

							$this->property = '';
							$this->sub_value_arr = array();
							$this->value = '';
						}
						if ($string{$i} === '}') {
							$this->explodeSelectors();
							$parsed->addToken(self::SEL_END, $this->selector);
							$this->status = 'is';
							$this->invalid_at = false;
							$this->selector = '';
						}
					} elseif (!$pn) {
						$this->sub_value .= $string{$i};

						if (ctype_space($string{$i})) {
							$this->sub_value = $this->optimise->subValue($this->property, $this->sub_value);
							if ($this->sub_value != '') {
								$this->sub_value_arr[] = $this->sub_value;
								$this->sub_value = '';
							}
						}
					}
					break;

				/* Case in string */
				case 'instr':
					if ($this->str_char === ')' && ($string{$i} === '"' || $string{$i} === '\'') && !$this->str_in_str && !self::escaped($string, $i)) {
						$this->str_in_str = true;
					} elseif ($this->str_char === ')' && ($string{$i} === '"' || $string{$i} === '\'') && $this->str_in_str && !self::escaped($string, $i)) {
						$this->str_in_str = false;
					}
					$temp_add = $string{$i};					 // ...and no not-escaped backslash at the previous position
					if (($string{$i} === "\n" || $string{$i} === "\r") && !($string{$i - 1} === '\\' && !self::escaped($string, $i - 1))) {
						$temp_add = "\\A ";
						$this->logger->log('Fixed incorrect newline in string', 'Warning');
					}
					// this optimisation remove space in css3 properties (see vendor-prefixed/webkit-gradient.csst)
					#if (!($this->str_char === ')' && in_array($string{$i}, $GLOBALS['csstidy']['whitespace']) && !$this->str_in_str)) {
						$this->cur_string .= $temp_add;
					#}
					if ($string{$i} == $this->str_char && !self::escaped($string, $i) && !$this->str_in_str) {
						$this->status = $this->from;
						if (!preg_match('|[' . implode('', self::$whitespace) . ']|uis', $this->cur_string) && $this->property !== 'content') {
							if (!$this->quoted_string) {
								if ($this->str_char === '"' || $this->str_char === '\'') {
									// Temporarily disable this optimization to avoid problems with @charset rule, quote properties, and some attribute selectors...
									// Attribute selectors fixed, added quotes to @chartset, no problems with properties detected. Enabled
									$this->cur_string = substr($this->cur_string, 1, -1);
								} else if (strlen($this->cur_string) > 3 && ($this->cur_string[1] === '"' || $this->cur_string[1] === '\'')) /* () */ {
									$this->cur_string = $this->cur_string[0] . substr($this->cur_string, 2, -2) . substr($this->cur_string, -1);
								}
							} else {
								$this->quoted_string = false;
							}
						}
						if ($this->from === 'iv') {
							if (!$this->quoted_string) {
								if (strpos($this->cur_string, ',') !== false) {
									// we can on only remove space next to ','
									$this->cur_string = implode(',', array_map('trim', explode(',', $this->cur_string)));
                                }
								// and multiple spaces (too expensive)
								if (strpos($this->cur_string, '  ') !== false) {
									$this->cur_string = preg_replace(",\s+,", " ", $this->cur_string);
                                }
							}
							$this->sub_value .= $this->cur_string;
						} elseif ($this->from === 'is') {
							$this->selector .= $this->cur_string;
						}
					}
					break;

				/* Case in-comment */
				case 'ic':
					if ($string{$i} === '*' && $string{$i + 1} === '/') {
						$this->status = $this->from;
						$i++;
						$parsed->addToken(self::COMMENT, $cur_comment);
						$cur_comment = '';
					} else {
						$cur_comment .= $string{$i};
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
	protected function explodeSelectors()
    {
		// Explode multiple selectors
		if ($this->configuration->mergeSelectors === Configuration::SEPARATE_SELECTORS) {
			$new_sels = array();
			$lastpos = 0;
			$this->sel_separate[] = strlen($this->selector);
			foreach ($this->sel_separate as $num => $pos) {
				if ($num == count($this->sel_separate) - 1) {
					$pos += 1;
				}

				$new_sels[] = substr($this->selector, $lastpos, $pos - $lastpos - 1);
				$lastpos = $pos;
			}

			if (count($new_sels) > 1) {
				foreach ($new_sels as $selector) {
					if (isset($this->parsed->css[$this->at][$this->selector])) {
						$this->parsed->mergeCssBlocks($this->at, $selector, $this->parsed->css[$this->at][$this->selector]);
					}
				}
				unset($this->parsed->css[$this->at][$this->selector]);
			}
		}
		$this->sel_separate = array();
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

		if ($add !== '\\' || !$this->configuration->removeBackSlash || strpos(self::$tokensList, $string{$i + 1}) !== false) {
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
		return (isset(self::$allProperties[$property]) && strpos(self::$allProperties[$property], $this->configuration->cssLevel) !== false);
	}

    /**
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }
}