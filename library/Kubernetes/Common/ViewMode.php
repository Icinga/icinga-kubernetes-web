<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

enum ViewMode: string
{
    case Minimal = 'minimal';
    case Common = 'common';
    case Detailed = 'detailed';
}
