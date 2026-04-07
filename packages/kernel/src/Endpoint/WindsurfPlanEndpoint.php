<?php
declare(strict_types=1);

namespace App\Endpoint;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\RoadRunner\PSR7\Psr7;
use Spiral\DataBridge\Database\CacheInterface;
use App\Service\CascadeClientInterface;

class WindsurfPlanEndpoint
{
    public function __construct(
        private CascadeClientInterface $cascadeClient,
        private CacheInterface $cache
    ) {}

    public function generatePlan(ServerRequestInterface $request): ResponseInterface
    {
        $psr7 = Psr7::fromGlobal();

        $data = json_decode((string) $request->getBody(), true) ?: [];

        $level = filter_var($data["level"] ?? 1, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
        $wind = filter_var($data["wind"] ?? 10, FILTER_VALIDATE_INT, ["options" => ["min_range" => 5, "max_range" => 40]]);
        $location = htmlspecialchars(trim($data["location"] ?? "open water"));

        if (!$level || !$wind) {
            return $psr7->json(["error" => "Invalid level/wind"], 400);
        }

        $cacheKey = "windsurf_plan_l{$level}_w{$wind}_" . md5($location);

        if ($cached = $this->cache->get($cacheKey)) {
            return $psr7->json(json_decode($cached, true), 200);
        }

        try {
            $prompt = "@windsurf-gsd /gsd:plan --level={$level} --wind={$wind}kt --location=\"{$location}\"";
            $plan = $this->cascadeClient->invoke($prompt);

            $this->cache->set($cacheKey, json_encode($plan), 3600);
            return $psr7->json($plan, 200);
        } catch (\Exception $e) {
            return $psr7->json(["error" => "Cascade API failed: " . $e->getMessage()], 500);
        }
    }
}
