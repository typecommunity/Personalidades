<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Verificar se está logado
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Pegar ID do usuário
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
$isAdmin = isAdmin();

if (!$userId) {
    redirect('login.php');
    exit;
}

// Buscar informações do usuário COM AVATAR
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT name, email, phone, avatar_url FROM admins WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT name, email, phone, subscription_status, avatar_url FROM users WHERE id = ?");
}
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('login.php');
    exit;
}

// ✅ USAR FOTO DO BANCO OU GERAR FALLBACK
if (!empty($user['avatar_url'])) {
    $userAvatar = htmlspecialchars($user['avatar_url']);
} else {
    $userAvatar = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=8b5cf6&color=fff&bold=true&size=128";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Dualis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600&display=swap');
        
        * {
            font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .dark-theme {
            --bg-primary: #202c33;
            --bg-secondary: #111b21;
            --text-primary: #e9edef;
            --text-secondary: #8696a0;
            --border-color: #2a3942;
            --hover-bg: #2a3942;
        }
        
        body {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .bg-primary { background: var(--bg-primary); }
        .bg-secondary { background: var(--bg-secondary); }
        .text-primary { color: var(--text-primary); }
        .text-secondary { color: var(--text-secondary); }
        .border-custom { border-color: var(--border-color); }
        
        .profile-avatar {
            object-fit: cover;
            object-position: center;
        }
    </style>
</head>
<body class="dark-theme min-h-screen">
    
    <div class="max-w-4xl mx-auto p-4 md:p-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <button onclick="window.location.href='chat.php'" class="text-secondary hover:text-primary transition">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                </button>
                <h1 class="text-2xl font-bold text-primary">Meu Perfil</h1>
            </div>
            <?php if ($isAdmin): ?>
                <span class="px-4 py-1.5 bg-green-600 text-white rounded-full text-sm font-semibold">Admin</span>
            <?php endif; ?>
        </div>
        
        <!-- Avatar -->
        <div class="bg-primary rounded-2xl p-8 mb-6 text-center">
            <div class="relative inline-block">
                <img 
                    src="<?= $userAvatar ?>" 
                    class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-secondary profile-avatar" 
                    alt="Avatar" 
                    id="avatarPreview"
                >
                
                <!-- Botão de Upload -->
                <label for="avatarUpload" class="absolute bottom-4 right-0 w-10 h-10 bg-green-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-green-700 transition shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </label>
                <input type="file" id="avatarUpload" accept="image/*" class="hidden" onchange="previewAvatar(this)">
            </div>
            
            <h2 class="text-xl font-bold text-primary"><?= htmlspecialchars($user['name']) ?></h2>
            <p class="text-secondary text-sm"><?= htmlspecialchars($user['email']) ?></p>
            
            <div id="uploadMessage" class="hidden mt-3 text-sm"></div>
        </div>
        
        <!-- Formulário de Edição -->
        <form id="profileForm" class="bg-primary rounded-2xl p-6 space-y-6">
            
            <div>
                <label class="block text-sm font-semibold text-primary mb-2">Nome Completo *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name"
                    value="<?= htmlspecialchars($user['name']) ?>" 
                    class="w-full px-4 py-3 rounded-lg border-2 border-custom bg-secondary text-primary focus:border-green-500 focus:outline-none transition"
                    required
                >
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-primary mb-2">Email *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email"
                    value="<?= htmlspecialchars($user['email']) ?>" 
                    class="w-full px-4 py-3 rounded-lg border-2 border-custom bg-secondary text-primary focus:border-green-500 focus:outline-none transition"
                    required
                >
            </div>
            
            <?php if (!$isAdmin): ?>
            <div>
                <label class="block text-sm font-semibold text-primary mb-2">Telefone (opcional)</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone"
                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                    class="w-full px-4 py-3 rounded-lg border-2 border-custom bg-secondary text-primary focus:border-green-500 focus:outline-none transition"
                    placeholder="(00) 00000-0000"
                >
            </div>
            <?php endif; ?>
            
            <hr class="border-custom">
            
            <div>
                <label class="block text-sm font-semibold text-primary mb-2">Nova Senha (deixe em branco para não alterar)</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="w-full px-4 py-3 rounded-lg border-2 border-custom bg-secondary text-primary focus:border-green-500 focus:outline-none transition"
                    placeholder="••••••••"
                    minlength="6"
                >
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-primary mb-2">Confirmar Nova Senha</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm"
                    class="w-full px-4 py-3 rounded-lg border-2 border-custom bg-secondary text-primary focus:border-green-500 focus:outline-none transition"
                    placeholder="••••••••"
                    minlength="6"
                >
            </div>
            
            <div id="message" class="hidden p-4 rounded-lg"></div>
            
            <div class="flex gap-4 pt-4">
                <button 
                    type="button" 
                    onclick="window.location.href='chat.php'" 
                    class="flex-1 px-6 py-3 bg-secondary text-primary rounded-lg font-semibold hover:bg-hover-bg transition"
                >
                    Cancelar
                </button>
                <button 
                    type="submit" 
                    class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition"
                    id="submitBtn"
                >
                    Salvar Alterações
                </button>
            </div>
            
        </form>
        
        <?php if (!$isAdmin): ?>
        <!-- Informações da Conta -->
        <div class="bg-primary rounded-2xl p-6 mt-6">
            <h3 class="text-lg font-bold text-primary mb-4">Informações da Conta</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-secondary">Status da Assinatura:</span>
                    <span class="font-semibold text-primary capitalize"><?= htmlspecialchars($user['subscription_status']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-secondary">Tipo de Conta:</span>
                    <span class="font-semibold text-primary">Usuário</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <script>
        // ===== PREVIEW DO AVATAR =====
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validar tamanho (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showUploadMessage('Imagem muito grande! Máximo 5MB', 'error');
                    input.value = '';
                    return;
                }
                
                // Validar tipo
                if (!file.type.startsWith('image/')) {
                    showUploadMessage('Apenas imagens são permitidas!', 'error');
                    input.value = '';
                    return;
                }
                
                // Preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                    showUploadMessage('Foto carregada! Clique em "Salvar Alterações" para confirmar.', 'success');
                };
                reader.readAsDataURL(file);
            }
        }
        
        function showUploadMessage(text, type) {
            const messageDiv = document.getElementById('uploadMessage');
            messageDiv.textContent = text;
            messageDiv.className = `mt-3 text-sm ${type === 'success' ? 'text-green-400' : 'text-red-400'}`;
            messageDiv.classList.remove('hidden');
            
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }
        
        // ===== SALVAR PERFIL =====
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            
            // Desabilitar botão
            submitBtn.disabled = true;
            submitBtn.textContent = 'Salvando...';
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone')?.value.trim() || '';
            const password = document.getElementById('password').value;
            const password_confirm = document.getElementById('password_confirm').value;
            const avatarFile = document.getElementById('avatarUpload').files[0];
            
            // Validações no cliente
            if (!name) {
                showMessage('Nome é obrigatório!', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
            
            if (!email) {
                showMessage('Email é obrigatório!', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
            
            // Validar senhas
            if (password && password !== password_confirm) {
                showMessage('As senhas não coincidem!', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
            
            if (password && password.length < 6) {
                showMessage('A senha deve ter no mínimo 6 caracteres!', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
            
            try {
                // Usar FormData para enviar arquivo
                const formData = new FormData();
                formData.append('name', name);
                formData.append('email', email);
                formData.append('phone', phone);
                if (password) formData.append('password', password);
                if (avatarFile) formData.append('avatar', avatarFile);
                
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('✅ Perfil atualizado com sucesso!', 'success');
                    
                    // Atualizar avatar na página se foi enviado
                    if (data.avatar_url) {
                        document.getElementById('avatarPreview').src = data.avatar_url;
                    }
                    
                    // Limpar campos de senha
                    document.getElementById('password').value = '';
                    document.getElementById('password_confirm').value = '';
                    
                    // Redirecionar após 2 segundos
                    setTimeout(() => {
                        window.location.href = 'chat.php';
                    }, 2000);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Erro:', error);
                showMessage('❌ Erro ao atualizar perfil', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = `p-4 rounded-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
            messageDiv.classList.remove('hidden');
            
            // Scroll até a mensagem
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
    
</body>
</html>