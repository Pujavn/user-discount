<?php

namespace PujaNaik\UserDiscount\Facades;

use Illuminate\Support\Facades\Facade;

class Discounts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'user-discount.manager';
    }
}
