<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $this->addTitleTab($this->translate('Node'));

        /** @var Node $node */
        $node = Node::on(Database::connection())
            ->filter(Filter::equal('id', $this->params->getRequired('id')))
            ->first();

        if ($node === null) {
            $this->httpNotFound($this->translate('Node not found'));
        }

        $this->addContent(new NodeDetail($node));
    }
}
