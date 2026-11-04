<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Kubernetes\ProvidedHook\Notifications\V2;

use Icinga\Module\Notifications\Hook\V2\V1FilterMigrationHook;
use ipl\Stdlib\Filter\Chain;

class V1FilterMigration implements V1FilterMigrationHook
{
    public function getSourceType(): string
    {
        // TODO: Implement getSourceType() method.
    }

    public function migrateFilter(string $v1Filter): Chain
    {
        // TODO: Implement migrateFilter() method.
    }
}
