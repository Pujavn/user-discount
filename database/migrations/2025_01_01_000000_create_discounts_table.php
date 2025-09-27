<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('discounts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('priority')->default(0);
            $t->unsignedTinyInteger('percent')->nullable();
            $t->bigInteger('fixed_minor')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->unsignedInteger('per_user_cap')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('discounts'); }
};
