<?php
/**
 * Exception TdTrung\Chalk
 *
 * @package TdTrung\Chalk
 * @author  Tran Dinh Trung <trandinhtrung176@gmail.com>
 */

namespace TdTrung\Chalk;

class StyleChain
{
    public $styles = [];
    private $colorInstance;

    public function __construct($style, Chalk $colorInstance)
    {
        array_push($this->styles, $style);
        $this->colorInstance = $colorInstance;
    }

    public function __invoke()
    {
        return $this->colorInstance->apply($this->styles, ...func_get_args());
    }

    public function __get($prop)
    {
        $other = $this->colorInstance->{$prop};
        $this->merge($other);

        return $this;
    }

    public function __call($method, $arguments)
    {

        if ($this->colorInstance->isTwoStageFns($method)) {
            $result = $this->colorInstance->$method(...$arguments);
            $this->merge($result);
            return $this;
        }

        $other = $this->colorInstance->{$method};
        $this->merge($other);

        return $this->__invoke(...$arguments);
    }

    private function merge(StyleChain $other)
    {
        $this->styles = array_merge($this->styles, $other->styles);
    }
}
