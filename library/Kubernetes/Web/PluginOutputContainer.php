<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\BaseHtmlElement;
use phpDocumentor\Reflection\Types\Static_;

class PluginOutputContainer extends BaseHtmlElement
{
    protected const DEFAULT_CLASS = 'plugin-output state-reason detail';

    protected $tag = 'div';

    public function __construct(PluginOutput $output)
    {
        $this->setHtmlContent($output);

        $this->getAttributes()->registerAttributeCallback('class', function () use ($output) {
            return $output->isHtml() ? static::DEFAULT_CLASS : self::DEFAULT_CLASS . ' preformatted';
        });
    }
}
