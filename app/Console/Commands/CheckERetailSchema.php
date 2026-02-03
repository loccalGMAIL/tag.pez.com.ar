<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckERetailSchema extends Command
{
    protected $signature = 'eretail:schema {table?}';
    protected $description = 'Verificar estructura de tablas en la BD de eRetail';

    public function handle()
    {
        $table = $this->argument('table');

        try {
            $this->info("Conectando a eRetail DB...");

            if ($table) {
                $this->showTableSchema($table);
            } else {
                // Mostrar tablas principales
                $tables = ['AP', 'Tag', 'Goods', 'GoodsBind'];
                foreach ($tables as $t) {
                    $this->showTableSchema($t);
                    $this->line('');
                }
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function showTableSchema($table)
    {
        $this->info("=== Tabla: {$table} ===");

        try {
            // Obtener columnas
            $columns = DB::connection('eretail')
                ->select("SHOW COLUMNS FROM {$table}");

            $this->table(
                ['Campo', 'Tipo', 'Null', 'Key', 'Default'],
                collect($columns)->map(function ($col) {
                    return [
                        $col->Field,
                        $col->Type,
                        $col->Null,
                        $col->Key,
                        $col->Default ?? 'NULL'
                    ];
                })
            );

            // Mostrar algunos registros de ejemplo
            $this->info("Primeros 3 registros:");
            $records = DB::connection('eretail')
                ->table($table)
                ->limit(3)
                ->get();

            if ($records->isEmpty()) {
                $this->warn("  (Sin registros)");
            } else {
                foreach ($records as $record) {
                    $this->line("  " . json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

        } catch (\Exception $e) {
            $this->error("  Error con tabla {$table}: " . $e->getMessage());
        }
    }
}
