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

            if (isset($line{0}) && isset($line{1}) && $line{0} === '-' && $line{1} === '-') {
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
                case '--SETTINGS--':
                    list($n, $v) = array_map('trim', explode('=', $line, 2));
                    $v = eval("return $v;");
                    $this->settings[$n] = $v;
                    break;
                case '--PRINT--':
                    $this->expect .= $line . "\n";
                    break;
            }
        }

        $this->expect = trim($this->expect);
    }
    
    /**
     * Implements SimpleExpectation::test().
     * @param $filename Filename of test file to test.
     */
    function test($filename = false)
    {
        if ($filename) $this->load($filename);
        $configure = new CSSTidy\Configuration($this->settings);

        if (!isset($this->settings['template'])) {
            $configure->setTemplate(new TestingTemplate);
        }

        $css = new \CSSTidy\CSSTidy($configure);
        $output = $css->process($this->css);
        $this->actual = $output->plain();
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
                $this->convertToDiff($this->expect),
                $this->convertToDiff($this->actual)
            )
        );

        $renderer = new Text_Diff_Renderer_Parallel;
        $renderer->original = 'Expected';
        $renderer->final    = 'Actual';

        $message .= $renderer->render($diff);
        return $message;
    }

    /**
     * @param string $data
     * @return array
     */
    protected function convertToDiff($data)
    {
        return explode("\n", $data);
    }
}
