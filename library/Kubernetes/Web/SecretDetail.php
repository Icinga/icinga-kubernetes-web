<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\EmptyState;
use Icinga\Module\Kubernetes\Model\Secret;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;

class SecretDetail extends BaseHtmlElement
{
    /** @var Secret */
    protected $secret;

    protected $defaultAttributes = [
        'class' => 'secret-detail',
    ];

    protected $tag = 'div';

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')      => $this->secret->name,
                t('Namespace') => $this->secret->namespace,
                t('Type')      => $this->secret->type,
                t('Created')   => $this->secret->created->format('Y-m-d H:i:s')
            ])
        );

        $this->addHtml(
            new Labels($this->secret->label)
        );

        $this->addHtml(new HtmlElement('h2', null, new Text('Data')));

        $iterator = $this->secret->data->getIterator();

        if (! $iterator->valid()) {
            $this->addHtml(new EmptyState(t('No data to display')));
        } else {
            foreach ($this->secret->data as $secretData) {
                $this->addHtml(
                    new HtmlElement(
                        'div',
                        new Attributes([
                            'class'               => 'collapsible',
                            'data-visible-height' => 100
                        ]),
                        new HtmlElement('h4', null, new Text($secretData->name)),
                        new HtmlElement('pre', null, new Text($secretData->value)),
                        new HtmlElement('h4', null, new Text(strlen($secretData->value) . " Bytes"))
                    ),
                );
            }
        }
    }
}
