<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

class ConfigMapDetail extends BaseHtmlElement
{
    use Translation;

    /** @var ConfigMap */
    protected $configMap;

    protected $tag = 'div';

    public function __construct($configMap)
    {
        $this->configMap = $configMap;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails(
                $this->configMap,
                [
                    $this->translate('Immutable') => Icons::ready($this->configMap->immutable)
                ]
            )),
            new Labels($this->configMap->label),
            new Data($this->configMap->data->execute())
        );
    }
}
