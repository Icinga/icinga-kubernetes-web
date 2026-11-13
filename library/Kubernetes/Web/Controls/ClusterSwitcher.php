<?php

/* Icinga for Kubernetes Web | (c) 2026 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Controls;

use Icinga\Module\Kubernetes\Common\ClusterOptions;
use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;

class ClusterSwitcher extends Form
{
    use ClusterOptions;
    use Translation;

    /** @var string URL parameter which stores the selected cluster */
    public const DEFAULT_CLUSTER_PARAM = 'cluster';

    protected $defaultAttributes = ['class' => 'cluster-switcher'];

    protected $method = 'GET';

    /** @var ?array<string, string> Clusters as UUID-label pairs, loaded on first use */
    protected ?array $clusters = null;

    /**
     * Get whether there is more than one cluster to choose from
     *
     * @return bool
     */
    public function hasMultipleClusters(): bool
    {
        return count($this->getClusters()) > 1;
    }

    /**
     * Get the selected cluster UUID, or null for all clusters
     *
     * The selection is ignored if there is nothing to choose from, as the switcher isn't shown then,
     * or if it isn't a known cluster, e.g. a stale preference for a removed cluster.
     *
     * @return ?string
     */
    public function getClusterUuid(): ?string
    {
        if (! $this->hasMultipleClusters()) {
            return null;
        }

        $uuid = $this->getPopulatedValue(static::DEFAULT_CLUSTER_PARAM);

        return is_string($uuid) && isset($this->getClusters()[$uuid]) ? $uuid : null;
    }

    protected function assemble(): void
    {
        $this->addElement('select', static::DEFAULT_CLUSTER_PARAM, [
            'class'   => 'autosubmit',
            'label'   => $this->translate('Cluster'),
            'options' => [static::ALL_CLUSTERS => $this->translate('All clusters')]
                + $this->getClusters(),
            'value'   => $this->getClusterUuid() ?? static::ALL_CLUSTERS,
        ]);

        // Same markup as ipl's SortControl
        $select = $this->getElement(static::DEFAULT_CLUSTER_PARAM);
        (new DivDecorator())->decorate($select);
        $select->prependWrapper(HtmlElement::create('div', ['class' => 'icinga-controls']));
    }

    /**
     * Get the clusters as UUID-label pairs
     *
     * @return array<string, string>
     */
    protected function getClusters(): array
    {
        return $this->clusters ??= static::fetchClusters();
    }
}
