<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('species_id')->nullable()->constrained('animal_species')->nullOnDelete();
            $table->string('morph')->nullable();
            $table->string('sex', 20)->default('unknown');
            $table->date('hatch_date')->nullable();
            $table->date('acquired_at')->nullable();
            $table->decimal('current_weight_grams', 8, 2)->nullable();
            $table->unsignedTinyInteger('feeding_interval_days')->default(14);
            $table->date('last_fed_at')->nullable();
            $table->string('secret_tag')->nullable();
            $table->string('remote_id')->nullable();
            $table->boolean('imported_from_api')->default(false);
            $table->json('api_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name']);
            $table->unique(['user_id', 'secret_tag']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
