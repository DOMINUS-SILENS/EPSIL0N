<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\ArticleUnite;
use App\Models\CreditReservation;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReservationService
{
    public function __construct(
        protected \App\Services\Sequences\AggregateSequenceService $aggregateSequenceService
    ) {}

    /**
     * Create a hard reservation for credit.
     */
    public function reserveCredit(int $entrepriseId, int $customerId, int $orderId, float $amount, int $expiresInHours = 24): CreditReservation
    {
        return DB::transaction(function () use ($entrepriseId, $customerId, $orderId, $amount, $expiresInHours) {
            // Lock the customer row to check credit limit
            /** @var \stdClass|null $customer */
            $customer = DB::table('customers')
                ->where('id', $customerId)
                ->lockForUpdate()
                ->first();

            if (!$customer) {
                throw new RuntimeException("Customer not found: {$customerId}");
            }

            // Compute used credit: ledger balance + other pending/confirmed reservations
            $used = DB::selectOne("
                SELECT COALESCE(SUM(amount), 0) as used
                FROM credit_reservations
                WHERE customer_id = ? AND status IN ('pending', 'confirmed')
            ", [$customerId])->used;

            //  $ledgerBalance = DB::selectOne("
            //    SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance
            //  FROM journal_lines
            // WHERE customer_id = ?
            // ", [$customerId])->balance;
            // app/Services/ReservationService.php, method reserveCredit
            // Remove or comment out the $ledgerBalance query and use:
            $ledgerBalance = 0;
            $available = $customer->credit_limit - $ledgerBalance - $used;

            if ($available < $amount) {
                throw new RuntimeException("Insufficient credit limit. Available: {$available}, Requested: {$amount}");
            }

            $seq = $this->aggregateSequenceService->next('credit_reservation', $customerId);

            return CreditReservation::create([
                'entreprise_id' => $entrepriseId,
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'amount' => $amount,
                'expires_at' => now()->addHours($expiresInHours),
                'sequence' => $seq,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Create a soft reservation for stock.
     */
    // In app/Services/ReservationService.php
    public function reserveStock(int $productId, int $warehouseId, int $orderId, float $qty, int $expiresInHours = 24): StockReservation
    {
        return DB::transaction(function () use ($productId, $warehouseId, $orderId, $qty, $expiresInHours) {
            $unitId = $this->getDefaultUnitId($productId);
            // Lock the stock row for update to prevent concurrent modifications
            $stock = DB::table('article_unite_depot')
                ->where('article_id', $productId)
                ->where('unite_id', $unitId)
                ->where('depot_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $available = ($stock ? $stock->quantite : 0) - $this->getReservedQuantity($productId, $warehouseId);

            if ($available < $qty) {
                throw new InsufficientStockException($productId, $qty, $available);
            }

            $seq = $this->aggregateSequenceService->next('stock_reservation', $productId);

            return StockReservation::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'order_id' => $orderId,
                'qty' => $qty,
                'expires_at' => now()->addHours($expiresInHours),
                'sequence' => $seq,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Expire soft reservations that have passed their expiry.
     */
    public function expireReservations(): void
    {
        CreditReservation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        StockReservation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get the default unit ID for an article.
     */
    protected function getDefaultUnitId(int $articleId): int
    {
        $unit = ArticleUnite::where('article_id', $articleId)
            ->where('is_default', 1)
            ->first();

        if (!$unit) {
            throw new RuntimeException("No default unit found for article: {$articleId}");
        }

        return $unit->article_unite_id;
    }

    /**
     * Get reserved quantity for a product in a warehouse.
     */
    protected function getReservedQuantity(int $productId, int $warehouseId): float
    {
        return StockReservation::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('qty') ?? 0;
    }
}
