<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Link;
use Ramsey\Uuid\Uuid;

class ServiceEnvironment extends Environment
{
	protected function addSpecificContent($content)
	{
		$treeHtml = $this->generateTree();
		echo $treeHtml;
	}

	protected function fetchIngresses()
	{
		$ingresses = Ingress::on($this->dbConnection);
		$filter = Filter::all();
		$filter->add(
			Filter::all(
				Filter::equal('ingress.namespace', $this->resource->namespace),
				Filter::equal('ingress.backend_service.service_name', $this->resource->name)
			)
		);

		foreach ($this->resource->port as $servicePort) {
			$filter->add(Filter::equal('ingress.backend_service.service_port_number', $servicePort->port));
		}

		return $ingresses->setFilter($filter);
	}

	protected function fetchPodOwner($pod)
	{
		return PodOwner::on($this->dbConnection)
			->filter(Filter::equal('pod_uuid', (string) Uuid::fromBytes($pod->uuid)))->first();
	}

	protected function fetchPods()
	{
		$pods = Pod::on(Database::connection())
			->with(['node']);

		if ($pods === null) {
			return null;
		}

		$selectors = $this->resource->selector->execute();
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
		}

		return $pods;
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
		$pods = $this->fetchPods();
		$ingresses = $this->fetchIngresses();

		$html = '<ul class="tree-env">';

		if ($pods !== null) {
			// Collect pod owners and pods
			$podOwners = [];
			foreach ($pods as $pod) {
				$podOwner = $this->fetchPodOwner($pod);
				$ownerKind = $this->fetchPodOwnerKind($podOwner);
				if ($ownerKind !== null && $ownerKind[0] !== null) {
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
						$html .= '<li class="deployment">Deployment: <a href="' . htmlspecialchars(
								$deploymentUrl
							) . '">' . htmlspecialchars($deployment->name) . '</a><ul>';
					}
				}
				$html .= '<li class="podOwner">PodOwner: <a href="' . htmlspecialchars(
						$ownerUrl
					) . '">' . htmlspecialchars($ownerName) . '</a><ul>';

				foreach ($podsInfo as $podInfo) {
					$pod = $podInfo['pod'];
					$podUrl = (string) (new Link($pod->name, Links::pod($pod)))->getUrl();
					$html .= '<li class="pod">Pod: <a href="' . htmlspecialchars($podUrl) . '">' . htmlspecialchars(
							$pod->name
						) . '</a><ul>';

					$html .= '<li class="service"><span class="highlight-text">Service: ' . htmlspecialchars(
							$this->resource->name
						) . '</span><ul>';

					if ($ingresses !== null) {
						foreach ($ingresses as $ingress) {
							if ($ingress->backend_service !== null) {
								foreach ($ingress->backend_service as $ingressService) {
									if ($ingressService->service_name === $this->resource->name) {
										$ingressUrl = (string) (new Link(
											$ingress->name, Links::ingress($ingress)
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
