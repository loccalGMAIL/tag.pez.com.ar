<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);

            // Credenciales API eRetail (por tenant, cifradas)
            $table->text('eretail_base_url')->nullable();
            $table->text('eretail_username')->nullable();
            $table->text('eretail_password')->nullable();
            $table->string('eretail_default_shop_code')->nullable();

            // Credenciales DB directa eRetail (por tenant, cifradas)
            $table->text('eretail_db_host')->nullable();
            $table->string('eretail_db_port')->default('3306');
            $table->text('eretail_db_database')->nullable();
            $table->text('eretail_db_username')->nullable();
            $table->text('eretail_db_password')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
