<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class PersistentVolumeDetail extends BaseHtmlElement
{
    use Translation;

    protected PersistentVolume $persistentVolume;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'persistent-volume-detail'];

    public function __construct(PersistentVolume $persistentVolume)
    {
        $this->persistentVolume = $persistentVolume;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details([
                $this->translate('Name')               => $this->persistentVolume->name,
                $this->translate('UID')                => $this->persistentVolume->uid,
                $this->translate('Resource Version')   => $this->persistentVolume->resource_version,
                $this->translate('Created')            => $this->persistentVolume->created->format('Y-m-d H:i:s'),
                $this->translate('Phase')              => new HtmlElement(
                    'span',
                    new Attributes([
                        'class' => 'persistent-volume-phase pv-phase-' . strtolower($this->persistentVolume->phase)
                    ]),
                    new Text($this->persistentVolume->phase)
                ),
                $this->translate('Volume Mode')        => $this->persistentVolume->volume_mode,
                $this->translate('Volume Source Type') => $this->persistentVolume->volume_source_type,
                $this->translate('Reclaim Policy')     => $this->persistentVolume->reclaim_policy,
                $this->translate('Storage Class')      => $this->persistentVolume->storage_class,
                $this->translate('Access Modes')       => implode(
                    ', ',
                    AccessModes::asNames($this->persistentVolume->access_modes)
                ),
                $this->translate('Capacity')           => Format::bytes($this->persistentVolume->capacity / 1000),
            ])
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PERSISTENT_VOLUME_CLAIMS)) {
            $this->addHtml(new HtmlElement(
                'section',
                new Attributes(['class' => 'persistent-volume-claims']),
                new HtmlElement('h2', null, new Text($this->translate('Claims'))),
                new PersistentVolumeClaimList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PERSISTENT_VOLUME_CLAIMS,
                    $this->persistentVolume->pvc
                ))
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_EVENTS,
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->persistentVolume->uuid))
                ))
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->persistentVolume->yaml));
        }
    }
}
