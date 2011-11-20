<?php
namespace CSSTidy;

/**
 * @property \CSSTidy\Configuration $configuration
 * @property \CSSTidy\Logger $logger
 * @property \CSSTidy\SelectorManipulate $selectorManipulate
 * @property \CSSTidy\Optimise $optimise
 */
class Container
{
    /** @var object[] */
    protected $services = array();

    public function __construct()
    {
        $cont = $this;
        $this->services = array(
            'logger' => function() {
                require_once __DIR__ . '/Logger.php';
                return new Logger;
            },
            'configuration' => function() {
                require_once __DIR__ . '/Configuration.php';
                return new Configuration;
            },
            'selectorManipulate' => function() {
                require_once __DIR__ . '/SelectorManipulate.php';
                return new SelectorManipulate;
            },
            'optimise' => function() use ($cont) {
                require_once __DIR__ . '/Optimise.php';
                return new Optimise($cont->logger, $cont->configuration, $cont->optimiseColor, $cont->optimiseNumber);
            },
            'optimiseColor' => function() use($cont) {
                require_once __DIR__ . '/optimise/Color.php';
                return new \CSSTidy\Optimise\Color($cont->logger, $cont->optimiseNumber);
            },
            'optimiseNumber' => function() use($cont) {
                require_once __DIR__ . '/optimise/Number.php';
                return new \CSSTidy\Optimise\Number($cont->logger, $cont->configuration->getConvertUnit());
            },
        );
    }

    /**
     * @param string $name
     * @return object
     * @throws \Exception
     */
    public function __get($name)
    {
        if (isset($this->services[$name])) {
            return $this->$name = $this->services[$name]();
        }

        throw new \Exception("Service with name '$name' not exists");
    }

    /**
     * @param string $name
     * @param object $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if (!is_object($value)) {
            throw new \Exception("Service '$name' must be object");
        }

        $this->$name = $value;
    }
}