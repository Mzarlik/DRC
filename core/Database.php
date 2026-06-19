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
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {
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
