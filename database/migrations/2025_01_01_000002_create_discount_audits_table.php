<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up()
    {
        Schema::create('discount_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('application_key')->nullable();
            $t->bigInteger('amount_minor')->default(0);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(
                ['discount_id', 'user_id', 'action', 'application_key'],
                'da_did_uid_act_appk_unique'
            );
        });
    }
    public function down()
    {
        Schema::dropIfExists('discount_audits');
    }
};
