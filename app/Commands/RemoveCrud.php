<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RemoveCrud extends BaseCommand
{
    protected $group       = 'Generators';
    protected $name        = 'remove:crud';
    protected $description = 'Elimina un CRUD previamente generado con make:crud (incluyendo migración).';

    public function run(array $params)
    {
        $name  = $params[0] ?? CLI::prompt('Nombre del recurso a eliminar (ej: User)');
        $table = strtolower($name) . 's';

        $controllerPath = APPPATH . "Controllers/{$name}/{$name}Controller.php";
        $modelPath      = APPPATH . "Models/{$name}/{$name}Model.php";
        $requestPath    = APPPATH . "Requests/{$name}/{$name}Request.php";
        $viewsPath      = APPPATH . "Views/" . strtolower($name);
        $useCasePath    = APPPATH . "UseCases/{$name}/";

        // Eliminar archivos y carpetas
        $this->deleteFile($controllerPath);
        $this->deleteFile($modelPath);
        $this->deleteFile($requestPath);
        $this->deleteDir($viewsPath);
        $this->deleteDir($useCasePath);

        // Eliminar migración
        $this->deleteMigration($table);

        // Eliminar rutas de Config/Routes.php
        $routesFile  = APPPATH . 'Config/Routes.php';
        $routeEntry  = "\$routes->resource('" . strtolower($name) . "', ['controller' => '{$name}/{$name}Controller']);";

        $routesContent = file_get_contents($routesFile);
        if (strpos($routesContent, $routeEntry) !== false) {
            $newContent = str_replace($routeEntry, '', $routesContent);
            file_put_contents($routesFile, $newContent);
            CLI::write("🗑️ Ruta eliminada de Config/Routes.php", 'yellow');
        }

        CLI::write("✅ CRUD de {$name} eliminado (archivos + migración)", 'green');
    }

    private function deleteFile($path)
    {
        if (file_exists($path)) {
            unlink($path);
            CLI::write("🗑️ Archivo eliminado: {$path}", 'yellow');
        }
    }

    private function deleteDir($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = "$dir/$file";
                if (is_dir($path)) {
                    $this->deleteDir($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
            CLI::write("🗑️ Carpeta eliminada: {$dir}", 'yellow');
        }
    }

    private function deleteMigration($table)
    {
        $migrationsPath = APPPATH . "Database/Migrations/";
        $files = glob($migrationsPath . "*Create{$table}Table.php");

        foreach ($files as $file) {
            unlink($file);
            CLI::write("🗑️ Migración eliminada: {$file}", 'yellow');

            // Revertir la migración (rollback)
            CLI::write("⏪ Ejecutando rollback de migración...", 'cyan');
            shell_exec('php spark migrate:rollback');
        }
    }
}
