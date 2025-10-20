<?php
/**
 * Registro de Novos Usuários
 */

require_once __DIR__ . '/admin/config.php';

// Se já está logado, redireciona
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

// Verificar se o registro está habilitado
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'allow_registration'");
    $allowRegistration = $stmt->fetchColumn();
    
    if ($allowRegistration === '0') {
        $error = "O registro de novos usuários está temporariamente desabilitado.";
    }
} catch (PDOException $e) {
    // Se não conseguir verificar, permite registro
}

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validações
    if (empty($name)) {
        $error = "O nome é obrigatório";
    } elseif (strlen($name) < 3) {
        $error = "O nome deve ter no mínimo 3 caracteres";
    } elseif (empty($email)) {
        $error = "O email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido";
    } elseif (empty($password)) {
        $error = "A senha é obrigatória";
    } elseif (strlen($password) < 6) {
        $error = "A senha deve ter no mínimo 6 caracteres";
    } elseif ($password !== $confirm_password) {
        $error = "As senhas não coincidem";
    } else {
        try {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Este email já está cadastrado";
            } else {
                // Criar hash da senha
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserir novo usuário
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        email, 
                        password_hash, 
                        name, 
                        phone, 
                        status, 
                        subscription_status,
                        created_at
                    ) VALUES (?, ?, ?, ?, 'active', 'free', NOW())
                ");
                
                $stmt->execute([
                    $email,
                    $passwordHash,
                    $name,
                    $phone ?: null
                ]);
                
                $userId = $pdo->lastInsertId();
                
                // FAZER LOGIN AUTOMÁTICO após registro
                session_regenerate_id(true);
                
                // Criar sessão no banco
                $sessionId = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + (defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 18000));
                
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (id, user_id, user_type, ip_address, user_agent, expires_at) 
                    VALUES (?, ?, 'user', ?, ?, ?)
                ");
                $stmt->execute([
                    $sessionId,
                    $userId,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $expiresAt
                ]);
                
                // Definir variáveis de sessão
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['session_id'] = $sessionId;
                
                // Redirecionar para o chat
                header('Location: /ia/admin/chat.php?welcome=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao criar conta. Tente novamente.";
            error_log("Erro registro: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Dualis</title>
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
        
        .register-container {
            width: 100%;
            max-width: 450px;
            z-index: 1;
            position: relative;
        }
        
        .register-box {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .register-header {
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
        
        .register-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .register-header p {
            color: #6b7280;
            font-size: 15px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
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
        
        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #111827;
            font-size: 14px;
        }
        
        .form-group label .optional {
            color: #9ca3af;
            font-weight: 400;
            font-size: 12px;
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
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .password-strength.show {
            display: block;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            border-radius: 2px;
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
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
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
        
        .register-footer {
            text-align: center;
            margin-top: 24px;
        }
        
        .register-footer a {
            color: #059669;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .register-footer a:hover {
            color: #047857;
            text-decoration: underline;
        }
        
        .copyright {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <div class="register-container">
        <div class="register-box">
            
            <!-- Logo -->
            <div class="register-header">
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
                <h1>Criar Conta</h1>
                <p>Comece sua jornada com a Dualis</p>
            </div>
            
            <!-- Mensagens -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form method="POST" action="" id="registerForm">
                
                <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        required 
                        autocomplete="name"
                        autofocus
                        minlength="3"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        placeholder="Seu nome completo"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="seu@email.com"
                    >
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        Telefone <span class="optional">(opcional)</span>
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        autocomplete="tel"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        placeholder="(00) 00000-0000"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        minlength="6"
                        placeholder="Mínimo 6 caracteres"
                    >
                    <div class="password-strength" id="passwordStrength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Digite a senha novamente"
                    >
                </div>
                
                <button type="submit" class="btn">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                    </svg>
                    Criar Conta
                </button>
                
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>OU</span>
            </div>
            
            <!-- Links -->
            <div class="register-footer">
                <span style="color: #6b7280; font-size: 14px;">Já tem uma conta?</span>
                <a href="/ia/admin/login.php">Fazer login</a>
            </div>
            
            <!-- Copyright -->
            <div class="copyright">
                <p>© <?= date('Y') ?> Dualis. Todos os direitos reservados - Versão não comercial</p>
            </div>
            
        </div>
    </div>
    
    <script>
        // Validação de força da senha
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthContainer = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
            
            strengthContainer.classList.add('show');
            strengthBar.style.width = strength + '%';
            
            if (strength < 40) {
                strengthBar.style.background = '#ef4444';
            } else if (strength < 70) {
                strengthBar.style.background = '#f59e0b';
            } else {
                strengthBar.style.background = '#10b981';
            }
        });
        
        // Validação de confirmação de senha
        const form = document.getElementById('registerForm');
        const confirmPassword = document.getElementById('confirm_password');
        
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                confirmPassword.focus();
            }
        });
        
        // Máscara de telefone
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            
            e.target.value = value;
        });
    </script>
    
</body>
</html>