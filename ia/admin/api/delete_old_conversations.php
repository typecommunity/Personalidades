<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Encontrar conversas antigas (90+ dias)
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $oldConversations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($oldConversations)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma conversa antiga encontrada',
            'deleted' => 0
        ]);
        exit;
    }
    
    $conversationIds = implode(',', $oldConversations);
    
    // Deletar mensagens
    $stmt = $pdo->prepare("DELETE FROM messages WHERE conversation_id IN ($conversationIds)");
    $stmt->execute();
    $deletedMessages = $stmt->rowCount();
    
    // Deletar conversas
    $stmt = $pdo->prepare("DELETE FROM conversations WHERE id IN ($conversationIds)");
    $stmt->execute();
    $deletedConversations = $stmt->rowCount();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "$deletedConversations conversas antigas excluídas",
        'deleted' => $deletedConversations,
        'messages_deleted' => $deletedMessages
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir conversas: ' . $e->getMessage()
    ]);
}
?>