<?php
namespace CSSTidy;

class Parser
{
    /**
     * All whitespace allowed in CSS without '\r', because is changed to '\n' before parsing
     * @static
     * @var string
     */
    public static $whitespace = " \n\t\x0B\x0C";

    /**
     * Array is generated from self::$whitespace in __constructor
     * @static
     * @var array
     */
    public static $whitespaceArray = array();

    /**
     * All CSS tokens used by csstidy
     *
     * @var string
     * @static
     */
    public static $tokensList = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';

    /** @var string */
    private static $stringTokens;

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

    /** @var int */
    protected $currentLine;

    /** @var Logger */
    protected $logger;

    /** @var bool */
    protected $discardInvalidProperties;

    /** @var string */
    protected $cssLevel;

    /** @var bool */
    protected $removeBackSlash;

    public function __construct(Logger $logger, $discardInvalidProperties, $cssLevel, $removeBackSlash)
    {
        $this->logger = $logger;
        $this->discardInvalidProperties = $discardInvalidProperties;
        $this->cssLevel = $cssLevel;
        $this->removeBackSlash = $removeBackSlash;

        // Prepare array of all CSS whitespaces
        self::$whitespaceArray = str_split(self::$whitespace);

        // String tokens
        self::$stringTokens = self::$whitespace . '\'"()';
    }

    /**
     * @param $string
     * @return Parsed
     */
    public function parse($string)
    {
        $parsed = new Parsed;

        // Normalize new line characters
        $string = str_replace(array("\r\n", "\r"), array("\n", "\n"), $string) . ' ';

        // Initialize variables
        $function = $currentString = $stringEndsWith = $subValue = $value = $property = $selector = '';
        $quotedString = false;
        $bracketCount = 0;
        $this->currentLine = 1;

        /*
         * Possible values:
         * - is = in selector
         * - ip = in property
         * - iv = in value
         * - instr = in string (started at " or ')
         * - inbrck = in bracket (started by ()
         * - at = in @-block
         */
        $status = 'is';
        $subValues = $from = $selectorSeparate = array();
        $stack = array($parsed);

        for ($i = 0, $size = strlen($string); $i < $size; $i++) {
            $current = $string{$i};

            if ($current === "\n") {
                ++$this->currentLine;
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
                            $this->setSubSelectors(end($stack), $selectorSeparate);
                            $selectorSeparate = array();
                        } else if ($current === ',') {
                            $selector = trim($selector) . ',';
                            $selectorSeparate[] = strlen($selector);
                        } else if ($current === '/' && $string{$i + 1} === '*') {
                            end($stack)->addComment(new Comment($this->parseComment($string, $i)));
                        } else if ($current === '@' && trim($selector) == '') {
                            $status = 'at';
                        } else if ($current === '"' || $current === "'") {
                            $currentString = $stringEndsWith = $current;
                            $status = 'instr';
                            $from[] = 'is';
                            /* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
                            $quotedString = ($string{$i - 1} === '=');
                        } else if ($current === '}') {
                            array_pop($stack);
                            $selector = '';
                        } else if ($current === '\\') {
                            $selector .= $this->unicode($string, $i);
                        } else if ($current === '*' && in_array($string{$i + 1}, array('.', '#', '[', ':'))) {
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
                        } else if ($current === '}') {
                            array_pop($stack);
                            $status = array_pop($from);
                            $selector = $property = '';
                        } else if ($current === '@') {
                            $status = 'at';
                        } else if ($current === '/' && $string{$i + 1} === '*') {
                            end($stack)->addComment(new Comment($this->parseComment($string, $i)));
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
                        if ($current === '/' && $string{$i + 1} === '*') {
                            end($stack)->addComment(new Comment($this->parseComment($string, $i)));
                        } else if ($current === '"' || $current === "'") {
                            $currentString = $stringEndsWith = $current;
                            $status = 'instr';
                            $from[] = 'iv';
                        } else if ($current === '(') {
                            $function = $subValue;
                            $subValue .= $current;
                            $bracketCount = 1;
                            $status = 'inbrck';
                            $from[] = 'iv';
                        } else if ($current === ',' || $current === '!') {
                            if (($trimmed = trim($subValue, self::$whitespace)) !== '') {
                                $subValues[] = $trimmed;
                                $subValue = '';
                            }
                            $subValues[] = $current;
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

                            // Remove right spaces added by Block::addProperty
                            $valid = $this->propertyIsValid(rtrim($property));
                            if ($valid || !$this->discardInvalidProperties) {
                                end($stack)->addProperty(new Property($property, $subValues, $this->currentLine));
                            } else {
                                $this->logger->log("Removed invalid property: $property", Logger::WARNING, $this->currentLine);
                            }
                            if (!$valid && !$this->discardInvalidProperties) {
                                $this->logger->log(
                                    "Invalid property in {$this->cssLevel}: $property",
                                    Logger::WARNING,
                                    $this->currentLine
                                );
                            }

                            $property = $value = '';
                            $subValues = array();
                        }
                        if ($current === '}') {
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
                            $currentString = $stringEndsWith = $current;
                            $quotedString = $function === 'format';
                            continue;
                        } else if ($current === '(') {
                            ++$bracketCount;
                        } else if ($current === ')' && --$bracketCount === 0) {
                            $status = array_pop($from); // Go back to prev parser
                            $function = '';
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
                        $this->logger->log('Fixed incorrect newline in string', Logger::WARNING, $this->currentLine);
                    }

                    $currentString .= $current;

                    if ($current === $stringEndsWith && !self::escaped($string, $i)) {
                        $status = array_pop($from);
                        if ($property !== 'content' && $property !== 'quotes' && !$quotedString) {
                            $currentString = self::removeQuotes($currentString);
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

                /* Case in at rule */
                case 'at':
                    if ($this->isToken($string, $i)) {
                        if ($current === '"' || $current === '\'') {
                            $status = 'instr';
                            $from[] = 'at';
                            $quotedString = true;
                            $currentString = $stringEndsWith = $current;
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
                            if (trim($subValue) !== '') {
                                $subValues[] = $subValue;
                            }

                            $status = $this->nextParserInAtRule($string, $i);
                            if ($status === 'ip') {
                                $selector = ' ';
                            }
                            $from[] = 'is';

                            $stack[] = end($stack)->addBlock(new AtBlock($subValues));

                            $subValues = array();
                            $subValue = '';
                        } else if ($current === '/' && $string{$i + 1} === '*') {
                            end($stack)->addComment(new Comment($this->parseComment($string, $i)));
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

        return $parsed;
    }

    /**
     * @todo Refactor
     * @param Selector $selector
     * @param array $selectorSeparate
     */
    protected function setSubSelectors(Selector $selector, array $selectorSeparate)
    {
        $lastPosition = 0;
        $selectorSeparate[] = strlen($selector->getName());

        $lastSelectorSeparateKey = count($selectorSeparate) - 1;
        foreach ($selectorSeparate as $num => $pos) {
            if ($num === $lastSelectorSeparateKey) {
                ++$pos;
            }

            $selector->subSelectors[] = substr($selector->getName(), $lastPosition, $pos - $lastPosition - 1);
            $lastPosition = $pos;
        }
    }

    /**
     * @todo If comment is to end of file
     * @param string $string
     * @param int $i
     * @return string
     */
    protected function parseComment($string, &$i)
    {
        $i += 2; // /*
        $commentLength = strpos($string, '*/', $i);
        $commentLength = $commentLength !== false  ? $commentLength - $i :  strlen($string) - $i - 1;

        if ($commentLength > 0) {
            $this->currentLine += substr_count($string, "\n", $i, $commentLength);
            $comment = substr($string, $i, $commentLength);
        } else {
            $comment = '';
        }

        $i += $commentLength + 1; // */
        return $comment;
    }

        /**
     * Process charset, namespace or import at rule
     * @param array $subValues
     * @param array $stack
     */
    protected function processAtRule(array $subValues, array $stack)
    {
        /** @var Parsed $parsed */
        $parsed = $stack[0];
        $rule = strtolower(array_shift($subValues));

        switch ($rule) {
            case 'charset':
                if (!empty($parsed->charset)) {
                   $this->logger->log("Only one @charset may be in document, previous is ignored",
                       Logger::WARNING,
                       $this->currentLine
                   );
                }

                $parsed->charset = $subValues[0];

                if (!empty($parsed->elements) || !empty($parsed->import) || !empty($parsed->namespace)) {
                    $this->logger->log("@charset must be before anything", Logger::WARNING, $this->currentLine);
                }
                break;

            case 'namespace':
                if (isset($subValues[1])) {
                    $subValues[0] = ' ' . $subValues[0];
                }

                $parsed->namespace[] = implode(' ', $subValues);
                if (!empty($parsed->elements)) {
                    $this->logger->log("@namespace must be before selectors", Logger::WARNING, $this->currentLine);
                }
                break;

            case 'import':
                $parsed->import[] = new LineAt($rule, $subValues);
                if (!empty($parsed->elements)) {
                    $this->logger->log("@import must be before anything selectors", Logger::WARNING, $this->currentLine);
                } else if (isset($stack[1])) {
                    $this->logger->log("@import cannot be inside @media", Logger::WARNING, $this->currentLine);
                }
                break;

            default:
                $lineAt = new LineAt($rule, $subValues);
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

        $decAdd = hexdec($add);
        if ($decAdd > 47 && $decAdd < 58 || $decAdd > 64 && $decAdd < 91 || $decAdd > 96 && $decAdd < 123) {
            $this->logger->log(
                "Replaced unicode notation: Changed \\$add to " . chr($decAdd),
                Logger::INFORMATION,
                $this->currentLine
            );
            $add = chr($decAdd);
            $replaced = true;
        } else {
            $add = $add === ' ' ? '\\' . $add : trim('\\' . $add);
        }

        if (isset($string{$i + 1}) && ctype_xdigit($string{$i + 1}) && ctype_space($string{$i})
                        && !$replaced || !ctype_space($string{$i})) {
            $i--;
        }

        if ($add !== '\\' || !$this->removeBackSlash || strpos(self::$tokensList, $string{$i + 1}) !== false) {
            return $add;
        }

        if ($add === '\\') {
            $this->logger->log('Removed unnecessary backslash', Logger::INFORMATION, $this->currentLine);
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
            $this->logger->log('Added semicolon to the end of declaration', Logger::WARNING, $this->currentLine);
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
            strpos(self::$allProperties[$property], $this->cssLevel) !== false);
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
     * @param string $string
     * @return mixed
     */
    public static function removeQuotes($string)
    {
        $withoutQuotes = substr($string, 1, -1);
        if (preg_match('|[' . self::$stringTokens .']|uis', $withoutQuotes)) { // If string contains whitespace
            return self::normalizeQuotes($string);
        }

        return $withoutQuotes;
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
     * Explodes a string as explode() does, however, not if $sep is escaped or within a string.
     * @param string $sep separator
     * @param string $string
     * @return array
     */
    public static function explodeWithoutString($sep, $string)
    {
        if ($string === '' || $string === $sep) {
            return array();
        }

        $insideString = false;
        $to = '';
        $output = array(0 => '');
        $num = 0;

        for ($i = 0, $len = strlen($string); $i < $len; $i++) {
            if ($insideString) {
                if ($string{$i} === $to && !self::escaped($string, $i)) {
                    $insideString = false;
                }
            } else {
                if ($string{$i} === $sep && !self::escaped($string, $i)) {
                    ++$num;
                    $output[$num] = '';
                    continue;
                } else if ($string{$i} === '"' || $string{$i} === '\'' || $string{$i} === '(' && !self::escaped($string, $i)) {
                    $insideString = true;
                    $to = ($string{$i} === '(') ? ')' : $string{$i};
                }
            }

            $output[$num] .= $string{$i};
        }

        return $output;
    }
}