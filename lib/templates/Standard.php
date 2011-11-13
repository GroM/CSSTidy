<?php
namespace CSSTidy\Template;

class Standard extends \CSSTidy\Template
{
    public $beforeAtRule = '<span class="at">';

    public $bracketAfterAtRule = "</span> <span class=\"format\">{</span>\n";

    public $atRuleClosingBracket = "\n<span class=\"format\">}</span>\n\n";

    public $indentInAtRule = '';

    public $lastLineInAtRule = "\n";

    public $beforeSelector = '<span class="selector">';

    public $selectorOpeningBracket = "</span> <span class=\"format\">{</span>\n";

    public $beforeProperty = '<span class="property">';

    public $beforeValue = '</span><span class="value">';

    public $afterValueWithSemicolon = "</span><span class=\"format\">;</span>\n";

    public $selectorClosingBracket = '<span class="format">}</span>';

    public $spaceBetweenBlocks = "\n\n";

    public $beforeComment = '<span class="comment">';

    public $afterComment = "</span>\n";
}