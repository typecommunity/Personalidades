<?php
/**
 * API: Atualizar Perfil do Usuário
 * Processa atualização de dados e upload de avatar
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

// Obter ID do usuário
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
$isAdmin = isAdmin();

if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não encontrado'
    ]);
    exit;
}

try {
    // Receber dados do POST (FormData)
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validações básicas
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Nome é obrigatório'
        ]);
        exit;
    }
    
    if (empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email é obrigatório'
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email inválido'
        ]);
        exit;
    }
    
    // Verificar se email já existe para outro usuário
    if ($isAdmin) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    }
    $stmt->execute([$email, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Este email já está em uso por outro usuário'
        ]);
        exit;
    }
    
    // ===== PROCESSAR UPLOAD DE AVATAR =====
    $avatarUrl = null;
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        
        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de arquivo inválido. Use apenas imagens (JPG, PNG, GIF, WEBP)'
            ]);
            exit;
        }
        
        // Validar tamanho (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'Arquivo muito grande. Tamanho máximo: 5MB'
            ]);
            exit;
        }
        
        // Criar diretório de upload se não existir
        $uploadDir = '../admin/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Gerar nome único para o arquivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_user_' . $userId . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;
        
        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // URL relativa para salvar no banco
            $avatarUrl = '/ia/admin/uploads/' . $fileName;
            
            // Deletar avatar antigo se existir
            if ($isAdmin) {
                $stmt = $pdo->prepare("SELECT avatar_url FROM admins WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
            }
            $stmt->execute([$userId]);
            $oldAvatar = $stmt->fetchColumn();
            
            if ($oldAvatar && file_exists('../admin/uploads/' . basename($oldAvatar))) {
                @unlink('../admin/uploads/' . basename($oldAvatar));
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao fazer upload do arquivo'
            ]);
            exit;
        }
    }
    
    // ===== ATUALIZAR BANCO DE DADOS =====
    
    // Preparar query base
    if ($isAdmin) {
        $table = 'admins';
        $fields = ['name = ?', 'email = ?'];
        $params = [$name, $email];
        
        // Phone não existe para admins, então não atualiza
    } else {
        $table = 'users';
        $fields = ['name = ?', 'email = ?', 'phone = ?'];
        $params = [$name, $email, $phone];
    }
    
    // Adicionar avatar se foi enviado
    if ($avatarUrl) {
        $fields[] = 'avatar_url = ?';
        $params[] = $avatarUrl;
    }
    
    // Adicionar senha se foi fornecida
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'A senha deve ter no mínimo 6 caracteres'
            ]);
            exit;
        }
        
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Adicionar ID no final
    $params[] = $userId;
    
    // Montar e executar query
    $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Atualizar informações na sessão
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    if ($avatarUrl) {
        $_SESSION['user_avatar'] = $avatarUrl;
    }
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Perfil atualizado com sucesso',
        'avatar_url' => $avatarUrl ?? null
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}