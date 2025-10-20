<?php
/**
 * API: Atualizar Personalidade
 * Arquivo: /admin/api/update_personality.php
 * Atualizado com suporte a avatar_image
 */

error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once '../config.php';

ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Validar se é admin
if (!isAdmin()) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    ob_end_flush();
    exit;
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar campos obrigatórios
if (empty($input['id']) || empty($input['name']) || empty($input['system_prompt'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'ID, Nome e System Prompt são obrigatórios'
    ]);
    ob_end_flush();
    exit;
}

try {
    // Buscar imagem antiga antes de atualizar
    $stmt = $pdo->prepare("SELECT avatar_image FROM personalities WHERE id = ?");
    $stmt->execute([$input['id']]);
    $oldData = $stmt->fetch();
    
    // Preparar dados
    $id = (int)$input['id'];
    $name = trim($input['name']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    $avatar_color = isset($input['avatar_color']) ? trim($input['avatar_color']) : '#059669';
    $sort_order = isset($input['sort_order']) ? (int)$input['sort_order'] : 0;
    $system_prompt = trim($input['system_prompt']);
    $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    
    // Verificar se a imagem foi alterada
    $avatar_image = isset($input['avatar_image']) ? trim($input['avatar_image']) : $oldData['avatar_image'];
    
    // Se a imagem foi removida (string vazia) e existia uma imagem antiga, deletar o arquivo
    if (empty($avatar_image) && !empty($oldData['avatar_image'])) {
        $oldImagePath = '../' . ltrim($oldData['avatar_image'], '/ia/admin/');
        if (file_exists($oldImagePath)) {
            @unlink($oldImagePath);
        }
        $avatar_image = null;
    }
    
    // Se há uma nova imagem e ela é diferente da antiga, deletar a antiga
    if (!empty($avatar_image) && !empty($oldData['avatar_image']) && $avatar_image !== $oldData['avatar_image']) {
        $oldImagePath = '../' . ltrim($oldData['avatar_image'], '/ia/admin/');
        if (file_exists($oldImagePath)) {
            @unlink($oldImagePath);
        }
    }
    
    // Atualizar no banco
    $stmt = $pdo->prepare("
        UPDATE personalities 
        SET 
            name = ?,
            description = ?,
            avatar_color = ?,
            avatar_image = ?,
            sort_order = ?,
            system_prompt = ?,
            is_active = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name,
        $description,
        $avatar_color,
        $avatar_image,
        $sort_order,
        $system_prompt,
        $is_active,
        $id
    ]);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Personalidade atualizada com sucesso'
    ]);
    
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar personalidade',
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();