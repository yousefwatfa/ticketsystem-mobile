<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The profile "link Telegram" flow and TelegramController already use these
 * columns, but no migration created them. Guarded so it is safe on databases
 * where they were added by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_link_token')) {
                $table->string('telegram_link_token', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'telegram_linked_at')) {
                $table->timestamp('telegram_linked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_link_token')) {
                $table->dropUnique(['telegram_link_token']);
                $table->dropColumn('telegram_link_token');
            }
            if (Schema::hasColumn('users', 'telegram_linked_at')) {
                $table->dropColumn('telegram_linked_at');
            }
        });
    }
};
