<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Widget\Icon;

class ConfigMapDetail extends BaseHtmlElement
{
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
                    t('Immutable') => new Icon($this->configMap->immutable ? 'check' : 'xmark')
                ]
            )),
            new Labels($this->configMap->label),
            new Data($this->configMap->data->execute())
        );
    }
}
