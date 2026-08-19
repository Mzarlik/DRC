<?php
namespace Core;

use PDO;
use PDOException;

/**
 * Clase estática para manejar la conexión a la base de datos usando PDO.
 * Garantiza una única instancia (Singleton pattern) y manejo seguro.
 */
class Database {
    private static $instance = null;
    private static $env = null; // Caché del .env: una sola lectura por proceso
    private $pdo;
    private $readPdo = null;

    private $host = '127.0.0.1';
    private $user = 'root'; // Cambiar en producción
    private $pass = '';     // Cambiar en producción
    private $dbname = 'drc_erp';
    private $charset = 'utf8mb4';

    // Replica properties
    private $readHost = null;
    private $readUser = null;
    private $readPass = null;
    private $readDbname = null;

    /**
     * Carga las variables de entorno desde el archivo .env si existe.
     * La lectura se cachea en una propiedad estática para no releer por request.
     */
    private function loadEnv() {
        if (self::$env === null) {
            $envPath = dirname(__DIR__) . '/.env';
            self::$env = [];
            if (file_exists($envPath)) {
                $parsed = parse_ini_file($envPath);
                if ($parsed !== false) {
                    self::$env = $parsed;
                }
            }
        }
        $env = self::$env;

        if (isset($env['DB_HOST'])) $this->host = $env['DB_HOST'];
        if (isset($env['DB_USER'])) $this->user = $env['DB_USER'];
        if (isset($env['DB_PASS'])) $this->pass = $env['DB_PASS'];
        if (isset($env['DB_NAME'])) $this->dbname = $env['DB_NAME'];
        if (isset($env['DB_CHARSET'])) $this->charset = $env['DB_CHARSET'];

        // Read replica
        if (isset($env['DB_READ_HOST'])) $this->readHost = $env['DB_READ_HOST'];
        if (isset($env['DB_READ_USER'])) $this->readUser = $env['DB_READ_USER'];
        if (isset($env['DB_READ_PASS'])) $this->readPass = $env['DB_READ_PASS'];
        if (isset($env['DB_READ_NAME'])) $this->readDbname = $env['DB_READ_NAME'];
    }

    /**
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {
        $this->loadEnv();
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa sentencias preparadas reales
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Evita inyecciones por encoding
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // En producción, guardar el error en un log y mostrar un mensaje genérico.
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }

    /**
     * Inicializa la conexión de lectura a la base de datos réplica.
     * Si no está configurada o falla, hace un fallback seguro a la de escritura.
     */
    private function initReadConnection() {
        if (empty($this->readHost) || 
            ($this->readHost === $this->host && 
             $this->readDbname === $this->dbname && 
             $this->readUser === $this->user && 
             $this->readPass === $this->pass)) {
            $this->readPdo = $this->pdo;
            return;
        }

        $dsn = "mysql:host={$this->readHost};dbname={$this->readDbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            $this->readPdo = new PDO($dsn, $this->readUser, $this->readPass, $options);
        } catch (PDOException $e) {
            // Fallback a la base de datos principal en caso de fallo
            $this->readPdo = $this->pdo;
        }
    }

    /**
     * Previene la clonación de la instancia
     */
    private function __clone() {}

    /**
     * Devuelve la conexión a la base de datos principal (Master) para escrituras.
     * Conservado por compatibilidad hacia atrás.
     *
     * @return PDO
     */
    public static function getConnection(): PDO {
        return self::getWriteConnection();
    }

    /**
     * Devuelve la conexión a la base de datos principal (Master) para escrituras.
     *
     * @return PDO
     */
    public static function getWriteConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    /**
     * Devuelve la conexión a la base de datos réplica (Slave) para consultas de lectura.
     *
     * @return PDO
     */
    public static function getReadConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        if (self::$instance->readPdo === null) {
            self::$instance->initReadConnection();
        }
        return self::$instance->readPdo;
    }

    /**
     * Genera un folio único y secuencial asegurando transaccionalidad.
     * 
     * @param string $modulo Identificador del módulo (ej. 'peticiones_2026')
     * @param string $prefix Prefijo opcional (ej. 'TK-2026-')
     * @param int $padding Longitud de los ceros a la izquierda
     * @return string Folio generado
     */
    public static function generateFolio($modulo, $prefix = '', $padding = 5) {
        $pdo = self::getConnection();
        $inTransaction = $pdo->inTransaction();
        
        if (!$inTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Bloqueo exclusivo de fila para evitar condiciones de carrera (Concurrency)
            $stmt = $pdo->prepare("SELECT ultimo_folio FROM folios_secuencia WHERE modulo = ? FOR UPDATE");
            $stmt->execute([$modulo]);
            $row = $stmt->fetch();

            if ($row) {
                $next = $row['ultimo_folio'] + 1;
                $pdo->prepare("UPDATE folios_secuencia SET ultimo_folio = ? WHERE modulo = ?")->execute([$next, $modulo]);
            } else {
                $next = 1;
                $pdo->prepare("INSERT INTO folios_secuencia (modulo, ultimo_folio) VALUES (?, ?)")->execute([$modulo, $next]);
            }

            if (!$inTransaction) {
                $pdo->commit();
            }

            return $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ejecuta una operación dentro de una transacción con reintentos automáticos
     * ante condiciones de Deadlock (MySQL Error 1213 / SQLSTATE 40001) o Lock Wait Timeout (1205).
     *
     * @param callable $callback Función anónima function(\PDO $pdo)
     * @param int $maxRetries Número máximo de intentos (por defecto 3)
     * @return mixed Retorno del callback
     * @throws \Throwable Si se agotan los reintentos o el error no es de concurrencia
     */
    public static function transactionWithRetry(callable $callback, int $maxRetries = 3) {
        $pdo = self::getConnection();
        $attempts = 0;

        while ($attempts < $maxRetries) {
            $attempts++;
            try {
                $pdo->beginTransaction();
                $result = $callback($pdo);
                $pdo->commit();
                return $result;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $msg = $e->getMessage();
                $code = (string)$e->getCode();

                // Detección de Deadlock (1213 / 40001) o Lock Timeout (1205)
                $isDeadlock = ($code === '40001' || $code === '1213' || str_contains($msg, '1213') || str_contains($msg, 'Deadlock') || str_contains($msg, '1205'));

                if ($isDeadlock && $attempts < $maxRetries) {
                    // Backoff exponencial con jitter (20ms a 50ms * intento)
                    usleep(rand(20000, 50000) * $attempts);
                    continue;
                }

                throw $e;
            }
        }
    }
}
