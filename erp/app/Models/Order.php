<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\HasCanonicalRouting;

class Order extends Model
{
    use Cacheable, HasFactory, HasCanonicalRouting;

    protected $legacyTable = 'orders'; // Legacy
    protected $canonicalTable = 'orders'; // Canonical Target

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Order statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Active order statuses (affect credit calculation)
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_CONFIRMED,
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
    ];

    protected $guarded = [];

    protected $casts = [
        'entreprise_id' => 'integer',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'ordered_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            // Clear customer orders cache
            Cache::forget("customer:orders:{$order->customer_id}");
            Cache::forget("customer:balance:{$order->customer_id}");
        });

        static::updated(function ($order) {
            if ($order->isDirty('status')) {
                Cache::forget("customer:orders:{$order->customer_id}");
                Cache::forget("customer:balance:{$order->customer_id}");
            }
        });
    }

    // =============================================================================
    // RELATIONSHIPS
    // =============================================================================

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =============================================================================
    // SCOPES
    // =============================================================================

    /**
     * Scope for active orders (affecting credit)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Scope for orders by customer
     */
    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for orders by creator (sales rep)
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope for orders by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for orders created after date
     */
    public function scopeCreatedAfter($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /**
     * Scope for orders created before date
     */
    public function scopeCreatedBefore($query, $date)
    {
        return $query->where('created_at', '<=', $date);
    }

    /**
     * Scope for today's orders
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // =============================================================================
    // QUERY HELPERS
    // =============================================================================

    /**
     * Get customer total orders with caching
     */
    public static function getCustomerTotal(string $customerId): float
    {
        return self::cached("customer:total:{$customerId}", function () use ($customerId) {
            return self::forCustomer($customerId)
                ->active()
                ->sum('total_amount') ?: 0;
        }, 60);
    }

    /**
     * Get orders by sales rep with pagination
     */
    public static function getBySalesRep(int $userId, int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = self::byCreator($userId)
            ->with(['lines', 'customer'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->forCustomer($filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->createdAfter($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->createdBefore($filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    // =============================================================================
    // COMPUTED ATTRIBUTES
    // =============================================================================

    /**
     * Check if order can be edited
     */
    public function getCanEditAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }

    /**
     * Check if order can be cancelled
     */
    public function getCanCancelAttribute(): bool
    {
        return !in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DELIVERED]);
    }

    /**
     * Get formatted status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SUBMITTED => 'Soumis',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En traitement',
            self::STATUS_SHIPPED => 'Expédié',
            self::STATUS_DELIVERED => 'Livré',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }

    /**
     * Get total quantity of items
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->lines->sum('quantity');
    }

    /**
     * Get item count
     */
    public function getItemCountAttribute(): int
    {
        return $this->lines->count();
    }
}
