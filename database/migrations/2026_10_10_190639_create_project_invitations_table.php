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
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX project_invitations_pending_unique ON project_invitations (project_id, email) WHERE status = 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS project_invitations_pending_unique');

        Schema::dropIfExists('project_invitations');
    }
};
