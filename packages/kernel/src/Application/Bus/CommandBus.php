<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Bus;

use Spiral\Kernel\Application\Contract\Bus\ICommandBus;
use Spiral\Kernel\Application\Contract\Command\ICommand;
use Spiral\Kernel\Application\Contract\Handler\ICommandHandler;
use Spiral\Kernel\Domain\Shared\Result\Result;

final class CommandBus implements ICommandBus
{
    /** @var array<class-string<ICommand<mixed>>, ICommandHandler<ICommand<mixed>, mixed>> */
    private array $handlers = [];

    /**
     * @template TCommand of ICommand
     * @template TResult
     * @param class-string<TCommand> $commandClass The class name of the command
     * @param ICommandHandler<TCommand, TResult> $handler The handler for the command
     */
    public function registerHandler(string $commandClass, ICommandHandler $handler): void
    {
        /** @var ICommandHandler<ICommand<mixed>, mixed> $handler */
        $this->handlers[$commandClass] = $handler;
    }

    /**
     * @template TResult
     * @param ICommand<TResult> $command
     * @return Result<TResult>
     */
    public function dispatch(ICommand $command): Result
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new \RuntimeException("No handler registered for command: {$commandClass}");
        }

        /** @var ICommandHandler<ICommand<TResult>, TResult> $handler */
        $handler = $this->handlers[$commandClass];

        return $handler->handle($command);
    }
}
