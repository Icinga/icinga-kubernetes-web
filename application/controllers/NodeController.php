<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Web\NodeDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class NodeController extends CompatController
{
    public function indexAction(): void
    {
        //$namespace = $this->params->getRequired('namespace');
        $id = $this->params->getRequired('id');
        $name = $this->params->get('name');

        //$this->addTitleTab("Node $namespace/$name");
        $this->addTitleTab("Node $name");

        $query = Node::on(Database::connection())
            ->filter(Filter::all(
                //Filter::equal('node.namespace', $namespace),
                Filter::equal('node.id', $id)
            ));

        /** @var Node $node */
        $node = $query->first();
        if ($node === null) {
            $this->httpNotFound($this->translate('Node not found'));
        }

        $this->addContent(new NodeDetail($node));
    }
}
