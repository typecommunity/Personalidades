// Service Worker - Dualis PWA
const CACHE_NAME = 'dualis-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Arquivos para cache inicial
const STATIC_CACHE_FILES = [
  '/',
  '/ia/admin/login.php',
  '/offline.html',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/icons/web-app-manifest-192x192.png',
  '/assets/icons/web-app-manifest-512x512.png',
  '/assets/icons/apple-touch-icon.png',
  '/assets/icons/favicon-96x96.png',
  '/manifest.json'
];

// Arquivos para cache dinâmico (rotas da API)
const DYNAMIC_CACHE_FILES = [
  '/api/chat.php',
  '/api/conversations.php',
  '/api/personalities.php'
];

// ========================================
// INSTALAÇÃO
// ========================================
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando Service Worker...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Cache aberto');
        return cache.addAll(STATIC_CACHE_FILES);
      })
      .then(() => {
        console.log('[SW] Arquivos estáticos em cache');
        return self.skipWaiting(); // Ativar imediatamente
      })
      .catch((error) => {
        console.error('[SW] Erro ao cachear arquivos:', error);
      })
  );
});

// ========================================
// ATIVAÇÃO
// ========================================
self.addEventListener('activate', (event) => {
  console.log('[SW] Ativando Service Worker...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Deletar caches antigos
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Deletando cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[SW] Service Worker ativado');
      return self.clients.claim(); // Controlar páginas abertas
    })
  );
});

// ========================================
// INTERCEPTAR REQUISIÇÕES (Fetch)
// ========================================
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar requisições de extensões do navegador
  if (url.protocol === 'chrome-extension:' || url.protocol === 'moz-extension:') {
    return;
  }

  // Estratégia: Cache First para arquivos estáticos
  if (request.method === 'GET' && isStaticAsset(url)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Estratégia: Network First para API e páginas PHP
  if (request.method === 'GET' && (url.pathname.endsWith('.php') || url.pathname.includes('/api/'))) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Estratégia: Network Only para POST/PUT/DELETE
  if (request.method !== 'GET') {
    event.respondWith(networkOnly(request));
    return;
  }

  // Fallback: Network First
  event.respondWith(networkFirst(request));
});

// ========================================
// ESTRATÉGIAS DE CACHE
// ========================================

/**
 * Cache First: Busca no cache primeiro, depois na rede
 * Ideal para: CSS, JS, imagens, fontes
 */
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      console.log('[SW] Cache HIT:', request.url);
      return cachedResponse;
    }

    console.log('[SW] Cache MISS:', request.url);
    const networkResponse = await fetch(request);
    
    // Cachear resposta se for bem-sucedida
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('[SW] Erro em cacheFirst:', error);
    return caches.match(OFFLINE_URL);
  }
}

/**
 * Network First: Busca na rede primeiro, depois no cache
 * Ideal para: Páginas HTML, API calls
 */
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Cachear resposta se for bem-sucedida
    if (networkResponse.ok && request.method === 'GET') {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[SW] Rede falhou, buscando cache:', request.url);
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Se não tem cache, retorna página offline
    return caches.match(OFFLINE_URL);
  }
}

/**
 * Network Only: Sempre busca na rede
 * Ideal para: POST, PUT, DELETE, dados sensíveis
 */
async function networkOnly(request) {
  try {
    return await fetch(request);
  } catch (error) {
    console.error('[SW] Erro em networkOnly:', error);
    
    // Retornar resposta JSON de erro para APIs
    if (request.url.includes('/api/')) {
      return new Response(
        JSON.stringify({ 
          success: false, 
          error: 'Sem conexão com a internet',
          offline: true 
        }),
        { 
          status: 503,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
    
    return caches.match(OFFLINE_URL);
  }
}

// ========================================
// HELPERS
// ========================================

/**
 * Verifica se a URL é um asset estático
 */
function isStaticAsset(url) {
  const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
  return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

// ========================================
// SINCRONIZAÇÃO EM BACKGROUND
// ========================================
self.addEventListener('sync', (event) => {
  console.log('[SW] Background Sync:', event.tag);
  
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncPendingMessages());
  }
});

async function syncPendingMessages() {
  // Implementar sincronização de mensagens pendentes
  console.log('[SW] Sincronizando mensagens...');
  // TODO: Buscar mensagens salvas localmente e enviar para o servidor
}

// ========================================
// NOTIFICAÇÕES PUSH
// ========================================
self.addEventListener('push', (event) => {
  console.log('[SW] Push recebido:', event);
  
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Dualis';
  const options = {
    body: data.body || 'Nova mensagem',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    data: data.url || '/',
    actions: [
      { action: 'open', title: 'Abrir' },
      { action: 'close', title: 'Fechar' }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notificação clicada:', event.action);
  
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    const url = event.notification.data || '/';
    event.waitUntil(
      clients.openWindow(url)
    );
  }
});

// ========================================
// MENSAGENS DO CLIENTE
// ========================================
self.addEventListener('message', (event) => {
  console.log('[SW] Mensagem recebida:', event.data);
  
  if (event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
  
  if (event.data.action === 'clearCache') {
    event.waitUntil(
      caches.delete(CACHE_NAME).then(() => {
        console.log('[SW] Cache limpo');
        event.ports[0].postMessage({ success: true });
      })
    );
  }
});