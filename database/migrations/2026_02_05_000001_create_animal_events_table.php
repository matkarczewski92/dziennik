<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->dateTime('happened_at')->nullable();
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['animal_id', 'happened_at']);
            $table->index(['animal_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_events');
    }
};
