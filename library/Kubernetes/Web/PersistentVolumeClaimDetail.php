<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;

class PersistentVolumeClaimDetail extends BaseHtmlElement
{
    use Translation;

    protected PersistentVolumeClaim $pvc;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'pvc-list'];

    public function __construct(PersistentVolumeClaim $pvc)
    {
        $this->pvc = $pvc;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->pvc, [
                $this->translate('Phase')                => new HtmlElement(
                    'span',
                    new Attributes(['class' => 'pvc-phase']),
                    new Text($this->pvc->phase)
                ),
                $this->translate('Volume Name')          => $this->pvc->volume_name ??
                    new EmptyState($this->translate('None')),
                $this->translate('Volume Mode')          => $this->pvc->volume_mode,
                $this->translate('Storage Class')        => $this->pvc->storage_class,
                $this->translate('Desired Access Modes') => implode(
                    ', ',
                    AccessModes::asNames($this->pvc->desired_access_modes)
                ),
                $this->translate('Actual Access Modes')  => $this->pvc->actual_access_modes !== null ?
                    implode(', ', AccessModes::asNames($this->pvc->actual_access_modes)) :
                    new EmptyState($this->translate('None')),
                $this->translate('Minimum Capacity')     => Format::bytes($this->pvc->minimum_capacity / 1000),
                $this->translate('Actual Capacity')      => $this->pvc->actual_capacity !== null ?
                    Format::bytes($this->pvc->actual_capacity / 1000) :
                    new EmptyState($this->translate('None'))
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
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->pvc->uuid))
                )
            ),
            new Yaml($this->pvc->yaml)
        );
    }
}
