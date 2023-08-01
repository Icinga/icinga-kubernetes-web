<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\TBD\AccessModes;
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

    protected $defaultAttributes = [
        'class' => 'persistent-volume-detail',
    ];

    protected $tag = 'div';

    public function __construct(PersistentVolume $persistentVolume)
    {
        $this->persistentVolume = $persistentVolume;
    }

    protected function assemble()
    {
        $volumeMode = $this->persistentVolume->volume_mode ?? PersistentVolume::DEFAULT_VOLUME_MODE;
        $this->addHtml(new Details([
            t('Name')          => $this->persistentVolume->name,
            t('Created')       => $this->persistentVolume->created->format('Y-m-d H:i:s'),
            t('Capacity')      => Format::bytes($this->persistentVolume->capacity / 1000),
            t('Access Modes')  => implode(', ', AccessModes::asNames($this->persistentVolume->access_modes)),
            t('Volume Mode')   => ucfirst(Str::camel($volumeMode)),
            t('Storage Class') => ucfirst(Str::camel($this->persistentVolume->storage_class)),
        ]));

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'persistent-volume-claims']),
            new HtmlElement('h2', null, new Text(t('Claims'))),
            new PersistentVolumeClaimList($this->persistentVolume->pvc)
        ));
    }
}
