<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;

class ReplicaSetEnvironment extends Environment
{
    protected function addSpecificContent($content)
    {
        $treeHtml = $this->generateTree();
        echo $treeHtml;
    }

    protected function fetchIngresses($services)
    {
        if ($services === null) {
            return null;
        }

        $ingresses = Ingress::on($this->dbConnection);
        $filter = Filter::any();
        foreach ($services as $service) {
            $filter->add(
                Filter::any(
                    Filter::equal('ingress.namespace', $service->namespace),
                    Filter::equal('ingress.backend_service.service_uuid', $service->uuid)
                )
            );
        }

        return $ingresses->setFilter($filter);
    }

    protected function fetchServices($pods)
    {
        if ($pods === null) {
            return null;
        }

        $services = Service::on($this->dbConnection);

        foreach ($pods as $pod) {
            $labels = $pod->label;
            $services->filter(Filter::equal('service.namespace', $pod->namespace));

            if ($labels !== null) {
                $labelFilters = [];
                foreach ($labels as $label) {
                    $labelFilters[] = Filter::all(
                        Filter::equal('service.selector.name', $label->name),
                        Filter::equal('service.selector.value', $label->value)
                    );
                }

                $services = $services->filter(Filter::any(...$labelFilters));
            }
        }

        return $services;
    }

    protected function fetchPods()
    {
        $pods = Pod::on($this->dbConnection)
            ->with(['node'])
            ->filter(Filter::equal('pod.namespace', $this->resource->namespace));

        $pods->filter(
            Filter::all(
                Filter::equal('pod.owner.owner_uuid', $this->resource->uuid)
            )
        );

        return $pods;
    }

    protected function fetchDeployment()
    {
        $replicaSetOwner = ReplicaSetOwner::on($this->dbConnection)
            ->filter(Filter::equal('replica_set_uuid', $this->resource->uuid))->first();

        if ($replicaSetOwner === null) {
            return null;
        }

        $ownerUuid = (string) Uuid::fromBytes($replicaSetOwner->owner_uuid);

        $deployment = Deployment::on($this->dbConnection)
            ->filter(Filter::equal('uuid', $ownerUuid))->first();

        return $deployment;
    }

    protected function generateTree()
    {
        $pods = $this->fetchPods();
        $services = $this->fetchServices($pods);
        $ingresses = $this->fetchIngresses($services);

        $html = '<ul class="tree-env">';

        $deployment = $this->fetchDeployment();
        if ($deployment !== null) {
            $deploymentUrl = (string) (new Link($deployment->name, Links::deployment($deployment)))->getUrl();
            $html .= '<li class="deployment">Deployment: <a href="' . htmlspecialchars($deploymentUrl) . '">' . htmlspecialchars($deployment->name) . '<ul>';
        }

        $html .= '<li class="podOwner"><span class="highlight-text">ReplicaSet: '. htmlspecialchars($this->resource->name) . '</span><ul>';
        if ($pods !== null) {
            foreach ($pods as $pod) {
                $podUrl = (string) (new Link($pod->name, Links::pod($pod)))->getUrl();
                $html .= '<li class="pod">Pod: <a href="' . htmlspecialchars($podUrl) . '">' . htmlspecialchars(
                        $pod->name
                    ) . '</a><ul>';

                if ($services !== null) {
                    foreach ($services as $service) {
                        $serviceUrl = (string) (new Link($service->name, Links::service($service)))->getUrl();
                        $html .= '<li class="service">Service: <a href="' . htmlspecialchars(
                                $serviceUrl
                            ) . '">' . htmlspecialchars($service->name) . '<ul>';

                        if ($ingresses !== null) {
                            foreach ($ingresses as $ingress) {
                                if ($ingress->backend_service !== null) {
                                    foreach ($ingress->backend_service as $ingressService) {
                                        if ($ingressService->service_name === $service->name) {
                                            $ingressUrl = (string) (new Link(
                                                $ingress->name,
                                                Links::ingress($ingress)
                                            ))->getUrl();
                                            $html .= '<li class="ingress">Ingress: <a href="' . htmlspecialchars(
                                                    $ingressUrl
                                                ) . '">' . htmlspecialchars(
                                                    $ingress->name
                                                ) . '</li>';
                                        }
                                    }
                                }
                            }
                        }
                        $html .= '</ul></li>';
                    }
                }
                $html .= '</ul></li>';
            }
        }
        $html .= '</ul></li>';
        $html .= '</ul>';

        return $html;
    }
}
