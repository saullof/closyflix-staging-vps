<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('posts', 'is_free')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->boolean('is_free')->default(false)->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('posts', 'is_free')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('is_free');
            });
        }
    }
};