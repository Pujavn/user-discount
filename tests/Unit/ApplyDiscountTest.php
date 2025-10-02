<?php

use PujaNaik\UserDiscount\Models\{Discount, UserDiscount, DiscountAudit};
use PujaNaik\UserDiscount\Facades\Discounts;
use Illuminate\Support\Facades\DB;

it('applies 10% once per key and enforces per-user cap', function () {
    // Create a user directly (table is created in TestCase)
    $userId = DB::table('users')->insertGetId([
        'name' => 'Test',
        'email' => 'test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create discount (percent path)
    $discount = Discount::create([
        'name'         => 'Welcome 10%',
        'slug'         => 'welcome-10',
        'active'       => true,
        'percent'      => 10,
        'per_user_cap' => 1,
        'starts_at'    => now()->subMinute(),
        'ends_at'      => now()->addDay(),
    ]);

    // Make sure percent persisted
    expect($discount->fresh()->percent)->toBe(10);

    // Assign (idempotent)
    Discounts::assign((object)['id' => $userId], $discount);
    expect(UserDiscount::where('user_id',$userId)->where('discount_id',$discount->id)->exists())->toBeTrue();

    // Eligible should include this discount
    $eligibleIds = Discount::active()
        ->whereNotExpired()
        ->whereHas('userDiscounts', fn($q) => $q->where('user_id', $userId)->whereNull('revoked_at'))
        ->pluck('id')->all();
    expect($eligibleIds)->toContain($discount->id);

    // Apply
    $subtotal = 10_000; // ₹100.00
    $first  = Discounts::apply((object)['id' => $userId], $subtotal, 'ORDER#1'); // expect 1000
    $again  = Discounts::apply((object)['id' => $userId], $subtotal, 'ORDER#1'); // expect 1000 (idempotent)
    $second = Discounts::apply((object)['id' => $userId], $subtotal, 'ORDER#2'); // expect 0 (cap reached)

    // At least one audit with amount > 0
    $applied = DiscountAudit::where('user_id',$userId)->where('discount_id',$discount->id)->where('action','applied')->get();
    expect($applied)->not->toBeEmpty();
    expect((int)$applied->first()->amount_minor)->toBeGreaterThan(0);

    // Final expectations
    expect($first)->toBe(1000)
        ->and($again)->toBe(1000)
        ->and($second)->toBe(0);
});
