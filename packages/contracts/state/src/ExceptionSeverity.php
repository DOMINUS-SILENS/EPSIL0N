<?php

declare(strict_types=1);

namespace Epsilone\Contracts\State;

enum ExceptionSeverity: string
{
    case Informational = 'informational';
    case Recoverable = 'recoverable';
    case Blocking = 'blocking';
    case IntegrityRisk = 'integrity_risk';
    case SecurityBoundaryViolation = 'security_boundary_violation';
    case IrreversibleActionWarning = 'irreversible_action_warning';
}
