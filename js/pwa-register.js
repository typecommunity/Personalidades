// Registrar Service Worker e configurar PWA
(function() {
  'use strict';

  // ========================================
  // REGISTRAR SERVICE WORKER
  // ========================================
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      registerServiceWorker();
    });
  } else {
    console.warn('Service Workers não são suportados neste navegador');
  }

  async function registerServiceWorker() {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/'
      });

      console.log('✅ Service Worker registrado:', registration.scope);

      // Verificar atualizações
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        console.log('🔄 Nova versão do Service Worker encontrada');

        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // Nova versão disponível
            showUpdateNotification();
          }
        });
      });

      // Verificar atualizações periodicamente (1 hora)
      setInterval(() => {
        registration.update();
      }, 60 * 60 * 1000);

    } catch (error) {
      console.error('❌ Erro ao registrar Service Worker:', error);
    }
  }

  // ========================================
  // NOTIFICAR NOVA VERSÃO
  // ========================================
  function showUpdateNotification() {
    if (confirm('🎉 Nova versão disponível! Deseja atualizar agora?')) {
      window.location.reload();
    }
  }

  // ========================================
  // DETECTAR INSTALAÇÃO DO PWA
  // ========================================
  let deferredPrompt;

  window.addEventListener('beforeinstallprompt', (e) => {
    console.log('💡 Prompt de instalação disponível');
    e.preventDefault();
    deferredPrompt = e;

    // Mostrar botão de instalação
    showInstallButton();
  });

  function showInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    
    if (!installBtn) {
      // Criar botão de instalação
      const btn = document.createElement('button');
      btn.id = 'pwa-install-btn';
      btn.className = 'pwa-install-button';
      btn.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Instalar App
      `;
      btn.onclick = installPWA;
      document.body.appendChild(btn);
    } else {
      installBtn.style.display = 'block';
    }
  }

  async function installPWA() {
    if (!deferredPrompt) return;

    // Mostrar prompt nativo
    deferredPrompt.prompt();

    // Aguardar resposta do usuário
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`Usuário ${outcome === 'accepted' ? 'aceitou' : 'recusou'} instalar o PWA`);

    // Limpar prompt
    deferredPrompt = null;

    // Esconder botão
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
      installBtn.style.display = 'none';
    }
  }

  // Detectar quando o PWA foi instalado
  window.addEventListener('appinstalled', () => {
    console.log('🎉 PWA instalado com sucesso!');
    deferredPrompt = null;

    // Esconder botão
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
      installBtn.style.display = 'none';
    }

    // Analytics (opcional)
    if (typeof gtag !== 'undefined') {
      gtag('event', 'pwa_installed');
    }
  });

  // ========================================
  // DETECTAR MODO STANDALONE
  // ========================================
  function isStandalone() {
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true
    );
  }

  if (isStandalone()) {
    console.log('📱 Executando em modo standalone (PWA)');
    document.body.classList.add('pwa-mode');
  }

  // ========================================
  // SINCRONIZAÇÃO EM BACKGROUND
  // ========================================
  if ('sync' in registration) {
    // Registrar sync quando voltar online
    window.addEventListener('online', () => {
      navigator.serviceWorker.ready.then((reg) => {
        return reg.sync.register('sync-messages');
      }).then(() => {
        console.log('📡 Background sync registrado');
      }).catch((error) => {
        console.error('Erro ao registrar background sync:', error);
      });
    });
  }

  // ========================================
  // NOTIFICAÇÕES PUSH (Opcional)
  // ========================================
  async function requestNotificationPermission() {
    if (!('Notification' in window)) {
      console.warn('Notificações não suportadas');
      return false;
    }

    if (Notification.permission === 'granted') {
      return true;
    }

    if (Notification.permission !== 'denied') {
      const permission = await Notification.requestPermission();
      return permission === 'granted';
    }

    return false;
  }

  // Expor função globalmente
  window.requestNotificationPermission = requestNotificationPermission;

  // ========================================
  // LIMPAR CACHE (Debug)
  // ========================================
  window.clearPWACache = async function() {
    if ('serviceWorker' in navigator) {
      const registration = await navigator.serviceWorker.ready;
      
      // Enviar mensagem para o SW
      const channel = new MessageChannel();
      
      channel.port1.onmessage = (event) => {
        if (event.data.success) {
          console.log('✅ Cache limpo com sucesso!');
          window.location.reload();
        }
      };
      
      registration.active.postMessage(
        { action: 'clearCache' },
        [channel.port2]
      );
    }
  };

  // ========================================
  // STATUS DA CONEXÃO
  // ========================================
  window.addEventListener('online', () => {
    console.log('🟢 Online');
    document.body.classList.remove('offline');
    document.body.classList.add('online');
    
    // Mostrar toast
    showToast('Conexão restaurada!', 'success');
  });

  window.addEventListener('offline', () => {
    console.log('🔴 Offline');
    document.body.classList.remove('online');
    document.body.classList.add('offline');
    
    // Mostrar toast
    showToast('Você está offline. Algumas funcionalidades podem não estar disponíveis.', 'warning');
  });

  function showToast(message, type = 'info') {
    // Criar toast simples
    const toast = document.createElement('div');
    toast.className = `pwa-toast pwa-toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    // Animar entrada
    setTimeout(() => toast.classList.add('show'), 100);

    // Remover após 3 segundos
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // ========================================
  // VERIFICAR COMPATIBILIDADE PWA
  // ========================================
  function checkPWACompatibility() {
    const features = {
      serviceWorker: 'serviceWorker' in navigator,
      notifications: 'Notification' in window,
      backgroundSync: 'sync' in ServiceWorkerRegistration.prototype,
      pushManager: 'PushManager' in window,
      manifest: 'manifest' in document.head.querySelector('link[rel="manifest"]')
    };

    console.log('🔍 Compatibilidade PWA:', features);
    return features;
  }

  // Verificar ao carregar
  window.addEventListener('load', () => {
    checkPWACompatibility();
  });

})();