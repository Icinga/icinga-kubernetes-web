<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use Icinga\Module\Kubernetes\TBD\AccessModes;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;

class PersistentVolumeClaimDetail extends BaseHtmlElement
{
    /** @var PersistentVolumeClaim */
    protected $pvc;

    protected $defaultAttributes = [
        'class' => 'persistent-volume-claim-detail',
    ];

    protected $tag = 'div';

    public function __construct(PersistentVolumeClaim $pvc)
    {
        $this->pvc = $pvc;
    }

    protected function assemble()
    {
        $volumeMode = $this->pvc->volume_mode ?? PersistentVolumeClaim::DEFAULT_VOLUME_MODE;
        $this->addHtml(new Details([
            t('Name')          => $this->pvc->name,
            t('Created')       => $this->pvc->created->format('Y-m-d H:i:s'),
            t('Capacity')      => Format::bytes($this->pvc->actual_capacity / 1000),
            t('Access Modes')  => implode(', ', AccessModes::asNames($this->pvc->actual_access_modes)),
            t('Volume Mode')   => ucfirst(Str::camel($volumeMode)),
            t('Storage Class') => ucfirst(Str::camel($this->pvc->storage_class)),
        ]));

        $this->addHtml(
            new Labels($this->pvc->label),
            new ConditionTable($this->pvc, (new PersistentVolumeClaimCondition())->getColumnDefinitions()),
        );

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'persistent-volume-claims']),
            new HtmlElement('h2', null, new Text(t('Claims'))),
            new PersistentVolumeList($this->pvc->persistent_volume)
        ));
    }
}
