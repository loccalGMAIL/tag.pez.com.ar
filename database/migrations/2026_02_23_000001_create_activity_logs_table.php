<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');        // auth | upload | tags | settings | eretail | system
            $table->string('level');       // info | warning | error
            $table->string('event');       // login | logout | login_failed | upload_created | ...
            $table->string('description'); // Mensaje legible para humanos
            $table->string('subject_type')->nullable(); // App\Models\Upload, etc.
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable(); // IP, user_agent, filename, counts, trace
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['type', 'level']);
            $table->index('organization_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
