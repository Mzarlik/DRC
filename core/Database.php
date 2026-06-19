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
    private $pdo;

    private $host = '127.0.0.1';
    private $user = 'root'; // Cambiar en producción
    private $pass = '';     // Cambiar en producción
    private $dbname = 'drc_erp';
    private $charset = 'utf8mb4';

    /**
     * Carga las variables de entorno desde el archivo .env si existe.
     */
    private function loadEnv() {
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            if ($env !== false) {
                if (isset($env['DB_HOST'])) $this->host = $env['DB_HOST'];
                if (isset($env['DB_USER'])) $this->user = $env['DB_USER'];
                if (isset($env['DB_PASS'])) $this->pass = $env['DB_PASS'];
                if (isset($env['DB_NAME'])) $this->dbname = $env['DB_NAME'];
                if (isset($env['DB_CHARSET'])) $this->charset = $env['DB_CHARSET'];
            }
        }
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
     * Previene la clonación de la instancia
     */
    private function __clone() {}

    /**
     * Devuelve la instancia de la conexión PDO
     *
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
