<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Secret;
use ipl\Html\BaseHtmlElement;

class SecretDetail extends BaseHtmlElement
{
    /** @var Secret */
    protected $secret;

    protected $tag = 'div';

    public function __construct(Secret $secret)
    {
        $this->secret = $secret;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->secret, [
                t('Type') => $this->secret->type,
            ])),
            new Labels($this->secret->label),
            new Data($this->secret->data->execute())
        );
    }
}
