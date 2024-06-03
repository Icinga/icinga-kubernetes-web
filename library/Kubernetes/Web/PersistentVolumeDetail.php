<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;

class PersistentVolumeDetail extends BaseHtmlElement
{
    use Translation;

    /** @var PersistentVolume */
    protected $persistentVolume;

    protected $tag = 'div';

    public function __construct(PersistentVolume $persistentVolume)
    {
        $this->persistentVolume = $persistentVolume;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->persistentVolume, [
                $this->translate('Phase')              => $this->persistentVolume->phase,
                $this->translate('Capacity')           => Format::bytes($this->persistentVolume->capacity / 1000),
                $this->translate('Access Modes')       => implode(
                    ', ',
                    AccessModes::asNames($this->persistentVolume->access_modes)
                ),
                $this->translate('Volume Mode')        => ucfirst(Str::camel(
                    $this->persistentVolume->getVolumeMode()
                )),
                $this->translate('Volume Source Type') => $this->persistentVolume->volume_source_type,
                $this->translate('Reclaim Policy')     => $this->persistentVolume->reclaim_policy,
                $this->translate('Storage Class')      => ucfirst(Str::camel($this->persistentVolume->storage_class))
            ])),
            new HtmlElement(
                'section',
                new Attributes(['class' => 'persistent-volume-claims']),
                new HtmlElement('h2', null, new Text($this->translate('Claims'))),
                new PersistentVolumeClaimList($this->persistentVolume->pvc)
            ),
            new Yaml($this->persistentVolume->yaml)
        );
    }
}
