<?php
namespace CSSTidy\Template;

require_once __DIR__ . '/Standard.php';

class High extends Standard
{
    public $atRuleClosingBracket = "\n<span class=\"format\">}</span>\n";

    public $selectorOpeningBracket = "</span><span class=\"format\">{</span>";

    public $afterValueWithSemicolon = "</span><span class=\"format\">;</span>";

    public $spaceBetweenBlocks = "\n";

    public $afterComment = "</span>";
}