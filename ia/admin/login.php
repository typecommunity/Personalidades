<?php
/**
 * Login Unificado - Admin e Usuários
 * Este arquivo permite login tanto para admins quanto usuários comuns
 */

require_once 'config.php';

// Se já está logado, redireciona para o local apropriado
if (isAdmin()) {
    header('Location: /ia/admin/dashboard.php');
    exit;
}

if (isUser()) {
    header('Location: /ia/admin/chat.php');
    exit;
}

$error = '';
$success = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos";
    } else {
        $loginSuccess = false;
        $userType = '';
        $userData = null;
        
        try {
            // PRIMEIRO: Tentar login como ADMIN
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login de ADMIN bem-sucedido
                
                // Limpar qualquer sessão anterior
                session_regenerate_id(true);
                unset($_SESSION['user_id']);
                unset($_SESSION['user_name']);
                unset($_SESSION['user_email']);
                
                // Criar sessão no banco
                $sessionId = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
                
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (id, admin_id, user_type, ip_address, user_agent, expires_at) 
                    VALUES (?, ?, 'admin', ?, ?, ?)
                ");
                $stmt->execute([
                    $sessionId,
                    $admin['id'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $expiresAt
                ]);
                
                // Atualizar último login
                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
                
                // Definir variáveis de sessão do admin
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['session_id'] = $sessionId;
                
                // Log da ação
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin_logs (admin_id, action, description, ip_address) 
                        VALUES (?, 'login', 'Login realizado com sucesso', ?)
                    ");
                    $stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? null]);
                } catch (PDOException $e) {
                    // Ignora erro de log
                }
                
                // Redirecionar para dashboard do admin
                header('Location: /ia/admin/dashboard.php');
                exit;
            }
            
            // SEGUNDO: Se não é admin, tentar login como USUÁRIO
            if (!$admin) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Verificar status do usuário
                    if ($user['status'] !== 'active') {
                        $error = "Sua conta está " . ($user['status'] === 'blocked' ? 'bloqueada' : 'inativa') . ". Entre em contato com o suporte.";
                    } else {
                        // Login de USUÁRIO bem-sucedido
                        
                        // Limpar qualquer sessão anterior
                        session_regenerate_id(true);
                        unset($_SESSION['admin_id']);
                        unset($_SESSION['admin_name']);
                        unset($_SESSION['admin_email']);
                        
                        // Criar sessão no banco
                        $sessionId = bin2hex(random_bytes(32));
                        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO sessions (id, user_id, user_type, ip_address, user_agent, expires_at) 
                            VALUES (?, ?, 'user', ?, ?, ?)
                        ");
                        $stmt->execute([
                            $sessionId,
                            $user['id'],
                            $_SERVER['REMOTE_ADDR'] ?? null,
                            $_SERVER['HTTP_USER_AGENT'] ?? null,
                            $expiresAt
                        ]);
                        
                        // Atualizar último login
                        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                        
                        // Definir variáveis de sessão do usuário
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'] ?? $user['email'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['session_id'] = $sessionId;
                        
                        // Redirecionar para o chat
                        header('Location: /ia/admin/chat.php');
                        exit;
                    }
                } else {
                    $error = "Email ou senha incorretos. Por favor, verifique seus dados e tente novamente.";
                }
            }
            
            // Se chegou aqui e não houve sucesso, é porque as credenciais estão erradas
            if (empty($error) && !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
                $error = "Email ou senha incorretos. Por favor, verifique seus dados e tente novamente.";
            }
            
        } catch (PDOException $e) {
            $error = "Erro ao processar login. Por favor, tente novamente em alguns instantes.";
            error_log("Erro login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dualis</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Elementos decorativos de fundo */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -200px;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            z-index: 1;
            position: relative;
        }
        
        .login-box {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            filter: drop-shadow(0 4px 8px rgba(5, 150, 105, 0.4));
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 15px;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideDown 0.3s ease-out, shake 0.5s ease-in-out;
            font-size: 14px;
            line-height: 1.5;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #111827;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: #fafbfc;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: white;
        }
        
        .form-group input.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.4);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }
        
        .divider span {
            background: white;
            padding: 0 12px;
            color: #9ca3af;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: #e5e7eb;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 24px;
        }
        
        .login-footer a {
            color: #059669;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .login-footer a:hover {
            color: #047857;
            text-decoration: underline;
        }
        
        .login-footer .separator {
            color: #e5e7eb;
            margin: 0 12px;
        }
        
        .copyright {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
    <!-- ============================================
     FAVICON COMPLETO
     Cole este código no <head> do login.php
     Logo após <title>Login - Dualis</title>
     ============================================ -->

<!-- Favicon Principal (Navegadores Modernos) -->
<link rel="icon" type="image/png" sizes="96x96" href="https://dualis.app/assets/icons/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="32x32" href="https://dualis.app/assets/icons/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://dualis.app/assets/icons/favicon-96x96.png">

<!-- Favicon ICO (Navegadores Antigos) -->
<link rel="shortcut icon" href="https://dualis.app/assets/icons/favicon.ico">

<!-- Favicon SVG (Navegadores Modernos que suportam) -->
<link rel="icon" type="image/svg+xml" href="https://dualis.app/assets/icons/favicon.svg">

<!-- Apple Touch Icon (iPhone/iPad) -->
<link rel="apple-touch-icon" href="https://dualis.app/assets/icons/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="180x180" href="https://dualis.app/assets/icons/apple-touch-icon.png">

<!-- Manifest PWA -->
<link rel="manifest" href="https://dualis.app/manifest.json">

<!-- Theme Color (Barra de Endereço Mobile) -->
<meta name="theme-color" content="#059669">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#059669">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#047857">

<!-- Apple Web App -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Dualis">

<!-- Microsoft Tiles (Windows) -->
<meta name="msapplication-TileColor" content="#059669">
<meta name="msapplication-TileImage" content="https://dualis.app/assets/icons/web-app-manifest-192x192.png">
<meta name="msapplication-config" content="https://dualis.app/browserconfig.xml">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://dualis.app/">
<meta property="og:title" content="Dualis - Sua consciência digital">
<meta property="og:description" content="Acesse o Dualis, sua consciência digital">
<meta property="og:image" content="https://dualis.app/assets/icons/web-app-manifest-512x512.png">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary">
<meta name="twitter:url" content="https://dualis.app/">
<meta name="twitter:title" content="Dualis - Sua consciência digital">
<meta name="twitter:description" content="Acesse o Dualis, sua consciência digital">
<meta name="twitter:image" content="https://dualis.app/assets/icons/web-app-manifest-512x512.png">
</head>
<body>
    
    <div class="login-container">
        <div class="login-box">
            
            <!-- Logo -->
            <div class="login-header">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L4 6V12C4 16.5 7 20.5 12 22C17 20.5 20 16.5 20 12V6L12 2Z" fill="url(#gradient)" opacity="0.9"/>
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#059669;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#d4af37;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <circle cx="9" cy="10" r="1.5" fill="white"/>
                    <circle cx="15" cy="10" r="1.5" fill="white"/>
                    <path d="M8 13C8 13 10 16 12 16C14 16 16 13 16 13" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <h1>Dualis</h1>
                <p>Sua consciência digital</p>
            </div>
            
            <!-- Mensagens -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form method="POST" action="">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autocomplete="email"
                        autofocus
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="seu@email.com"
                        class="<?= $error ? 'error' : '' ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="<?= $error ? 'error' : '' ?>"
                    >
                </div>
                
                <button type="submit" class="btn">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Entrar
                </button>
                
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>OU</span>
            </div>
            
            <!-- Links -->
            <div class="login-footer">
                <a href="/ia/register.php">Criar nova conta</a>
                <span class="separator">|</span>
                <a href="/ia/forgot-password.php">Esqueci minha senha</a>
            </div>
            
            <!-- Copyright -->
            <div class="copyright">
                <p>© <?= date('Y') ?> Dualis. Todos os direitos reservados. Versão não comercial.</p>
            </div>
            
        </div>
    </div>
  <!-- ============================================
     SNIPPET: Adicionar no login.php
     Copie este código ANTES do </body>
     ============================================ -->

<!-- Botão Flutuante de Instalação PWA -->
<button id="pwa-install-floating-btn" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: none;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    z-index: 99999;
    transition: all 0.3s ease;
    animation: slideInUp 0.5s ease, pulse 2s ease-in-out 1s infinite;
">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
    <span id="pwa-btn-text">Instalar App</span>
</button>

<!-- Banner de Instalação (iOS/Firefox) -->
<div id="pwa-install-banner" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 16px 20px;
    display: none;
    align-items: center;
    justify-content: space-between;
    z-index: 99998;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    animation: slideInDown 0.5s ease;
">
    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
        <img src="/assets/icons/apple-touch-icon.png" style="width: 40px; height: 40px; border-radius: 8px;">
        <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 15px;">📱 Instale o Dualis</div>
            <div style="font-size: 13px; opacity: 0.9;">Acesso rápido da sua tela inicial</div>
        </div>
    </div>
    <button onclick="showInstallInstructions()" style="
        padding: 8px 20px;
        background: white;
        color: #2563eb;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
        margin-left: 12px;
    ">
        Ver Como
    </button>
    <button onclick="closeBanner()" style="
        background: transparent;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0 8px;
        margin-left: 12px;
        opacity: 0.8;
    ">×</button>
</div>

<!-- Modal de Instruções -->
<div id="pwa-install-modal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
">
    <div style="
        background: white;
        border-radius: 24px;
        max-width: 500px;
        width: 100%;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: scaleIn 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    ">
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="/assets/icons/web-app-manifest-192x192.png" style="width: 80px; height: 80px; border-radius: 16px; margin-bottom: 16px;">
            <h2 style="font-size: 24px; color: #1f2937; margin-bottom: 8px;">Instalar Dualis</h2>
            <p style="color: #6b7280; font-size: 14px;">Acesse o app direto da sua tela inicial</p>
        </div>

        <div id="pwa-modal-content" style="color: #4b5563; line-height: 1.8; font-size: 15px;">
            <!-- Conteúdo dinâmico baseado no navegador -->
        </div>

        <button onclick="closeModal()" style="
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            font-size: 16px;
        ">
            Entendi
        </button>
    </div>
</div>

<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-100%); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

#pwa-install-floating-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
}

@media (max-width: 640px) {
    #pwa-install-floating-btn {
        bottom: 80px; /* Acima de menus mobile */
        right: 16px;
        padding: 12px 20px;
        font-size: 14px;
    }
    
    #pwa-install-banner {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
}
</style>

<!-- ============================================
     PWA SNIPPET FINAL - VERSÃO CORRIGIDA
     Copie TODO este código e cole ANTES do </body> no login.php
     ============================================ -->

<!-- Botão Flutuante de Instalação PWA -->
<button id="pwa-install-floating-btn" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: none;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    z-index: 99999;
    transition: all 0.3s ease;
    animation: slideInUp 0.5s ease, pulse 2s ease-in-out 1s infinite;
">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
    <span id="pwa-btn-text">Instalar App</span>
</button>

<!-- Banner de Instalação (iOS/Firefox) -->
<div id="pwa-install-banner" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 16px 20px;
    display: none;
    align-items: center;
    justify-content: space-between;
    z-index: 99998;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    animation: slideInDown 0.5s ease;
">
    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
        <img src="https://dualis.app/assets/icons/apple-touch-icon.png" style="width: 40px; height: 40px; border-radius: 8px;" alt="Dualis" onerror="this.style.display='none'">
        <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 15px;">📱 Instale o Dualis</div>
            <div style="font-size: 13px; opacity: 0.9;">Acesso rápido da sua tela inicial</div>
        </div>
    </div>
    <button onclick="showInstallInstructions()" style="
        padding: 8px 20px;
        background: white;
        color: #2563eb;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
        margin-left: 12px;
    ">
        Ver Como
    </button>
    <button onclick="closeBanner()" style="
        background: transparent;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0 8px;
        margin-left: 12px;
        opacity: 0.8;
    ">×</button>
</div>

<!-- Modal de Instruções -->
<div id="pwa-install-modal" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
">
    <div style="
        background: white;
        border-radius: 24px;
        max-width: 500px;
        width: 100%;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: scaleIn 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    ">
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="https://dualis.app/assets/icons/web-app-manifest-192x192.png" style="width: 80px; height: 80px; border-radius: 16px; margin-bottom: 16px;" alt="Dualis Logo" onerror="this.style.display='none'">
            <h2 style="font-size: 24px; color: #1f2937; margin-bottom: 8px;">Instalar Dualis</h2>
            <p style="color: #6b7280; font-size: 14px;">Acesse o app direto da sua tela inicial</p>
        </div>

        <div id="pwa-modal-content" style="color: #4b5563; line-height: 1.8; font-size: 15px;">
            <!-- Conteúdo dinâmico baseado no navegador -->
        </div>

        <button onclick="closeModal()" style="
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            font-size: 16px;
        ">
            Entendi
        </button>
    </div>
</div>

<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-100%); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

#pwa-install-floating-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
}

@media (max-width: 640px) {
    #pwa-install-floating-btn {
        bottom: 80px;
        right: 16px;
        padding: 12px 20px;
        font-size: 14px;
    }
    
    #pwa-install-banner {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
}
</style>

<script>
(function() {
    'use strict';

    let deferredPrompt = null;
    const installBtn = document.getElementById('pwa-install-floating-btn');
    const installBanner = document.getElementById('pwa-install-banner');
    const installModal = document.getElementById('pwa-install-modal');
    const modalContent = document.getElementById('pwa-modal-content');
    const btnText = document.getElementById('pwa-btn-text');

    // Detectar navegador e plataforma
    function detectDevice() {
        const ua = navigator.userAgent;
        return {
            isIOS: /iPhone|iPad|iPod/.test(ua),
            isAndroid: /Android/.test(ua),
            isChrome: /Chrome/.test(ua) && !/Edg/.test(ua),
            isEdge: /Edg/.test(ua),
            isFirefox: /Firefox/.test(ua),
            isSafari: /Safari/.test(ua) && !/Chrome/.test(ua),
            isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua),
            isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone
        };
    }

    const device = detectDevice();
    console.log('PWA: Dispositivo detectado', device);

    // Se já está instalado, não mostrar nada
    if (device.isStandalone) {
        console.log('PWA: Já instalado, não mostrando UI');
        return;
    }

    // Capturar evento de instalação (Chrome/Edge)
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('PWA: beforeinstallprompt capturado');
        e.preventDefault();
        deferredPrompt = e;
        installBtn.style.display = 'flex';
    });

    // Click no botão flutuante
    installBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
            btnText.textContent = 'Instalando...';
            installBtn.disabled = true;

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                btnText.textContent = '✅ Instalado!';
                setTimeout(() => installBtn.style.display = 'none', 2000);
            } else {
                btnText.textContent = 'Instalar App';
                installBtn.disabled = false;
            }

            deferredPrompt = null;
        } else {
            showInstallInstructions();
        }
    });

    // Mostrar instruções baseado no dispositivo
    window.showInstallInstructions = function() {
        let instructions = '';

        if (device.isIOS) {
            instructions = `
                <div style="background: #fef3c7; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <strong style="color: #92400e;">🍎 iPhone/iPad (Safari):</strong>
                </div>
                <ol style="padding-left: 24px; margin-bottom: 16px;">
                    <li style="margin-bottom: 12px;">Toque no botão <strong>Compartilhar</strong> □↑ (na barra inferior do Safari)</li>
                    <li style="margin-bottom: 12px;">Role para baixo e toque em <strong>"Adicionar à Tela de Início"</strong></li>
                    <li style="margin-bottom: 12px;">Toque em <strong>"Adicionar"</strong></li>
                    <li>Pronto! O ícone do Dualis estará na sua tela inicial ✅</li>
                </ol>
                <div style="background: #e0e7ff; padding: 12px; border-radius: 8px; font-size: 13px; color: #1e40af;">
                    💡 <strong>Dica:</strong> O Safari não permite instalação automática. Você precisa fazer manualmente.
                </div>
            `;
        } else if (device.isAndroid && !device.isChrome && !device.isEdge) {
            instructions = `
                <div style="background: #fef3c7; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <strong style="color: #92400e;">🤖 Android:</strong>
                </div>
                <ol style="padding-left: 24px; margin-bottom: 16px;">
                    <li style="margin-bottom: 12px;">Toque nos <strong>três pontinhos</strong> (⋮) no canto superior</li>
                    <li style="margin-bottom: 12px;">Selecione <strong>"Instalar app"</strong> ou <strong>"Adicionar à tela inicial"</strong></li>
                    <li style="margin-bottom: 12px;">Confirme tocando em <strong>"Instalar"</strong></li>
                    <li>O app estará disponível na sua tela inicial ✅</li>
                </ol>
            `;
        } else if (device.isFirefox && !device.isMobile) {
            instructions = `
                <div style="background: #fecaca; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <strong style="color: #991b1b;">🦊 Firefox Desktop</strong>
                </div>
                <p style="margin-bottom: 16px;">
                    O Firefox Desktop tem suporte limitado a PWAs. 
                    Recomendamos usar <strong>Chrome</strong> ou <strong>Edge</strong> para instalar o app.
                </p>
                <p style="color: #6b7280; font-size: 14px;">
                    Você pode adicionar aos favoritos com <strong>Ctrl+D</strong> (Windows) ou <strong>Cmd+D</strong> (Mac)
                </p>
            `;
        } else {
            instructions = `
                <div style="background: #e0e7ff; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                    <strong style="color: #3730a3;">💻 Instalação via Menu:</strong>
                </div>
                <ol style="padding-left: 24px; margin-bottom: 16px;">
                    <li style="margin-bottom: 12px;">Clique no <strong>menu do navegador</strong> (⋮ ou ⋯)</li>
                    <li style="margin-bottom: 12px;">Procure por <strong>"Instalar Dualis"</strong> ou <strong>"Instalar aplicativo"</strong></li>
                    <li style="margin-bottom: 12px;">Clique em <strong>"Instalar"</strong></li>
                    <li>O app será adicionado ao seu computador ✅</li>
                </ol>
                <p style="color: #6b7280; font-size: 14px;">
                    Ou procure pelo ícone ⊕ na barra de endereço
                </p>
            `;
        }

        modalContent.innerHTML = instructions;
        installModal.style.display = 'flex';
        closeBanner();
    };

    window.closeModal = function() {
        installModal.style.display = 'none';
    };

    window.closeBanner = function() {
        installBanner.style.display = 'none';
        localStorage.setItem('pwa-banner-closed', Date.now());
    };

    // Mostrar banner ou botão baseado no dispositivo
    function showInstallUI() {
        // Para iOS: SEMPRE mostrar banner
        if (device.isIOS) {
            console.log('PWA: iOS detectado, mostrando banner');
            installBanner.style.display = 'flex';
            return;
        }

        const bannerClosed = localStorage.getItem('pwa-banner-closed');
        const daysSinceClosed = bannerClosed ? (Date.now() - parseInt(bannerClosed)) / (1000 * 60 * 60 * 24) : 999;

        if (daysSinceClosed < 7) {
            if (!deferredPrompt && (device.isChrome || device.isEdge)) {
                installBtn.style.display = 'flex';
            }
            return;
        }

        if (device.isAndroid && !device.isChrome && !device.isEdge) {
            setTimeout(() => {
                installBanner.style.display = 'flex';
            }, 2000);
        } else if (device.isFirefox && !device.isMobile) {
            setTimeout(() => {
                installBtn.style.display = 'flex';
            }, 3000);
        } else if (!deferredPrompt) {
            setTimeout(() => {
                installBtn.style.display = 'flex';
            }, 3000);
        }
    }

    // Detectar quando PWA foi instalado
    window.addEventListener('appinstalled', () => {
        console.log('PWA: Instalado com sucesso');
        installBtn.style.display = 'none';
        installBanner.style.display = 'none';
    });

    // Fechar modal clicando fora
    installModal.addEventListener('click', (e) => {
        if (e.target === installModal) {
            closeModal();
        }
    });

    // Inicializar
    console.log('PWA: Iniciando UI');
    showInstallUI();

})();
</script>

</body>
</html>