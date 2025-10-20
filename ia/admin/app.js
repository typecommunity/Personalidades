// Variáveis globais
let currentConversationId = null;
let currentIsGroup = false;
let currentGroupMembers = [];
let currentGroupInfo = null;
let messagePolling = null;
let groupPhotoFile = null;

// Dados do PHP
const isAdmin = window.isAdminUser || false;
const userId = window.currentUserId || 0;
const userAvatar = window.userAvatarUrl || '';
const userGender = window.userGenderValue || 'neutral';
const userName = window.userNameValue || '';

// ===== TEMA =====
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.contains('dark-theme');
    
    if (isDark) {
        body.classList.remove('dark-theme');
        body.classList.add('light-theme');
        localStorage.setItem('theme', 'light');
    } else {
        body.classList.remove('light-theme');
        body.classList.add('dark-theme');
        localStorage.setItem('theme', 'dark');
    }
}

// ===== PREVIEW FOTO DO GRUPO =====
function previewGroupPhoto(input) {
    if (input.files && input.files[0]) {
        groupPhotoFile = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('groupPhotoPreview').innerHTML = 
                `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// ===== MODAIS =====
function showNewChatModal() {
    document.getElementById('newChatModal').classList.add('active');
}

function closeNewChatModal(event) {
    if (!event || event.target.id === 'newChatModal') {
        document.getElementById('newChatModal').classList.remove('active');
    }
}

function showCreateGroupModal() {
    document.getElementById('createGroupModal').classList.add('active');
}

function closeCreateGroupModal(event) {
    if (!event || event.target.id === 'createGroupModal') {
        document.getElementById('createGroupModal').classList.remove('active');
        document.querySelectorAll('.personality-selector.selected').forEach(el => {
            el.classList.remove('selected');
        });
        document.getElementById('selectedCount').textContent = '0 selecionadas';
        document.getElementById('groupPhotoPreview').innerHTML = `
            <svg class="w-8 h-8 photo-upload-icon" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>`;
        groupPhotoFile = null;
        document.getElementById('groupName').value = '';
        document.getElementById('groupDescription').value = '';
    }
}

function togglePersonalitySelection(element) {
    element.classList.toggle('selected');
    const count = document.querySelectorAll('.personality-selector.selected').length;
    document.getElementById('selectedCount').textContent = `${count} selecionada${count !== 1 ? 's' : ''}`;
}

// ===== CRIAR GRUPO =====
async function createGroup() {
    const groupName = document.getElementById('groupName').value.trim();
    const groupDescription = document.getElementById('groupDescription').value.trim();
    const selectedPersonalities = [];
    
    document.querySelectorAll('.personality-selector.selected').forEach(el => {
        selectedPersonalities.push(el.dataset.personalityId);
    });
    
    if (!groupName) {
        alert('Por favor, insira um nome para o grupo');
        return;
    }
    
    if (selectedPersonalities.length < 2) {
        alert('Selecione pelo menos 2 personalidades para o grupo');
        return;
    }
    
    try {
        let avatarUrl = null;
        if (groupPhotoFile) {
            const formData = new FormData();
            formData.append('photo', groupPhotoFile);
            formData.append('type', 'group');
            
            const uploadResponse = await fetch('api/upload_photo.php', {
                method: 'POST',
                body: formData
            });
            
            const uploadData = await uploadResponse.json();
            if (uploadData.success) {
                avatarUrl = uploadData.url;
            }
        }
        
        const response = await fetch('api/create_group.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: groupName,
                description: groupDescription,
                personalities: selectedPersonalities,
                user_id: userId,
                avatar_url: avatarUrl
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeCreateGroupModal();
            location.reload();
        } else {
            alert('Erro ao criar grupo: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao criar grupo');
    }
}

// ===== CRIAR NOVA CONVERSA =====
async function createNewConversation(personalityId) {
    try {
        const response = await fetch('api/create_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                personality_id: personalityId,
                user_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeNewChatModal();
            location.reload();
        } else {
            alert('Erro ao criar conversa: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao criar conversa');
    }
}

// ===== ABRIR CONVERSA =====
async function openConversation(conversationId, isGroup = false) {
    if (messagePolling) {
        clearInterval(messagePolling);
        messagePolling = null;
    }
    
    currentConversationId = conversationId;
    currentIsGroup = isGroup;
    currentGroupMembers = [];
    currentGroupInfo = null;
    
    const emptyState = document.getElementById('emptyState');
    const chatContainer = document.getElementById('chatContainer');
    const sidebar = document.getElementById('sidebar');
    const groupMembersBar = document.getElementById('groupMembersBar');
    const chatArea = document.getElementById('chatArea');
    const messagesContainer = document.getElementById('messagesContainer');
    
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('border-l-4', 'border-green-500');
    });
    const selectedItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('border-l-4', 'border-green-500');
    }
    
    if (window.innerWidth >= 768 && chatArea) {
        chatArea.style.display = 'flex';
        chatArea.classList.remove('hidden');
        chatArea.classList.add('flex');
    }
    
    if (emptyState) {
        emptyState.style.display = 'none';
        emptyState.classList.add('hidden');
    }
    
    if (chatContainer) {
        chatContainer.classList.remove('hidden');
        chatContainer.classList.add('flex');
        chatContainer.style.display = 'flex';
        chatContainer.style.flexDirection = 'column';
        chatContainer.style.height = '100%';
        chatContainer.style.width = '100%';
        chatContainer.style.visibility = 'visible';
        chatContainer.style.opacity = '1';
    }
    
    if (window.innerWidth < 768 && sidebar) {
        sidebar.classList.add('hidden');
        sidebar.style.display = 'none';
    }
    
    if (messagesContainer) {
        messagesContainer.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-secondary">Carregando...</p></div>';
    }
    
    if (isGroup) {
        if (groupMembersBar) {
            groupMembersBar.classList.remove('hidden');
            groupMembersBar.style.display = 'block';
        }
        await loadGroupMembers(conversationId);
    } else {
        if (groupMembersBar) {
            groupMembersBar.classList.add('hidden');
            groupMembersBar.style.display = 'none';
        }
    }
    
    await loadMessages(conversationId, false, isGroup);
    
    messagePolling = setInterval(() => {
        loadMessages(conversationId, true, isGroup);
    }, 3000);
    
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        setTimeout(() => messageInput.focus(), 200);
    }
}

// ===== CARREGAR MEMBROS DO GRUPO =====
async function loadGroupMembers(conversationId) {
    try {
        const response = await fetch(`api/get_group_members.php?conversation_id=${conversationId}`);
        
        if (!response.ok) return;
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) return;
        
        const data = await response.json();
        
        if (data.success && data.members) {
            currentGroupMembers = data.members;
            
            if (data.group_info) {
                currentGroupInfo = data.group_info;
            }
            
            const container = document.getElementById('groupMembersList');
            if (container) {
                container.innerHTML = '';
                
                data.members.forEach(member => {
                    const avatarUrl = member.avatar_image || 
                        `https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=${(member.avatar_color || '#8b5cf6').replace('#', '')}&color=fff&bold=true&size=24`;
                    
                    container.innerHTML += `
                        <div class="flex items-center gap-1 bg-secondary px-2 py-1 rounded-full">
                            <img src="${avatarUrl}" class="w-5 h-5 rounded-full" alt="${member.name}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(member.name)}&background=8b5cf6&color=fff&bold=true&size=24'">
                            <span class="text-xs text-primary">${member.name}</span>
                        </div>
                    `;
                });
                
                const chatStatus = document.getElementById('chatStatus');
                if (chatStatus) {
                    chatStatus.textContent = `${data.members.length} ${data.members.length === 1 ? 'personalidade' : 'personalidades'}`;
                }
            }
        }
    } catch (error) {
        // Silenciar erro para não bloquear abertura do chat
    }
}

// ===== CARREGAR MENSAGENS =====
async function loadMessages(conversationId, silent = false, isGroup = false) {
    try {
        const url = isGroup 
            ? `api/get_group_messages.php?conversation_id=${conversationId}`
            : `api/get_messages.php?conversation_id=${conversationId}`;
            
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.success) return;
        
        const chatNameEl = document.getElementById('chatName');
        const chatAvatarEl = document.getElementById('chatAvatar');
        const chatStatusEl = document.getElementById('chatStatus');
        
        if (isGroup && data.group_info) {
            currentGroupInfo = data.group_info;
            
            if (chatNameEl) chatNameEl.textContent = data.group_info.name;
            
            const groupAvatar = data.group_info.avatar_url || 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(data.group_info.name)}&background=059669&color=fff&bold=true&size=40`;
            if (chatAvatarEl) chatAvatarEl.src = groupAvatar;
            
            if (chatStatusEl && data.members_count) {
                chatStatusEl.textContent = `${data.members_count} ${data.members_count === 1 ? 'personalidade' : 'personalidades'}`;
            }
        } else if (!isGroup && data.personality) {
            if (chatNameEl) chatNameEl.textContent = data.personality.name;
            
            const avatarUrl = data.personality.avatar_image || 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(data.personality.name)}&background=${(data.personality.avatar_color || '#8b5cf6').replace('#', '')}&color=fff&bold=true&size=40`;
            if (chatAvatarEl) chatAvatarEl.src = avatarUrl;
            if (chatStatusEl) chatStatusEl.textContent = 'online';
            
            window.currentPersonalityAvatar = avatarUrl;
        }
        
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const shouldScroll = !silent || (container.scrollHeight - container.scrollTop === container.clientHeight);
        
        container.innerHTML = '';
        
        if (!data.messages || data.messages.length === 0) {
            container.innerHTML = `
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <p class="text-secondary">Nenhuma mensagem ainda. Comece a conversar!</p>
                    </div>
                </div>
            `;
            return;
        }
        
        let lastDate = null;
        data.messages.forEach((msg) => {
            const msgDate = new Date(msg.created_at).toLocaleDateString('pt-BR');
            
            if (msgDate !== lastDate) {
                container.innerHTML += `
                    <div class="flex justify-center my-3">
                        <div class="bg-primary shadow-sm px-3 py-1.5 rounded-md">
                            <span class="text-xs text-secondary">${msgDate === new Date().toLocaleDateString('pt-BR') ? 'HOJE' : msgDate}</span>
                        </div>
                    </div>
                `;
                lastDate = msgDate;
            }
            
            const isUser = msg.role === 'user';
            const isSystemMessage = !isUser && !msg.personality_id;
            const time = new Date(msg.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const favorited = msg.is_favorited ? 'favorited' : '';
            
            let avatarHtml = '';
            let senderName = '';
            
            if (isUser) {
                const safeUserAvatar = userAvatar || `https://ui-avatars.com/api/?name=User&background=8b5cf6&color=fff&bold=true&size=32`;
                avatarHtml = `<img src="${safeUserAvatar}" class="message-avatar" alt="Você" onerror="this.src='https://ui-avatars.com/api/?name=User&background=8b5cf6&color=fff&bold=true&size=32'">`;
            } else if (isSystemMessage && isGroup && currentGroupInfo) {
                // ✅ MENSAGEM DE SISTEMA EM GRUPO = USAR FOTO DO GRUPO
                const groupAvatar = currentGroupInfo.avatar_url || 
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(currentGroupInfo.name)}&background=059669&color=fff&bold=true&size=32`;
                avatarHtml = `<img src="${groupAvatar}" class="message-avatar personality-avatar" alt="${currentGroupInfo.name}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(currentGroupInfo.name)}&background=059669&color=fff&bold=true&size=32'">`;
                senderName = `<div class="text-xs text-green font-semibold mb-1">Sistema</div>`;
            } else if (isGroup && msg.personality_name) {
                const personalityAvatar = msg.personality_avatar || 
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(msg.personality_name)}&background=8b5cf6&color=fff&bold=true&size=32`;
                avatarHtml = `<img src="${personalityAvatar}" class="message-avatar personality-avatar" alt="${msg.personality_name}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(msg.personality_name)}&background=8b5cf6&color=fff&bold=true&size=32'">`;
                senderName = `<div class="text-xs text-green font-semibold mb-1">${msg.personality_name}</div>`;
            } else {
                const fallbackAvatar = window.currentPersonalityAvatar || `https://ui-avatars.com/api/?name=AI&background=8b5cf6&color=fff&bold=true&size=32`;
                avatarHtml = `<img src="${fallbackAvatar}" class="message-avatar personality-avatar" alt="Assistente" onerror="this.src='https://ui-avatars.com/api/?name=AI&background=8b5cf6&color=fff&bold=true&size=32'">`;
            }
            
            let content = escapeHtml(msg.content);
            if (msg.mentioned_personalities) {
                try {
                    const mentions = JSON.parse(msg.mentioned_personalities);
                    mentions.forEach(mention => {
                        const regex = new RegExp(`@${mention}`, 'gi');
                        content = content.replace(regex, `<span class="mention-tag">@${mention}</span>`);
                    });
                } catch (e) {}
            }
            
            container.innerHTML += `
                <div class="flex ${isUser ? 'justify-end' : 'justify-start'} mb-2">
                    ${!isUser ? `<div class="mr-2 flex items-end">${avatarHtml}</div>` : ''}
                    <div class="bg-message-${isUser ? 'sent' : 'received'} shadow-sm rounded-lg message-bubble message-tail-${isUser ? 'right' : 'left'} relative">
                        ${senderName}
                        <button class="favorite-btn ${favorited}" onclick="toggleFavorite(${msg.id}, this)" title="${favorited ? 'Remover favorito' : 'Adicionar aos favoritos'}">
                            <svg fill="${favorited ? '#f59e0b' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
                        <p class="text-[14.2px] text-primary leading-[19px] whitespace-pre-wrap">${content}</p>
                        <div class="flex items-center justify-end mt-1 space-x-1">
                            <span class="text-[11px] text-tertiary">${time}</span>
                            ${isUser ? '<svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512zm-4.1 0l-.478-.372a.365.365 0 0 0-.51.063L4.566 9.879a.32.32 0 0 1-.484.033L1.891 7.769a.366.366 0 0 0-.515.006l-.423.433a.364.364 0 0 0 .006.514l3.258 3.185c.143.14.361.125.484-.033l6.272-8.048a.365.365 0 0 0-.063-.51z"/></svg>' : ''}
                        </div>
                    </div>
                    ${isUser ? `<div class="ml-2 flex items-end">${avatarHtml}</div>` : ''}
                </div>
            `;
        });
        
        if (shouldScroll) {
            container.scrollTop = container.scrollHeight;
        }
        
    } catch (error) {
        // Silenciar erro
    }
}

// ===== ENVIAR MENSAGEM =====
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content || !currentConversationId) return;
    
    input.value = '';
    input.disabled = true;
    
    const container = document.getElementById('messagesContainer');
    const time = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    
    const safeUserAvatar = userAvatar || `https://ui-avatars.com/api/?name=User&background=8b5cf6&color=fff&bold=true&size=32`;
    const userAvatarHtml = `<img src="${safeUserAvatar}" class="message-avatar" alt="Você" onerror="this.src='https://ui-avatars.com/api/?name=User&background=8b5cf6&color=fff&bold=true&size=32'">`;
    
    container.innerHTML += `
        <div class="flex justify-end mb-2">
            <div class="bg-message-sent shadow-sm rounded-lg message-bubble message-tail-right relative">
                <p class="text-[14.2px] text-primary leading-[19px] whitespace-pre-wrap">${escapeHtml(content)}</p>
                <div class="flex items-center justify-end mt-1 space-x-1">
                    <span class="text-[11px] text-tertiary">${time}</span>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 16 15"><path d="M15.01 3.316l-.478-.372a.365.365 0 0 0-.51.063L8.666 9.879a.32.32 0 0 1-.484.033l-.358-.325a.319.319 0 0 0-.484.032l-.378.483a.418.418 0 0 0 .036.541l1.32 1.266c.143.14.361.125.484-.033l6.272-8.048a.366.366 0 0 0-.064-.512z"/></svg>
                </div>
            </div>
            <div class="ml-2 flex items-end">${userAvatarHtml}</div>
        </div>
    `;
    
    if (currentIsGroup) {
        container.innerHTML += `
            <div class="flex justify-start mb-2" id="typingIndicator">
                <div class="bg-message-received shadow-sm rounded-lg px-4 py-3 message-tail-left relative">
                    <div class="typing-indicator flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            </div>
        `;
    } else {
        const chatAvatar = document.getElementById('chatAvatar');
        const avatarSrc = (chatAvatar && chatAvatar.src) ? chatAvatar.src : (window.currentPersonalityAvatar || 'https://ui-avatars.com/api/?name=AI&background=8b5cf6&color=fff&bold=true&size=32');
        container.innerHTML += `
            <div class="flex justify-start mb-2" id="typingIndicator">
                <img src="${avatarSrc}" class="message-avatar personality-avatar mr-2" alt="Digitando" onerror="this.src='https://ui-avatars.com/api/?name=AI&background=8b5cf6&color=fff&bold=true&size=32'">
                <div class="bg-message-received shadow-sm rounded-lg px-4 py-3 message-tail-left relative">
                    <div class="typing-indicator flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            </div>
        `;
    }
    
    container.scrollTop = container.scrollHeight;
    
    try {
        const endpoint = currentIsGroup ? 'send_group_message.php' : 'send_message.php';
        const response = await fetch(`api/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                content: content,
                user_gender: userGender,
                user_name: userName
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const responseText = await response.text();
        const data = JSON.parse(responseText);
        
        document.getElementById('typingIndicator')?.remove();
        
        if (data.success) {
            setTimeout(async () => {
                await loadMessages(currentConversationId, false, currentIsGroup);
            }, 500);
        } else {
            alert('Erro ao enviar mensagem: ' + data.message);
            await loadMessages(currentConversationId, false, currentIsGroup);
        }
    } catch (error) {
        document.getElementById('typingIndicator')?.remove();
        alert('Erro ao enviar mensagem');
    } finally {
        input.disabled = false;
        input.focus();
    }
}

// ===== FAVORITAR MENSAGEM =====
async function toggleFavorite(messageId, button) {
    try {
        const isFavorited = button.classList.contains('favorited');
        
        const response = await fetch('api/favorite_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                message_id: messageId,
                action: isFavorited ? 'remove' : 'add'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.classList.toggle('favorited');
            const svg = button.querySelector('svg');
            if (button.classList.contains('favorited')) {
                svg.setAttribute('fill', '#f59e0b');
                button.title = 'Remover favorito';
            } else {
                svg.setAttribute('fill', 'none');
                button.title = 'Adicionar aos favoritos';
            }
        }
    } catch (error) {
        // Silenciar erro
    }
}

// ===== DELETAR CONVERSA =====
async function deleteConversation() {
    if (!currentConversationId) return;
    
    const message = currentIsGroup 
        ? 'Tem certeza que deseja excluir este grupo? Esta ação não pode ser desfeita.'
        : 'Tem certeza que deseja excluir esta conversa? Esta ação não pode ser desfeita.';
    
    if (!confirm(message)) return;
    
    try {
        const response = await fetch('api/delete_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: currentConversationId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao excluir: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao excluir');
    }
}

// ===== BUSCAR CONVERSAS =====
function searchConversations() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.conversation-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(search) ? 'block' : 'none';
    });
}

// ===== FECHAR CHAT =====
function closeChat() {
    const sidebar = document.getElementById('sidebar');
    const chatContainer = document.getElementById('chatContainer');
    const emptyState = document.getElementById('emptyState');
    const groupMembersBar = document.getElementById('groupMembersBar');
    
    if (messagePolling) {
        clearInterval(messagePolling);
        messagePolling = null;
    }
    
    currentConversationId = null;
    currentIsGroup = false;
    currentGroupMembers = [];
    currentGroupInfo = null;
    
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('border-l-4', 'border-green-500');
    });
    
    if (groupMembersBar) {
        groupMembersBar.classList.add('hidden');
        groupMembersBar.style.display = 'none';
    }
    
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    if (chatContainer) {
        chatContainer.classList.add('hidden');
        chatContainer.classList.remove('flex');
        chatContainer.style.display = 'none';
    }
    
    if (emptyState) {
        emptyState.classList.remove('hidden');
        emptyState.style.display = 'flex';
    }
    
    if (sidebar) {
        sidebar.classList.remove('hidden');
        sidebar.style.display = '';
        sidebar.scrollTop = 0;
    }
}

// ===== UTILITÁRIOS =====
function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== INICIALIZAÇÃO =====
window.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.body.className = savedTheme + '-theme';
    
    function setViewportHeight() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
    
    const messageInput = document.getElementById('messageInput');
    if (messageInput && window.innerWidth < 768) {
        messageInput.addEventListener('focus', () => {
            setTimeout(() => {
                const container = document.getElementById('messagesContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 300);
        });
    }
});

window.addEventListener('beforeunload', () => {
    if (messagePolling) {
        clearInterval(messagePolling);
    }
});

if (window.innerWidth < 768) {
    let lastHeight = window.innerHeight;
    
    window.addEventListener('resize', () => {
        const currentHeight = window.innerHeight;
        const chatContainer = document.getElementById('chatContainer');
        
        if (chatContainer && !chatContainer.classList.contains('hidden')) {
            if (currentHeight < lastHeight) {
                const messagesContainer = document.getElementById('messagesContainer');
                if (messagesContainer) {
                    setTimeout(() => {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }, 100);
                }
            }
        }
        
        lastHeight = currentHeight;
    });
}