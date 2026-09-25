<?php
/**
 * Configuración de la base de datos
 * Sistema de Inventario - Ropa Infantil
 * 
 * @package Config
 * @version 1.0
 */

/**
 * Clase para manejar la conexión a la base de datos
 */
class Database {
    
    // CONFIGURACIÓN PARA LOCALHOST (XAMPP)
    private $host = 'localhost';
    private $db_name = 'inventario_ropa_infantil';
    private $username = 'root';
    private $password = '';
    
    private $conn;

    /**
     * Obtiene una conexión PDO a la base de datos
     * 
     * @return PDO Conexión a la base de datos
     * @throws PDOException Si hay un error de conexión
     */
    public function getConnection() {
        $this->conn = null;
        $local = __DIR__ . '/db.local.php';
        if (is_file($local)) {
            $cfg = require $local;
            if (is_array($cfg)) {
                $this->host = (string) ($cfg['host'] ?? $this->host);
                $this->db_name = (string) ($cfg['db_name'] ?? $this->db_name);
                $this->username = (string) ($cfg['username'] ?? $this->username);
                $this->password = (string) ($cfg['password'] ?? $this->password);
            }
        }

        try {
            // Conexión simple y compatible con StackCP
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            // Log del error para depuración
            error_log("Error de conexión: " . $e->getMessage());
            
            // Mensaje genérico que funciona bien en producción
            // Mantenemos el mensaje simple como en tu versión que funciona
            die("❌ Error de conexión a la base de datos. Por favor, verifique el Host, el Nombre de la Base de Datos, el Usuario y la Contraseña.");
        }

        return $this->conn;
    }
}
