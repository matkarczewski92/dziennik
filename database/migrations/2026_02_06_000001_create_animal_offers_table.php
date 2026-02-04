<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('animal_offers')) {
            return;
        }

        Schema::create('animal_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2);
            $table->date('sold_date')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'sold_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_offers');
    }
};

