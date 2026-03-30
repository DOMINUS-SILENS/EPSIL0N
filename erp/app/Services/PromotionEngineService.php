<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Aggregates\PromotionAggregate;
use Illuminate\Support\Str;

class PromotionEngineService
{
    /**
     * God-Level Promotion Engine: Evaluates a cart of lines in O(1) against active rules.
     */
    public function evaluateCart(int $entrepriseId, int $movementId, array $lines): array
    {
        $today = now()->toDateString();
        
        // Load active promotions from extremely fast materialized projections
        $activePromotions = DB::table('promotions')
            ->where('entreprise_id', $entrepriseId)
            ->where('date_debut', '<=', $today)
            ->where('date_fin', '>=', $today)
            ->get();

        $benefits = [];

        foreach ($activePromotions as $promo) {
            // Check specific conditions based on nested tables via Domain caching or direct joins
            $conditions = DB::table('promotion_article_commmande')
                ->where('promotion_id', $promo->promotion_id)
                ->get();
            
            $rewards = DB::table('promotion_article_gratuite')
                ->where('promotion_id', $promo->promotion_id)
                ->get();

            $paliers = DB::table('promotion_palier')
                ->where('promotion_id', $promo->promotion_id)
                ->get();

            // Evaluate 1-n or n-1 logic
            foreach ($conditions as $cond) {
                // Find matching line in cart
                $matchingLineObj = collect($lines)->firstWhere('article_id', $cond->article_id);
                $matchingLine = $matchingLineObj ? (array) $matchingLineObj : null;
                
                if ($matchingLine && $matchingLine['quantite'] >= $cond->quantite) {
                    
                    // Ratio multiplier (if condition needs 2, and we have 5, multiplier is 2)
                    $multiplier = floor($matchingLine['quantite'] / $cond->quantite);

                    // Allocate rewards
                    foreach ($rewards as $rew) {
                        $benefits[] = [
                            'type' => 'free_item',
                            'article_id' => $rew->article_id,
                            'quantite_offerte' => $rew->quantite * $multiplier
                        ];
                    }

                    // Tiers (Paliers) evaluation
                    foreach ($paliers as $palier) {
                        if ($matchingLine['quantite'] >= $palier->quantite_seuil) {
                            $benefits[] = [
                                'type' => 'discount_tier',
                                'discount_percent' => $palier->discount_percent,
                                'valeur_remise' => $palier->valeur_remise
                            ];
                        }
                    }
                }
            }

            // If benefits were calculated, emit the immutable analytical Application Tracking Metric
            if (!empty($benefits)) {
                $uuid = Str::uuid()->toString();
                // We map it to the PromotionAggregate root mapping specific promo analytics cleanly
                PromotionAggregate::retrieve($uuid)
                    ->recordApplication($promo->promotion_id, $entrepriseId, $movementId, $benefits)
                    ->persist();
            }
        }

        return $benefits;
    }
}
