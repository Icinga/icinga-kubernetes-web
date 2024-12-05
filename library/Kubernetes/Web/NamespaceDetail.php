<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class NamespaceDetail extends BaseHtmlElement
{
    use Translation;

    protected NamespaceModel $namespace;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'namespace-detail'];

    public function __construct(NamespaceModel $namespace)
    {
        $this->namespace = $namespace;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details([
                $this->translate('Name')             => $this->namespace->name,
                $this->translate('UID')              => $this->namespace->uid,
                $this->translate('Resource Version') => $this->namespace->resource_version,
                $this->translate('Created')          => $this->namespace->created->format('Y-m-d H:i:s'),
                $this->translate('Phase')            => new HtmlElement(
                    'span',
                    new Attributes([
                        'class' => 'namespace-phase namespace-phase-' . strtolower($this->namespace->phase)
                    ]),
                    new Text($this->namespace->phase)
                )
            ]),
            new Labels($this->namespace->label),
            new Annotations($this->namespace->annotation)
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->namespace->uuid)))
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->namespace->yaml));
        }
    }
}
