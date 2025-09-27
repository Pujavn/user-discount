<?php

namespace PujaNaik\UserDiscount\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Discount extends Model
{
    protected $fillable = ['name','slug','active','priority','percent','fixed_minor','starts_at','ends_at','per_user_cap','meta'];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function scopeActive(Builder $q) { return $q->where('active', true); }
    public function scopeWhereNotExpired(Builder $q) {
        return $q->where(function($qq){
            $qq->whereNull('starts_at')->orWhere('starts_at','<=',now());
        })->where(function($qq){
            $qq->whereNull('ends_at')->orWhere('ends_at','>=',now());
        });
    }

    public function userDiscounts() { return $this->hasMany(UserDiscount::class); }
}
