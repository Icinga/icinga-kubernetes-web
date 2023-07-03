<?php

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Donut;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\TimeAgo;

class DaemonSetDetail extends BaseHtmlElement
{
    protected $defaultAttributes = [
        'class' => 'daemon-set-detail'
    ];

    protected $tag = 'div';

    /** @var ReplicaSet */
    protected $daemonSet;

    public function __construct(DaemonSet $daemonSet)
    {
        $this->daemonSet = $daemonSet;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')                     => $this->daemonSet->name,
            t('Namespace')                => $this->daemonSet->namespace,
            t('UID')                      => $this->daemonSet->uid,
            t('Update Strategy')          => ucfirst(Str::camel($this->daemonSet->update_strategy)),
            t('Min Ready Seconds')        => $this->daemonSet->min_ready_seconds,
            t('Desired Number Scheduled') => $this->daemonSet->desired_number_scheduled,
            t('Current Number Scheduled') => $this->daemonSet->current_number_scheduled,
            t('Update Number Scheduled')  => $this->daemonSet->update_number_scheduled,
            t('Number Misscheduled')      => $this->daemonSet->number_misscheduled,
            t('Number Ready')             => $this->daemonSet->number_ready,
            t('Number Available')         => $this->daemonSet->number_available,
            t('Number Unavailable')       => $this->daemonSet->number_unavailable,
            t('Created')                  => new TimeAgo($this->daemonSet->created->getTimestamp())
        ]));

        $this->addHtml(
            new Labels($this->daemonSet->label),
            new ConditionTable($this->daemonSet, (new ReplicaSetCondition())->getColumnDefinitions())
        );
        $data = [
            $this->daemonSet->number_available,
            $this->daemonSet->number_ready - $this->daemonSet->number_available,
            $this->daemonSet->desired_number_scheduled - $this->daemonSet->number_ready,
            $this->daemonSet->number_unavailable
        ];
        $labels = [
            t('Available'),
            t('Ready but not yet available'),
            t('Not yet ready'),
            t('Not yet scheduled or failing')
        ];
        $donut = (new Donut())
            ->setData($data)
            ->setLabelCallback(function ($index) use ($labels) {
                return new HtmlElement('span', null, new Text($labels[$index]));
            });
        $this->addHtml($donut);

        $this->addHtml(new ConditionTable($this->daemonSet, (new ReplicaSetCondition())->getColumnDefinitions()));

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'resource-pods']),
            new HtmlElement('h2', null, new Text(t('Pods'))),
            new PodList($this->daemonSet->pods->with(['node']))
        ));

//        $this->addHtml(new HtmlElement(
//            'section',
//            new Attributes(['class' => 'resource-events']),
//            new HtmlElement('h2', null, new Text(t('Events'))),
//            new EventList(Event::on(Database::connection())
//                ->filter(Filter::all(
//                    Filter::equal('reference_kind', 'DaemonSet'),
//                    Filter::equal('reference_namespace', $this->daemonSet->namespace),
//                    Filter::equal('reference_name', $this->daemonSet->name)
//                )))
//        ));
    }

}
