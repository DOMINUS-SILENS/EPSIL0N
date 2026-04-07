<?php
declare(strict_types=1);

namespace App\Service;

class WindsurfCascadeClient implements CascadeClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function invoke(string $prompt): array
    {
        // TODO: Real Windsurf API call
        // Simulate from previous plan
        return [
            "conditions" => ["wind" => "15kt"],
            "safety_check" => ["PFD", "buddy"],
            "phases" => [["warm-up" => "10min"]]
        ];
    }
}
