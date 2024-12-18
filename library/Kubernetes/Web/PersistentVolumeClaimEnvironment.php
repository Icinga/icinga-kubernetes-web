<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class PersistentVolumeClaimEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected PersistentVolumeClaim $pvc)
    {
    }

    public function render(): ValidHtml
    {
        $childrenFilter = Filter::equal('pod.pvc.name', $this->pvc->name);

        $pods = $this->pvc->pod
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->pvc, $this->pvc->persistent_volume, $pods, null, $childrenFilter)
            );
    }
}
