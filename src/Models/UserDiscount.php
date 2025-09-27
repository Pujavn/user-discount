<?php

namespace PujaNaik\UserDiscount\Models;

use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
    protected $fillable = ['user_id','discount_id','assigned_at','revoked_at','usage_count'];

    public function discount() { return $this->belongsTo(Discount::class); }
}
