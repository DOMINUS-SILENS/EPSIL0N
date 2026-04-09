<?php

declare(strict_types=1);

namespace Epsilone\Contracts\State;

enum LatencyTruth: string
{
    case LocallyStaged = 'locally_staged';
    case DurablyQueued = 'durably_queued';
    case ServerProcessing = 'server_processing';
    case ServerCommitted = 'server_committed';
    case ServerRejected = 'server_rejected';
    case ServerConflicted = 'server_conflicted';
    case SyncNotYetReconciled = 'sync_not_yet_reconciled';
}
