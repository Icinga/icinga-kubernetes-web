<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controls;

use Icinga\Module\Kubernetes\Web\Widget\ClusterForm;
use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use Ramsey\Uuid\Uuid;

class ClusterSwitcher extends Form
{
    use Translation;

    /** @var string URL parameter which stores the selected cluster */
    public const DEFAULT_CLUSTER_PARAM = 'cluster';

    protected $defaultAttributes = ['class' => 'cluster-switcher'];

    protected $method = 'GET';

    /**
     * Get the selected cluster UUID, or null for all clusters
     *
     * @return ?string
     */
    public function getClusterUuid(): ?string
    {
        $uuid = $this->getPopulatedValue(static::DEFAULT_CLUSTER_PARAM);

        return is_string($uuid) && Uuid::isValid($uuid) ? $uuid : null;
    }

    protected function assemble(): void
    {
        $this->addElement('select', static::DEFAULT_CLUSTER_PARAM, [
            'class'   => 'autosubmit',
            'label'   => $this->translate('Cluster'),
            'options' => [ClusterForm::ALL_CLUSTERS => $this->translate('All clusters')]
                + iterator_to_array(ClusterForm::yieldClusters()),
            'value'   => $this->getClusterUuid() ?? ClusterForm::ALL_CLUSTERS
        ]);

        // Same markup as ipl's SortControl
        $select = $this->getElement(static::DEFAULT_CLUSTER_PARAM);
        (new DivDecorator())->decorate($select);
        $select->prependWrapper(HtmlElement::create('div', ['class' => 'icinga-controls']));
    }
}
