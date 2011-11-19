<?php
class TestingTemplate extends \CSSTidy\Template
{
    public $beforeAtRule = '';

    public $bracketAfterAtRule = " {\n";

    public $atRuleClosingBracket = "}\n\n";

    public $indentInAtRule = '';

    public $lastLineInAtRule = "\n";

    public $beforeSelector = '';

    public $selectorOpeningBracket = " {\n";

    public $beforeProperty = '';

    public $beforeValue = '';

    public $afterValueWithSemicolon = ";\n";

    public $selectorClosingBracket = '}';

    public $spaceBetweenBlocks = "\n\n";

    public $beforeComment = '';

    public $afterComment = "\n";
}