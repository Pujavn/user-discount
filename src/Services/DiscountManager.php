<?php

namespace PujaNaik\UserDiscount\Services;

use Illuminate\Support\Facades\DB;
use PujaNaik\UserDiscount\Models\{Discount, UserDiscount, DiscountAudit};
use PujaNaik\UserDiscount\Events\{DiscountAssigned, DiscountRevoked, DiscountApplied};

class DiscountManager
{
    public function __construct(private array $config) {}

    public function assign($user, Discount $discount): void
    {
        $ud = UserDiscount::firstOrCreate(
            ['user_id' => $user->id, 'discount_id' => $discount->id],
            ['assigned_at' => now()]
        );

        DiscountAudit::create([
            'discount_id' => $discount->id,
            'user_id'     => $user->id,
            'action'      => 'assigned',
        ]);

        event(new DiscountAssigned($user, $discount));
    }

    public function revoke($user, Discount $discount): void
    {
        $ud = UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->first();

        if ($ud) {
            $ud->revoked_at = now();
            $ud->save();

            DiscountAudit::create([
                'discount_id' => $discount->id,
                'user_id'     => $user->id,
                'action'      => 'revoked',
            ]);

            event(new DiscountRevoked($user, $discount));
        }
    }

    public function eligibleFor($user)
    {
        return Discount::active()
            ->whereNotExpired()
            ->whereHas('userDiscounts', fn($q) => $q->where('user_id', $user->id)->whereNull('revoked_at'))
            ->get();
    }

    public function apply($user, int $subtotal, string $applicationKey): int
    {
        return DB::transaction(function () use ($user, $subtotal, $applicationKey) {
            $eligible = $this->eligibleFor($user);

            $totalDiscount = 0;

            foreach ($eligible as $discount) {
                // check caps
                $usage = DiscountAudit::where('discount_id', $discount->id)
                    ->where('user_id', $user->id)
                    ->where('action', 'applied')
                    ->count();

                if ($discount->per_user_cap && $usage >= $discount->per_user_cap) {
                    continue;
                }

                // idempotency
                $exists = DiscountAudit::where('discount_id', $discount->id)
                    ->where('user_id', $user->id)
                    ->where('action', 'applied')
                    ->where('application_key', $applicationKey)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $amount = $discount->percent
                    ? intdiv($subtotal * $discount->percent, 100)
                    : ($discount->fixed_minor ?? 0);

                $totalDiscount += $amount;

                DiscountAudit::create([
                    'discount_id'    => $discount->id,
                    'user_id'        => $user->id,
                    'action'         => 'applied',
                    'application_key'=> $applicationKey,
                    'amount_minor'   => $amount,
                ]);

                event(new DiscountApplied($user, $discount, $amount));
            }

            return $totalDiscount;
        });
    }
}
