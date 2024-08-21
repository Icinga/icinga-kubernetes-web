<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Secret;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

class SecretDetail extends BaseHtmlElement
{
    use Translation;

    protected Secret $secret;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'secret-detail'];

    public function __construct(Secret $secret)
    {
        $this->secret = $secret;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->secret, [
                $this->translate('Type') => $this->secret->type
            ])),
            new Labels($this->secret->label),
            new Annotations($this->secret->annotation)
        );
    }
}
