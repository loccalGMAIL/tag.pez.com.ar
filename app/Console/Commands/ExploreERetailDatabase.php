<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExploreERetailDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eretail:explore {--table= : Tabla específica a explorar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explorar la estructura de la base de datos eRetail para diagnosticar problemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Explorando base de datos eRetail...');
        
        try {
            // Probar conexión
            DB::connection('eretail')->getPdo();
            $this->info('✅ Conexión exitosa a eRetail');
            
            // Obtener información de la base de datos
            $this->showDatabaseInfo();
            
            // Listar tablas disponibles
            $this->showAvailableTables();
            
            // Explorar tabla específica si se especifica
            $tableName = $this->option('table');
            if ($tableName) {
                $this->exploreSpecificTable($tableName);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error explorando eRetail: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Mostrar información de la base de datos
     */
    private function showDatabaseInfo()
    {
        $this->line('📊 Información de la base de datos:');
        
        try {
            $dbName = DB::connection('eretail')->getDatabaseName();
            $this->line("   Base de datos: {$dbName}");
            
            $tables = DB::connection('eretail')->select('SHOW TABLES');
            $this->line("   Total de tablas: " . count($tables));
            
        } catch (\Exception $e) {
            $this->warn("   ⚠️ No se pudo obtener información: " . $e->getMessage());
        }
    }
    
    /**
     * Mostrar tablas disponibles
     */
    private function showAvailableTables()
    {
        $this->line('📋 Tablas disponibles:');
        
        try {
            $tables = DB::connection('eretail')->select('SHOW TABLES');
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $this->line("   📁 {$tableName}");
                
                // Mostrar número de registros si es una tabla pequeña
                try {
                    $count = DB::connection('eretail')->table($tableName)->count();
                    $this->line("      📊 Registros: " . number_format($count));
                } catch (\Exception $e) {
                    $this->line("      ⚠️ No se pudo contar registros");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error listando tablas: " . $e->getMessage());
        }
    }
    
    /**
     * Explorar una tabla específica
     */
    private function exploreSpecificTable(string $tableName)
    {
        $this->line("🔍 Explorando tabla: {$tableName}");
        
        try {
            // Verificar si la tabla existe
            $tableExists = DB::connection('eretail')->select("SHOW TABLES LIKE '{$tableName}'");
            
            if (empty($tableExists)) {
                $this->error("   ❌ La tabla '{$tableName}' no existe");
                return;
            }
            
            // Mostrar estructura de la tabla
            $this->showTableStructure($tableName);
            
            // Mostrar algunos registros de ejemplo
            $this->showSampleRecords($tableName);
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error explorando tabla: " . $e->getMessage());
        }
    }
    
    /**
     * Mostrar estructura de una tabla
     */
    private function showTableStructure(string $tableName)
    {
        $this->line("   🏗️ Estructura de la tabla:");
        
        try {
            $columns = DB::connection('eretail')->select("DESCRIBE {$tableName}");
            
            $this->table(
                ['Campo', 'Tipo', 'Null', 'Key', 'Default', 'Extra'],
                array_map(function($column) {
                    return [
                        $column->Field,
                        $column->Type,
                        $column->Null,
                        $column->Key,
                        $column->Default ?? 'NULL',
                        $column->Extra
                    ];
                }, $columns)
            );
            
        } catch (\Exception $e) {
            $this->warn("      ⚠️ No se pudo obtener estructura: " . $e->getMessage());
        }
    }
    
    /**
     * Mostrar registros de ejemplo
     */
    private function showSampleRecords(string $tableName)
    {
        $this->line("   📝 Registros de ejemplo:");
        
        try {
            $records = DB::connection('eretail')->table($tableName)->limit(3)->get();
            
            if ($records->isEmpty()) {
                $this->line("      📭 La tabla está vacía");
                return;
            }
            
            // Convertir registros a array para mostrar
            $data = [];
            foreach ($records as $record) {
                $row = [];
                foreach ((array) $record as $key => $value) {
                    $row[$key] = is_string($value) && strlen($value) > 50 
                        ? substr($value, 0, 50) . '...' 
                        : $value;
                }
                $data[] = $row;
            }
            
            // Mostrar solo las primeras columnas si hay muchas
            $columns = array_keys($data[0]);
            if (count($columns) > 8) {
                $columns = array_slice($columns, 0, 8);
                $this->line("      ⚠️ Mostrando solo las primeras 8 columnas");
            }
            
            $this->table($columns, array_map(function($row) use ($columns) {
                return array_intersect_key($row, array_flip($columns));
            }, $data));
            
        } catch (\Exception $e) {
            $this->warn("      ⚠️ No se pudieron obtener registros: " . $e->getMessage());
        }
    }
}

