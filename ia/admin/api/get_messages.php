<?php
/**
 * API: Buscar Mensagens de uma Conversa
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

$conversationId = $_GET['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da conversa é obrigatório'
    ]);
    exit;
}

try {
    // Buscar conversa e validar acesso
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    // ✅ Incluir avatar_image na query
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.avatar_color, p.avatar_image
        FROM conversations c
        JOIN personalities p ON c.personality_id = p.id
        WHERE c.id = ? AND c.user_id = ?
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

    // ✅ CORRIGIDO: Buscar mensagens com LEFT JOIN na tabela favorite_messages
    $stmt = $pdo->prepare("
        SELECT 
            m.id, 
            m.role, 
            m.content, 
            m.created_at,
            CASE WHEN fm.id IS NOT NULL THEN 1 ELSE 0 END as is_favorited
        FROM messages m
        LEFT JOIN favorite_messages fm ON fm.message_id = m.id AND fm.user_id = ?
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$userId, $conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Converter is_favorited para boolean
    foreach ($messages as &$message) {
        $message['is_favorited'] = (bool)$message['is_favorited'];
    }

    echo json_encode([
        'success' => true,
        'conversation' => [
            'id' => $conversation['id'],
            'title' => $conversation['title']
        ],
        'personality' => [
            'name' => $conversation['name'],
            'avatar_color' => $conversation['avatar_color'],
            'avatar_image' => $conversation['avatar_image'] ?? ''
        ],
        'messages' => $messages
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar mensagens: ' . $e->getMessage()
    ]);
}