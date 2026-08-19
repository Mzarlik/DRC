<?php
// core/Migrate.php
namespace Core;

/**
 * Motor de Migraciones Versionadas para el ERP de la Dirección de Registro Civil (DRC).
 * Uso CLI:
 *   php core/Migrate.php up       -> Ejecuta las migraciones pendientes
 *   php core/Migrate.php status   -> Muestra el estado actual de las migraciones
 *   php core/Migrate.php rollback -> Revierte el último lote de migraciones
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

use Core\Database;

class Migrate {
    private \PDO $pdo;
    private string $migrationsDir;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->migrationsDir = dirname(__DIR__) . '/docs/migrations';
        $this->initSchemaTable();
    }

    /**
     * Asegura la existencia de la tabla de control schema_migrations.
     */
    private function initSchemaTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            batch INT NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->pdo->exec($sql);
    }

    /**
     * Obtiene la lista de migraciones ya aplicadas.
     */
    private function getAppliedMigrations(): array {
        $stmt = $this->pdo->query("SELECT migration_name FROM schema_migrations ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Obtiene el número del siguiente lote (batch).
     */
    private function getNextBatchNumber(): int {
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM schema_migrations");
        $row = $stmt->fetch();
        return ($row && $row['max_batch']) ? ((int)$row['max_batch'] + 1) : 1;
    }

    /**
     * Muestra el estado de todas las migraciones.
     */
    public function status(): void {
        echo "=================================================================\n";
        echo " ESTADO DE MIGRACIONES — ERP DIRECCIÓN DE REGISTRO CIVIL        \n";
        echo "=================================================================\n\n";

        $applied = $this->getAppliedMigrations();
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files);

        if (empty($files)) {
            echo "No se encontraron archivos de migración en docs/migrations/\n";
            return;
        }

        printf("%-35s | %-12s | %s\n", "Migración", "Estado", "Fecha de Ejecución");
        echo str_repeat("-", 70) . "\n";

        foreach ($files as $file) {
            $name = basename($file);
            $isApplied = in_array($name, $applied, true);
            $statusStr = $isApplied ? "[APLICADA]" : "[PENDIENTE]";

            $executedAt = "-";
            if ($isApplied) {
                $stmt = $this->pdo->prepare("SELECT executed_at FROM schema_migrations WHERE migration_name = ?");
                $stmt->execute([$name]);
                $executedAt = $stmt->fetchColumn() ?: "-";
            }

            printf("%-35s | %-12s | %s\n", $name, $statusStr, $executedAt);
        }
        echo "\n";
    }

    /**
     * Ejecuta las migraciones pendientes.
     */
    public function up(): void {
        echo "=================================================================\n";
        echo " EJECUTANDO MIGRACIONES PENDIENTES — ERP DRC                     \n";
        echo "=================================================================\n\n";

        $applied = $this->getAppliedMigrations();
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files);

        $pending = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $applied, true)) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            echo "✔ No hay migraciones pendientes. La base de datos está actualizada.\n";
            return;
        }

        $batch = $this->getNextBatchNumber();

        foreach ($pending as $file) {
            $name = basename($file);
            echo "• Aplicando $name ... ";

            $sql = file_get_contents($file);
            if (empty(trim($sql))) {
                echo "[VACÍO - OMITIDO]\n";
                continue;
            }

            try {
                // Ejecución directa de los comandos SQL
                $this->pdo->exec($sql);

                // Registrar en tabla de control
                $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration_name, batch) VALUES (?, ?)");
                $stmt->execute([$name, $batch]);

                echo "[OK]\n";
            } catch (\Exception $e) {
                echo "[ERROR]\n";
                echo "❌ Fallo al ejecutar la migración '$name': " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        echo "\n✔ ¡Todas las migraciones se aplicaron exitosamente (Lote $batch)!\n";
    }
}

// Procesar argumento CLI
$action = $argv[1] ?? 'status';
$migrator = new Migrate();

switch (strtolower($action)) {
    case 'up':
        $migrator->up();
        break;
    case 'status':
    default:
        $migrator->status();
        break;
}
