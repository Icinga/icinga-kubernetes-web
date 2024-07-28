<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;

class PodEnvironment extends Environment
{
	protected function addSpecificContent($content)
	{
		$treeHtml = $this->generateTree();
		echo $treeHtml;
	}

	protected function fetchIngresses($services)
	{
		if ($services->first() === null) {
			return null;
		}

		$ingress = Ingress::on($this->dbConnection);
		$filter = Filter::any();
		foreach ($services as $service) {
			$filter->add(
				Filter::all(
					Filter::equal('ingress.namespace', $service->namespace),
					Filter::equal('ingress.backend_service.service_name', $service->name)
				)
			);

			foreach ($service->port as $servicePort) {
				$filter->add(Filter::equal('ingress.backend_service.service_port_number', $servicePort->port));
			}
		}

		$ingresses = $ingress->filter($filter);

		return $ingresses;
	}

	protected function fetchServices()
	{
		$labels = $this->resource->label;
		$services = Service::on($this->dbConnection)
			->filter(Filter::equal('service.namespace', $this->resource->namespace));

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

		return $services;
	}

	protected function fetchPods()
	{
		$podOwner = $this->fetchPodOwner();
		$owner = $this->fetchPodOwnerKind($podOwner)[0];

		if ($owner === null) {
			return null;
		}

		$pods = Pod::on($this->dbConnection)
			->filter(
				Filter::equal('pod.owner.owner_uuid', $owner->uuid)
			);

		return $pods;
	}

	protected function fetchPodOwner()
	{
		return PodOwner::on($this->dbConnection)
			->filter(Filter::equal('pod_uuid', (string) Uuid::fromBytes($this->resource->uuid)))->first();
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
		$ingresses = $this->fetchIngresses($services);
		$pods = $this->fetchPods();

		$html = '<ul class="tree-env">';

		// Collect pod owners and pods
		$podOwners = [];
		$podOwner = $this->fetchPodOwner();
		$ownerKind = $this->fetchPodOwnerKind($podOwner);
		if ($ownerKind !== null && $ownerKind[0] !== null) {
			$podOwners[$ownerKind[0]->name][] = ['pod' => $this->resource, 'owner' => $ownerKind];
		}

		foreach ($podOwners as $ownerName => $podsInfo) {
			$owner = $podsInfo[0]['owner'][0];
			$ownerUrl = $podsInfo[0]['owner'][1];

			// Check if owner is a ReplicaSet and fetch deployment if true
			if ($owner instanceof ReplicaSet) {
				$deployment = $this->fetchDeployment($owner);
				if ($deployment !== null) {
					$deploymentUrl = (string) (new Link($deployment->name, Links::deployment($deployment)))->getUrl();
					$html .= '<li class="deployment">Deployment: <a href="' . htmlspecialchars(
							$deploymentUrl
						) . '">' . htmlspecialchars($deployment->name) . '</a><ul>';
				}
			}

			$html .= '<li class="podOwner">PodOwner: <a href="' . htmlspecialchars($ownerUrl) . '">' . htmlspecialchars(
					$ownerName
				) . '</a><ul>';

			// Iterate over all pods
			if ($pods !== null) {
				foreach ($pods as $pod) {
					// Ensure $pod is not null
					if ($pod !== null) {
						$html .= '<li class="pod">Pod: ';

						// Highlight the current pod's name
						if ($pod->uuid === $this->resource->uuid) {
							$html .= '<span class="highlight-text">' . htmlspecialchars($pod->name) . '</span>';
						} else {
							$html .= htmlspecialchars($pod->name);
						}

						$html .= '<ul>';

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
														) . '">' . htmlspecialchars($ingress->name) . '</a></li>';
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
			}
			$html .= '</ul></li>';

			// Close deployment <ul> if it exists
			if ($owner instanceof ReplicaSet && isset($deployment)) {
				$html .= '</ul></li>';
			}
		}
		$html .= '</ul>';

		return $html;
	}
}
