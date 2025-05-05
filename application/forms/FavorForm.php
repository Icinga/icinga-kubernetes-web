<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use ipl\Web\Widget\Icon;

class FavorForm extends CommandForm
{
    protected $defaultAttributes = ['class' => 'inline'];

    public function __construct()
    {
    }

    protected function assembleElements(): void
    {
    }

    protected function assembleSubmitButton(): void
    {
        $this->addElement(
            'submitButton',
            'btn_submit',
            [
                'class' => ['link-button spinner'],
                'label' => [
                    (new Icon('star'))->setStyle('far'),
                    t('Favor')
                ],
                'title' => t('Favor Resource')
            ]
        );
    }
}
