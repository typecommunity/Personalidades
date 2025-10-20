<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO settings (`key`, `value`) 
        VALUES (:key, :value) 
        ON DUPLICATE KEY UPDATE `value` = :value2
    ");
    
    $saved = 0;
    foreach ($input as $key => $value) {
        if (in_array($key, ['id', 'submit'])) {
            continue;
        }
        
        $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':value2' => $value
        ]);
        
        $saved++;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "$saved configurações salvas com sucesso",
        'saved' => $saved
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao salvar: ' . $e->getMessage()
    ]);
}
?>