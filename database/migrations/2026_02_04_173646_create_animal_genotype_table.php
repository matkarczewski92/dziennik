<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_genotype', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genotype_id')->constrained('animal_genotype_category')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->string('type')->comment('v-homozygota, h-heterozygota, p-poshet');
            $table->timestamps();

            $table->index(['animal_id', 'genotype_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_genotype');
    }
};

