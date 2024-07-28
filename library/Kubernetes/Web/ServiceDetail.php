<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Endpoint;
use Icinga\Module\Kubernetes\Model\EndpointSlice;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\ServicePort;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;

class ServiceDetail extends BaseHtmlElement
{
    use Translation;

    protected Service $service;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'service-detail'];

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    protected function assemble(): void
    {
        $endpointSlices = EndpointSlice::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('endpoint_slice.label.name', 'kubernetes.io/service-name'),
                    Filter::equal('endpoint_slice.label.value', $this->service->name)
                )
            )->first();

        $this->addHtml(
            new Details(new ResourceDetails($this->service, [
                $this->translate('Type')                              => $this->service->type,
                $this->translate('Cluster IP')                        => $this->service->cluster_ip,
                $this->translate('Cluster IPs')                       => $this->service->cluster_ips,
                $this->translate('External IPs')                      => $this->service->external_ips ??
                    new EmptyState($this->translate('None')),
                $this->translate('Session Affinity')                  => $this->service->session_affinity,
                $this->translate('External Name')                     => $this->service->external_name ??
                    new EmptyState($this->translate('None')),
                $this->translate('External Traffic Policy')           => $this->service->external_traffic_policy ??
                    new EmptyState($this->translate('None')),
                $this->translate('Health Check Node Port')            => $this->service->health_check_node_port ??
                    new EmptyState($this->translate('None')),
                $this->translate('Publish Not Ready Addresses')       => Icons::ready(
                    $this->service->publish_not_ready_addresses
                ),
                $this->translate('IP Families')                       => $this->service->ip_families ??
                    new EmptyState($this->translate('None')),
                $this->translate('IP Family Policy')                  => $this->service->ip_family_policy ??
                    new EmptyState($this->translate('None')),
                $this->translate('Allocate Load Balancer Node Ports') => Icons::ready(
                    $this->service->allocate_load_balancer_node_ports
                ),
                $this->translate('Load Balancer Class')               => $this->service->load_balancer_class ??
                    new EmptyState($this->translate('None')),
                $this->translate('Internal Traffic Policy')           => $this->service->internal_traffic_policy
            ])),
            new Labels($this->service->label),
            new Annotations($this->service->annotation),
            new PortTable($this->service->port, (new ServicePort())->getColumnDefinitions()),
            new EndpointTable($endpointSlices->endpoint, (new Endpoint())->getColumnDefinitions()),
			new ServiceEnvironment($this->service)
        );

        $selectors = $this->service->selector->execute();
        if ($selectors->valid()) {
            $pods = Pod::on(Database::connection())
                ->with(['node'])
                ->filter(Filter::all(
                    Filter::equal('pod.namespace', $this->service->namespace)
                ));
            foreach ($selectors as $selector) {
                $pods->filter(Filter::all(
                    Filter::equal('pod.label.name', $selector->name),
                    Filter::equal('pod.label.value', $selector->value)
                ));
            }
            $this->addHtml((new PodList(
                $pods
            ))->setWrapper(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods')))
            )));
        }

        $this->addHtml(new Yaml($this->service->yaml));
    }
}
