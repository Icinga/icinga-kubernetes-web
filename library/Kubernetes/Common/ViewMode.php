<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

enum ViewMode: string
{
    case Minimal = 'minimal';
    case Common = 'common';
    case Detailed = 'detailed';
}
