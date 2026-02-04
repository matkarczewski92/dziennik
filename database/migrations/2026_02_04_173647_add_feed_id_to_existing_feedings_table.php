<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feedings')) {
            return;
        }

        if (! Schema::hasColumn('feedings', 'feed_id')) {
            Schema::table('feedings', function (Blueprint $table): void {
                $table->foreignId('feed_id')
                    ->nullable()
                    ->after('animal_id')
                    ->constrained('feeds')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('feedings') || ! Schema::hasColumn('feedings', 'feed_id')) {
            return;
        }

        Schema::table('feedings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('feed_id');
        });
    }
};

