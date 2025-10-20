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
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? null);
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Validações
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Nome é obrigatório']);
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Email inválido']);
    }
    
    if (empty($password) || strlen($password) < 6) {
        jsonResponse(['success' => false, 'message' => 'Senha deve ter no mínimo 6 caracteres']);
    }
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Este email já está cadastrado']);
    }
    
    // Criar hash da senha
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Inserir usuário
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $passwordHash,
        $phone,
        $status
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Log da ação
    logAdminAction(
        $_SESSION['admin_id'],
        'create_user',
        "Usuário criado: $name ($email) - ID: $userId"
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'Usuário criado com sucesso!',
        'id' => $userId
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao criar usuário: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao criar usuário'], 500);
}