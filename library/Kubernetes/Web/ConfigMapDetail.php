<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

class ConfigMapDetail extends BaseHtmlElement
{
    use Translation;

    protected ConfigMap $configMap;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'config-map-detail'];

    public function __construct(ConfigMap $configMap)
    {
        $this->configMap = $configMap;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails(
                $this->configMap,
                [
                    $this->translate('Immutable') => Icons::ready($this->configMap->immutable)
                ]
            )),
            new Labels($this->configMap->label),
            new Annotations($this->configMap->annotation),
            new Data($this->configMap->data),
            new Yaml($this->configMap->yaml)
        );
    }
}
