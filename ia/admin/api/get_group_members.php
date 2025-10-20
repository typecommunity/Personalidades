<?php
header('Content-Type: application/json');
require_once '../config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$conversationId = $_GET['conversation_id'] ?? null;

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'ID da conversa não fornecido']);
    exit;
}

try {
    // Buscar o group_id a partir da conversa
    $stmt = $pdo->prepare("
        SELECT id, group_name, group_description, avatar_url
        FROM group_conversations 
        WHERE conversation_id = ? AND is_active = 1
    ");
    $stmt->execute([$conversationId]);
    $group = $stmt->fetch();
    
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Grupo não encontrado']);
        exit;
    }
    
    // Buscar membros do grupo
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.avatar_color,
            p.avatar_image,
            p.description
        FROM group_members gm
        JOIN personalities p ON gm.personality_id = p.id
        WHERE gm.group_id = ? AND gm.is_active = 1
        ORDER BY p.name ASC
    ");
    $stmt->execute([$group['id']]);
    $members = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'members' => $members,
        'count' => count($members),
        'group_info' => [
            'name' => $group['group_name'],
            'description' => $group['group_description'],
            'avatar_url' => $group['avatar_url']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em get_group_members.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar membros: ' . $e->getMessage()]);
}
?>