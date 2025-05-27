<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Detail;

use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Web\Widget\Annotations;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\Labels;
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
            new Annotations($this->configMap->annotation)
        );
    }
}
