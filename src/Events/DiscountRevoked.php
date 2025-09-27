<?php

namespace PujaNaik\UserDiscount\Events;

use Illuminate\Queue\SerializesModels;
use PujaNaik\UserDiscount\Models\Discount;

class DiscountRevoked
{
    use SerializesModels;

    public function __construct(
        public $user,
        public Discount $discount
    ) {}
}
