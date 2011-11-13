<?php
namespace CSSTidy\Template;

require_once __DIR__ . '/High.php';

class Highest extends High
{
    public $bracketAfterAtRule = "</span><span class=\"format\">{</span>";

    public $atRuleClosingBracket = "<span class=\"format\">}</span>";

    public $lastLineInAtRule = '';

    public $selectorOpeningBracket = "</span><span class=\"format\">{</span>";

    public $spaceBetweenBlocks = '';
}