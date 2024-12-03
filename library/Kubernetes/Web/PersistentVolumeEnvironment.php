<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PersistentVolume;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;

class PersistentVolumeEnvironment implements ValidHtml
{
    private PersistentVolume $persistentVolume;

    public function __construct($persistentVolume)
    {
        $this->persistentVolume = $persistentVolume;
    }

    public function render(): ValidHtml
    {
        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->persistentVolume, null, $this->persistentVolume->pvc, null, null)
            );
    }
}
