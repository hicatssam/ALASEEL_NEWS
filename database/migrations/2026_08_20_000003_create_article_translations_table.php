<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title', 500);
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('source_hash', 64);
            $table->timestamps();
            $table->unique(['article_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_translations');
    }
};
