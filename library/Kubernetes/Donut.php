<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Table;
use ipl\Html\Text;

class Donut extends BaseHtmlElement
{
    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'donut'];

    /**
     * The donut data
     *
     * @var iterable
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $heading;

    /**
     * @var int
     */
    protected $headingLevel = 2;

    /**
     * @var callable
     */
    protected $labelCallback;

    /**
     * Set the data to display
     *
     * @param iterable $data
     *
     * @return $this
     */
    public function setData(iterable $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setHeading(string $heading, int $level): self
    {
        $this->heading = $heading;
        $this->headingLevel = $level;

        return $this;
    }

    public function setLabelCallback(callable $callback): self
    {
        $this->labelCallback = $callback;

        return $this;
    }

    public function assemble()
    {
        $donut = new \Icinga\Chart\Donut();
        $legend = new Table();

        foreach ($this->data as $index => $value) {
            $donut->addSlice((int) $value, ['class' => 'segment-' . $index]);
            $legend->add(
                [
                    New HtmlElement('span', new Attributes(['class' => 'badge badge-' . $index])),
                    call_user_func($this->labelCallback, $index),
                    $value
                ]
            );
        }

        if ($this->heading !== null) {
            $this->addHtml(new HtmlElement("h{$this->headingLevel}", null, new Text($this->heading)));
        }

        $this->addHtml(new HtmlString($donut->render()), $legend);
    }
}
