<?php

declare(strict_types=1);

namespace Epsilone\Contracts\State;

enum CanonicalViewState: string
{
    case Empty = 'empty';
    case Loading = 'loading';
    case Partial = 'partial';
    case Ready = 'ready';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Processing = 'processing';
    case Synced = 'synced';
    case Stale = 'stale';
    case Conflicted = 'conflicted';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Unauthorized = 'unauthorized';
    case Archived = 'archived';
}
