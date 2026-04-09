<?php

declare(strict_types=1);

namespace Epsilone\Contracts\State;

enum PermissionEffect: string
{
    case Hidden = 'hidden';
    case Disabled = 'disabled';
    case Enabled = 'enabled';
}
