<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\EmptyState;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class ConfigMapDetail extends BaseHtmlElement
{
    /** @var ConfigMap */
    protected $configMap;

    protected $defaultAttributes = [
        'class' => 'config-map-detail',
    ];

    protected $tag = 'div';

    public function __construct($configMap)
    {
        $this->configMap = $configMap;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')      => $this->configMap->name,
                t('Namespace') => $this->configMap->namespace,
                t('Created')   => $this->configMap->created->format('Y-m-d H:i:s')
            ])
        );

        $this->addHtml(
            new Labels($this->configMap->label)
        );

        $this->addHtml(new HtmlElement('h2', null, new Text('Data')));

        $iterator = $this->configMap->data->getIterator();

        if (! $iterator->valid()) {
            $this->addHtml(new EmptyState(t('No data to display')));
        } else {
            foreach ($iterator as $data) {
                $this->addHtml(
                    new HtmlElement(
                        'div',
                        new Attributes([
                            'class'               => 'collapsible',
                            'data-visible-height' => 100
                        ]),
                        new HtmlElement('h4', null, new Text($data->name)),
                        new HtmlElement('pre', null, new Text($data->value))
                    )
                );
            }
        }
    }
}
