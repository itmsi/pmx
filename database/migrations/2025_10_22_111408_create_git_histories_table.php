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
        Schema::create('git_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('branch');
            $table->text('commit_message');
            $table->string('commit_hash');
            $table->timestamp('pushed_at');
            $table->string('repository_name')->nullable();
            $table->string('repository_url')->nullable();
            $table->timestamps();
            
            $table->index(['ticket_id', 'pushed_at']);
            $table->index('commit_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_histories');
    }
};
