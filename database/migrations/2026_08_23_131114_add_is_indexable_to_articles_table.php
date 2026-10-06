<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'is_indexable')) {
                $table->boolean('is_indexable')
                    ->default(true)
                    ->index()
                    ->after('is_editor_pick');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'is_indexable')) {
                $table->dropIndex(['is_indexable']);
                $table->dropColumn('is_indexable');
            }
        });
    }
};