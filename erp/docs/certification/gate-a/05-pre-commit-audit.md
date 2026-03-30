# Gate A Addendum — Pre-Commit Side Effects Audit

**Status**: ✅ Passed & Fixed
**Date**: 29 March 2026

## 1. Scope & Objective
This audit guarantees that **NO asynchronous or non-transactional side-effects** occur before a database transaction makes its absolute commit. A side effect triggered before the DB commit leads to a "Distributed Lie" if the database eventually rolls back.

## 2. Findings (Grep Analysis)

An automated regex scan across `app/Services`, `app/Models`, and `app/Console`:
```bash
grep -rnE "(Redis::publish|dispatch\(|event\()" app/
```

**Results Evaluated**:
1. `App\Services\OutboxService::publishDomain`
   * **Violation Found**: Real-time Broadcast via `\Illuminate\Support\Facades\Redis::publish` was executed inside the `DB::transaction()` closure. This is a severe pre-commit side effect because the transaction has not been flushed to disk yet.
   * **Resolution**: Wrapped the publish call within `DB::afterCommit(function() {...})`.

2. `App\Services\SyncBatchService::processChunk`
   * **Violation Found**: `$this->publishToRedis()` was called sequentially at the end of the transaction logic but *before* implicit commit.
   * **Resolution**: Wrapped via `DB::afterCommit(...)`.

3. `App\Console\Commands\ProcessOutbox`
   * **Status**: Safe. `ProcessOutbox` reads from the already committed table. Side effects are intentional here post-commit.

4. `App\Http\Controllers\Api\SyncController` (legacy)
   * **Status**: Deprecated/Legacy code contained inline `Redis::publish`. Has been superseded by the `SyncBatchService`. 

## 3. Policy Enforcement
Any new broadcasts, queue jobs (`dispatch()`), or application events (`event()`) triggered during a state mutation **must** be contained either:
1. Implicitly inside `DomainOutbox` (Our chosen primary pattern).
2. Explicitly inside `DB::afterCommit(fn() => ...)` if real-time volatility is desired.
