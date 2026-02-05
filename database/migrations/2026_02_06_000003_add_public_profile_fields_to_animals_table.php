<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (! Schema::hasColumn('animals', 'public_profile_enabled')) {
                $table->boolean('public_profile_enabled')->default(false)->after('social_media_consent');
            }

            if (! Schema::hasColumn('animals', 'public_profile_token')) {
                $table->string('public_profile_token', 64)->nullable()->unique()->after('public_profile_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            if (Schema::hasColumn('animals', 'public_profile_token')) {
                $table->dropUnique('animals_public_profile_token_unique');
                $table->dropColumn('public_profile_token');
            }

            if (Schema::hasColumn('animals', 'public_profile_enabled')) {
                $table->dropColumn('public_profile_enabled');
            }
        });
    }
};
