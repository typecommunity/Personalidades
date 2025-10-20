<?php
/**
 * API: Deletar Personalidade
 * Arquivo: /admin/api/delete_personality.php
 * Atualizado: Remove também a foto do servidor
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

// Validar ID
if (empty($input['id'])) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'ID é obrigatório'
    ]);
    ob_end_flush();
    exit;
}

try {
    $id = (int)$input['id'];
    
    // Buscar dados da personalidade antes de deletar (para remover a imagem)
    $stmt = $pdo->prepare("SELECT avatar_image FROM personalities WHERE id = ?");
    $stmt->execute([$id]);
    $personality = $stmt->fetch();
    
    if (!$personality) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Personalidade não encontrada'
        ]);
        ob_end_flush();
        exit;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    try {
        // Deletar conversas associadas (opcional - ou usar CASCADE no banco)
        $stmt = $pdo->prepare("DELETE FROM conversations WHERE personality_id = ?");
        $stmt->execute([$id]);
        
        // Deletar personalidade
        $stmt = $pdo->prepare("DELETE FROM personalities WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit da transação
        $pdo->commit();
        
        // Remover imagem do servidor (se existir)
        if (!empty($personality['avatar_image'])) {
            $imagePath = '../' . ltrim($personality['avatar_image'], '/ia/admin/');
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Personalidade deletada com sucesso'
        ]);
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao deletar personalidade',
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();