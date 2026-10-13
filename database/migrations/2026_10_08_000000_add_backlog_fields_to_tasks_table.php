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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->after('assignee_id')->constrained('users')->nullOnDelete();
            $table->string('code', 16)->nullable()->after('author_id');
            $table->unsignedSmallInteger('points')->nullable()->after('code');

            $table->index('author_id');
            $table->unique(['author_id', 'code']);
        });

        DB::table('tasks')->update([
            'author_id' => DB::raw('COALESCE(assignee_id, (SELECT owner_id FROM projects WHERE projects.id = tasks.project_id))'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['author_id', 'code']);
            $table->dropIndex(['author_id']);
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn(['code', 'points']);
        });
    }
};
