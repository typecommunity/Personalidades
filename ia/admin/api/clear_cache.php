<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    // Limpar sessões antigas
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $deletedSessions = $stmt->rowCount();
    
    // Limpar arquivos temporários
    $cacheDir = __DIR__ . '/../../cache/';
    $deletedFiles = 0;
    
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deletedFiles++;
            }
        }
    }
    
    // Otimizar tabelas
    $tables = ['users', 'conversations', 'messages', 'personalities', 'settings'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("OPTIMIZE TABLE `$table`");
        } catch (Exception $e) {
            // Ignorar erros
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache limpo com sucesso',
        'details' => [
            'sessions_deleted' => $deletedSessions,
            'files_deleted' => $deletedFiles
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao limpar cache: ' . $e->getMessage()
    ]);
}
?>