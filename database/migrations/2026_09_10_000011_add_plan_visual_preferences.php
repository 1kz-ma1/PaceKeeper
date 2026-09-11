<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('visual_icon', 16)->nullable()->after('category');
            $table->string('accent_key', 24)->default('sky')->after('visual_icon');
            $table->string('roadmap_world', 32)->default('default')->after('accent_key');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['visual_icon', 'accent_key', 'roadmap_world']);
        });
    }
};
