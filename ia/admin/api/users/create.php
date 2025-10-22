<?php
header('Content-Type: application/json');
require_once '../../config.php';

// Verificar se é admin
if (!isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    exit;
}

try {
    // Validar dados recebidos
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validações
    if (empty($name)) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($email)) {
        throw new Exception('Email é obrigatório');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    if (empty($password)) {
        throw new Exception('Senha é obrigatória');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('A senha deve ter no mínimo 6 caracteres');
    }
    
    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está cadastrado');
    }
    
    // Hash da senha
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Inserir usuário
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $passwordHash,
        $phone ?: null,
        $status
    ]);
    
    $userId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuário criado com sucesso',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}