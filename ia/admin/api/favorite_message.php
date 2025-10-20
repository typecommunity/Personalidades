<?php
/**
 * API: Favoritar/Desfavoritar Mensagem
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

// Pegar dados do POST
$data = json_decode(file_get_contents('php://input'), true);

$messageId = $data['message_id'] ?? null;
$action = $data['action'] ?? 'add'; // 'add' ou 'remove'

if (!$messageId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da mensagem é obrigatório'
    ]);
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    // Verificar se a mensagem existe e pertence a uma conversa do usuário
    $stmt = $pdo->prepare("
        SELECT m.id 
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE m.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$messageId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Mensagem não encontrada ou sem permissão'
        ]);
        exit;
    }
    
    if ($action === 'add') {
        // ✅ ADICIONAR aos favoritos (ignora se já existe)
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO favorite_messages (user_id, message_id, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $messageId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem adicionada aos favoritos',
            'favorited' => true
        ]);
        
    } else {
        // ✅ REMOVER dos favoritos
        $stmt = $pdo->prepare("
            DELETE FROM favorite_messages 
            WHERE user_id = ? AND message_id = ?
        ");
        $stmt->execute([$userId, $messageId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem removida dos favoritos',
            'favorited' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao favoritar mensagem: ' . $e->getMessage()
    ]);
}