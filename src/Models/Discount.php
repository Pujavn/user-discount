<?php

namespace PujaNaik\UserDiscount\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Discount extends Model
{
    protected $fillable = [
        'name', 'slug', 'active', 'priority',
        'percent', 'fixed_minor',
        'starts_at', 'ends_at',
        'per_user_cap', 'meta',
    ];

    protected $casts = [
        'active'        => 'boolean',
        'priority'      => 'integer',
        'percent'       => 'integer',
        'fixed_minor'   => 'integer',
        'per_user_cap'  => 'integer',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'meta'          => 'array',
    ];

    protected $attributes = [
        'active'   => true,
        'priority' => 0,
    ];

    /* ---------- Scopes ---------- */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }

    public function scopeWhereNotExpired(Builder $q): Builder
    {
        return $q->where(function ($qq) {
            $qq->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($qq) {
            $qq->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        });
    }

    /**
     * Optionally: one-liner to fetch discounts usable for a given user (assigned & not revoked).
     * Keeps service/controller code clean if you want to use it outside the manager.
     */
    public function scopeUsableFor(Builder $q, int $userId): Builder
    {
        return $q->active()
            ->whereNotExpired()
            ->whereHas('userDiscounts', fn ($uq) =>
                $uq->where('user_id', $userId)->whereNull('revoked_at')
            )
            ->orderByDesc('priority');
    }

    /* ---------- Relations ---------- */

    public function userDiscounts()
    {
        return $this->hasMany(UserDiscount::class);
    }

    /* ---------- Accessors (optional) ---------- */

    public function getHasPercentAttribute(): bool
    {
        return !is_null($this->percent) && $this->percent > 0;
    }

    public function getHasFixedAttribute(): bool
    {
        return !is_null($this->fixed_minor) && $this->fixed_minor > 0;
    }
}
