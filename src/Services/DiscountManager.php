<?php

namespace PujaNaik\UserDiscount\Services;

use Illuminate\Support\Facades\DB;
use PujaNaik\UserDiscount\Models\{Discount, UserDiscount, DiscountAudit};
use PujaNaik\UserDiscount\Events\{DiscountAssigned, DiscountRevoked, DiscountApplied};
use Illuminate\Database\QueryException;

class DiscountManager
{
    public function __construct(private array $config) {}

    public function assign($user, Discount $discount): void
    {
        DB::transaction(function () use ($user, $discount) {
            // idempotent assignment
            $ud = UserDiscount::firstOrCreate(
                ['user_id' => $user->id, 'discount_id' => $discount->id],
                ['assigned_at' => now()]
            );

            if ($ud->wasRecentlyCreated) {
                DiscountAudit::create([
                    'discount_id' => $discount->id,
                    'user_id'     => $user->id,
                    'action'      => 'assigned',
                ]);
            }

            DB::afterCommit(fn() => event(new DiscountAssigned($user, $discount)));
        });
    }

    public function revoke($user, Discount $discount): void
    {
        DB::transaction(function () use ($user, $discount) {
            $ud = UserDiscount::where('user_id', $user->id)
                ->where('discount_id', $discount->id)
                ->first();

            if ($ud && is_null($ud->revoked_at)) {
                $ud->revoked_at = now();
                $ud->save();

                DiscountAudit::create([
                    'discount_id' => $discount->id,
                    'user_id'     => $user->id,
                    'action'      => 'revoked',
                ]);

                DB::afterCommit(fn() => event(new DiscountRevoked($user, $discount)));
            }
        });
    }

    public function eligibleFor($user)
    {
        return Discount::active()
            ->whereNotExpired()
            ->whereHas(
                'userDiscounts',
                fn($q) =>
                $q->where('user_id', $user->id)->whereNull('revoked_at')
            )
            ->orderByDesc('priority')
            ->get();
    }

    public function apply($user, int $subtotalMinor, string $applicationKey): int
    {
        return DB::transaction(function () use ($user, $subtotalMinor, $applicationKey) {
            $eligible = $this->eligibleFor($user);

            $totalDiscount = 0;

            foreach ($eligible as $discount) {
                // per-user usage cap
                $usage = DiscountAudit::where('discount_id', $discount->id)
                    ->where('user_id', $user->id)
                    ->where('action', 'applied')
                    ->count();

                if ($discount->per_user_cap && $usage >= $discount->per_user_cap) {
                    continue;
                }

                // idempotency check (fast path)
                $exists = DiscountAudit::where('discount_id', $discount->id)
                    ->where('user_id', $user->id)
                    ->where('action', 'applied')
                    ->where('application_key', $applicationKey)
                    ->first();

                if ($exists) {
                    // if same key used, re-count its amount (idempotent)
                    $totalDiscount += (int) ($exists->amount_minor ?? 0);
                    continue;
                }

                // compute amount (percent or fixed), with safe caps
                $amount = $this->computeAmount($discount, $subtotalMinor);
                if ($amount <= 0) {
                    continue;
                }

                // insert audit row; rely on unique index as final guard
                try {
                    DiscountAudit::create([
                        'discount_id'     => $discount->id,
                        'user_id'         => $user->id,
                        'action'          => 'applied',
                        'application_key' => $applicationKey,
                        'amount_minor'    => $amount,
                    ]);
                } catch (QueryException $e) {
                    // if another concurrent txn inserted the same (unique) audit, treat as idempotent
                    if ($this->isDuplicateKey($e)) {
                        // re-fetch the row and use its amount
                        $row = DiscountAudit::where('discount_id', $discount->id)
                            ->where('user_id', $user->id)
                            ->where('action', 'applied')
                            ->where('application_key', $applicationKey)
                            ->first();
                        $amount = (int) ($row->amount_minor ?? 0);
                    } else {
                        throw $e;
                    }
                }

                $totalDiscount += $amount;

                DB::afterCommit(fn() => event(new DiscountApplied($user, $discount, $amount)));

                // If you implement stacking/exclusive tags, you can break/continue here deterministically
                // e.g., if (!$this->canStackFurther($discount)) break;
            }

            return $totalDiscount;
        });
    }

    private function computeAmount(Discount $discount, int $subtotalMinor): int
    {
        // optional overall percent safety cap from config (e.g., 80)
        $maxPercent = (int) ($this->config['max_percent_cap'] ?? 100);

        if (!empty($discount->percent)) {
            $percent = min((int) $discount->percent, $maxPercent);
            $raw = ($subtotalMinor * $percent) / 100;
        } else {
            // Use the field you actually store: fixed_minor or fixed (minor units)
            $fixed = (int) ($discount->fixed_minor ?? $discount->fixed ?? 0);
            $raw = min($fixed, $subtotalMinor);
        }

        // rounding strategy from config (floor|ceil|bankers)
        $rounding = $this->config['rounding'] ?? 'floor';
        return match ($rounding) {
            'ceil'    => (int) ceil($raw),
            'bankers' => (int) round($raw, 0, PHP_ROUND_HALF_EVEN),
            default   => (int) floor($raw),
        };
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'Duplicate entry')
            || str_contains($msg, 'UNIQUE constraint failed')
            || str_contains($msg, 'duplicate key value violates unique constraint');
    }
}
