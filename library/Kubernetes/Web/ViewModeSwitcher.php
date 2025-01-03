<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ViewMode;
use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\Web\Common\FormUid;
use ipl\Web\Widget\IcingaIcon;

class ViewModeSwitcher extends Form
{
    use FormUid;
    use Translation;

    protected $defaultAttributes = [
        'class' => 'view-mode-switcher',
        'name'  => 'view-mode-switcher'
    ];

    /** @var ViewMode Default view mode */
    public const DEFAULT_VIEW_MODE = ViewMode::Common;

    /** @var string Default view mode param */
    const DEFAULT_VIEW_MODE_PARAM = 'view';

    /** @var array<string, string> View mode-icon pairs */
    public static array $viewModes = [
        ViewMode::Minimal->value  => 'minimal',
        ViewMode::Common->value   => 'default',
        ViewMode::Detailed->value => 'detailed',
    ];

    protected ?ViewMode $defaultViewMode = null;

    /** @var string */
    protected $method = 'POST';

    /** @var callable */
    protected $protector;

    protected string $viewModeParam = self::DEFAULT_VIEW_MODE_PARAM;

    /** @var ViewMode[] */
    protected array $ignoredViewModes = [];

    /**
     * Get the default mode
     *
     * @return ViewMode
     */
    public function getDefaultViewMode(): ViewMode
    {
        return $this->defaultViewMode ?: static::DEFAULT_VIEW_MODE;
    }

    /**
     * Set the default view mode
     *
     * @param ViewMode $defaultViewMode
     *
     * @return $this
     */
    public function setDefaultViewMode(ViewMode $defaultViewMode): static
    {
        $this->defaultViewMode = $defaultViewMode;

        return $this;
    }

    /**
     * Get the view mode URL parameter
     *
     * @return string
     */
    public function getViewModeParam(): string
    {
        return $this->viewModeParam;
    }

    /**
     * Set the view mode URL parameter
     *
     * @param string $viewModeParam
     *
     * @return $this
     */
    public function setViewModeParam(string $viewModeParam): static
    {
        $this->viewModeParam = $viewModeParam;

        return $this;
    }

    /**
     * Get the view mode
     *
     * @return ViewMode
     */
    public function getViewMode(): ViewMode
    {
        $viewMode = ViewMode::from(
            $this->getPopulatedValue($this->getViewModeParam()) ?? $this->getDefaultViewMode()->value
        );

        if (array_key_exists($viewMode->value, static::$viewModes)) {
            return $viewMode;
        }

        return $this->getDefaultViewMode();
    }

    /**
     * Set the view mode
     *
     * @param ViewMode $name
     *
     * @return $this
     */
    public function setViewMode(ViewMode $name): static
    {
        $this->populate([$this->getViewModeParam() => $name]);

        return $this;
    }

    /**
     * Set callback to protect ids with
     *
     * @param callable $protector
     *
     * @return  $this
     */
    public function setIdProtector(callable $protector): static
    {
        $this->protector = $protector;

        return $this;
    }

    private function protectId($id)
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }

    /**
     * Add view modes to be ignored
     *
     * @param ViewMode ...$viewModes
     *
     * @return $this
     */
    public function addIgnoredViewModes(ViewMode ...$viewModes): static
    {
        array_push($this->ignoredViewModes, ...$viewModes);
        $this->ignoredViewModes = array_values(array_unique($this->ignoredViewModes, SORT_REGULAR));

        return $this;
    }

    protected function assemble(): void
    {
        $viewModeParam = $this->getViewModeParam();

        $this->addElement($this->createUidElement());
        $this->addElement(new HiddenElement($viewModeParam));

        foreach (static::$viewModes as $viewMode => $icon) {
            $viewMode = ViewMode::from($viewMode);

            if (in_array($viewMode, $this->ignoredViewModes)) {
                continue;
            }

            $protectedId = $this->protectId('view-mode-switcher-' . $icon);
            $input = new InputElement($viewModeParam, [
                'class' => 'autosubmit',
                'id'    => $protectedId,
                'name'  => $viewModeParam,
                'type'  => 'radio',
                'value' => $viewMode->value
            ]);
            $input->getAttributes()->registerAttributeCallback('checked', fn() => $viewMode === $this->getViewMode());

            $label = new HtmlElement('label', Attributes::create(['for' => $protectedId]), new IcingaIcon($icon));
            $label->getAttributes()->registerAttributeCallback('title', fn() => $this->getTitle($viewMode));

            $this->addHtml($input, $label);
        }
    }

    /**
     * Return the title for the view mode when it is active and inactive
     *
     * @param ViewMode $viewMode
     *
     * @return string Title for the view mode when it is active and inactive
     */
    protected function getTitle(ViewMode $viewMode): string
    {
        return match ($viewMode) {
            ViewMode::Minimal  => $viewMode === $this->getViewMode()
                ? $this->translate('Minimal view active')
                : $this->translate('Switch to minimal view'),
            ViewMode::Common   => $viewMode === $this->getViewMode()
                ? $this->translate('Common view active')
                : $this->translate('Switch to common view'),
            ViewMode::Detailed => $viewMode === $this->getViewMode()
                ? $this->translate('Detailed view active')
                : $this->translate('Switch to detailed view'),
        };
    }
}
