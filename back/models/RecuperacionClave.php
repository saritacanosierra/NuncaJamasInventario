<?php

class RecuperacionClave {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
        $this->asegurarTabla();
    }

    public function crear($usuarioId) {
        $reciente = $this->conn->prepare(
            "SELECT id FROM recuperaciones_clave
             WHERE usuario_id = :usuario_id
               AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
             LIMIT 1"
        );
        $reciente->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $reciente->execute();
        if ($reciente->fetch()) {
            return null;
        }

        $this->conn->prepare(
            "UPDATE recuperaciones_clave SET usado = 1 WHERE usuario_id = :usuario_id AND usado = 0"
        )->execute([':usuario_id' => $usuarioId]);

        $token = bin2hex(random_bytes(32));
        $stmt = $this->conn->prepare(
            "INSERT INTO recuperaciones_clave (usuario_id, token_hash, expira_en)
             VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token_hash' => hash('sha256', $token),
        ]);

        return $token;
    }

    public function buscarValido($token) {
        if (!is_string($token) || strlen($token) !== 64) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "SELECT id, usuario_id FROM recuperaciones_clave
             WHERE token_hash = :token_hash
               AND usado = 0
               AND expira_en > NOW()
             LIMIT 1"
        );
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        return $stmt->fetch();
    }

    public function descartar($token) {
        if (!is_string($token) || $token === '') {
            return;
        }
        $stmt = $this->conn->prepare(
            "DELETE FROM recuperaciones_clave WHERE token_hash = :token_hash AND usado = 0"
        );
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
    }

    public function consumir($id) {
        $stmt = $this->conn->prepare(
            "UPDATE recuperaciones_clave SET usado = 1 WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function asegurarTabla() {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS recuperaciones_clave (
                id INT NOT NULL AUTO_INCREMENT,
                usuario_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expira_en DATETIME NOT NULL,
                usado TINYINT(1) NOT NULL DEFAULT 0,
                fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_token (token_hash),
                KEY idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
