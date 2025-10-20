<?php
/**
 * API: Atualizar Usuário
 * Endpoint: POST /admin/api/update_user.php
 * Função: Atualiza informações de um usuário
 */

session_start();
require_once '../../config.php';

// Headers JSON
header('Content-Type: application/json');

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Obter dados JSON do corpo da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Validar se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'JSON inválido'
    ]);
    exit;
}

// Validar ID do usuário (obrigatório)
if (!isset($input['id']) || !is_numeric($input['id']) || $input['id'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID do usuário é obrigatório e deve ser um número válido'
    ]);
    exit;
}

$userId = (int)$input['id'];

try {
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        exit;
    }

    // Construir query de atualização dinâmica
    $updates = [];
    $params = [];
    
    // Campos que podem ser atualizados
    $allowedFields = ['name', 'email', 'status', 'role', 'bio', 'avatar'];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $value = $input[$field];
            
            // ===== VALIDAÇÕES ESPECÍFICAS =====
            
            // 1. Validar EMAIL
            if ($field === 'email') {
                if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Email inválido'
                    ]);
                    exit;
                }
                
                // Verificar se o email já está em uso por outro usuário
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$value, $userId]);
                
                if ($stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Este email já está em uso por outro usuário'
                    ]);
                    exit;
                }
            }
            
            // 2. Validar STATUS
            if ($field === 'status') {
                $validStatuses = ['active', 'inactive', 'banned'];
                if (!in_array($value, $validStatuses)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Status inválido. Valores aceitos: ' . implode(', ', $validStatuses)
                    ]);
                    exit;
                }
            }
            
            // 3. Validar ROLE (função)
            if ($field === 'role') {
                $validRoles = ['user', 'admin', 'moderator'];
                if (!in_array($value, $validRoles)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Função inválida. Valores aceitos: ' . implode(', ', $validRoles)
                    ]);
                    exit;
                }
            }
            
            // 4. Validar NAME
            if ($field === 'name' && (empty($value) || strlen($value) > 255)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome inválido ou muito longo (máx. 255 caracteres)'
                ]);
                exit;
            }
            
            // Adicionar campo à query
            $updates[] = "`$field` = ?";
            $params[] = $value;
        }
    }
    
    // Se não houver campos para atualizar
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum campo válido para atualizar foi fornecido'
        ]);
        exit;
    }
    
    // Adicionar ID ao final dos parâmetros (para o WHERE)
    $params[] = $userId;
    
    // Executar atualização
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if (!$result) {
        throw new Exception('Falha ao executar atualização no banco de dados');
    }
    
    // Log da ação (opcional - se a tabela admin_logs existir)
    try {
        $adminId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
        $changedFields = array_keys(array_intersect_key($input, array_flip($allowedFields)));
        
        $logStmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, description, created_at) 
            VALUES (?, 'update_user', ?, NOW())
        ");
        $logStmt->execute([
            $adminId,
            "Usuário ID #$userId atualizado. Campos alterados: " . implode(', ', $changedFields)
        ]);
    } catch (PDOException $e) {
        // Tabela admin_logs pode não existir, apenas ignorar
    }
    
    // Buscar dados atualizados do usuário
    $stmt = $pdo->prepare("
        SELECT id, name, email, status, role, bio, avatar, created_at, last_login 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Usuário atualizado com sucesso',
        'user_id' => $userId,
        'updated_fields' => array_keys(array_intersect_key($input, array_flip($allowedFields))),
        'user' => $updatedUser
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()
    ]);
}
?>