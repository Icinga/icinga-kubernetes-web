<?php

namespace Icinga\Module\Kubernetes\Web;

use gipfl\IcingaWeb2\Icon;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class Hint extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['id' => 'reorder-hint'];

    protected string $hint;

    public function __construct(string $hint)
    {
        $this->hint = $hint;
    }

//    const POPUP_HTML = '<div id="reorder-hint">\n' +
//    '   <i class="icon fa-lightbulb fa"></i>\n' +
//    '   <p>To reorder favorites via drag &amp; drop sort by \'Custom Order\'.</p>\n' +
//    '   <span class="button-container">\n' +
//    '      <button class="close link-button">Close</button>\n' +
//    '      <button class="close-forever link-button">Never show again</button>\n' +
//    '   </span>\n' +
//    '   <i class="minimize fa-minus fa"></i>\n' +
//    '</div>';

    public function assemble()
    {
        $this->addHtml(new Icon('lightbulb'));
        $this->addHtml(Html::tag('p', content: $this->hint));
        $this->addHtml(Html::tag('span', ['class' => 'button-container'], [
            Html::tag('button', ['class' => 'link-button close'], 'Close'),
            Html::tag('button', ['class' => 'link-button close-forever'], 'Never show again')
        ]));
    }
}
