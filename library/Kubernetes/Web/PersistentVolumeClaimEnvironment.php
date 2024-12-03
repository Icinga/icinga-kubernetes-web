<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;

class PersistentVolumeClaimEnvironment implements ValidHtml
{
    private PersistentVolumeClaim $pvc;

    public function __construct($pvc)
    {
        $this->pvc = $pvc;
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
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->pvc, $this->pvc->persistent_volume, $pods, null, $childrenFilter)
            );
    }
}
