<?php
/**
 * API: Buscar mensagens de uma conversa específica (Admin)
 * Arquivo: api/get_conversation_messages.php
 */

require_once '../config.php';

header('Content-Type: application/json');

// Verificar se é admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Não autorizado'
    ]);
    exit;
}

$conversation_id = $_GET['id'] ?? 0;

if (!$conversation_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da conversa é obrigatório'
    ]);
    exit;
}

try {
    // Buscar informações da conversa
    $stmt = $pdo->prepare("
        SELECT c.*, p.name as personality_name, p.avatar_color, p.avatar_image
        FROM conversations c
        JOIN personalities p ON c.personality_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        echo json_encode([
            'success' => false,
            'message' => 'Conversa não encontrada'
        ]);
        exit;
    }
    
    // Buscar mensagens
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE conversation_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'conversation' => $conversation,
        'personality_name' => $conversation['personality_name'],
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar mensagens: ' . $e->getMessage()
    ]);
}
?>