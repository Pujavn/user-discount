<?php

namespace PujaNaik\UserDiscount\Events;

use Illuminate\Queue\SerializesModels;

class DiscountAssigned
{
    use SerializesModels;
    public function __construct(public $user, public $discount) {}
}
