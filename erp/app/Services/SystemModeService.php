<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemModeService
{
    public const MODE_NORMAL = 'NORMAL';

    public const MODE_DEGRADED = 'DEGRADED';

    public const MODE_SAFE_HALT = 'SAFE_HALT';

    public function getCurrentMode(): string
    {
        return Cache::remember('system_mode', 60, function () {
            $mode = DB::table('system_modes')->where('is_active', true)->first();

            return $mode ? $mode->mode : self::MODE_NORMAL;
        });
    }

    public function setMode(string $mode): void
    {
        DB::transaction(function () use ($mode) {
            DB::table('system_modes')->update(['is_active' => false]);
            DB::table('system_modes')->where('mode', $mode)->update(['is_active' => true]);
        });
        Cache::forget('system_mode');
    }

    /**
     * Check if the system is in a given mode.
     */
    public function isMode(string $mode): bool
    {
        return $this->getCurrentMode() === $mode;
    }

    /**
     * Can we process a certain priority?
     */
    public function canProcess(string $priority): bool
    {
        $mode = $this->getCurrentMode();

        if ($mode === self::MODE_SAFE_HALT) {
            return false; // no processing
        }

        if ($mode === self::MODE_DEGRADED) {
            // Only P0 financial allowed
            return $priority === 'p0_financial';
        }

        return true; // NORMAL – everything allowed
    }

    public function allowedOperations(): array
    {
        return match ($this->getCurrentMode()) {
            self::MODE_NORMAL => ['all'],
            self::MODE_DEGRADED => ['p0_financial', 'read'],
            self::MODE_SAFE_HALT => ['read'],
            default => [],
        };
    }
}
