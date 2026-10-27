<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Expo push token, e.g. ExponentPushToken[xxxxxxxx]
            $table->string('token')->unique();
            $table->string('platform', 20)->nullable();
            $table->string('device_name')->nullable();
            // the Sanctum token that registered it, so logout removes the right device
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
