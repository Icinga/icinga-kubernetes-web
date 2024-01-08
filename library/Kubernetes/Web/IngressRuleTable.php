<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;

class IngressRuleTable extends Table
{
    protected $ingress;

    protected $backend;

    protected $ruleColumnDefinitions;

    protected $backendColumnDefinitions;

    protected $tlsColumnDefinitions;

    protected $defaultAttributes = [
        'class' => 'common-table collapsible'
    ];

    public function __construct(
        $ingress,
        $backend,
        array $ruleColumnDefinitions,
        array $backendColumnDefinitions,
        array $tlsColumnDefinitions
    ) {
        $this->ingress = $ingress;
        $this->backend = $backend;
        $this->ruleColumnDefinitions = $ruleColumnDefinitions;
        $this->backendColumnDefinitions = $backendColumnDefinitions;
        $this->tlsColumnDefinitions = $tlsColumnDefinitions;
    }

    public function assemble()
    {
        $this->addWrapper(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text(t('Rules')))
        ));

        $header = new HtmlElement('tr');
        foreach ($this->ruleColumnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        foreach ($this->backendColumnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        foreach ($this->tlsColumnDefinitions as $label) {
            $header->addHtml(new HtmlElement('th', null, Text::create($label)));
        }
        $this->getHeader()->addHtml($header);

        foreach ($this->ingress->ingress_rule as $rule) {
            $row = new HtmlElement('tr');
            foreach ($this->ruleColumnDefinitions as $column => $_) {
                $content = Text::create($rule->$column);
                $row->addHtml(new HtmlElement('td', null, $content));
            }
            $backend = $this->backend;
            foreach ($rule->$backend as $backend) {
                foreach ($this->backendColumnDefinitions as $column => $_) {
                    $content = Text::create($backend->$column);
                    $row->addHtml(new HtmlElement('td', null, $content));
                }
            }
            foreach ($this->ingress->ingress_tls as $ingressTls) {
                foreach ($this->tlsColumnDefinitions as $column => $_) {
                    $content = Text::create($ingressTls->$column);
                    $row->addHtml(new HtmlElement('td', null, $content));
                }
            }
            $this->addHtml($row);
        }
    }
}
