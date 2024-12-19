<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PersistentVolume;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;

class PersistentVolumeEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected PersistentVolume $persistentVolume)
    {
    }

    public function render(): ValidHtml
    {
        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->persistentVolume, null, $this->persistentVolume->pvc, null, null)
            );
    }
}
