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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('key', 5)->nullable()->after('description');
            $table->string('color', 7)->nullable()->after('key');
            $table->string('icon', 32)->nullable()->after('color');
            $table->date('target_date')->nullable()->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['key', 'color', 'icon', 'target_date']);
        });
    }
};
