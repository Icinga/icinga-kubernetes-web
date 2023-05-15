<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\StatefulSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\TimeAgo;

class StatefulSetDetail extends BaseHtmlElement
{
    /** @var StatefulSet */
    private $statefulSet;

    protected $tag = 'div';

    public function __construct($statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')         => $this->statefulSet->name,
            t('Namespace')    => $this->statefulSet->namespace,
            t('UID')          => $this->statefulSet->uid,
            t('Service Name') => $this->statefulSet->service_name,
            t('Created')      => new TimeAgo($this->statefulSet->created->getTimestamp())
        ]));

        $this->addHtml(new ConditionTable($this->statefulSet, (new StatefulSetCondition())->getColumnDefinitions()));

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'stateful-set-pods']),
            new HtmlElement('h2', null, new Text(t('Pods'))),
            new PodList($this->statefulSet->pods)
        ));
    }
}
