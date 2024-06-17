<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;

class PersistentVolumeClaimDetail extends BaseHtmlElement
{
    use Translation;

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
                $this->translate('Phase')                => $this->pvc->phase,
                $this->translate('Desired Access Modes') => implode(
                    ', ',
                    AccessModes::asNames($this->pvc->desired_access_modes)
                ),
                $this->translate('Actual Access Modes')  => implode(
                    ', ',
                    AccessModes::asNames($this->pvc->actual_access_modes)
                ),
                $this->translate('Minimum Capacity')     => Format::bytes($this->pvc->minimum_capacity / 1000),
                $this->translate('Actual Capacity')      => Format::bytes($this->pvc->actual_capacity / 1000),
                $this->translate('Volume Name')          => $this->pvc->volume_name,
                $this->translate('Volume Mode')          => ucfirst(Str::camel($this->pvc->getVolumeMode())),
                $this->translate('Storage Class')        => ucfirst(Str::camel($this->pvc->storage_class))
            ])),
            new Labels($this->pvc->label),
            new Annotations($this->pvc->annotation),
            new ConditionTable($this->pvc, (new PersistentVolumeClaimCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->pvc->pod)
            ),
            new Yaml($this->pvc->yaml)
        );
    }
}
