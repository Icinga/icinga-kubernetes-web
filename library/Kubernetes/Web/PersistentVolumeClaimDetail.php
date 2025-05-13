<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaimCondition;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
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

    protected $defaultAttributes = ['class' => 'object-detail pvc-detail'];

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
                    new Attributes([
                        'class' => 'persistent-volume-claim-phase pvc-phase-' . strtolower($this->pvc->phase)
                    ]),
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
            new PersistentVolumeClaimEnvironment($this->pvc),
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PODS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                (new ResourceList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PODS,
                    $this->pvc->pod
                )))
                    ->setViewMode(ViewMode::Common)
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                (new ResourceList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->pvc->uuid))))
                    ->setViewMode(ViewMode::Common)
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->pvc->yaml));
        }
    }
}
