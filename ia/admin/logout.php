<?php
/**
 * Logout - Encerra a sessão do usuário ou admin
 * VERSÃO CORRIGIDA COM MELHOR TRATAMENTO DE ERROS
 */

// Captura qualquer output acidental
ob_start();

// Carrega configurações
require_once 'config.php';

try {
    // Registrar logout no log se for admin
    if (isAdmin() && isset($_SESSION['admin_id'])) {
        try {
            // Salva o admin_id antes de destruir a sessão
            $adminId = $_SESSION['admin_id'];
            
            $stmt = $pdo->prepare("
                INSERT INTO admin_logs (admin_id, action, description, ip_address)
                VALUES (?, 'logout', 'Logout realizado', ?)
            ");
            $stmt->execute([
                $adminId, 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            // Ignora erro de log - não deve impedir logout
            error_log("Erro ao registrar logout: " . $e->getMessage());
        }
    }
    
    // Destruir sessão
    destroySession();
    
    // Limpa o buffer de output
    ob_end_clean();
    
    // Redirecionar para login
    redirect('login.php');
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro crítico no logout: " . $e->getMessage());
    
    // Limpa buffer
    ob_end_clean();
    
    // Força redirecionamento mesmo com erro
    header('Location: login.php');
    exit;
}