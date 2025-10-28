<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeCrud extends BaseCommand
{
    protected $group       = 'Generators';
    protected $name        = 'make:crud';
    protected $description = 'Genera un CRUD completo con estructura DDD (UseCases, Modelo, Controlador, Request, Vistas, Migración y Rutas).';

    public function run(array $params)
    {
        $name  = $params[0] ?? CLI::prompt('Nombre del recurso (ej: UserRole)');

        // Convierte a PascalCase (UserRole, ProductCategory, etc.)
        $pascalName = str_replace(' ', '', ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $name)));

        // Convierte a snake_case para las tablas (user_roles, product_categories, etc.)
        $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $pascalName)) . 's';

        $modelName      = $pascalName . 'Model';
        $controllerName = $pascalName . 'Controller';
        $requestName    = $pascalName . 'Request';
        $migrationName  = 'Create' . ucfirst($table) . 'Table';

        // === Paths ===
        $controllerPath = APPPATH . "Controllers/{$pascalName}/{$controllerName}.php";
        $modelPath      = APPPATH . "Models/{$pascalName}/{$modelName}.php";
        $requestPath    = APPPATH . "Requests/{$pascalName}/{$requestName}.php";
        $viewsPath      = APPPATH . "Views/{$pascalName}";
        $useCasePath    = APPPATH . "UseCases/{$pascalName}/";
        $migrationPath  = APPPATH . "Database/Migrations/" . date('Y-m-d-His') . "_{$migrationName}.php";

        // === Crear directorios si no existen ===
        foreach ([dirname($controllerPath), dirname($modelPath), dirname($requestPath), $viewsPath, $useCasePath] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        // === Modelo ===
        if (!file_exists($modelPath)) {
            file_put_contents($modelPath, $this->getModelStub($pascalName, $modelName, $table));
            CLI::write("✅ Modelo creado: {$modelPath}", 'green');
        }

        // === Controlador ===
        if (!file_exists($controllerPath)) {
            file_put_contents($controllerPath, $this->getControllerStub($pascalName, $controllerName, $modelName, $requestName));
            CLI::write("✅ Controlador creado: {$controllerPath}", 'green');
        }

        // === Request ===
        if (!file_exists($requestPath)) {
            file_put_contents($requestPath, $this->getRequestStub($pascalName, $requestName));
            CLI::write("✅ Request creado: {$requestPath}", 'green');
        }

        // === Vistas ===
        if (!is_dir($viewsPath) || count(glob("$viewsPath/*.php")) === 0) {
            file_put_contents("{$viewsPath}/index.php", "<h1>Listado de {$pascalName}</h1>");
            file_put_contents("{$viewsPath}/create.php", "<h1>Crear {$pascalName}</h1>");
            file_put_contents("{$viewsPath}/edit.php", "<h1>Editar {$pascalName}</h1>");
            CLI::write("✅ Vistas creadas en: {$viewsPath}", 'green');
        }

        // === UseCases ===
        $useCases = [
            'Get'    => $this->getUseCaseGet($pascalName, $modelName),
            'Create' => $this->getUseCaseCreate($pascalName, $modelName),
            'Update' => $this->getUseCaseUpdate($pascalName, $modelName),
            'Delete' => $this->getUseCaseDelete($pascalName, $modelName),
        ];

        foreach ($useCases as $uc => $content) {
            $ucFile = $useCasePath . "{$uc}{$pascalName}.php";
            if (!file_exists($ucFile)) {
                file_put_contents($ucFile, $content);
                CLI::write("✅ Caso de uso creado: {$ucFile}", 'green');
            }
        }

        // === Migración ===
        if (!file_exists($migrationPath)) {
            file_put_contents($migrationPath, $this->getMigrationStub($migrationName, $table));
            CLI::write("✅ Migración creada: {$migrationPath}", 'green');
        }

        // === Rutas automáticas en app/Config/Routes.php ===
        $routesFile  = APPPATH . 'Config/Routes.php';
        $basePath    = strtolower($pascalName);
        $routeBlock  = <<<ROUTES
\$routes->get('/{$basePath}', '{$pascalName}\\{$controllerName}::index');
\$routes->get('/{$basePath}/create', '{$pascalName}\\{$controllerName}::create');
\$routes->post('/{$basePath}/store', '{$pascalName}\\{$controllerName}::store');
ROUTES;

        $routesContent = file_get_contents($routesFile);

        if (strpos($routesContent, $routeBlock) === false) {
            $newContent = preg_replace(
                '/(\$routes->get\(\'\/\',.*\);)/',
                "$1\n\n    " . $routeBlock,
                $routesContent
            );
            file_put_contents($routesFile, $newContent);
            CLI::write("✅ Rutas añadidas a Config/Routes.php para {$pascalName}", 'green');
        } else {
            CLI::write("⚠️ Las rutas ya existían en Config/Routes.php", 'yellow');
        }

        CLI::write("🎉 CRUD generado para {$pascalName} con estructura DDD", 'blue');
    }

    // ==== STUBS ====

    private function getModelStub($pascalName, $modelName, $table)
    {
        return "<?php

namespace App\Models\\{$pascalName};

use CodeIgniter\Model;

class {$modelName} extends Model
{
    protected \$table      = '{$table}';
    protected \$primaryKey = 'id';
    protected \$allowedFields = ['name', 'created_at', 'updated_at'];
}
";
    }

    private function getControllerStub($pascalName, $controllerName, $modelName, $requestName)
    {
        return "<?php

namespace App\Controllers\\{$pascalName};

use App\Models\\{$pascalName}\\{$modelName};
use App\Requests\\{$pascalName}\\{$requestName};
use App\UseCases\\{$pascalName}\\Get{$pascalName};
use App\UseCases\\{$pascalName}\\Create{$pascalName};
use App\UseCases\\{$pascalName}\\Update{$pascalName};
use App\UseCases\\{$pascalName}\\Delete{$pascalName};
use CodeIgniter\Controller;

class {$controllerName} extends Controller
{
    public function index()
    {
        \$useCase = new Get{$pascalName}();
        \$data['items'] = \$useCase->execute();
        return view('{$pascalName}/index', \$data);
    }

    public function create()
    {
        return view('{$pascalName}/create');
    }

    public function store()
    {
        \$useCase = new Create{$pascalName}();
        \$useCase->execute(\$this->request->getPost());
        return redirect()->to('/" . strtolower($pascalName) . "');
    }

    public function edit(\$id)
    {
        \$model = new {$modelName}();
        \$data['item'] = \$model->find(\$id);
        return view('{$pascalName}/edit', \$data);
    }

    public function update(\$id)
    {
        \$useCase = new Update{$pascalName}();
        \$useCase->execute(\$id, \$this->request->getPost());
        return redirect()->to('/" . strtolower($pascalName) . "');
    }

    public function delete(\$id)
    {
        \$useCase = new Delete{$pascalName}();
        \$useCase->execute(\$id);
        return redirect()->to('/" . strtolower($pascalName) . "');
    }
}
";
    }

    private function getRequestStub($pascalName, $requestName)
    {
        return "<?php

namespace App\Requests\\{$pascalName};

class {$requestName}
{
    public static \$rules = [
        'name' => 'required|min_length[3]'
    ];
}
";
    }

    private function getMigrationStub($migrationName, $table)
    {
        return "<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class {$migrationName} extends Migration
{
    public function up()
    {
        \$this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        \$this->forge->addKey('id', true);
        \$this->forge->createTable('{$table}');
    }

    public function down()
    {
        \$this->forge->dropTable('{$table}');
    }
}
";
    }

    // ==== USE CASES ====

    private function getUseCaseGet($pascalName, $modelName)
    {
        return "<?php

namespace App\UseCases\\{$pascalName};

use App\Models\\{$pascalName}\\{$modelName};

class Get{$pascalName}
{
    public function execute()
    {
        \$model = new {$modelName}();
        return \$model->findAll();
    }
}
";
    }

    private function getUseCaseCreate($pascalName, $modelName)
    {
        return "<?php

namespace App\UseCases\\{$pascalName};

use App\Models\\{$pascalName}\\{$modelName};

class Create{$pascalName}
{
    public function execute(array \$data)
    {
        \$model = new {$modelName}();
        return \$model->insert(\$data);
    }
}
";
    }

    private function getUseCaseUpdate($pascalName, $modelName)
    {
        return "<?php

namespace App\UseCases\\{$pascalName};

use App\Models\\{$pascalName}\\{$modelName};

class Update{$pascalName}
{
    public function execute(int \$id, array \$data)
    {
        \$model = new {$modelName}();
        return \$model->update(\$id, \$data);
    }
}
";
    }

    private function getUseCaseDelete($pascalName, $modelName)
    {
        return "<?php

namespace App\UseCases\\{$pascalName};

use App\Models\\{$pascalName}\\{$modelName};

class Delete{$pascalName}
{
    public function execute(int \$id)
    {
        \$model = new {$modelName}();
        return \$model->delete(\$id);
    }
}
";
    }
}
