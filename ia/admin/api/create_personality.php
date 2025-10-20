<?php
/**
 * API: Criar Nova Personalidade
 * Arquivo: /admin/api/create_personality.php
 * Atualizado com suporte a avatar_image
 */

error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once '../config.php';

ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Verificar se é admin
if (!isAdmin()) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    ob_end_flush();
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? null;
$description = $data['description'] ?? '';
$avatarColor = $data['avatar_color'] ?? '#8b5cf6';
$avatarImage = $data['avatar_image'] ?? null;
$systemPrompt = $data['system_prompt'] ?? null;
$isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
$sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;

// Validações
if (!$name) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Nome é obrigatório'
    ]);
    ob_end_flush();
    exit;
}

if (!$systemPrompt) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'System prompt é obrigatório'
    ]);
    ob_end_flush();
    exit;
}

try {
    // Inserir personalidade (COM avatar_image)
    $stmt = $pdo->prepare("
        INSERT INTO personalities 
        (name, description, avatar_color, avatar_image, system_prompt, is_active, sort_order, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $name,
        $description,
        $avatarColor,
        $avatarImage,
        $systemPrompt,
        $isActive,
        $sortOrder
    ]);
    
    $personalityId = $pdo->lastInsertId();
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'personality_id' => $personalityId,
        'message' => 'Personalidade criada com sucesso'
    ]);
    
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar personalidade',
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();