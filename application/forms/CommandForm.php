<?php

/* Icinga Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Forms;

use ipl\Html\Form;

abstract class CommandForm extends Form
{
    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    /**
     * Create and add form elements representing the command's options
     *
     * @return void
     */
    abstract protected function assembleElements(): void;

    /**
     * Create and add a submit button to the form
     *
     * @return void
     */
    abstract protected function assembleSubmitButton(): void;

    protected function assemble(): void
    {
        $this->assembleElements();
        $this->assembleSubmitButton();
    }
}
