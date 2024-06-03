<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Controllers;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Web\NodeDetail;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;
use Ramsey\Uuid\Uuid;

class NodeController extends CompatController
{
    public function indexAction(): void
    {
        $this->addTitleTab($this->translate('Node'));

        $uuid = $this->params->getRequired('id');
        $uuidBytes = Uuid::fromString($uuid)->getBytes();

        /** @var Node $node */
        $node = Node::on(Database::connection())
            ->filter(Filter::equal('uuid', $uuidBytes))
            ->first();

        if ($node === null) {
            $this->httpNotFound($this->translate('Node not found'));
        }

        $this->addContent(new NodeDetail($node));
    }
}
