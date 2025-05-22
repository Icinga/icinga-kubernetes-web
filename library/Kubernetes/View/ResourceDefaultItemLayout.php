<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\View;

use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Secret;
use ipl\Html\HtmlDocument;
use ipl\Web\Layout\ItemLayout;

class ResourceDefaultItemLayout extends ItemLayout
{
    protected function assembleMain(HtmlDocument $container): void
    {
        switch (true) {
            case $this->item instanceof Ingress:
            case $this->item instanceof PersistentVolume:
            case $this->item instanceof PersistentVolumeClaim:
            case $this->item instanceof Secret:
                $this->registerHeader($container);
                $this->registerFooter($container);

                break;
            default:
                parent::assembleMain($container);
        }
    }

    protected function assembleHeader(HtmlDocument $container): void
    {
        switch (true) {
            case $this->item instanceof Secret:
            case $this->item instanceof PersistentVolume:
            case $this->item instanceof PersistentVolumeClaim:
                $this->registerTitle($container);
                $this->registerExtendedInfo($container);

                break;
            default:
                parent::assembleHeader($container);
        }
    }
}
