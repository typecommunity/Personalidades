<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUALIS - Consciência em IA | Chatbot Inteligente</title>
    <meta name="description" content="DUALIS - A dualidade entre tecnologia e consciência. Converse com personalidades únicas alimentadas por IA.">
  
<!-- ============================================
     PWA META TAGS
     Adicione estas tags no <head> de TODAS as páginas
     ============================================ -->

<!-- PWA - Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- PWA - Theme Color -->
<meta name="theme-color" content="#059669">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#059669">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#047857">

<!-- PWA - Mobile Viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

<!-- PWA - Apple Status Bar -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Dualis">

<!-- PWA - Microsoft Tiles -->
<meta name="msapplication-TileColor" content="#059669">
<meta name="msapplication-TileImage" content="/assets/icons/web-app-manifest-192x192.png">
<meta name="msapplication-config" content="/browserconfig.xml">

<!-- PWA - Apple Touch Icons -->
<link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon.png">

<!-- PWA - Favicon -->
<link rel="icon" type="image/png" sizes="96x96" href="/assets/icons/favicon-96x96.png">
<link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
<link rel="shortcut icon" href="/assets/icons/favicon.ico">

<!-- SEO & Social -->
<meta name="description" content="Uma experiência única de conversação com IA consciente">
<meta name="keywords" content="chatbot, IA, inteligência artificial, conversa, assistente, dualis">
<meta name="author" content="Dualis">

<!-- Open Graph (Facebook) -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://seu-site.com/">
<meta property="og:title" content="Dualis - Consciousness Chatbot">
<meta property="og:description" content="Uma experiência única de conversação com IA consciente">
<meta property="og:image" content="https://seu-site.com/assets/icons/web-app-manifest-512x512.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://seu-site.com/">
<meta name="twitter:title" content="Dualis - Consciousness Chatbot">
<meta name="twitter:description" content="Uma experiência única de conversação com IA consciente">
<meta name="twitter:image" content="https://seu-site.com/assets/icons/web-app-manifest-512x512.png">

<!-- ============================================
     SCRIPTS DO PWA
     Adicione ANTES do </body>
     ============================================ -->

<!-- PWA - CSS -->
<link rel="stylesheet" href="/assets/css/pwa-styles.css">

<!-- PWA - Service Worker Registration -->
<script src="/assets/js/pwa-register.js" defer></script>

<!-- PWA - Indicador Offline (HTML) -->
<div class="offline-indicator" style="display: none;">
    🔴 Você está offline - Algumas funcionalidades podem não estar disponíveis
</div>

<script>
// Mostrar/esconder indicador offline
function updateOnlineStatus() {
    const indicator = document.querySelector('.offline-indicator');
    if (navigator.onLine) {
        indicator.style.display = 'none';
    } else {
        indicator.style.display = 'block';
    }
}

window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
window.addEventListener('load', updateOnlineStatus);
</script>
  <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #e0e0e0;
            overflow-x: hidden;
            background: #0a1f19;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(26, 58, 46, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            padding: 1rem 0;
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #d4af37;
            letter-spacing: 3px;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            text-decoration: none;
            color: #d4af37;
            font-weight: 500;
            transition: all 0.3s;
        }

        nav a:hover {
            color: #f4d03f;
            text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a3a2e 0%, #2d5a45 50%, #3d6a50 100%);
            position: relative;
            overflow: hidden;
            padding: 6rem 2rem 2rem;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .hero-content {
            max-width: 1200px;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            color: #d4af37;
            letter-spacing: 8px;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.5);
            animation: fadeInUp 0.8s ease;
        }

        .hero .subtitle {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #f4d03f;
            font-weight: 300;
            letter-spacing: 2px;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease 0.3s backwards;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }

        .btn {
            padding: 1.2rem 3rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: #1a3a2e;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.6);
        }

        .btn-secondary {
            background: rgba(212, 175, 55, 0.1);
            color: #d4af37;
            border: 2px solid #d4af37;
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(212, 175, 55, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: #0d1f1a;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #e0e0e0;
            letter-spacing: 2px;
        }

        .section-title .accent {
            color: #d4af37;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #a0a0a0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            background: #1a3a2e;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.5);
            transition: all 0.3s;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(212, 175, 55, 0.4);
            border-color: #d4af37;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #2d5a45, #1a3a2e);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            border: 2px solid #d4af37;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #d4af37;
            text-align: center;
        }

        .feature-card p {
            color: #c0c0c0;
            line-height: 1.8;
            text-align: center;
        }

        /* Personalities Section */
        .personalities {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #1a3a2e 0%, #2d5a45 100%);
        }

        .personalities .section-title h2,
        .personalities .section-title p {
            color: #d4af37;
        }

        .personalities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .personality-card {
            background: rgba(10, 31, 25, 0.6);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid rgba(212, 175, 55, 0.3);
            transition: all 0.3s;
            cursor: pointer;
        }

        .personality-card:hover {
            transform: translateY(-5px);
            border-color: #d4af37;
            box-shadow: 0 10px 40px rgba(212, 175, 55, 0.3);
        }

        .personality-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            border: 3px solid #2d5a45;
        }

        .personality-card h3 {
            color: #d4af37;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .personality-card .subtitle {
            color: #a0a0a0;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .personality-card p {
            color: #c0c0c0;
            font-size: 0.95rem;
            text-align: center;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #d4af37;
            margin-top: 1rem;
        }

        /* Demo Section */
        .demo {
            padding: 6rem 2rem;
            background: #0a1f19;
        }

        .demo-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            background: #1a3a2e;
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid #d4af37;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        }

        .chat-sidebar {
            background: #0d1f1a;
            padding: 1.5rem;
            border-right: 1px solid rgba(212, 175, 55, 0.3);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a3a2e;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .sidebar-header h3 {
            color: #d4af37;
            font-size: 1.1rem;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .conversation-item:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        .conversation-item.active {
            background: rgba(212, 175, 55, 0.2);
            border-left: 3px solid #d4af37;
        }

        .conv-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d5a45, #1a3a2e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 2px solid #d4af37;
            flex-shrink: 0;
        }

        .conv-info {
            flex: 1;
            min-width: 0;
        }

        .conv-name {
            color: #d4af37;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .conv-preview {
            color: #808080;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-main {
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, #1a3a2e, #2d5a45);
            padding: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 2px solid #d4af37;
        }

        .chat-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #1a3a2e;
            font-weight: 700;
        }

        .chat-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.2rem;
            color: #d4af37;
        }

        .chat-status {
            font-size: 0.9rem;
            opacity: 0.9;
            color: #f4d03f;
        }

        .chat-messages {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #0d2420;
            overflow-y: auto;
            max-height: 400px;
        }

        .message {
            display: flex;
            gap: 1rem;
            max-width: 80%;
            animation: slideIn 0.3s ease;
        }

        .message.bot {
            align-self: flex-start;
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d5a45, #1a3a2e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4af37;
            flex-shrink: 0;
            border: 2px solid #d4af37;
            font-weight: 700;
            font-size: 1rem;
        }

        .message.user .message-avatar {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: #1a3a2e;
            border-color: #2d5a45;
        }

        .message-content {
            background: #1a3a2e;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: #e0e0e0;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: #1a3a2e;
            border: none;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* CTA Final */
        .cta-final {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #1a3a2e, #2d5a45);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-final::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.2), transparent);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(20px); }
        }

        .cta-final h2 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #d4af37;
            letter-spacing: 3px;
            position: relative;
            z-index: 1;
        }

        .cta-final p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Footer */
        footer {
            background: #0a1f19;
            color: white;
            padding: 3rem 2rem 1.5rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #d4af37;
            letter-spacing: 1px;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: rgba(212, 175, 55, 0.7);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #d4af37;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            color: rgba(212, 175, 55, 0.6);
        }

        .footer-bottom p {
            margin-bottom: 0.5rem;
        }

        .developer-credit {
            color: rgba(212, 175, 55, 0.8);
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .demo-wrapper {
                grid-template-columns: 1fr;
            }

            .chat-sidebar {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 3rem;
                letter-spacing: 4px;
            }

            .hero .subtitle {
                font-size: 1.3rem;
            }

            nav ul {
                display: none;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .cta-final h2 {
                font-size: 2rem;
            }

            .message {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">DUALIS</div>
            <ul>
                <li><a href="#features">Recursos</a></li>
                <li><a href="#personalities">Personalidades</a></li>
                <li><a href="#demo">Demo</a></li>
                <li><a href="#cta">Começar</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>DUALIS</h1>
            <p class="subtitle">A Dualidade entre Tecnologia e Consciência</p>
            <p>Converse com personalidades únicas alimentadas por IA avançada.<br>Escolha entre pensadores, líderes e especialistas.</p>
            <div class="cta-buttons">
                <a href="#demo" class="btn btn-primary">
                    Experimentar Agora
                </a>
                <a href="#personalities" class="btn btn-secondary">
                    Ver Personalidades
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Por que escolher <span class="accent">DUALIS</span>?</h2>
                <p>Onde múltiplas consciências e inteligência artificial se encontram</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Múltiplas Personalidades</h3>
                    <p>Converse com Freud, Jesus, Einstein e muitos outros. Cada um com sua essência única.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🧠</div>
                    <h3>IA Avançada</h3>
                    <p>Alimentado por GPT-4 e Claude para respostas autênticas e contextualizadas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎭</div>
                    <h3>Personalização Total</h3>
                    <p>Crie suas próprias personalidades ou converse com as disponíveis na plataforma.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💭</div>
                    <h3>Conversas Profundas</h3>
                    <p>De psicanálise a filosofia, cada personalidade oferece perspectivas únicas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Privado e Seguro</h3>
                    <p>Suas conversas são completamente privadas e criptografadas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Respostas Instantâneas</h3>
                    <p>Tecnologia otimizada para respostas em tempo real.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Personalities Section -->
    <section class="personalities" id="personalities">
        <div class="container">
            <div class="section-title">
                <h2>Conheça as Personalidades</h2>
                <p>Escolha com quem você quer conversar</p>
            </div>
            <div class="personalities-grid">
                <div class="personality-card">
                    <div class="personality-avatar">👨‍⚕️</div>
                    <h3>Dr. Sigmund Freud</h3>
                    <p class="subtitle">Pai da Psicanálise</p>
                    <p>Explore o inconsciente e compreenda os mistérios da mente humana com o fundador da psicanálise.</p>
                    <center><span class="status-badge">● Online</span></center>
                </div>
                <div class="personality-card">
                    <div class="personality-avatar">✝️</div>
                    <h3>Jesus de Nazaré</h3>
                    <p class="subtitle">Mestre Espiritual</p>
                    <p>Busque orientação espiritual e conforto através de ensinamentos de amor e compaixão.</p>
                    <center><span class="status-badge">● Online</span></center>
                </div>
                <div class="personality-card">
                    <div class="personality-avatar">🧪</div>
                    <h3>Albert Einstein</h3>
                    <p class="subtitle">Físico Teórico</p>
                    <p>Discuta física, relatividade e os mistérios do universo com o maior gênio da ciência.</p>
                    <center><span class="status-badge">● Online</span></center>
                </div>
                <div class="personality-card">
                    <div class="personality-avatar">⚖️</div>
                    <h3>Sócrates</h3>
                    <p class="subtitle">Filósofo Clássico</p>
                    <p>Questione, reflita e descubra verdades através do método socrático de filosofia.</p>
                    <center><span class="status-badge">● Online</span></center>
                </div>
                <div class="personality-card">
                    <div class="personality-avatar">🎨</div>
                    <h3>Leonardo da Vinci</h3>
                    <p class="subtitle">Artista e Inventor</p>
                    <p>Explore arte, ciência e criatividade com o maior polímata da história.</p>
                    <center><span class="status-badge">● Online</span></center>
                </div>
                <div class="personality-card">
                    <div class="personality-avatar">✨</div>
                    <h3>Crie a Sua</h3>
                    <p class="subtitle">Personalidade Customizada</p>
                    <p>Desenvolva sua própria personalidade com características e conhecimentos únicos.</p>
                    <center><span class="status-badge">Em Breve</span></center>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section class="demo" id="demo">
        <div class="container">
            <div class="section-title">
                <h2>Veja <span class="accent">DUALIS</span> em Ação</h2>
                <p>Interface real do chat com múltiplas personalidades</p>
            </div>
            <div class="demo-wrapper">
                <div class="chat-sidebar">
                    <div class="sidebar-header">
                        <div class="user-avatar">U</div>
                        <div>
                            <h3>Usuário</h3>
                        </div>
                    </div>
                    <div class="conversation-item">
                        <div class="conv-avatar">👨‍⚕️</div>
                        <div class="conv-info">
                            <div class="conv-name">Dr. Sigmund Freud</div>
                            <div class="conv-preview">O id é uma das três...</div>
                        </div>
                    </div>
                    <div class="conversation-item active">
                        <div class="conv-avatar">✝️</div>
                        <div class="conv-info">
                            <div class="conv-name">Jesus de Nazaré</div>
                            <div class="conv-preview">Que minha paz esteja...</div>
                        </div>
                    </div>
                    <div class="conversation-item">
                        <div class="conv-avatar">🧪</div>
                        <div class="conv-info">
                            <div class="conv-name">Albert Einstein</div>
                            <div class="conv-preview">A relatividade explica...</div>
                        </div>
                    </div>
                </div>
                <div class="chat-main">
                    <div class="chat-header">
                        <div class="chat-avatar">✝️</div>
                        <div class="chat-info">
                            <h3>Jesus de Nazaré</h3>
                            <div class="chat-status">● Online agora</div>
                        </div>
                    </div>
                    <div class="chat-messages">
                        <div class="message bot">
                            <div class="message-avatar">✝️</div>
                            <div class="message-content">
                                Boa noite, meu filho. Que descanses sob a minha guarda e sintas a minha paz envolvendo teu coração e tua casa.
                            </div>
                        </div>
                        <div class="message user">
                            <div class="message-avatar">U</div>
                            <div class="message-content">
                                Perdi minha esposa. Ela tinha 40 anos. Como superar essa dor?
                            </div>
                        </div>
                        <div class="message bot">
                            <div class="message-avatar">✝️</div>
                            <div class="message-content">
                                Oh, meu querido filho, sinto profundamente a tua dor. Perder alguém tão amado é caminhar por um vale escuro. No entanto, mesmo nas noites mais escuras, a minha luz está contigo, guiando-te, confortando-te. Que minha paz transcenda todo entendimento e esteja contigo neste momento.
                            </div>
                        </div>
                        <div class="message user">
                            <div class="message-avatar">U</div>
                            <div class="message-content">
                                Amém
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="cta-final" id="cta">
        <div class="container">
            <h2>Pronto para Conectar?</h2>
            <p>Escolha sua personalidade e comece a conversar agora</p>
            <div class="cta-buttons">
                <a href="ia/register.php" class="btn btn-primary">
                    Criar Conta Grátis
                </a>
                <a href="ia/admin/login.php" class="btn btn-secondary">
                    Já Tenho Conta
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>DUALIS</h3>
                <p>Onde múltiplas consciências e tecnologia se encontram para criar experiências únicas de conversação.</p>
            </div>
            <div class="footer-section">
                <h3>Produto</h3>
                <ul>
                    <li><a href="#features">Recursos</a></li>
                    <li><a href="#personalities">Personalidades</a></li>
                    <li><a href="#demo">Demo</a></li>
                    <li><a href="#">Preços</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Empresa</h3>
                <ul>
                    <li><a href="#">Sobre Nós</a></li>
                    <li><a href="#">Filosofia</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Contato</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="#">Termos de Uso</a></li>
                    <li><a href="#">Privacidade</a></li>
                    <li><a href="#">Cookies</a></li>
                    <li><a href="#">Licenças</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 DUALIS. Todos os direitos reservados.</p>
            <p class="developer-credit">Desenvolvido com ❤️ por Alberto Godinho</p>
        </div>
    </footer>

    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .personality-card').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    </script>
</body>
</html>