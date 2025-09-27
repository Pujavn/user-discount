<?php

namespace PujaNaik\UserDiscount\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountAudit extends Model
{
    protected $fillable = ['discount_id','user_id','action','application_key','amount_minor','meta'];

    protected $casts = ['meta' => 'array'];
}
