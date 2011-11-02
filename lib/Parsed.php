<?php
namespace CSSTidy;

class Parsed
{
    /** @var array */
    public $css = array();

    /** @var array */
    public $tokens = array();

    /** @var string */
    public $charset = '';

    /** @var array */
    public $import = array();

    /** @var string */
    public $namespace = '';

    /** @var bool */
    protected $preserveCss;

    /** @var int */
    protected $mergeSelectors;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->preserveCss = $configuration->preserveCss;
        $this->mergeSelectors = $configuration->mergeSelectors;
    }

    /**
	 * Adds a token to $this->tokens
	 * @param int $type
	 * @param string $data
	 * @param bool $do add a token even if preserve_css is off
     * @return void
	 */
	public function addToken($type, $data, $do = false)
    {
		if ($this->preserveCss || $do) {
			$this->tokens[] = array($type, ($type === CSSTidy::COMMENT) ? $data : trim($data));
		}
	}

    /**
	 * Adds a property with value to the existing CSS code
	 * @param string $media
	 * @param string $selector
	 * @param string $property
	 * @param string $newValue
	 * @access private
	 * @version 1.2
	 */
	public function addProperty($media, $selector, $property, $newValue)
    {
		if ($this->preserveCss || trim($newValue) == '') {
			return;
		}

		if (isset($this->css[$media][$selector][$property])) {
			if ((CSSTidy::isImportant($this->css[$media][$selector][$property]) && CSSTidy::isImportant($newValue)) || !CSSTidy::isImportant($this->css[$media][$selector][$property])) {
				$this->css[$media][$selector][$property] = trim($newValue);
			}
		} else {
			$this->css[$media][$selector][$property] = trim($newValue);
		}
	}

    /**
	 * Adds CSS to an existing media/selector
	 * @param string $media
	 * @param string $selector
	 * @param array $css_add
	 * @version 1.1
	 */
	public function mergeCssBlocks($media, $selector, array $css_add)
    {
		foreach ($css_add as $property => $value) {
			$this->addProperty($media, $selector, $property, $value, false);
		}
	}

    /**
	 * Start a new media section.
	 * Check if the media is not already known,
	 * else rename it with extra spaces
	 * to avoid merging
	 *
	 * @param string $media
	 * @return string
	 */
	public function newMediaSection($media)
    {
		if ($this->preserveCss) {
			return $media;
		}

		// if the last @media is the same as this
		// keep it
		if (!$this->css || !is_array($this->css) || empty($this->css)) {
			return $media;
		}

		end($this->css);
		list($at,) = each($this->css);

		if ($at == $media) {
			return $media;
		}

		while (isset($this->css[$media])) {
			if (is_numeric($media)) {
				$media++;
            } else {
				$media .= " ";
            }
        }
		return $media;
	}

    /**
	 * Start a new selector.
	 * If already referenced in this media section,
	 * rename it with extra space to avoid merging
	 * except if merging is required,
	 * or last selector is the same (merge siblings)
	 *
	 * never merge @font-face
	 *
	 * @param string $media
	 * @param string $selector
	 * @return string
	 */
	public function newSelector($media, $selector)
    {
		if ($this->preserveCss) {
			return $selector;
		}

		$selector = trim($selector);
		if (strncmp($selector, "@font-face", 10) != 0) {
			if ($this->mergeSelectors != false) // WTF? mergeSelector and false?
				return $selector;

			if (!$this->css || !isset($this->css[$media]) || !$this->css[$media]) {
				return $selector;
            }

			// if last is the same, keep it
			end($this->css[$media]);
			list($sel,) = each($this->css[$media]);

			if ($sel == $selector) {
				return $selector;
			}
		}

		while (isset($this->css[$media][$selector])) {
			$selector .= " ";
        }

		return $selector;
	}

    	/**
	 * Start a new propertie.
	 * If already references in this selector,
	 * rename it with extra space to avoid override
	 *
	 * @param string $media
	 * @param string $selector
	 * @param string $property
	 * @return string
	 */
	public function newProperty($media, $selector, $property)
    {
		if ($this->preserveCss) {
			return $property;
		}

		if (!$this->css || !isset($this->css[$media][$selector]) || !$this->css[$media][$selector]) {
			return $property;
        }

		while (isset($this->css[$media][$selector][$property])) {
			$property .= " ";
        }

		return $property;
	}
}