<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\CopyToClipboard;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;

class Annotations extends BaseHtmlElement
{
    use Translation;

    protected iterable $annotations;

    protected $tag = 'section';

    protected $defaultAttributes = ['class' => 'annotations'];

    public function __construct(iterable $annotations)
    {
        $this->annotations = $annotations;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement('h2', null, new Text($this->translate('Annotations'))));

        $annotations = yield_iterable($this->annotations);
        if ($annotations->valid()) {
            $container = new HtmlElement(
                'div',
                new Attributes([
                    'class'               => 'collapsible',
                    'data-visible-height' => 100
                ])
            );

            foreach ($annotations as $annotation) {
                $value = json_decode($annotation->value);

                $container->addHtml(new HorizontalKeyValue(
                    $annotation->name,
                    $value !== null && ! is_scalar($value) ?
                        CopyToClipboard::attachTo(new HtmlElement(
                            'pre',
                            null,
                            new Text(json_encode(
                                $value,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                            ))
                        )) :
                        $annotation->value
                ));
            }

            $this->addHtml($container);
        } else {
            $this->addHtml(new EmptyState($this->translate('No items to display.')));
        }
    }
}
