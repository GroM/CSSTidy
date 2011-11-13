<?php
namespace CSSTidy;

class Template
{
    public $beforeAtRule;

    public $atRuleClosingBracket;

    public $indentInAtRule;

    public $lastLineInAtRule;

    public $bracketAfterAtRule;

    public $beforeSelector;

    public $selectorOpeningBracket;

    public $beforeProperty;

    public $beforeValue;

    public $afterValueWithSemicolon;

    public $selectorClosingBracket;

    public $spaceBetweenBlocks;

    public $beforeComment;

    public $afterComment;

    /**
     * @return Template
     */
    public function getWithoutHtml()
    {
        $return = clone $this;
        foreach ($return as &$value) {
            $value = strip_tags($value);
        }

        return $return;
    }

    /**
     * @static
     * @param string $content
     * @return Template
     * @throws \Exception
     */
    public static function loadFromString($content)
    {
        $content = strip_tags($content, '<span>');
        $content = str_replace("\r\n", "\n", $content); // Unify newlines (because the output also only uses \n)
        $parts = explode('|', $content);

        if (count($parts) !== 14) {
            throw new \Exception("Template must contains 14 parts");
        }

        $template = new self;
        $template->beforeAtRule = $parts[0];
        $template->bracketAfterAtRule = $parts[1];
        $template->beforeSelector = $parts[2];
        $template->selectorOpeningBracket = $parts[3];
        $template->beforeProperty = $parts[4];
        $template->beforeValue = $parts[5];
        $template->afterValueWithSemicolon = $parts[6];
        $template->selectorClosingBracket = $parts[7];
        $template->spaceBetweenBlocks = $parts[8];
        $template->atRuleClosingBracket = $parts[9];
        $template->indentInAtRule = $parts[10];
        $template->beforeComment = $parts[11];
        $template->afterComment = $parts[12];
        $template->lastLineInAtRule = $parts[13];

        return $template;
    }
}