<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\PodPvc;
use Icinga\Module\Kubernetes\Model\PodVolume;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;

class ContainerMountTable extends Table
{
    protected $container;

    protected $defaultAttributes = [
        'class' => 'common-table collapsible'
    ];

    protected $mountColumnDefinitions;

    protected $volumeColumnDefinitions;

    public function __construct($container, array $mountColumnDefinitions, array $volumeColumnDefinitions)
    {
        $this->container = $container;
        $this->mountColumnDefinitions = $mountColumnDefinitions;
        $this->volumeColumnDefinitions = $volumeColumnDefinitions;
    }

    protected function assemble(): void
    {
        $header = new HtmlElement('tr');
        foreach ($this->mountColumnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }

        foreach ($this->volumeColumnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }

        $this->getHeader()->addHtml($header);

        foreach ($this->container->mount as $mount) {
            // PVC
            $podPvc = PodPvc::on(Database::connection())
                ->filter(
                    Filter::all(
                        Filter::equal('pod_uuid', $this->container->pod_uuid),
                        Filter::equal('volume_name', $mount->volume_name)
                    )
                )
                ->first();

            $row = new HtmlElement('tr');
            if ($podPvc !== null) {
                $pvc = PersistentVolumeClaim::on(Database::connection())
                    ->filter(Filter::all(Filter::equal('pvc.name', $podPvc->claim_name)))->first();

                foreach ($this->mountColumnDefinitions as $column => $_) {
                    $content = Text::create($mount->$column ?? '-');
                    $row->addHtml(new HtmlElement('td', null, $content));
                }
                foreach ($this->volumeColumnDefinitions as $column => $_) {
                    if ($column === 'source') {
                        $content = new Link(
                            $podPvc->claim_name,
                            Links::pvc($pvc),
                            ['class' => 'subject']
                        );
                    } else {
                        $content = Text::create('PersistentVolumeClaim');
                    }
                    $row->addHtml(new HtmlElement('td', null, $content));
                }
                $this->addHtml($row);
                continue;
            }

            // Volume
            $volume = PodVolume::on(Database::connection())
                ->filter(
                    Filter::all(
                        Filter::equal('pod_uuid', $this->container->pod_uuid),
                        Filter::equal('volume_name', $mount->volume_name)
                    )
                )
                ->first();

            foreach ($this->mountColumnDefinitions as $column => $_) {
                $content = Text::create($mount->$column ?? '-');
                $row->addHtml(new HtmlElement('td', null, $content));
            }

            foreach ($this->volumeColumnDefinitions as $column => $_) {
                if ($column === 'source') {
                    $source = json_decode($volume->$column);
                    if (isset($source->name)) {
                        $content = Text::create($source->name);
                        $row->addHtml(new HtmlElement('td', null, $content));
                    }
                    if (isset($source->path)) {
                        $content = Text::create($source->path);
                        $row->addHtml(new HtmlElement('td', null, $content));
                    }
                } else {
                    $content = Text::create($volume->$column);
                    $row->addHtml(new HtmlElement('td', null, $content));
                }
            }
            $this->addHtml($row);
        }

        $this->addWrapper(
            new HtmlElement(
                'section',
                new Attributes(['class' => 'container-mounts']),
                new HtmlElement('h2', null, new Text(t('Mounts')))
            )
        );
    }
}
