<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\AccessModes;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Util\Format;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;

class PersistentVolumeDetail extends BaseHtmlElement
{
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
                t('Phase')              => $this->persistentVolume->phase,
                t('Capacity')           => Format::bytes($this->persistentVolume->capacity / 1000),
                t('Access Modes')       => implode(', ', AccessModes::asNames($this->persistentVolume->access_modes)),
                t('Volume Mode')        => ucfirst(Str::camel($this->persistentVolume->getVolumeMode())),
                t('Volume Source Type') => $this->persistentVolume->volume_source_type,
                t('Reclaim Policy')     => $this->persistentVolume->reclaim_policy,
                t('Storage Class')      => ucfirst(Str::camel($this->persistentVolume->storage_class))
            ])),
            new HtmlElement(
                'section',
                new Attributes(['class' => 'persistent-volume-claims']),
                new HtmlElement('h2', null, new Text(t('Claims'))),
                new PersistentVolumeClaimList($this->persistentVolume->pvc)
            )
        );
    }
}
