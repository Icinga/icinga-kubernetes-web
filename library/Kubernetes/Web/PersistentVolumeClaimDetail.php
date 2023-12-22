<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;

class PersistentVolumeClaimDetail extends BaseHtmlElement
{
    /** @var PersistentVolumeClaim */
    protected $pvc;

    protected $tag = 'div';

    public function __construct(PersistentVolumeClaim $pvc)
    {
        $this->pvc = $pvc;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->pvc, [
                t('Phase')                => $this->pvc->phase,
                t('Desired Access Modes') => implode(', ', AccessModes::asNames($this->pvc->desired_access_modes)),
                t('Actual Access Modes')  => implode(', ', AccessModes::asNames($this->pvc->actual_access_modes)),
                t('Minimum Capacity')     => Format::bytes($this->pvc->minimum_capacity / 1000),
                t('Actual Capacity')      => Format::bytes($this->pvc->actual_capacity / 1000),
                t('Volume Name')          => $this->pvc->volume_name,
                t('Volume Mode')          => ucfirst(Str::camel($this->pvc->getVolumeMode())),
                t('Storage Class')        => ucfirst(Str::camel($this->pvc->storage_class))
            ])),
            new Labels($this->pvc->label),
            new ConditionTable($this->pvc, (new PersistentVolumeClaimCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Pods'))),
                new PodList($this->pvc->pod)
            )
        );
    }
}
