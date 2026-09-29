<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->text('goal')->nullable()->after('name');
            $table->string('status')->default('planned')->index()->after('end_date');
        });

        $today = now()->toDateString();

        DB::table('sprints')
            ->where('end_date', '<', $today)
            ->update(['status' => 'completed']);

        DB::table('sprints')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->update(['status' => 'active']);

        DB::table('sprints')
            ->where('start_date', '>', $today)
            ->update(['status' => 'planned']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['goal', 'status']);
        });
    }
};
