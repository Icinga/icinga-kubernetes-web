<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;

class IngressEnvironment extends Environment
{
    protected function addSpecificContent($content)
    {
        $treeHtml = $this->generateTree();

        echo $treeHtml;
    }

    protected function fetchServices()
    {
        $services = Service::on($this->dbConnection)
            ->filter(Filter::any(Filter::equal('namespace', $this->resource->namespace)));

        $filters = [];
        foreach ($this->resource->backend_service as $backendService) {
            $filters[] = Filter::all(
                Filter::equal('service.name', $backendService->service_name),
                Filter::equal('service.port.port', $backendService->service_port_number)
            );
        }

        if (count($filters) === 0) {
            return null;
        }

        $services = $services->filter(Filter::any(...$filters));

        return $services;
    }

    protected function fetchPodOwner($pod)
    {
        if ($pod === 0) { // necessary?
            return null;
        }

        return PodOwner::on($this->dbConnection)
            ->filter(Filter::equal('pod_uuid', (string) Uuid::fromBytes($pod->uuid)))->first();
    }


    protected function fetchPods($services)
    {
        if ($services === null) {
            return null;
        }

        $pods = Pod::on(Database::connection())
            ->with(['node']);

        foreach ($services as $service) {
            $selectors = $service->selector->execute();
            if ($selectors->valid()) {
                $pods->filter(
                    Filter::all(
                        Filter::equal('pod.namespace', $this->resource->namespace)
                    )
                );
                foreach ($selectors as $selector) {
                    $pods->filter(
                        Filter::all(
                            Filter::equal('pod.label.name', $selector->name),
                            Filter::equal('pod.label.value', $selector->value)
                        )
                    );
                }

                return $pods;
            }
        }

        return null;
    }

    protected function fetchDeployment($replicaSet)
    {
        if ($replicaSet === null) {
            return null;
        }

        $replicaSetOwner = ReplicaSetOwner::on($this->dbConnection)
            ->filter(Filter::equal('replica_set_uuid', $replicaSet->uuid))->first();

        if ($replicaSetOwner === null) {
            return null;
        } else {
            $deployment = Deployment::on($this->dbConnection)
                ->filter(Filter::all(Filter::equal('uuid', (string) Uuid::fromBytes($replicaSetOwner->owner_uuid))))
                ->first();

            return $deployment;
        }
    }

    protected function generateTree()
    {
        $services = $this->fetchServices();
        $pods = $this->fetchPods($services);

        $html = '<ul class="tree-env">';

        if ($pods !== null) {
            // Collect pod owners and pods
            $podOwners = [];
            foreach ($pods as $pod) {
                $podOwner = $this->fetchPodOwner($pod);
                $ownerKind = $this->fetchPodOwnerKind($podOwner);
                if ($ownerKind !== null) {
                    $podOwners[$ownerKind[0]->name][] = ['pod' => $pod, 'owner' => $ownerKind];
                }
            }

            foreach ($podOwners as $ownerName => $podsInfo) {
                $owner = $podsInfo[0]['owner'][0];
                $ownerUrl = $podsInfo[0]['owner'][1];

                // Check if owner is a ReplicaSet and fetch deployment if true
                if ($owner instanceof ReplicaSet) {
                    $deployment = $this->fetchDeployment($owner);
                    if ($deployment !== null) {
                        $deploymentUrl = (string) (new Link($deployment->name, Links::deployment($deployment)))->getUrl(
                        );
                        $html .= '<li class="deployment">Deployment: <a href="' . htmlspecialchars($deploymentUrl)
                            . '">' . htmlspecialchars($deployment->name) . '</a><ul>';
                    }
                }
                $html .= '<li class="podOwner">PodOwner: <a href="' . htmlspecialchars($ownerUrl)
                    . '">' . htmlspecialchars($ownerName) . '</a><ul>';

                foreach ($podsInfo as $podInfo) {
                    $pod = $podInfo['pod'];
                    $podUrl = (string) (new Link($pod->name, Links::pod($pod)))->getUrl();
                    $html .= '<li class="pod">Pod: <a href="' . htmlspecialchars($podUrl) . '">' . htmlspecialchars(
                            $pod->name
                        ) . '</a><ul>';

                    if ($services !== null) {
                        foreach ($services as $service) {
                            $serviceUrl = (string) (new Link($service->name, Links::service($service)))->getUrl();
                            $html .= '<li class="service">Service: <a href="' . htmlspecialchars(
                                    $serviceUrl
                                ) . '">' . htmlspecialchars($service->name) . '</a><ul>';

                            if ($this->resource->backend_service !== null) {
                                foreach ($this->resource->backend_service as $ingressService) {
                                    if ($ingressService->service_name === $service->name) {
                                        $html .= '<li class="ingress"><span class="highlight-text">Ingress: ' . htmlspecialchars(
                                                $this->resource->name
                                            ) . '</span></li>';
                                    }
                                }
                            }
                            $html .= '</ul></li>';
                        }
                    }
                    $html .= '</ul></li>';
                }
                $html .= '</ul></li>';

                // Close deployment <ul> if it exists
                if ($owner instanceof ReplicaSet && isset($deployment)) {
                    $html .= '</ul></li>';
                }
            }
        }
        $html .= '</ul>';

        return $html;
    }
}
