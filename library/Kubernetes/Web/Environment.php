<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Links;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Url;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;
use Ramsey\Uuid\Uuid;
use Sabberworm\CSS\CSSList\AtRuleBlockList;

abstract class Environment extends HtmlDocument
{
	protected $resource;
	protected $dbConnection;

	public function __construct($resource)
	{
		$this->resource = $resource;
		$this->dbConnection = Database::connection();
	}

	public function assemble()
	{
		$content = '<ul class="tree-env">';

		$this->addSpecificContent($content);
	}

	abstract protected function addSpecificContent($content);

	protected function fetchPodOwnerKind($podOwner)
	{
		if ($podOwner === null) {
			return [null, null];
		}
			switch ($podOwner->kind) {
				case 'replica_set':
					$replicaSet = ReplicaSet::on($this->dbConnection)
						->filter(Filter::equal('uuid', (string) Uuid::fromBytes($podOwner->owner_uuid)))->first();
					$link = (string) (new Link($replicaSet->name, Links::replicaSet($replicaSet)))->getUrl();

					return [$replicaSet, $link];
				case 'job':
					$job = Job::on($this->dbConnection)
						->filter(Filter::equal('uuid', (string) Uuid::fromBytes($podOwner->owner_uuid)))->first();
					$link = (string) (new Link($job->name, Links::job($job)))->getUrl();

					return [$job, $link];
				case 'daemon_set':
					$daemonSet = DaemonSet::on($this->dbConnection)
						->filter(Filter::equal('uuid', (string) Uuid::fromBytes($podOwner->owner_uuid)))->first();
					$link = (string) (new Link($daemonSet->name, Links::daemonSet($daemonSet)))->getUrl();

					return [$daemonSet, $link];
				case 'stateful_set':
					$statefulSet = StatefulSet::on($this->dbConnection)
						->filter(Filter::equal('uuid', (string) Uuid::fromBytes($podOwner->owner_uuid)))->first();
					$link = (string) (new Link($statefulSet->name, Links::statefulSet($statefulSet)))->getUrl();

					return [$statefulSet, $link];
				default:
					return null;
			}
	}
}
