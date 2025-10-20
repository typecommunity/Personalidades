/**
 * PIPO - Admin JavaScript
 */

// =============================================
// PERSONALIDADES
// =============================================

/**
 * Abrir modal para criar nova personalidade
 */
function openPersonalityModal() {
    document.getElementById('modalTitle').textContent = 'Nova Personalidade';
    document.getElementById('personalityForm').reset();
    document.getElementById('personality_id').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('avatar_color').value = '#8b5cf6';
    document.getElementById('colorPreview').textContent = '#8b5cf6';
    document.getElementById('personalityModal').classList.add('active');
}

/**
 * Editar personalidade existente
 */
function editPersonality(personality) {
    document.getElementById('modalTitle').textContent = 'Editar Personalidade';
    document.getElementById('personality_id').value = personality.id;
    document.getElementById('name').value = personality.name;
    document.getElementById('description').value = personality.description || '';
    document.getElementById('avatar_color').value = personality.avatar_color;
    document.getElementById('colorPreview').textContent = personality.avatar_color;
    document.getElementById('sort_order').value = personality.sort_order;
    document.getElementById('system_prompt').value = personality.system_prompt;
    document.getElementById('is_active').checked = personality.is_active == 1;
    document.getElementById('personalityModal').classList.add('active');
}

/**
 * Fechar modal
 */
function closePersonalityModal() {
    document.getElementById('personalityModal').classList.remove('active');
}

/**
 * Salvar personalidade (criar ou editar)
 */
async function savePersonality(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Converter checkbox para 1 ou 0
    formData.set('is_active', form.is_active.checked ? '1' : '0');
    
    const id = formData.get('id');
    const url = id ? 'api/personalities/update.php' : 'api/personalities/create.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Personalidade salva com sucesso!');
            closePersonalityModal();
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao salvar personalidade: ' + error.message);
    }
}

/**
 * Excluir personalidade
 */
async function deletePersonality(id) {
    if (!confirm('Tem certeza que deseja excluir esta personalidade? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        const response = await fetch('api/personalities/delete.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Personalidade excluída com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao excluir personalidade: ' + error.message);
    }
}

/**
 * Ativar/Desativar personalidade
 */
async function toggleStatus(id, newStatus) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_active', newStatus ? '1' : '0');
        
        const response = await fetch('api/personalities/update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao atualizar status: ' + error.message);
    }
}

/**
 * Atualizar ordem de exibição
 */
async function updateOrder(id, newOrder) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('sort_order', newOrder);
        
        const response = await fetch('api/personalities/update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            alert('Erro ao atualizar ordem: ' + result.message);
            location.reload();
        }
    } catch (error) {
        alert('Erro ao atualizar ordem: ' + error.message);
    }
}

// =============================================
// USUÁRIOS
// =============================================

/**
 * Ver detalhes do usuário
 */
function viewUser(userId) {
    window.location.href = `user-details.php?id=${userId}`;
}

/**
 * Bloquear/Desbloquear usuário
 */
async function toggleUserStatus(userId, newStatus) {
    if (!confirm(`Tem certeza que deseja ${newStatus === 'blocked' ? 'bloquear' : 'desbloquear'} este usuário?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('status', newStatus);
        
        const response = await fetch('api/users/update-status.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        alert('Erro ao atualizar usuário: ' + error.message);
    }
}

// =============================================
// MODAL GENÉRICO
// =============================================

/**
 * Fechar modal ao clicar fora
 */
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
});

/**
 * Fechar modal com ESC
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

// =============================================
//