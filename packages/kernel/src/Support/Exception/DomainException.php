<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Base exception for domain-layer failures.
 *
 * DomainException represents business rule violations and domain constraint
 * failures. These are expected failure modes within the business context
 * and should be handled gracefully by the application layer.
 *
 * Unlike KernelException, DomainException indicates that something about
 * the business state or rules prevented the operation - NOT a programming error.
 *
 * Subclasses should provide specific error codes and context for each
 * failure scenario to enable proper error responses and logging.
 *
 * @package Spiral\Kernel\Support\Exception
 */
abstract class DomainException extends KernelException
{
}