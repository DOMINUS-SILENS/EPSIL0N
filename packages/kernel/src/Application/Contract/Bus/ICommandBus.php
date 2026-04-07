<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Contract\Bus;

use Spiral\Kernel\Application\Contract\Command\ICommand;
use Spiral\Kernel\Domain\Shared\Result\Result;

interface ICommandBus
{
    /**
     * Dispatches a command to its registered handler and returns the result.
     *
     * @template TResult
     * @param ICommand<TResult> $command
     * @return Result<TResult>
     */
    public function dispatch(ICommand $command): Result;
}
