<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;

class FavoriteToggleForm extends CompatForm
{
    public function __construct(
        /** @var bool $checked Indicates whether the form should be checked on creation */
        protected bool $checked
    ) {
    }

    protected function assemble(): void
    {
        $checkbox = $this->createElement(
            'checkbox',
            'favorite-checkbox',
            [
                'class'          => 'autosubmit favorite-checkbox',
                'value'          => $this->checked,
                'checkedValue'   => 1,
                'uncheckedValue' => 0,
            ]
        );

        if (! $checkbox->getAttributes()->has('id')) {
            $checkbox->setAttribute(
                'id',
                $checkbox->getName() . '_' . rand(0, 9999999)
            );
        }

        $this->registerElement($checkbox);

        $document = new HtmlDocument();
        $document->addHtml(
            $checkbox,
            new HtmlElement(
                'label',
                Attributes::create([
                    'class' => 'favorite-checkbox-label',
                    'for'   => $checkbox->getAttributes()->get('id')->getValue(),
                ]),
            ),
        );

        $checkbox->prependWrapper($document);

        $this->addHtml($checkbox);
    }
}
