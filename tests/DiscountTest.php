<?php

use PujaNaik\UserDiscount\Facades\Discounts;
use PujaNaik\UserDiscount\Models\Discount;

it('applies percent discount with cap', function () {
    $user = \App\Models\User::factory()->create();
    $discount = Discount::create([
        'name' => 'Welcome10',
        'slug' => 'welcome10',
        'active' => true,
        'percent' => 10,
        'per_user_cap' => 1,
    ]);

    Discounts::assign($user, $discount);

    $first = Discounts::apply($user, 10000, 'ORDER#1');
    expect($first)->toBe(1000);

    $second = Discounts::apply($user, 10000, 'ORDER#2');
    expect($second)->toBe(0);
});
