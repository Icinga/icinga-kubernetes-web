<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget;

use Countable;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;

class ItemCountIndicator extends BaseHtmlElement implements Countable
{
    use Translation;

    public const STYLE_BALL = 'ball';

    public const STYLE_BOX = 'box';

    protected const MAX_ITEMS = 10;

    protected string $style = self::STYLE_BALL;

    protected array $indicators = [];

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'item-count-indicator'];

    public function getStyle(): string
    {
        return $this->style;
    }

    public function setStyle(string $style): static
    {
        switch ($style) {
            case self::STYLE_BALL:
            case self::STYLE_BOX:
                break;
            default:
                throw new InvalidArgumentException();
        }

        $this->style = $style;

        return $this;
    }


    public function getIndicator(string $state): int
    {
        if (! isset($this->indicators[$state])) {
            return 0;
        }

        return $this->indicators[$state];
    }

    public function addIndicator(string $state, int $count): self
    {
        if (! isset($this->indicators[$state])) {
            $this->indicators[$state] = 0;
        }

        $this->indicators[$state] += $count;

        return $this;
    }

    public function count(): int
    {
        return array_sum($this->indicators);
    }

    protected function assemble(): void
    {
        $priorities = [
            'ok'       => 0,
            'pending'  => 1,
            'unknown'  => 2,
            'warning'  => 3,
            'critical' => 4
        ];


        array_multisort(
            array_map(fn($item) => $priorities[$item], array_keys($this->indicators)),
            SORT_DESC,
            array_keys($this->indicators),
            $this->indicators
        );

        if (count($this) === 0) {
            $this->addAttributes(['class' => 'empty-state']);
            $this->addHtml(new Text($this->translate('None')));
        } elseif (count($this) > static::MAX_ITEMS) {
            $this->addAttributes(['class' => 'text']);

            foreach ($this->indicators as $state => $count) {
                if ($count > 0) {
                    $this->addHtml(new HtmlElement('li', new Attributes(['class' => $state]), new Text($count)));
                }
            }
        } else {
            $this->addAttributes(['class' => $this->getStyle()]);

            foreach ($this->indicators as $state => $count) {
                for ($i = 0; $i < $count; ++$i) {
                    $this->addHtml(new HtmlElement('li', new Attributes(['class' => $state])));
                }
            }
        }
    }
}
