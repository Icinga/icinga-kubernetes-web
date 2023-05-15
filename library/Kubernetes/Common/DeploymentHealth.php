<?php

namespace Icinga\Module\Kubernetes\Common;

class DeploymentHealth
{
    const HEALTHY = 'healthy';
    const UNHEALTHY = 'unhealthy';
    const CRITICAL = 'critical';
    const UNKNOWN = 'unknown';
}