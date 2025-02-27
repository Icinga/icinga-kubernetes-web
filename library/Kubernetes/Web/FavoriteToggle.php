<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Web\Compat\CompatForm;

class FavoriteToggle extends CompatForm
{
    /** @var string Default favorite param */
    const string DEFAULT_FAVORITE_PARAM = 'show-favorites';

    /** @var string The URL parameter which stores whether to show the favorites */
    protected string $favoriteParam = self::DEFAULT_FAVORITE_PARAM;

    /** @var string */
    protected $method = 'POST';

    /**
     * Get the name of the URL parameter which stores whether to show the favorites
     *
     * @return string
     */
    public function getFavoriteParam(): string
    {
        return $this->favoriteParam;
    }

    /**
     * Set the name of the URL parameter which stores whether to show the favorites
     *
     * @param string $favoriteParam
     *
     * @return $this
     */
    public function setFavoriteParam($favoriteParam): self
    {
        $this->favoriteParam = $favoriteParam;

        return $this;
    }

    protected function assemble(): void
    {
        $this->addAttributes(['class' => 'favorite-toggle inline']);

        $this->addElement(
            'checkbox',
            self::DEFAULT_FAVORITE_PARAM,
            [
                'label' => 'Favorites Only',
                'class' => 'autosubmit',
            ]
        );
    }
}
