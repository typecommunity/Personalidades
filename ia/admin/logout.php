<?php
/**
 * Logout - Encerra a sessão do usuário ou admin
 * VERSÃO CORRIGIDA - Simplificada
 */

require_once 'config.php';

// Registrar logout no log se for admin
if (isAdmin()) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, description, ip_address)
            VALUES (?, 'logout', 'Logout realizado', ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'], 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Ignora erro de log - não deve impedir logout
        error_log("Erro ao registrar logout: " . $e->getMessage());
    }
}

// Destruir sessão (função já existe no config.php)
destroySession();

// ✅ CORREÇÃO: Usar redirect sem barra inicial
redirect('login.php');