<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCanonicalRouting;
use App\Models\Scopes\ActiveScope;

class Article extends Model
{
    use Cacheable, HasFactory, HasCanonicalRouting;

    /**
     * Parallel Reconstruction Mapping (v11)
     */
    protected $legacyTable = 'article'; // Default legacy
    protected $canonicalTable = 'articles'; // Target canonical

    protected $primaryKey = 'id';

    public $incrementing = true;

    /**
     * Use $guarded for dynamic fields to improve security
     */
    protected $guarded = ['id'];

    /**
     * Optimized casts for Canonical Schema v1
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_stock_managed' => 'boolean',
        'is_archived' => 'boolean',
        'stock_quantity' => 'decimal:3',
        'min_quantity' => 'decimal:3',
        'optimal_quantity' => 'decimal:3',
        'price_purchase' => 'decimal:2',
        'price_selling' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Hidden fields for API responses
     */
    protected $hidden = [
        'article_project_id',
        'project_modele_quantite',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache on save/update/delete
        static::saved(function ($article) {
            $article->invalidateCache();
        });

        static::deleted(function ($article) {
            $article->invalidateCache();
        });
    }

    /**
     * Invalidate relevant cache entries
     */
    public function invalidateCache(): void
    {
        $keys = [
            "article:{$this->id}",
            "article:ean:{$this->ean13}",
            "article:barcode:{$this->bar_code}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Invalidate family and brand caches
        if ($this->article_famille_id) {
            Cache::forget("family:articles:{$this->article_famille_id}");
        }
    }

    // =============================================================================
    // RELATIONSHIPS
    // =============================================================================

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(ArticleFamille::class, 'article_famille_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ArticleMarque::class, 'article_marque_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ArticleUnite::class, 'article_id', 'article_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(BalanceStock::class, 'article_id', 'article_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ArticleMovement::class, 'article_id', 'article_id');
    }

    // =============================================================================
    // SCOPES
    // =============================================================================

    /**
     * Scope for active products only (Canonical names)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    /**
     * Scope for stock-managed products
     */
    public function scopeStockManaged($query)
    {
        return $query->where('is_stock_managed', true);
    }

    /**
     * Scope for products by enterprise
     */
    public function scopeForEnterprise($query, int $enterpriseId)
    {
        return $query->where('entreprise_id', $enterpriseId);
    }

    /**
     * Scope for products in a family
     */
    public function scopeInFamily($query, int $familyId)
    {
        return $query->where('article_famille_id', $familyId);
    }

    /**
     * Scope for products by EAN13
     */
    public function scopeByEan13($query, string $ean13)
    {
        return $query->where('ean13', $ean13);
    }

    /**
     * Scope for products by barcode
     */
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('bar_code', $barcode);
    }

    // =============================================================================
    // CACHED ACCESSORS
    // =============================================================================

    /**
     * Find by EAN13 with caching
     */
    public static function findByEan13(string $ean13): ?self
    {
        return self::cached("ean:{$ean13}", function () use ($ean13) {
            return self::active()
                ->where('ean13', $ean13)
                ->first();
        }, 300);
    }

    /**
     * Find by barcode with caching
     */
    public static function findByBarcode(string $barcode): ?self
    {
        return self::cached("barcode:{$barcode}", function () use ($barcode) {
            return self::active()
                ->where('bar_code', $barcode)
                ->first();
        }, 300);
    }

    /**
     * Get stock for a specific depot with caching
     */
    public function getStockForDepot(int $depotId): ?float
    {
        return self::cached("stock:{$this->id}:{$depotId}", function () use ($depotId) {
            $balance = BalanceStock::where('article_id', $this->id)
                ->where('depot_id', $depotId)
                ->first();

            return $balance ? (float) $balance->quantite_disponible : null;
        }, 30); // Short TTL for stock
    }

    // =============================================================================
    // COMPUTED ATTRIBUTES
    // =============================================================================

    /**
     * Get full designation with abbreviation
     */
    public function getFullDesignationAttribute(): string
    {
        if ($this->abreviation) {
            return "{$this->designation} ({$this->abreviation})";
        }
        return $this->designation;
    }

    /**
     * Check if stock is low
     */
    public function getIsStockLowAttribute(): bool
    {
        if (!$this->is_stock_managed) {
            return false;
        }

        $stock = $this->quantite_stock ?? 0;
        $min = $this->quantite_min ?? 0;

        return $stock <= $min;
    }

    /**
     * Get available stock percentage
     */
    public function getStockPercentageAttribute(): ?float
    {
        if (!$this->is_stock_managed || !$this->quantite_optimale) {
            return null;
        }

        $stock = $this->quantite_stock ?? 0;
        $optimal = $this->quantite_optimale;

        return min(100, round(($stock / $optimal) * 100, 2));
    }
}
