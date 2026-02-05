<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (! Schema::hasColumn('animals', 'social_media_consent')) {
                $table->boolean('social_media_consent')->default(false)->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (Schema::hasColumn('animals', 'social_media_consent')) {
                $table->dropColumn('social_media_consent');
            }
        });
    }
};
