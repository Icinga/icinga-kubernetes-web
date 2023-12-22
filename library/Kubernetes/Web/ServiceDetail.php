<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Endpoint;
use Icinga\Module\Kubernetes\Model\EndpointSlice;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Html\BaseHtmlElement;
use ipl\Html\FormattedString;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;

class ServiceDetail extends BaseHtmlElement
{
    /** @var Service */
    protected $service;

    protected $tag = 'div';

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    protected function assemble()
    {
        $endpointSlices = EndpointSlice::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('endpoint_slice.label.name', "kubernetes.io/service-name"),
                    Filter::equal('endpoint_slice.label.value', $this->service->name)
                )
            )->first();

        $this->addHtml(
            new Details(new ResourceDetails($this->service, [
                t('Type')                              => ucfirst(Str::camel($this->service->type)),
                t('Cluster IP')                        => $this->service->cluster_ip,
                t('Cluster IPs')                       => $this->service->cluster_ips,
                t('External IPs')                      => $this->service->external_ips,
                t('Session Affinity')                  => ucfirst(Str::camel($this->service->session_affinity)),
                t('External Name')                     => $this->service->external_name,
                t('External Traffic Policy')           => ucfirst(Str::camel($this->service->external_traffic_policy)),
                t('Health Check Node Port')            => $this->service->health_check_node_port,
                t('Publish Not Ready Addresses')       => $this->service->publish_not_ready_addresses,
                t('IP Families')                       => $this->service->ip_families,
                t('IP Family Policy')                  => ucfirst(Str::camel($this->service->ip_family_policy)),
                t('Allocate Load Balancer Node Ports') => $this->service->allocate_load_balancer_node_ports,
                t('Load Balancer Class')               => $this->service->load_balancer_class,
                t('Internal Traffic Policy')           => ucfirst(Str::camel($this->service->internal_traffic_policy))
            ])),
            new Labels($this->service->label),
            new InternalEndpointList($this->service->service_port),
            new EndpointTable($endpointSlices->endpoint, (new Endpoint())->getColumnDefinitions())
        );

        $selectors = $this->service->selector->execute();
        if ($selectors->valid()) {
            $pods = new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Pods')))
            );

            foreach ($selectors as $selector) {
                $pods->addHtml(
                    new HtmlElement(
                        'section',
                        null,
                        new HtmlElement(
                            'h3',
                            null,
                            FormattedString::create('%s: %s', $selector->name, $selector->value)
                        ),
                        new PodList(Pod::on(Database::connection())
                            ->with(['node'])
                            ->filter(Filter::all(
                                Filter::equal('pod.namespace', $this->service->namespace),
                                Filter::equal('pod.label.name', $selector->name),
                                Filter::equal('pod.label.value', $selector->value)
                            )))
                    )
                );
            }

            $this->addHtml($pods);
        }
    }
}
