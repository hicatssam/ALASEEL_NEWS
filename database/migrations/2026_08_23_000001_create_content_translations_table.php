<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 5);
            $table->json('data');
            $table->char('source_hash', 64);
            $table->string('status', 20)->default('completed');
            $table->text('error')->nullable();
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'locale'],
                'content_translations_unique'
            );
            $table->index(
                ['translatable_type', 'translatable_id'],
                'content_translations_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
