<?php
namespace CSSTidy\Test;

/**@file
 * Script for unit testing, allows for more fine grained error reporting
 * when things go wrong.
 * @author Edward Z. Yang <admin@htmlpurifier.org>
 *
 * Required
 * unit-tets/Text : http://download.pear.php.net/package/Text_Diff-1.1.1.tgz
 * unit-tests/simpletest/ : http://downloads.sourceforge.net/project/simpletest/simpletest/simpletest_1.0.1/simpletest_1.0.1.tar.gz?r=&ts=1289748853&use_mirror=freefr
 *
 */

error_reporting(E_ALL ^ E_DEPRECATED);


class UnitTests
{
    public function run()
    {
        $this->includeLibrary();
        $this->includeSimpleTest();
        $this->includeTextDiff();

        $testFiles = array('test.csst.php');

        // Setup test files
        $test = new \TestSuite('CSSTidy unit tests');
        foreach ($testFiles as $testFile) {
            $filePath = "unit-tests/$testFile";

            if (!file_exists($filePath)) {
                throw new \Exception("Test file '$testFile' was not found");
            }

            require_once $filePath;
            list($x, $classSuffix) = explode('.', $testFile);
            $test->addTestClass("csstidy_test_$classSuffix");
        }

        if (\SimpleReporter::inCli()) {
            $reporter = new \TextReporter();
        } else {
            $reporter = new Reporter('UTF-8');
        }

        $test->run($reporter);
    }

    protected function includeTextDiff()
    {
        $location = __DIR__ . '/Text/';

        if (!file_exists($location . 'Diff.php')) {
            throw new \Exception("Text Diff must be downloaded into testing/Text directory. Please download library from http://download.pear.php.net/package/Text_Diff-1.1.1.tgz and unpack into properly folder");
        }

        require_once 'Text/Diff.php';
        require_once 'Text/Diff/Renderer.php';
    }

    protected function includeSimpleTest()
    {
        $location = __DIR__ . '/simpletest/';

        if (!file_exists($location . 'unit_tester.php')) {
            throw new \Exception("Simpletest must be downloaded into testintg/simpletest directory. Please download library from http://downloads.sourceforge.net/project/simpletest/simpletest/simpletest_1.0.1/simpletest_1.0.1.tar.gz and unpack into properly folder");
        }

        require_once $location . 'unit_tester.php';
        require_once $location . 'reporter.php';

        require_once __DIR__ . '/unit-tests/Reporter.php';
        require_once __DIR__ . '/unit-tests/class.csstidy_harness.php';
        require_once __DIR__ . '/unit-tests.inc';
    }

    protected function includeLibrary()
    {
        require_once __DIR__ . '/../lib/CSSTidy.php';
    }
}

$unitTest = new UnitTests();
$unitTest->run();