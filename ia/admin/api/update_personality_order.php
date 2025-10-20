<?php
/**
 * API: Atualizar Ordem da Personalidade
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

header('Content-Type: application/json');

// Verificar se é admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID é obrigatório'
    ]);
    exit;
}

try {
    // Atualizar ordem
    $stmt = $pdo->prepare("
        UPDATE personalities 
        SET sort_order = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$sortOrder, $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Ordem atualizada com sucesso'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Personalidade não encontrada'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar ordem: ' . $e->getMessage()
    ]);
}