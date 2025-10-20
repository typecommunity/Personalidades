// ===== SISTEMA DE DEBUG DUALIS =====
console.log('%c╔══════════════════════════════════════╗', 'color: #00ff00; font-weight: bold');
console.log('%c║   DUALIS DEBUG SYSTEM v1.0           ║', 'color: #00ff00; font-weight: bold');
console.log('%c╚══════════════════════════════════════╝', 'color: #00ff00; font-weight: bold');

// Teste 1: Verificar variáveis globais
console.log('%c[1] VERIFICANDO VARIÁVEIS GLOBAIS', 'color: #ffff00; font-weight: bold');
console.log('window.isAdminUser:', typeof window.isAdminUser, '=', window.isAdminUser);
console.log('window.currentUserId:', typeof window.currentUserId, '=', window.currentUserId);
console.log('window.userAvatarUrl:', typeof window.userAvatarUrl, '=', window.userAvatarUrl);
console.log('window.userGenderValue:', typeof window.userGenderValue, '=', window.userGenderValue);
console.log('window.userNameValue:', typeof window.userNameValue, '=', window.userNameValue);

// Teste 2: Verificar estrutura DOM
console.log('%c[2] VERIFICANDO ESTRUTURA DOM', 'color: #ffff00; font-weight: bold');
const elementos = {
    'sidebar': document.getElementById('sidebar'),
    'chatArea': document.getElementById('chatArea'),
    'chatContainer': document.getElementById('chatContainer'),
    'emptyState': document.getElementById('emptyState'),
    'messagesContainer': document.getElementById('messagesContainer'),
    'messageInput': document.getElementById('messageInput'),
    'chatAvatar': document.getElementById('chatAvatar'),
    'chatName': document.getElementById('chatName'),
    'chatStatus': document.getElementById('chatStatus'),
    'groupMembersBar': document.getElementById('groupMembersBar'),
    'groupMembersList': document.getElementById('groupMembersList')
};

Object.entries(elementos).forEach(([nome, elemento]) => {
    if (elemento) {
        console.log(`✓ ${nome}:`, 'ENCONTRADO');
    } else {
        console.error(`✗ ${nome}:`, 'NÃO ENCONTRADO');
    }
});

// Teste 3: Testar conectividade com APIs
console.log('%c[3] TESTANDO CONECTIVIDADE COM APIs', 'color: #ffff00; font-weight: bold');

const apiEndpoints = [
    'api/get_messages.php',
    'api/get_group_messages.php',
    'api/get_group_members.php',
    'api/send_message.php',
    'api/send_group_message.php',
    'api/create_conversation.php',
    'api/create_group.php',
    'api/delete_conversation.php',
    'api/favorite_message.php'
];

async function testAPIConnectivity() {
    console.log('Iniciando testes de conectividade...');
    
    for (const endpoint of apiEndpoints) {
        try {
            const response = await fetch(endpoint, {
                method: endpoint.includes('send_') || endpoint.includes('create_') || endpoint.includes('delete_') || endpoint.includes('favorite_') ? 'POST' : 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const status = response.status;
            const statusText = response.statusText;
            
            if (status === 200) {
                console.log(`✓ ${endpoint}:`, `${status} ${statusText}`, 'color: green');
            } else if (status === 400 || status === 401) {
                console.log(`⚠ ${endpoint}:`, `${status} ${statusText}`, '(Esperado - faltam parâmetros)');
            } else if (status === 404) {
                console.error(`✗ ${endpoint}:`, `${status} ${statusText}`, 'ARQUIVO NÃO ENCONTRADO!');
            } else if (status === 500) {
                console.error(`✗ ${endpoint}:`, `${status} ${statusText}`, 'ERRO NO SERVIDOR!');
            } else {
                console.warn(`? ${endpoint}:`, `${status} ${statusText}`);
            }
        } catch (error) {
            console.error(`✗ ${endpoint}:`, 'ERRO:', error.message);
        }
    }
}

// Executar teste após um pequeno delay
setTimeout(testAPIConnectivity, 1000);

// Teste 4: Verificar funções JavaScript
console.log('%c[4] VERIFICANDO FUNÇÕES JAVASCRIPT', 'color: #ffff00; font-weight: bold');

const funcoes = [
    'openConversation',
    'loadMessages',
    'loadGroupMembers',
    'sendMessage',
    'createGroup',
    'createNewConversation',
    'toggleFavorite',
    'deleteConversation',
    'closeChat',
    'toggleTheme'
];

setTimeout(() => {
    funcoes.forEach(nome => {
        if (typeof window[nome] === 'function') {
            console.log(`✓ ${nome}():`, 'DEFINIDA');
        } else {
            console.error(`✗ ${nome}():`, 'NÃO DEFINIDA');
        }
    });
}, 2000);

// Teste 5: Monitorar requisições fetch
console.log('%c[5] MONITORANDO REQUISIÇÕES FETCH', 'color: #ffff00; font-weight: bold');

const originalFetch = window.fetch;
window.fetch = async function(...args) {
    const url = args[0];
    const options = args[1] || {};
    
    console.log('%c→ FETCH REQUEST', 'color: #00ffff; font-weight: bold');
    console.log('  URL:', url);
    console.log('  Method:', options.method || 'GET');
    if (options.body) {
        try {
            console.log('  Body:', JSON.parse(options.body));
        } catch (e) {
            console.log('  Body:', options.body);
        }
    }
    
    try {
        const response = await originalFetch.apply(this, args);
        
        console.log('%c← FETCH RESPONSE', 'color: #00ff00; font-weight: bold');
        console.log('  URL:', url);
        console.log('  Status:', response.status, response.statusText);
        console.log('  Headers:', Object.fromEntries([...response.headers.entries()]));
        
        return response;
    } catch (error) {
        console.error('%c✗ FETCH ERROR', 'color: #ff0000; font-weight: bold');
        console.error('  URL:', url);
        console.error('  Error:', error);
        throw error;
    }
};

// Teste 6: Verificar conversas no DOM
console.log('%c[6] VERIFICANDO CONVERSAS NO DOM', 'color: #ffff00; font-weight: bold');

setTimeout(() => {
    const conversationItems = document.querySelectorAll('.conversation-item');
    console.log('Total de conversas encontradas:', conversationItems.length);
    
    conversationItems.forEach((item, index) => {
        const id = item.getAttribute('data-conversation-id');
        const isGroup = item.getAttribute('data-is-group');
        console.log(`  [${index + 1}] ID: ${id}, Grupo: ${isGroup === '1' ? 'SIM' : 'NÃO'}`);
    });
}, 2000);

// Teste 7: Testar URL base
console.log('%c[7] TESTANDO URL BASE', 'color: #ffff00; font-weight: bold');
console.log('window.location.href:', window.location.href);
console.log('window.location.pathname:', window.location.pathname);
console.log('window.location.origin:', window.location.origin);

// Construir URL base
const pathParts = window.location.pathname.split('/');
const baseIndex = pathParts.indexOf('admin');
let basePath = '';
if (baseIndex !== -1) {
    basePath = pathParts.slice(0, baseIndex + 1).join('/') + '/';
}
console.log('Base path calculado:', basePath);
console.log('URL API completa seria:', window.location.origin + basePath + 'api/');

// Teste 8: Verificar localStorage
console.log('%c[8] VERIFICANDO LOCALSTORAGE', 'color: #ffff00; font-weight: bold');
console.log('Theme:', localStorage.getItem('theme'));

// Resumo final
setTimeout(() => {
    console.log('%c╔══════════════════════════════════════╗', 'color: #00ff00; font-weight: bold');
    console.log('%c║   DEBUG COMPLETO                     ║', 'color: #00ff00; font-weight: bold');
    console.log('%c╚══════════════════════════════════════╝', 'color: #00ff00; font-weight: bold');
    console.log('Se houver ✗ ou erros acima, corrija-os primeiro!');
    console.log('Agora você pode testar clicando em um grupo.');
}, 3000);

// Helper: Função para testar um endpoint específico
window.testAPI = async function(endpoint, method = 'GET', body = null) {
    console.log(`%c[TESTE MANUAL] ${endpoint}`, 'color: #ff00ff; font-weight: bold');
    try {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };
        
        if (body) {
            options.body = JSON.stringify(body);
        }
        
        const response = await fetch(endpoint, options);
        console.log('Status:', response.status);
        
        const text = await response.text();
        console.log('Resposta (texto):', text.substring(0, 500));
        
        try {
            const json = JSON.parse(text);
            console.log('Resposta (JSON):', json);
        } catch (e) {
            console.error('Não é JSON válido');
        }
    } catch (error) {
        console.error('Erro:', error);
    }
};

console.log('%cUse window.testAPI("api/get_group_members.php?conversation_id=15") para testar manualmente', 'color: #ffff00');