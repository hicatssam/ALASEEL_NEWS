<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('content_type', 30)->default('news')->after('content');
            $table->index(['content_type', 'status', 'published_at'], 'articles_type_status_published_idx');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_type_status_published_idx');
            $table->dropColumn('content_type');
        });
    }
};
