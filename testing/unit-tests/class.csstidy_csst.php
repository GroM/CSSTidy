<?php

require_once 'Text_Diff_Renderer_Parallel.php';

/**
 * CSSTidy CSST expectation, for testing CSS parsing.
 */
class csstidy_csst extends SimpleExpectation
{
    /** Filename of test */
    var $filename;
    
    /** Test name */
    var $test;
    
    /** CSS for test to parse */
    var $css = '';
    
    /** Settings for csstidy */
    var $settings = array();
    
    /** Expected var_export() output of $css->css[41] (no at block) */
    var $expect = '';
    
    /** Boolean whether or not to use $css->css instead for $expect */
    var $fullexpect = false;

		/** Print form of CSS that can be tested **/
    var $print = false;

    /** Actual result */
    var $actual;
    
    /**
     * Loads this class from a file.
     * @param $filename String filename to load
     */
    function load($filename) {
        $this->filename = $filename;

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $state = null;

        foreach ($lines as $line) {

            if ($line{0} === '-' && $line{1} === '-') {
                // detected section
                $state = $line;
                continue;
            }

            switch ($state) {
                case '--TEST--':
                    $this->test = trim($line);
                    break;
                case '--CSS--':
                    $this->css .= $line . "\n";
                    break;
                case '--FULLEXPECT--':
                    $this->fullexpect = true;
                    $this->expect .= $line . "\n";
                    break;
                case '--EXPECT--':
                    $this->expect .= $line . "\n";
                    break;
                case '--SETTINGS--':
                    list($n, $v) = array_map('trim', explode('=', $line, 2));
                    $v = eval("return $v;");
                    $this->settings[$n] = $v;
                    break;
                case '--PRINT--':
                    $this->print = true;
                    $this->expect .= $line . "\n";
                    break;
            }
        }

        if ($this->print) {
            $this->expect = trim($this->expect);
        } else {
            if ($this->expect)
                $this->expect = eval("return ".$this->expect.";");
            if (!$this->fullexpect)
                $this->expect = array(41 => $this->expect);
        }
    }
    
    /**
     * Implements SimpleExpectation::test().
     * @param $filename Filename of test file to test.
     */
    function test($filename = false)
    {
        if ($filename) $this->load($filename);
        $configure = new CSSTidy\Configuration($this->settings);
        $css = new CSSTidyTest($configure);
        $output = $css->parse($this->css);

        if ($this->print) {
            $this->actual = $output->plain();
        } else {
            $this->actual = $css->getParsed()->css;
        }
        return $this->expect === $this->actual;
    }
    
    /**
     * Implements SimpleExpectation::testMessage().
     */
    function testMessage()
    {
        $message = $this->test . ' test at '. htmlspecialchars($this->filename);
        return $message;
    }
    
    /**
     * Renders the test with an HTML diff table.
     */
    function render()
    {
        $message = '<pre>'. htmlspecialchars($this->css) .'</pre>';
        $diff = new Text_Diff(
						'auto',
						array(
								explode("\n", $this->print?$this->expect:var_export($this->expect,true)),
								explode("\n", $this->print?$this->actual:var_export($this->actual,true))
						)
				);
        $renderer = new Text_Diff_Renderer_Parallel;
        $renderer->original = 'Expected';
        $renderer->final    = 'Actual';
        $message .= $renderer->render($diff);
        return $message;
    }
}

class CSSTidyTest extends CSSTidy\CSSTidy
{
    public function getParsed()
    {
        return $this->parsed;
    }
}
