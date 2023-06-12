<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
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
        $details = new HtmlElement('div');
        $details->addHtml(new HtmlElement('h2', null, new Text('Details')));
        $details->addHtml(new HorizontalKeyValue(t('Name'), $this->pvc->name));
        $details->addHtml(new HorizontalKeyValue(t('Created'), $this->pvc->created->format('Y-m-d H:i:s')));
        $details->addHtml(new HorizontalKeyValue(t('Volume Name'), $this->pvc->volume_name));

        $labels = new HtmlElement(
            'section',
            new Attributes(['class' => 'labels']),
            new HtmlElement('h2', null, new Text(t('Labels')))
        );
        /** @var Label $label */
        foreach ($this->pvc->label as $label) {
            $labels->addHtml(new HorizontalKeyValue($label->name, $label->value));
        }
        $this->addHtml(
            $details,
            new ConditionTable($this->pvc, (new PersistentVolumeClaimCondition())->getColumnDefinitions()),
            $labels,
        );
    }
}
