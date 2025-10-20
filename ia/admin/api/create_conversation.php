<?php
/**
 * API: Criar Nova Conversa
 * 100% Compatível com a estrutura do banco
 * ✅ CORRIGIDO: Evita conversas duplicadas
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
$personalityId = $data['personality_id'] ?? null;
$userId = $data['user_id'] ?? null;

// Validações
if (!$personalityId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da personalidade é obrigatório'
    ]);
    exit;
}

if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do usuário é obrigatório'
    ]);
    exit;
}

try {
    // Verificar se a personalidade existe e está ativa
    $stmt = $pdo->prepare("SELECT id, name FROM personalities WHERE id = ? AND is_active = 1");
    $stmt->execute([$personalityId]);
    $personality = $stmt->fetch();
    
    if (!$personality) {
        echo json_encode([
            'success' => false,
            'message' => 'Personalidade não encontrada ou inativa'
        ]);
        exit;
    }
    
    // ✅ VERIFICAR SE JÁ EXISTE UMA CONVERSA COM ESSA PERSONALIDADE
    $stmt = $pdo->prepare("
        SELECT id 
        FROM conversations 
        WHERE user_id = ? 
        AND personality_id = ? 
        AND conversation_type = 'individual'
        LIMIT 1
    ");
    $stmt->execute([$userId, $personalityId]);
    $existingConversation = $stmt->fetch();
    
    // Se já existe, retornar o ID da conversa existente
    if ($existingConversation) {
        echo json_encode([
            'success' => true,
            'conversation_id' => $existingConversation['id'],
            'existing' => true,
            'message' => 'Conversa já existe'
        ]);
        exit;
    }
    
    // Criar nova conversa
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user_id, personality_id, title, conversation_type, created_at, updated_at)
        VALUES (?, ?, ?, 'individual', NOW(), NOW())
    ");
    
    $title = "Conversa com " . $personality['name'];
    $stmt->execute([$userId, $personalityId, $title]);
    
    $conversationId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'existing' => false,
        'message' => 'Conversa criada com sucesso'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar conversa: ' . $e->getMessage()
    ]);
}