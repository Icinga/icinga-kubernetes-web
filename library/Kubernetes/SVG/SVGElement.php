<?php

namespace Icinga\Module\Kubernetes\SVG;

class SVGElement
{
    protected $attributes = [];

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function getAttributes(): string
    {
        $attributeString = '';
        foreach ($this->attributes as $name => $value) {
            $attributeString .= "$name=\"$value\"";
        }

        return $attributeString;
    }
}
