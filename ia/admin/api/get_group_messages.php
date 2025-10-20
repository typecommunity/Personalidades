<?php
header('Content-Type: application/json');
require_once '../config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$conversationId = $_GET['conversation_id'] ?? null;
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'ID da conversa não fornecido']);
    exit;
}

try {
    // Verificar se a conversa pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversa não encontrada']);
        exit;
    }
    
    // Buscar informações do grupo
    $stmt = $pdo->prepare("
        SELECT gc.*,
               (SELECT COUNT(*) FROM group_members WHERE group_id = gc.id AND is_active = 1) as members_count
        FROM group_conversations gc
        WHERE gc.conversation_id = ? AND gc.is_active = 1
    ");
    $stmt->execute([$conversationId]);
    $groupInfo = $stmt->fetch();
    
    if (!$groupInfo) {
        echo json_encode(['success' => false, 'message' => 'Informações do grupo não encontradas']);
        exit;
    }
    
    // Buscar mensagens
    $stmt = $pdo->prepare("
        SELECT
            m.*,
            p.name as personality_name,
            p.avatar_color,
            p.avatar_image as personality_avatar
        FROM messages m
        LEFT JOIN personalities p ON m.personality_id = p.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'group_info' => [
            'id' => $groupInfo['id'],
            'name' => $groupInfo['name'],  // ✅ CORRIGIDO
            'description' => $groupInfo['description'],  // ✅ CORRIGIDO
            'avatar_url' => $groupInfo['avatar_url'],
            'members_count' => (int)$groupInfo['members_count']
        ],
        'members_count' => (int)$groupInfo['members_count']
    ]);
    
} catch (Exception $e) {
    error_log("Erro em get_group_messages.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar mensagens: ' . $e->getMessage()]);
}
?>