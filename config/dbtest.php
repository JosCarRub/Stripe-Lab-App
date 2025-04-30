<?php
declare(strict_types=1);

namespace App\database;

use PDO;
use PDOException;
use Dotenv\Dotenv;

/**
 * Clase Database: Gestiona la conexión a la base de datos y proporciona
 * métodos para realizar operaciones CRUD
 */
class Database {
    /**
     * Instancia única de la conexión PDO
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Opciones de configuración para PDO
     * @var array
     */
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    /**
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {
        // Singleton pattern
    }

    /**
     * Previene la clonación del objeto
     */
    private function __clone() {
        // Singleton pattern
    }

    /**
     * Carga las variables de entorno desde el archivo .env
     */
    private static function loadEnvironment(): void {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }
    }

    /**
     * Obtiene o crea una instancia única de la conexión a la base de datos
     *
     * @return PDO Instancia de PDO para la conexión a la base de datos
     * @throws PDOException Si no se puede establecer la conexión
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::loadEnvironment();

            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dbname = $_ENV['DB_NAME'] ?? '';
            $username = $_ENV['DB_USER'] ?? '';
            $password = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $username, $password, self::$options);
            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                throw new PDOException("No se pudo establecer conexión con la base de datos: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    /**
     * Ejecuta una consulta SQL y devuelve el resultado
     *
     * @param string $sql La consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return array|false Array asociativo con los resultados o false en caso de error
     */
    public static function query(string $sql, array $params = []): array|false {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta una consulta SQL y devuelve una sola fila
     *
     * @param string $sql La consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return array|false Un solo registro como array asociativo o false en caso de error
     */
    public static function queryOne(string $sql, array $params = []): array|false {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta una consulta SQL y devuelve un solo valor
     *
     * @param string $sql La consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return mixed El valor de la primera columna de la primera fila o false
     */
    public static function queryScalar(string $sql, array $params = []): mixed {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta una sentencia SQL que no devuelve resultados (INSERT, UPDATE, DELETE)
     *
     * @param string $sql La sentencia SQL a ejecutar
     * @param array $params Parámetros para la sentencia preparada
     * @return int|false Número de filas afectadas o false en caso de error
     */
    public static function execute(string $sql, array $params = []): int|false {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error al ejecutar la sentencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserta una fila en la tabla especificada
     *
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo con los datos a insertar (columna => valor)
     * @return string|false ID del último registro insertado o false en caso de error
     */
    public static function insert(string $table, array $data): string|false {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute(array_values($data));

            return self::getInstance()->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al insertar en {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza filas en la tabla especificada
     *
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo con los datos a actualizar (columna => valor)
     * @param string $where Condición WHERE sin la palabra "WHERE"
     * @param array $params Parámetros para la condición WHERE
     * @return int|false Número de filas afectadas o false en caso de error
     */
    public static function update(string $table, array $data, string $where, array $params = []): int|false {
        try {
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = ?";
            }

            $setClause = implode(', ', $set);
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

            $stmt = self::getInstance()->prepare($sql);
            $values = array_merge(array_values($data), $params);
            $stmt->execute($values);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error al actualizar {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina filas de la tabla especificada
     *
     * @param string $table Nombre de la tabla
     * @param string $where Condición WHERE sin la palabra "WHERE"
     * @param array $params Parámetros para la condición WHERE
     * @return int|false Número de filas afectadas o false en caso de error
     */
    public static function delete(string $table, string $where, array $params = []): int|false {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";

            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error al eliminar de {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Comienza una transacción
     *
     * @return bool True si la transacción se inició con éxito, false en caso contrario
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Confirma una transacción
     *
     * @return bool True si la transacción se confirmó con éxito, false en caso contrario
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }

    /**
     * Revierte una transacción
     *
     * @return bool True si la transacción se revirtió con éxito, false en caso contrario
     */
    public static function rollBack(): bool {
        return self::getInstance()->rollBack();
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $table Nombre de la tabla
     * @return bool True si la tabla existe, false en caso contrario
     */
    public static function tableExists(string $table): bool {
        try {
            $stmt = self::getInstance()->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar la existencia de la tabla {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea la tabla de pagos si no existe
     *
     * @return bool True si la tabla existe o se creó con éxito, false en caso contrario
     */
    public static function ensurePaymentsTableExists(): bool {
        if (self::tableExists('payments')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE payments (
                id_payment VARCHAR(36) PRIMARY KEY,
                event_id VARCHAR(255) NOT NULL,
                customer_id VARCHAR(255) NOT NULL,
                payment_intent_id VARCHAR(255) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                payload TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            return self::execute($sql) !== false;
        } catch (PDOException $e) {
            error_log("Error al crear la tabla payments: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los eventos de pago recientes
     *
     * @param int $limit Número máximo de registros a devolver
     * @return array Lista de eventos de pago
     */
    public static function getRecentPaymentEvents(int $limit = 10): array {
        return self::query("SELECT * FROM payments ORDER BY created_at DESC LIMIT ?", [$limit]) ?: [];
    }

    /**
     * Obtiene los detalles de un evento de pago específico
     *
     * @param string $id ID del evento de pago
     * @return array|false Detalles del evento o false si no se encuentra
     */
    public static function getPaymentEventDetails(string $id): array|false {
        return self::queryOne("SELECT * FROM payments WHERE id_payment = ?", [$id]);
    }
}