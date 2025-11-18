<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications\V1;

use Generator;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\Config as KConfig;
use Icinga\Module\Notifications\Hook\V1\SourceHook;
use InvalidArgumentException;
use ipl\Html\Contract\Form;
use ipl\Html\HtmlDocument;
use ipl\I18n\Translation;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Control\SearchEditor;
use ipl\Web\Filter\Renderer;
use ipl\Web\Widget\Icon;
use JsonException;

class Source implements SourceHook
{
    use Translation;

    public function getSourceType(): string
    {
        return KConfig::DEFAULT_NOTIFICATIONS_TYPE;
    }

    public function getSourceLabel(): string
    {
        return KConfig::DEFAULT_NOTIFICATIONS_NAME;
    }

    public function getSourceIcon(): Icon
    {
        return new Icon('globe');
    }

    public function getRuleFilterTargets(int $sourceId): array
    {
        $kinds = [
            'daemonset'   => $this->translate('Daemon Sets'),
            'deployment'  => $this->translate('Deployments'),
            'node'        => $this->translate('Nodes'),
            'pod'         => $this->translate('Pods'),
            'replicaset'  => $this->translate('Replica Sets'),
            'statefulset' => $this->translate('Stateful Sets')
        ];

        $generator = static function () use ($sourceId, $kinds): Generator {
            foreach ($kinds as $kind => $label) {
                yield json_encode(['version' => 1, 'source' => $sourceId, 'kind' => $kind]) => $label;
            }
        };

        return iterator_to_array($generator());
    }

    public function getRuleFilterEditor(string $filter): SearchEditor
    {
        try {
            $data = json_decode($filter, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Logger::error('Failed to parse rule filter configuration: %s (Error: %s)', $filter, $e);
            throw new ConfigurationError($this->translate(
                'Failed to parse rule filter configuration. Please contact your system administrator.'
            ));
        }

        if ($data['version'] !== 1 || ! isset($data['kind']) || ! isset($data['source'])) {
            Logger::error('Invalid rule filter configuration: %s', $filter);
            throw new ConfigurationError($this->translate(
                'Invalid rule filter configuration. Please contact your system administrator.'
            ));
        }

        $kind = $data['kind'];
        $source = $data['source'];

        $url = Factory::createListUrl($kind);//->with(['_disableLayout' => true, 'showCompact' => true]);
        $url->setPath($url->getPath() . '/complete');
        $editor = new SearchEditor();
        $editor->setQueryString($data['filter'] ?? '');
        $editor->setSuggestionUrl($url);
        $editor->on(HtmlDocument::ON_ASSEMBLED, function (SearchEditor $editor) use ($kind, $source) {
            // TODO(el): Comment and code is copied from Icinga DB Web. Check why this is necessary.
            // Not using addElement, as otherwise the submit button is hidden because it's not last-of-type
            $kind = $editor->createElement('hidden', 'kind', ['value' => $kind]);
            $editor->registerElement($kind);
            $editor->prependHtml($kind);

            $source = $editor->createElement('hidden', 'source', ['value' => $source]);
            $editor->registerElement($source);
            $editor->prependHtml($source);
        });

        return $editor;
    }

    public function serializeRuleFilter(Form $editor): string
    {
        if (! $editor instanceof SearchEditor) {
            throw new InvalidArgumentException('Editor must be an instance of ' . SearchEditor::class);
        }

        $rule = $editor->getFilter();
        $filter = (new Renderer($rule))->render();
        if ($filter === '') {
            return '';
        }

        $kind = $editor->getElement('kind')->getValue();

        $model = Factory::createModel($kind);
        $query = $model::on(Database::connection())->filter(Filter::all(
            Filter::equal('uuid', ':uuid'),
            Filter::equal('cluster_uuid', ':cluster_uuid')
        ));
        $query->columns([new Expression('1')])->filter($rule)->limit(1);
        [$sql, $parameters] = $query->getDb()->getQueryBuilder()->assembleSelect(
            $query->assembleSelect()->resetOrderBy()
        );

        return json_encode([
            'version' => 1,
            'source'  => $editor->getElement('source')->getValue(),
            'kind'    => $kind,
            'filter'  => $filter,
            'query'   => $sql,
            'args'    => $parameters
        ], JSON_THROW_ON_ERROR);
    }
}
