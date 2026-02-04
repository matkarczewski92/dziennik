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
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_id')->nullable()->constrained('feeds')->nullOnDelete();
            $table->date('fed_at');
            $table->string('prey');
            $table->decimal('prey_weight_grams', 8, 2)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fed_at']);
            $table->index(['animal_id', 'fed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedings');
    }
};
