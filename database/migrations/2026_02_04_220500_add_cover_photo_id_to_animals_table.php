<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (! Schema::hasColumn('animals', 'cover_photo_id')) {
                $table->foreignId('cover_photo_id')
                    ->nullable()
                    ->after('notes')
                    ->constrained('photos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (Schema::hasColumn('animals', 'cover_photo_id')) {
                $table->dropConstrainedForeignId('cover_photo_id');
            }
        });
    }
};

