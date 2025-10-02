<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up() {
        Schema::create('user_discounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $t->unsignedInteger('usage_count')->default(0);
            $t->timestamp('assigned_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id','discount_id']);
        });
    }
    public function down() { Schema::dropIfExists('user_discounts'); }
};
