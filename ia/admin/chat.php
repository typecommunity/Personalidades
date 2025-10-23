<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
$isAdmin = isAdmin();

if (!$userId) {
    redirect('login.php');
    exit;
}

// Buscar informações do usuário
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT name, email, avatar_url, 'neutral' as gender FROM admins WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT name, email, avatar_url, gender FROM users WHERE id = ?");
}
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('login.php');
    exit;
}

if (!empty($user['avatar_url'])) {
    $userAvatar = htmlspecialchars($user['avatar_url']);
} else {
    $userAvatar = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=8b5cf6&color=fff&bold=true&size=40";
}

// Buscar personalidades ativas
$stmt = $pdo->query("
    SELECT id, name, description, avatar_color, avatar_image, 
           interaction_style, expertise_topics, personality_traits, response_frequency
    FROM personalities 
    WHERE is_active = 1 
    ORDER BY sort_order ASC
");
$personalities = $stmt->fetchAll();

// Buscar conversas
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.personality_id,
        c.title,
        c.updated_at,
        c.conversation_type,
        c.group_name,
        c.group_description,
        p.name as personality_name,
        p.avatar_color,
        p.avatar_image,
        gc.avatar_url as group_avatar,
        (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT GROUP_CONCAT(p2.name SEPARATOR ', ') 
         FROM group_members gm 
         JOIN personalities p2 ON gm.personality_id = p2.id 
         WHERE gm.group_id = gc.id AND gm.is_active = 1) as group_members
    FROM conversations c
    LEFT JOIN personalities p ON c.personality_id = p.id
    LEFT JOIN group_conversations gc ON c.id = gc.conversation_id
    WHERE c.user_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$userId]);
$conversations = $stmt->fetchAll();

function getPersonalityAvatar($name, $color, $image, $size = 49) {
    if (!empty($image)) {
        return htmlspecialchars($image);
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=" . urlencode(ltrim($color, '#')) . "&color=fff&bold=true&size=" . $size;
}

function getGroupAvatar($groupName, $avatarUrl = null, $size = 49) {
    if (!empty($avatarUrl)) {
        return htmlspecialchars($avatarUrl);
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($groupName) . "&background=059669&color=fff&bold=true&size=" . $size;
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Agora';
            }
            return $diff->i . ' min';
        }
        return $ago->format('H:i');
    } elseif ($diff->d == 1) {
        return 'Ontem';
    } elseif ($diff->d < 7) {
        return $diff->d . ' dias';
    } else {
        return $ago->format('d/m');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#111b21">
    <title>Dualis - Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dark-theme">
    
    <div class="h-screen flex overflow-hidden">
        
        <!-- SIDEBAR -->
        <div class="w-full md:w-[420px] bg-primary flex flex-col" id="sidebar">
            
            <!-- Header -->
            <div class="bg-secondary px-4 py-2.5 flex items-center justify-between mobile-sidebar-header">
                <div class="flex items-center gap-3">
                    <img 
                        src="<?= $userAvatar ?>" 
                        class="user-avatar-header" 
                        alt="<?= htmlspecialchars($user['name']) ?>"
                        title="<?= htmlspecialchars($user['name']) ?>"
                    >
                    <?php if ($isAdmin): ?>
                        <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="icon-color hover:text-primary p-2" onclick="showNewChatModal()" title="Nova conversa">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                    </button>
                    
                    <button class="icon-color hover:text-primary p-2" onclick="showCreateGroupModal()" title="Criar grupo">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3 11h-2v2h-2v-2H9v-2h2V9h2v2h2v2z"/>
                            <path d="M17 17v-3h2v3c0 1.1-.9 2-2 2H7c-1.1 0-2-.9-2-2v-3h2v3h10z"/>
                        </svg>
                    </button>
                    
                    <button class="icon-color hover:text-primary p-2" onclick="window.location.href='profile.php'" title="Editar Perfil">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                    
                    <button class="icon-color hover:text-primary p-2" onclick="toggleTheme()" title="Trocar tema">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 18c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6zm0-10c-2.2 0-4 1.8-4 4s1.8 4 4 4 4-1.8 4-4-1.8-4-4-4zM12 3c.5 0 1-.4 1-1V1c0-.5-.5-1-1-1s-1 .5-1 1v1c0 .6.5 1 1 1zm0 18c-.5 0-1 .4-1 1v1c0 .5.5 1 1 1s1-.5 1-1v-1c0-.6-.5-1-1-1zm9-10c.5 0 1-.4 1-1s-.5-1-1-1h-1c-.5 0-1 .4-1 1s.5 1 1 1h1zM3 13c.5 0 1-.4 1-1s-.5-1-1-1H2c-.5 0-1 .4-1 1s.5 1 1 1h1z"/>
                        </svg>
                    </button>
                    
                    <?php if ($isAdmin): ?>
                        <button class="icon-color hover:text-primary p-2" onclick="window.location.href='personalities.php'" title="Gerenciar">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12A3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5a3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97c0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65A.506.506 0 0 0 14 2h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.08-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1c0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66Z"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                    
                    <button class="icon-color hover:text-primary p-2" onclick="if(confirm('Deseja sair?')) window.location.href='logout.php'" title="Sair">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9Z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Busca -->
            <div class="bg-primary px-3 pt-2 pb-3 border-b border-custom">
                <div class="bg-secondary rounded-lg px-3 py-1.5 flex items-center space-x-3">
                    <svg class="w-5 h-5 text-secondary" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5A6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5S14 7.01 14 9.5S11.99 14 9.5 14z"/>
                    </svg>
                    <input 
                        type="text" 
                        placeholder="Pesquisar" 
                        class="flex-1 bg-transparent outline-none text-[14px] text-primary placeholder-secondary" 
                        id="searchInput" 
                        onkeyup="searchConversations()"
                    >
                </div>
            </div>
            
            <!-- Lista de Conversas -->
            <div class="flex-1 overflow-y-auto" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div class="p-4 text-center text-secondary">
                        <p class="mb-4">Nenhuma conversa ainda</p>
                        <button onclick="showNewChatModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Iniciar conversa
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <?php 
                            $isGroup = $conv['conversation_type'] === 'group';
                            if ($isGroup) {
                                $convAvatar = getGroupAvatar($conv['group_name'], $conv['group_avatar']);
                                $convName = htmlspecialchars($conv['group_name']);
                            } else {
                                $convAvatar = getPersonalityAvatar($conv['personality_name'], $conv['avatar_color'], $conv['avatar_image']);
                                $convName = htmlspecialchars($conv['personality_name']);
                            }
                        ?>
                        <div class="hover-chat px-4 py-3 flex items-center space-x-3 cursor-pointer conversation-item" 
                             data-conversation-id="<?= $conv['id'] ?>" 
                             onclick="openConversation(<?= $conv['id'] ?>, <?= $isGroup ? 'true' : 'false' ?>)">
                            <img src="<?= $convAvatar ?>" class="w-12 h-12 rounded-full personality-avatar" alt="<?= $convName ?>">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-primary font-medium text-[15px] truncate"><?= $convName ?></h3>
                                    <?php if ($isGroup): ?>
                                        <span class="group-badge">GRUPO</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-secondary text-[14px] truncate">
                                    <?php if (!empty($conv['last_message'])): ?>
                                        <?= htmlspecialchars(substr($conv['last_message'], 0, 50)) ?>
                                    <?php elseif ($isGroup && !empty($conv['group_members'])): ?>
                                        <?= htmlspecialchars($conv['group_members']) ?>
                                    <?php else: ?>
                                        Nova conversa
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="text-xs text-secondary">
                                <?= timeAgo($conv['last_message_time'] ?? $conv['updated_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ÁREA DE CHAT -->
        <div class="flex-1 bg-chat flex items-center justify-center hidden md:flex" id="emptyState">
            <div class="text-center">
                <div class="mb-4">
                    <svg class="w-24 h-24 mx-auto text-secondary opacity-50" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-light text-primary mb-2">Dualis Chat</h2>
                <p class="text-secondary">Selecione uma conversa para começar</p>
            </div>
        </div>
        
        <div class="flex-1 flex-col hidden" id="chatContainer">
            <div id="chatArea" class="flex flex-col h-full">
                
                <!-- Header do Chat -->
                <div class="bg-secondary px-4 py-2.5 flex items-center justify-between mobile-header">
                    <div class="flex items-center space-x-3">
                        <button class="md:hidden icon-color p-2" onclick="closeChat()">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                        </button>
                        <img src="" class="w-10 h-10 rounded-full personality-avatar" alt="" id="chatAvatar">
                        <div>
                            <h3 class="text-primary font-medium text-[15px]" id="chatName"></h3>
                            <p class="text-[13px] text-secondary" id="chatStatus">online</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button class="icon-color hover:text-primary p-2" onclick="deleteConversation()" title="Excluir conversa">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Lista de membros do grupo -->
                <div class="bg-primary border-b border-custom px-4 py-2 hidden" id="groupMembersBar">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-secondary">Membros:</span>
                        <div class="flex gap-2 flex-wrap" id="groupMembersList"></div>
                    </div>
                </div>
                
                <!-- Mensagens -->
                <div class="flex-1 overflow-y-auto whatsapp-bg px-6 md:px-16 py-5" id="messagesContainer"></div>
                
                <!-- Input de Mensagem -->
                <div class="bg-secondary px-4 py-2 flex items-center space-x-3">
                    <div class="flex-1 bg-primary rounded-lg px-3 py-2">
                        <input 
                            type="text" 
                            id="messageInput" 
                            placeholder="Digite uma mensagem" 
                            class="w-full bg-transparent outline-none text-[15px] text-primary placeholder-secondary" 
                            onkeypress="handleKeyPress(event)"
                        >
                    </div>
                    <button class="text-green hover:text-green-600 p-2" onclick="sendMessage()" title="Enviar">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- MODAL NOVA CONVERSA -->
    <div id="newChatModal" class="modal-overlay" onclick="closeNewChatModal(event)">
        <div class="bg-primary rounded-2xl p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-primary mb-4">Nova Conversa</h3>
            <p class="text-secondary text-sm mb-6">Escolha uma personalidade para conversar:</p>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php foreach ($personalities as $p): ?>
                    <?php 
                        $pAvatar = getPersonalityAvatar($p['name'], $p['avatar_color'], $p['avatar_image'], 48);
                    ?>
                    <div class="hover-chat cursor-pointer rounded-xl p-4 border border-custom" onclick="createNewConversation(<?= $p['id'] ?>)">
                        <div class="flex items-center space-x-3">
                            <img 
                                src="<?= $pAvatar ?>" 
                                class="w-12 h-12 rounded-full personality-avatar" 
                                alt="<?= htmlspecialchars($p['name']) ?>"
                            >
                            <div class="flex-1">
                                <h4 class="font-medium text-primary"><?= htmlspecialchars($p['name']) ?></h4>
                                <p class="text-xs text-secondary"><?= htmlspecialchars($p['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button onclick="closeNewChatModal()" class="mt-6 w-full py-2 bg-secondary text-primary rounded-lg hover:bg-hover-bg transition">
                Cancelar
            </button>
        </div>
    </div>
    
    <!-- MODAL CRIAR GRUPO - ESTRUTURA MELHORADA -->
    <div id="createGroupModal" class="modal-overlay" onclick="closeCreateGroupModal(event)">
        <div class="bg-primary rounded-2xl max-w-lg w-full mx-4" onclick="event.stopPropagation()">
            
            <!-- Modal Header -->
            <div class="modal-header">
                <h3 class="text-xl font-bold text-primary">Criar Novo Grupo</h3>
            </div>
            
            <!-- Modal Body com Scroll -->
            <div class="modal-body">
                <!-- Upload de Foto -->
                <div class="photo-upload-area" onclick="document.getElementById('groupPhotoInput').click()">
                    <div id="groupPhotoPreview">
                        <svg class="w-8 h-8 photo-upload-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                </div>
                <input type="file" id="groupPhotoInput" accept="image/*" onchange="previewGroupPhoto(this)">
                <p class="text-center text-xs text-secondary mb-4">Clique para adicionar foto do grupo</p>
                
                <!-- Nome do Grupo -->
                <div class="mb-4">
                    <label class="text-sm text-secondary block mb-2">Nome do Grupo</label>
                    <input 
                        type="text" 
                        id="groupName" 
                        class="w-full bg-secondary rounded-lg px-4 py-2 text-primary outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Ex: Filósofos Reunidos"
                    >
                </div>
                
                <!-- Descrição -->
                <div class="mb-4">
                    <label class="text-sm text-secondary block mb-2">Descrição (opcional)</label>
                    <textarea 
                        id="groupDescription" 
                        class="w-full bg-secondary rounded-lg px-4 py-2 text-primary outline-none focus:ring-2 focus:ring-green-500 resize-none"
                        rows="2"
                        placeholder="Descreva o propósito do grupo..."
                    ></textarea>
                </div>
                
                <!-- Seleção de Personalidades -->
                <div class="mb-4">
                    <label class="text-sm text-secondary block mb-2">
                        Selecione as Personalidades (mín. 2)
                        <span class="text-green ml-2" id="selectedCount">0 selecionadas</span>
                    </label>
                    <div class="space-y-2 max-h-48 overflow-y-auto bg-secondary rounded-lg p-3">
                        <?php foreach ($personalities as $p): ?>
                            <?php 
                                $pAvatar = getPersonalityAvatar($p['name'], $p['avatar_color'], $p['avatar_image'], 40);
                            ?>
                            <div class="personality-selector" data-personality-id="<?= $p['id'] ?>" onclick="togglePersonalitySelection(this)">
                                <div class="personality-checkbox"></div>
                                <img 
                                    src="<?= $pAvatar ?>" 
                                    class="w-10 h-10 rounded-full personality-avatar" 
                                    alt="<?= htmlspecialchars($p['name']) ?>"
                                >
                                <div class="flex-1">
                                    <h4 class="font-medium text-primary text-sm"><?= htmlspecialchars($p['name']) ?></h4>
                                    <p class="text-xs text-secondary line-clamp-1"><?= htmlspecialchars($p['description']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer com Botões Fixos -->
            <div class="modal-footer">
                <div class="flex gap-3">
                    <button onclick="closeCreateGroupModal()" class="flex-1 py-2 bg-secondary text-primary rounded-lg hover:bg-hover-bg transition">
                        Cancelar
                    </button>
                    <button onclick="createGroup()" class="flex-1 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Criar Grupo
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Passar dados do PHP para JavaScript
        window.isAdminUser = <?= $isAdmin ? 'true' : 'false' ?>;
        window.currentUserId = <?= $userId ?>;
        window.userAvatarUrl = '<?= $userAvatar ?>';
        window.userGenderValue = '<?= $user['gender'] ?? 'neutral' ?>';
        window.userNameValue = '<?= addslashes($user['name']) ?>';
    </script>
    <script src="app.js"></script>
    
</body>
</html>