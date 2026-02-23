<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // uploads
        Schema::table('uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('uploads', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')->on('organizations')->cascadeOnDelete();
            }
            if (!$this->indexExists('uploads', 'uploads_org_id_index')) {
                $table->index('organization_id', 'uploads_org_id_index');
            }
            if (!$this->indexExists('uploads', 'uploads_org_status_index')) {
                $table->index(['organization_id', 'status'], 'uploads_org_status_index');
            }
            if (!$this->indexExists('uploads', 'uploads_org_created_index')) {
                $table->index(['organization_id', 'created_at'], 'uploads_org_created_index');
            }
        });

        // products: reemplazar unique(codigo_interno) por unique(organization_id, codigo_interno)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')->on('organizations')->cascadeOnDelete();
            }
            if ($this->indexExists('products', 'products_codigo_interno_unique')) {
                $table->dropUnique('products_codigo_interno_unique');
            }
            if (!$this->indexExists('products', 'products_org_codigo_unique')) {
                $table->unique(['organization_id', 'codigo_interno'], 'products_org_codigo_unique');
            }
            if (!$this->indexExists('products', 'products_org_id_index')) {
                $table->index('organization_id', 'products_org_id_index');
            }
        });

        // product_variants
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')->on('organizations')->cascadeOnDelete();
            }
            if (!$this->indexExists('product_variants', 'pv_org_codigo_barras_unique')) {
                $table->unique(['organization_id', 'codigo_interno', 'cod_barras'], 'pv_org_codigo_barras_unique');
            }
            if (!$this->indexExists('product_variants', 'pv_org_id_index')) {
                $table->index('organization_id', 'pv_org_id_index');
            }
        });

        // upload_process_logs
        Schema::table('upload_process_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('upload_process_logs', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')->on('organizations')->cascadeOnDelete();
            }
            if (!$this->indexExists('upload_process_logs', 'upl_org_id_index')) {
                $table->index('organization_id', 'upl_org_id_index');
            }
        });

        // app_settings: reemplazar unique(key) por unique(organization_id, key)
        Schema::table('app_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('app_settings', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')
                    ->references('id')->on('organizations')->cascadeOnDelete();
            }
            if ($this->indexExists('app_settings', 'app_settings_key_unique')) {
                $table->dropUnique('app_settings_key_unique');
            }
            if ($this->indexExists('app_settings', 'app_settings_key_index')) {
                $table->dropIndex('app_settings_key_index');
            }
            if (!$this->indexExists('app_settings', 'settings_org_key_unique')) {
                $table->unique(['organization_id', 'key'], 'settings_org_key_unique');
            }
            if (!$this->indexExists('app_settings', 'settings_org_id_index')) {
                $table->index('organization_id', 'settings_org_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            if ($this->indexExists('app_settings', 'settings_org_key_unique')) {
                $table->dropUnique('settings_org_key_unique');
            }
            if ($this->indexExists('app_settings', 'settings_org_id_index')) {
                $table->dropIndex('settings_org_id_index');
            }
            $table->dropColumn('organization_id');
        });

        Schema::table('upload_process_logs', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            if ($this->indexExists('upload_process_logs', 'upl_org_id_index')) {
                $table->dropIndex('upl_org_id_index');
            }
            $table->dropColumn('organization_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            if ($this->indexExists('product_variants', 'pv_org_codigo_barras_unique')) {
                $table->dropUnique('pv_org_codigo_barras_unique');
            }
            if ($this->indexExists('product_variants', 'pv_org_id_index')) {
                $table->dropIndex('pv_org_id_index');
            }
            $table->dropColumn('organization_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            if ($this->indexExists('products', 'products_org_codigo_unique')) {
                $table->dropUnique('products_org_codigo_unique');
            }
            if ($this->indexExists('products', 'products_org_id_index')) {
                $table->dropIndex('products_org_id_index');
            }
            $table->string('codigo_interno', 255)->unique()->change();
            $table->dropColumn('organization_id');
        });

        Schema::table('uploads', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            if ($this->indexExists('uploads', 'uploads_org_id_index')) {
                $table->dropIndex('uploads_org_id_index');
            }
            $table->dropColumn('organization_id');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($indexName);
    }
};
