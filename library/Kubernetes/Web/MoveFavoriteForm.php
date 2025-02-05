<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Model\Favorite;
use ipl\Html\Form;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Stdlib\Filter;
use ipl\Web\Common\CsrfCounterMeasure;
use LogicException;
use Ramsey\Uuid\Uuid;

class MoveFavoriteForm extends Form
{
    use CsrfCounterMeasure;

    protected $defaultAttributes = ['hidden' => true];

    protected $method = 'POST';

    /** @var Connection */
    protected $db;

    /** @var string */
    protected $kind;

    /**
     * Create a new MoveFavoriteForm
     *
     * @param ?Connection $db
     */
    public function __construct(Connection $db = null)
    {
        $this->db = $db;
    }

    /**
     * Get the kind
     *
     * @return string
     */
    public function getKind(): string
    {
        if ($this->kind === null) {
            throw new LogicException('The form must be successfully submitted first');
        }

        return $this->kind;
    }

    protected function assemble(): void
    {
        $this->addElement('hidden', 'uuid', ['required' => true]);
        $this->addElement('hidden', 'priority', ['required' => true]);
    }

    protected function onSuccess(): void
    {
        $favoriteUuid = Uuid::fromString($this->getValue('uuid'))->getBytes();
        $newPriority = $this->getValue('priority');

        /** @var ?Favorite $favorite */
        $favorite = Favorite::on($this->db)
            ->columns(['kind', 'priority'])
            ->filter(Filter::all(
                Filter::equal('resource_uuid', $favoriteUuid),
                Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
            ))
            ->first();
        if ($favorite === null) {
            throw new HttpNotFoundException('Favorite not found');
        }

        $transactionStarted = ! $this->db->inTransaction();
        if ($transactionStarted) {
            $this->db->beginTransaction();
        }

        $this->kind = $favorite->kind;

        // Free up the current priority used by the favorite in question
        $this->db->update('favorite', ['priority' => null], ['resource_uuid = ?' => $favoriteUuid]);

        // Update the priorities of the favorites that are affected by the move
        if ($newPriority < $favorite->priority) {
            $affectedFavorites = $this->db->select(
                (new Select())
                    ->columns('resource_uuid')
                    ->from('favorite')
                    ->where([
                        'kind = ?'      => $favorite->kind,
                        'priority >= ?' => $newPriority,
                        'priority < ?'  => $favorite->priority
                    ])
                    ->orderBy('priority', SORT_DESC)
            );
            foreach ($affectedFavorites as $affectedFavorite) {
                $this->db->update(
                    'favorite',
                    ['priority' => new Expression('priority + 1')],
                    ['resource_uuid = ?' => $affectedFavorite->resource_uuid]
                );
            }
        } elseif ($newPriority > $favorite->priority) {
            $affectedFavorites = $this->db->select(
                (new Select())
                    ->columns('resource_uuid')
                    ->from('favorite')
                    ->where([
                        'kind = ?'      => $favorite->kind,
                        'priority > ?'  => $favorite->priority,
                        'priority <= ?' => $newPriority
                    ])
                    ->orderBy('priority ASC')
            );
            foreach ($affectedFavorites as $affectedFavorite) {
                $this->db->update(
                    'favorite',
                    ['priority' => new Expression('priority - 1')],
                    ['resource_uuid = ?' => $affectedFavorite->resource_uuid]
                );
            }
        }

        // Now insert the favorite at the new priority
        $this->db->update('favorite', ['priority' => $newPriority], ['resource_uuid = ?' => $favoriteUuid]);

        if ($transactionStarted) {
            $this->db->commitTransaction();
        }
    }
}
