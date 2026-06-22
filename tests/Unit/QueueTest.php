<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Database;

/**
 * Pruebas unitarias para el sistema de cola de reportes.
 */
class QueueTest extends TestCase {
    /**
     * Prueba que se puedan insertar, consultar y eliminar trabajos en la cola.
     */
    public function testJobQueueLifecycle() {
        $pdo = Database::getWriteConnection();
        
        $userId = 1; // ID de administrador o usuario por defecto
        $type = 'test_job_' . uniqid();
        $payload = json_encode(['foo' => 'bar']);
        
        // 1. Insertar trabajo
        $stmt = $pdo->prepare("INSERT INTO jobs (user_id, type, payload, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$userId, $type, $payload]);
        $jobId = $pdo->lastInsertId();
        
        $this->assertNotEmpty($jobId);
        
        // 2. Consultar el trabajo y verificar propiedades
        $stmtSelect = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmtSelect->execute([$jobId]);
        $job = $stmtSelect->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($job);
        $this->assertEquals($userId, $job['user_id']);
        $this->assertEquals($type, $job['type']);
        $this->assertEquals($payload, $job['payload']);
        $this->assertEquals('pending', $job['status']);
        
        // 3. Cambiar el estatus del trabajo
        $stmtUpdate = $pdo->prepare("UPDATE jobs SET status = 'completed', file_path = 'public/exports/test.xlsx' WHERE id = ?");
        $stmtUpdate->execute([$jobId]);
        
        $stmtSelect->execute([$jobId]);
        $updatedJob = $stmtSelect->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals('completed', $updatedJob['status']);
        $this->assertEquals('public/exports/test.xlsx', $updatedJob['file_path']);
        
        // 4. Limpieza: Eliminar el trabajo de prueba
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$jobId]);
    }
}
