<?php
declare(strict_types=1);

namespace App\Service;

interface CascadeClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function invoke(string $prompt): array;
}
