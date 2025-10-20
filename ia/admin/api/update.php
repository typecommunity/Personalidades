<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido']);
    }
    
    // Buscar personalidade existente
    $stmt = $pdo->prepare("SELECT * FROM personalities WHERE id = ?");
    $stmt->execute([$id]);
    $personality = $stmt->fetch();
    
    if (!$personality) {
        jsonResponse(['success' => false, 'message' => 'Personalidade não encontrada']);
    }
    
    // Campos que podem ser atualizados
    $fields = [];
    $values = [];
    
    if (isset($_POST['name'])) {
        $fields[] = "name = ?";
        $values[] = sanitize($_POST['name']);
    }
    
    if (isset($_POST['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitize($_POST['description']);
    }
    
    if (isset($_POST['avatar_color'])) {
        $fields[] = "avatar_color = ?";
        $values[] = sanitize($_POST['avatar_color']);
    }
    
    if (isset($_POST['system_prompt'])) {
        $fields[] = "system_prompt = ?";
        $values[] = $_POST['system_prompt']; // Não sanitizar
    }
    
    if (isset($_POST['is_active'])) {
        $fields[] = "is_active = ?";
        $values[] = (int)$_POST['is_active'];
    }
    
    if (isset($_POST['sort_order'])) {
        $fields[] = "sort_order = ?";
        $values[] = (int)$_POST['sort_order'];
    }
    
    if (empty($fields)) {
        jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar']);
    }
    
    // Atualizar
    $values[] = $id;
    $sql = "UPDATE personalities SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    // Log
    logAdminAction(
        $_SESSION['admin_id'], 
        'update_personality', 
        "Personalidade atualizada: ID $id"
    );
    
    jsonResponse([
        'success' => true, 
        'message' => 'Personalidade atualizada com sucesso!'
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao atualizar personalidade: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao atualizar personalidade'], 500);
}