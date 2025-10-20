<?php
/**
 * API: Deletar Conversa
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$conversationId = $data['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da conversa é obrigatório'
    ]);
    exit;
}

try {
    // Validar acesso
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    $stmt = $pdo->prepare("
        SELECT id FROM conversations
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode([
            'success' => false,
            'message' => 'Conversa não encontrada ou sem permissão'
        ]);
        exit;
    }
    
    // Deletar conversa (CASCADE deleta mensagens automaticamente)
    $stmt = $pdo->prepare("DELETE FROM conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversa deletada com sucesso'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao deletar conversa: ' . $e->getMessage()
    ]);
}