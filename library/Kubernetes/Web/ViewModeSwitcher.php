<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use ipl\Html\Attributes;
use ipl\Html\Form;
use ipl\Html\FormElement\HiddenElement;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Web\Common\FormUid;
use ipl\Web\Widget\IcingaIcon;

class ViewModeSwitcher extends Form
{
    use FormUid;

    protected $defaultAttributes = [
        'class' => 'view-mode-switcher',
        'name'  => 'view-mode-switcher'
    ];

    /** @var string Minimal view mode */
    public const VIEW_MODE_MINIMAL = 'minimal';

    /** @var string Common view mode */
    public const VIEW_MODE_COMMON = 'common';

    /** @var string Detailed view mode */
    public const VIEW_MODE_DETAILED = 'detailed';

    /** @var string Default view mode */
    public const DEFAULT_VIEW_MODE = 'common';

    /** @var string Default view mode param */
    public const DEFAULT_VIEW_MODE_PARAM = 'view';

    /** @var array View mode-icon pairs */
    public static $viewModes = [
        'minimal'  => 'minimal',
        'common'   => 'default',
        'detailed' => 'detailed',
        'tabular'  => 'tabular'
    ];

    /** @var string */
    protected $defaultViewMode;

    /** @var string */
    protected $method = 'POST';

    /** @var callable */
    protected $protector;

    /** @var string */
    protected $viewModeParam = self::DEFAULT_VIEW_MODE_PARAM;

    /** @var array */
    protected $ignoredViewModes = ['tabular'];

    /**
     * Get the default mode
     *
     * @return string
     */
    public function getDefaultViewMode(): string
    {
        return $this->defaultViewMode ?: static::DEFAULT_VIEW_MODE;
    }

    /**
     * Set the default view mode
     *
     * @param string $defaultViewMode
     *
     * @return $this
     */
    public function setDefaultViewMode(string $defaultViewMode): self
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
    public function setViewModeParam(string $viewModeParam): self
    {
        $this->viewModeParam = $viewModeParam;

        return $this;
    }

    /**
     * Get the view mode
     *
     * @return string
     */
    public function getViewMode(): string
    {
        $viewMode = $this->getPopulatedValue($this->getViewModeParam(), $this->getDefaultViewMode());

        if (array_key_exists($viewMode, static::$viewModes)) {
            return $viewMode;
        }

        return $this->getDefaultViewMode();
    }

    /**
     * Set the view mode
     *
     * @param string $name
     *
     * @return $this
     */
    public function setViewMode(string $name)
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
    public function setIdProtector(callable $protector): self
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

    public function addIgnoredViewModes(string ...$viewModes): self
    {
        array_push($this->ignoredViewModes, ...$viewModes);

        return $this;
    }

    protected function assemble()
    {
        $viewModeParam = $this->getViewModeParam();

        $this->addElement($this->createUidElement());
        $this->addElement(new HiddenElement($viewModeParam));

        foreach (static::$viewModes as $viewMode => $icon) {
            if (in_array($viewMode, $this->ignoredViewModes)) {
                continue;
            }

            $protectedId = $this->protectId('view-mode-switcher-' . $icon);
            $input = new InputElement($viewModeParam, [
                'class' => 'autosubmit',
                'id'    => $protectedId,
                'name'  => $viewModeParam,
                'type'  => 'radio',
                'value' => $viewMode
            ]);
            $input->getAttributes()->registerAttributeCallback('checked', function () use ($viewMode) {
                return $viewMode === $this->getViewMode();
            });

            $label = new HtmlElement(
                'label',
                Attributes::create([
                    'for' => $protectedId
                ]),
                new IcingaIcon($icon)
            );
            $label->getAttributes()->registerAttributeCallback('title', function () use ($viewMode) {

                return $this->getTitle($viewMode);
            });

            $this->addHtml($input, $label);
        }
    }

    /**
     * Return the title for the view mode when it is active and inactive
     *
     * @param string $viewMode
     *
     * @return string Title for the view mode when it is active and inactive
     */
    protected function getTitle(string $viewMode): string
    {
        $active = null;
        $inactive = null;
        switch ($viewMode) {
            case 'minimal':
                $active = t('Minimal view active');
                $inactive = t('Switch to minimal view');
                break;
            case 'common':
                $active = t('Common view active');
                $inactive = t('Switch to common view');
                break;
            case 'detailed':
                $active = t('Detailed view active');
                $inactive = t('Switch to detailed view');
                break;
        }

        return $viewMode === $this->getViewMode() ? $active : $inactive;
    }
}
