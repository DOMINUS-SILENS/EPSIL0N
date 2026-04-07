<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Contract\Handler;

use Spiral\Kernel\Application\Contract\Command\ICommand;
use Spiral\Kernel\Domain\Shared\Result\Result;

/**
 * @template TCommand of ICommand
 * @template TResult
 */
interface ICommandHandler
{
    /**
     * @param TCommand $command
     * @return Result<TResult>
     */
    public function handle(ICommand $command): Result;
}
