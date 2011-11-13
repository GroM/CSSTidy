<?php
namespace CSSTidy\Template;

require_once __DIR__ . '/Standard.php';

class Low extends Standard
{
    public $indentInAtRule = "\t";

    public $selectorOpeningBracket = "</span>\n<span class=\"format\">{</span>\n";

    public $beforeProperty = "\t<span class=\"property\">";

    public $beforeValue = '</span> <span class="value">';
}