<?php
/**
 * API: Criar Grupo de Conversas (CORRIGIDA)
 * Arquivo: api/create_group.php
 * 
 * Correção: personality_id agora pode ser NULL para grupos
 */

require_once '../config.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$personalities = $data['personalities'] ?? [];
$user_id = $data['user_id'] ?? null;
$avatar_url = $data['avatar_url'] ?? null; // Adicionar avatar_url

// Validações
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nome do grupo é obrigatório']);
    exit;
}

if (!is_array($personalities) || count($personalities) < 2) {
    echo json_encode(['success' => false, 'message' => 'Selecione pelo menos 2 personalidades']);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário é obrigatório']);
    exit;
}

try {
    // Verificar se as personalidades existem
    $placeholders = str_repeat('?,', count($personalities) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM personalities 
        WHERE id IN ($placeholders) AND is_active = 1
    ");
    $stmt->execute($personalities);
    $validPersonalities = $stmt->fetchColumn();
    
    if ($validPersonalities != count($personalities)) {
        echo json_encode(['success' => false, 'message' => 'Uma ou mais personalidades inválidas']);
        exit;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // 1. Criar conversa do tipo grupo (personality_id é NULL para grupos)
    $stmt = $pdo->prepare("
        INSERT INTO conversations (
            user_id, 
            personality_id, 
            conversation_type, 
            title, 
            group_name, 
            group_description,
            created_at, 
            updated_at
        ) VALUES (?, NULL, 'group', ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([$user_id, $name, $name, $description]);
    $conversationId = $pdo->lastInsertId();
    
    // 2. Criar registro do grupo
    $stmt = $pdo->prepare("
        INSERT INTO group_conversations (
            conversation_id, 
            creator_id, 
            name, 
            description, 
            avatar_url,
            is_active,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([$conversationId, $user_id, $name, $description, $avatar_url]);
    $groupId = $pdo->lastInsertId();
    
    // 3. Adicionar personalidades ao grupo
    $stmt = $pdo->prepare("
        INSERT INTO group_members (
            group_id, 
            personality_id, 
            response_probability,
            is_active,
            joined_at
        ) VALUES (?, ?, ?, 1, NOW())
    ");
    
    // Calcular probabilidade baseada no número de membros
    $baseProbability = 1 / count($personalities);
    $probability = max(0.3, min(0.7, $baseProbability)); // Entre 30% e 70%
    
    foreach ($personalities as $personalityId) {
        $stmt->execute([$groupId, $personalityId, $probability]);
    }
    
    // 4. Buscar nomes das personalidades para mensagem de boas-vindas
    $stmt = $pdo->prepare("
        SELECT name 
        FROM personalities 
        WHERE id IN ($placeholders)
        ORDER BY name
    ");
    $stmt->execute($personalities);
    $personalityNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 5. Adicionar mensagem de boas-vindas
    $welcomeMessage = "🎉 Grupo '$name' criado com sucesso!\n";
    $welcomeMessage .= "👥 Participantes: " . implode(', ', $personalityNames);
    
    if (!empty($description)) {
        $welcomeMessage .= "\n📝 Descrição: $description";
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            conversation_id, 
            role, 
            personality_id,
            content, 
            created_at
        ) VALUES (?, 'assistant', NULL, ?, NOW())
    ");
    $stmt->execute([$conversationId, $welcomeMessage]);
    
    // Confirmar transação
    $pdo->commit();
    
    // Retornar sucesso com informações do grupo
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'group_id' => $groupId,
        'group_name' => $name,
        'member_count' => count($personalities),
        'members' => $personalityNames,
        'message' => 'Grupo criado com sucesso!'
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log do erro (opcional)
    error_log("Erro ao criar grupo: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar grupo: ' . $e->getMessage()
    ]);
}
?>