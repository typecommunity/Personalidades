<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Verificar e criar tabela de configurações se não existir
try {
    $pdo->query("SELECT 1 FROM settings LIMIT 1");
} catch (PDOException $e) {
    // Tabela não existe, vamos criar
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `key` varchar(100) NOT NULL,
          `value` text,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `key` (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Inserir configurações padrão
    $defaultSettings = [
        ['site_name', 'Pipo - Consciousness Chatbot'],
        ['site_description', 'Uma experiência de conversação consciente'],
        ['api_provider', 'openai'],
        ['openai_api_key', ''],
        ['anthropic_api_key', ''],
        ['openai_model', 'gpt-4-turbo-preview'],
        ['anthropic_model', 'claude-3-sonnet-20240229'],
        ['max_tokens', '2000'],
        ['temperature', '0.7'],
        ['maintenance_mode', '0'],
        ['allow_registration', '1'],
        ['require_email_verification', '0'],
        ['session_timeout', '30'],
        ['theme_primary_color', '#059669'],
        ['theme_secondary_color', '#667eea'],
        ['enable_analytics', '1'],
        ['enable_notifications', '1']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT * FROM settings");
$settingsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar configurações em array associativo
$settings = [];
foreach ($settingsRaw as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Definir valores padrão se não existirem
$defaults = [
    'site_name' => 'Pipo - Consciousness Chatbot',
    'site_description' => 'Uma experiência de conversação consciente',
    'api_provider' => 'openai',
    'openai_api_key' => '',
    'anthropic_api_key' => '',
    'openai_model' => 'gpt-4-turbo-preview',
    'anthropic_model' => 'claude-3-sonnet-20240229',
    'max_tokens' => '2000',
    'temperature' => '0.7',
    'maintenance_mode' => '0',
    'allow_registration' => '1',
    'require_email_verification' => '0',
    'session_timeout' => '30',
    'theme_primary_color' => '#059669',
    'theme_secondary_color' => '#667eea',
    'enable_analytics' => '1',
    'enable_notifications' => '1',
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Estatísticas do sistema
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalConversations = $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Tamanho do banco de dados (MySQL)
try {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $result = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
        FROM information_schema.TABLES 
        WHERE table_schema = '$dbName'
        GROUP BY table_schema
    ")->fetch(PDO::FETCH_ASSOC);
    $dbSize = $result ? $result['size_mb'] . ' MB' : 'N/A';
} catch (Exception $e) {
    $dbSize = 'N/A';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Pipo Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #059669 0%, #047857 100%);
            --gradient-secondary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        /* ===== LAYOUT ===== */
        .settings-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
            margin-bottom: 40px;
        }
        
        /* ===== SIDEBAR DE NAVEGAÇÃO ===== */
        .settings-nav {
            position: sticky;
            top: 24px;
            height: fit-content;
        }
        
        .settings-nav-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
        }
        
        .settings-nav-title {
            font-size: 14px;
            font-weight: 800;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }
        
        .settings-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 6px;
            text-decoration: none;
        }
        
        .settings-nav-item svg {
            width: 20px;
            height: 20px;
        }
        
        .settings-nav-item:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .settings-nav-item.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        
        /* ===== SEÇÕES DE CONTEÚDO ===== */
        .settings-content {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }
        
        .settings-section {
            background: white;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            display: none;
        }
        
        .settings-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .section-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .section-icon.primary { background: var(--gradient-primary); }
        .section-icon.secondary { background: var(--gradient-secondary); }
        .section-icon.danger { background: var(--gradient-danger); }
        .section-icon.warning { background: var(--gradient-warning); }
        
        .section-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }
        
        .section-header-text h2 {
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        
        .section-header-text p {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* ===== FORMULÁRIOS ===== */
        .form-grid {
            display: grid;
            gap: 28px;
        }
        
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .form-label-badge.required {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .form-label-badge.optional {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            color: #1f2937;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .form-help {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .form-help code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: #374151;
        }
        
        /* ===== TOGGLE SWITCH ===== */
        .toggle-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            background: #f9fafb;
            border-radius: 14px;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .toggle-wrapper:hover {
            border-color: #d1d5db;
        }
        
        .toggle-info h4 {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .toggle-info p {
            font-size: 13px;
            color: #6b7280;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 32px;
            background: #d1d5db;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-switch.active {
            background: var(--gradient-primary);
        }
        
        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-switch.active::before {
            left: 32px;
        }
        
        /* ===== BOTÕES ===== */
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
            padding-top: 32px;
            border-top: 2px solid #f3f4f6;
        }
        
        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }
        
        .btn svg {
            width: 18px;
            height: 18px;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-danger {
            background: var(--gradient-danger);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        /* ===== STATS CARDS ===== */
        .stats-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-mini-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            border: 2px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-mini-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .stat-mini-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }
        
        .stat-mini-icon.primary { background: var(--gradient-primary); }
        .stat-mini-icon.secondary { background: var(--gradient-secondary); }
        .stat-mini-icon.warning { background: var(--gradient-warning); }
        .stat-mini-icon.danger { background: var(--gradient-danger); }
        
        .stat-mini-content h4 {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .stat-mini-content p {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }
        
        /* ===== ALERT ===== */
        .alert {
            padding: 20px 24px;
            border-radius: 14px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 28px;
        }
        
        .alert svg {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        
        .alert-warning {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            color: #92400e;
        }
        
        .alert-danger {
            background: #fee2e2;
            border: 2px solid #f87171;
            color: #7f1d1d;
        }
        
        .alert-info {
            background: #dbeafe;
            border: 2px solid #60a5fa;
            color: #1e3a8a;
        }
        
        .alert-content h5 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        
        .alert-content p {
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* ===== HEADER ===== */
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .header-title h1 {
            font-size: 36px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.02em;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .settings-nav {
                position: relative;
                top: 0;
            }
            
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-dashboard">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="admin-main">
        
        <!-- Header -->
        <div class="admin-header">
            <div class="header-title">
                <h1>⚙️ Configurações</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            
            <div class="settings-container">
                
                <!-- Navigation Sidebar -->
                <div class="settings-nav">
                    <div class="settings-nav-card">
                        <div class="settings-nav-title">Configurações</div>
                        <a class="settings-nav-item active" data-section="general">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Geral
                        </a>
                        
                        <a class="settings-nav-item" data-section="api">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            API & Modelos
                        </a>
                        
                        <a class="settings-nav-item" data-section="security">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Segurança
                        </a>
                        
                        <a class="settings-nav-item" data-section="appearance">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                            Aparência
                        </a>
                        
                        <a class="settings-nav-item" data-section="system">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                            Sistema
                        </a>
                    </div>
                </div>
                
                <!-- Content Sections -->
                <div class="settings-content">
                    
                    <!-- SEÇÃO: GERAL -->
                    <div class="settings-section active" id="section-general">
                        <div class="section-header">
                            <div class="section-icon primary">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </div>
                            <div class="section-header-text">
                                <h2>Configurações Gerais</h2>
                                <p>Defina as informações básicas do seu chatbot</p>
                            </div>
                        </div>
                        
                        <form id="formGeneral">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        Nome do Site
                                        <span class="form-label-badge required">Obrigatório</span>
                                    </label>
                                    <input type="text" class="form-input" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                                    <span class="form-help">Este nome aparecerá no título da página e em outros lugares</span>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Descrição do Site</label>
                                    <textarea class="form-textarea" name="site_description" rows="4"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                                    <span class="form-help">Uma breve descrição sobre o propósito do seu chatbot</span>
                                </div>
                                
                                <div class="toggle-wrapper">
                                    <div class="toggle-info">
                                        <h4>Modo de Manutenção</h4>
                                        <p>Desativa o acesso público ao sistema</p>
                                    </div>
                                    <div class="toggle-switch <?= $settings['maintenance_mode'] == '1' ? 'active' : '' ?>" onclick="toggleSwitch(this, 'maintenance_mode')">
                                        <input type="hidden" name="maintenance_mode" value="<?= $settings['maintenance_mode'] ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- SEÇÃO: API & MODELOS -->
                    <div class="settings-section" id="section-api">
                        <div class="section-header">
                            <div class="section-icon secondary">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="section-header-text">
                                <h2>API & Modelos de IA</h2>
                                <p>Configure as chaves de API e modelos de linguagem</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="alert-content">
                                <h5>Informação Importante</h5>
                                <p>As chaves de API são armazenadas de forma segura e criptografada. Nunca compartilhe suas chaves com terceiros.</p>
                            </div>
                        </div>
                        
                        <form id="formApi">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Provedor de API</label>
                                    <select class="form-select" name="api_provider">
                                        <option value="openai" <?= $settings['api_provider'] == 'openai' ? 'selected' : '' ?>>OpenAI (GPT-4, GPT-3.5)</option>
                                        <option value="anthropic" <?= $settings['api_provider'] == 'anthropic' ? 'selected' : '' ?>>Anthropic (Claude)</option>
                                    </select>
                                </div>
                                
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label">
                                            OpenAI API Key
                                            <span class="form-label-badge optional">Opcional</span>
                                        </label>
                                        <input type="password" class="form-input" name="openai_api_key" value="<?= htmlspecialchars($settings['openai_api_key']) ?>" placeholder="sk-...">
                                        <span class="form-help">Obtenha em <code>platform.openai.com</code></span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            Anthropic API Key
                                            <span class="form-label-badge optional">Opcional</span>
                                        </label>
                                        <input type="password" class="form-input" name="anthropic_api_key" value="<?= htmlspecialchars($settings['anthropic_api_key']) ?>" placeholder="sk-ant-...">
                                        <span class="form-help">Obtenha em <code>console.anthropic.com</code></span>
                                    </div>
                                </div>
                                
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Modelo OpenAI</label>
                                        <select class="form-select" name="openai_model">
                                            <option value="gpt-4-turbo-preview" <?= $settings['openai_model'] == 'gpt-4-turbo-preview' ? 'selected' : '' ?>>GPT-4 Turbo</option>
                                            <option value="gpt-4" <?= $settings['openai_model'] == 'gpt-4' ? 'selected' : '' ?>>GPT-4</option>
                                            <option value="gpt-3.5-turbo" <?= $settings['openai_model'] == 'gpt-3.5-turbo' ? 'selected' : '' ?>>GPT-3.5 Turbo</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Modelo Anthropic</label>
                                        <select class="form-select" name="anthropic_model">
                                            <option value="claude-3-opus-20240229" <?= $settings['anthropic_model'] == 'claude-3-opus-20240229' ? 'selected' : '' ?>>Claude 3 Opus</option>
                                            <option value="claude-3-sonnet-20240229" <?= $settings['anthropic_model'] == 'claude-3-sonnet-20240229' ? 'selected' : '' ?>>Claude 3 Sonnet</option>
                                            <option value="claude-3-haiku-20240307" <?= $settings['anthropic_model'] == 'claude-3-haiku-20240307' ? 'selected' : '' ?>>Claude 3 Haiku</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Max Tokens</label>
                                        <input type="number" class="form-input" name="max_tokens" value="<?= htmlspecialchars($settings['max_tokens']) ?>" min="100" max="4000">
                                        <span class="form-help">Limite de tokens por resposta (100 - 4000)</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Temperature</label>
                                        <input type="number" class="form-input" name="temperature" value="<?= htmlspecialchars($settings['temperature']) ?>" min="0" max="2" step="0.1">
                                        <span class="form-help">Criatividade da IA (0.0 - 2.0)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Salvar Configurações de API
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- SEÇÃO: SEGURANÇA -->
                    <div class="settings-section" id="section-security">
                        <div class="section-header">
                            <div class="section-icon danger">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div class="section-header-text">
                                <h2>Segurança & Autenticação</h2>
                                <p>Gerencie configurações de segurança e acesso</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="alert-content">
                                <h5>Atenção</h5>
                                <p>Alterar estas configurações pode afetar o acesso dos usuários ao sistema. Proceda com cuidado.</p>
                            </div>
                        </div>
                        
                        <form id="formSecurity">
                            <div class="form-grid">
                                <div class="toggle-wrapper">
                                    <div class="toggle-info">
                                        <h4>Permitir Novos Registros</h4>
                                        <p>Usuários podem criar novas contas</p>
                                    </div>
                                    <div class="toggle-switch <?= $settings['allow_registration'] == '1' ? 'active' : '' ?>" onclick="toggleSwitch(this, 'allow_registration')">
                                        <input type="hidden" name="allow_registration" value="<?= $settings['allow_registration'] ?>">
                                    </div>
                                </div>
                                
                                <div class="toggle-wrapper">
                                    <div class="toggle-info">
                                        <h4>Verificação de Email</h4>
                                        <p>Requer verificação de email ao registrar</p>
                                    </div>
                                    <div class="toggle-switch <?= $settings['require_email_verification'] == '1' ? 'active' : '' ?>" onclick="toggleSwitch(this, 'require_email_verification')">
                                        <input type="hidden" name="require_email_verification" value="<?= $settings['require_email_verification'] ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Timeout de Sessão (minutos)</label>
                                    <input type="number" class="form-input" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout']) ?>" min="5" max="1440">
                                    <span class="form-help">Tempo de inatividade antes do logout automático</span>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Salvar Configurações de Segurança
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- SEÇÃO: APARÊNCIA -->
                    <div class="settings-section" id="section-appearance">
                        <div class="section-header">
                            <div class="section-icon secondary">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <div class="section-header-text">
                                <h2>Aparência & Personalização</h2>
                                <p>Customize as cores e visual do sistema</p>
                            </div>
                        </div>
                        
                        <form id="formAppearance">
                            <div class="form-grid">
                                <div class="form-grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Cor Primária</label>
                                        <div style="display: flex; gap: 16px; align-items: center;">
                                            <input type="color" class="form-input" name="theme_primary_color" value="<?= htmlspecialchars($settings['theme_primary_color']) ?>" style="width: 100px; height: 52px;">
                                            <span style="font-weight: 700; color: #374151; font-family: 'Courier New', monospace;"><?= htmlspecialchars($settings['theme_primary_color']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Cor Secundária</label>
                                        <div style="display: flex; gap: 16px; align-items: center;">
                                            <input type="color" class="form-input" name="theme_secondary_color" value="<?= htmlspecialchars($settings['theme_secondary_color']) ?>" style="width: 100px; height: 52px;">
                                            <span style="font-weight: 700; color: #374151; font-family: 'Courier New', monospace;"><?= htmlspecialchars($settings['theme_secondary_color']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Salvar Aparência
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- SEÇÃO: SISTEMA -->
                    <div class="settings-section" id="section-system">
                        <div class="section-header">
                            <div class="section-icon warning">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                            </div>
                            <div class="section-header-text">
                                <h2>Informações do Sistema</h2>
                                <p>Estatísticas e manutenção do banco de dados</p>
                            </div>
                        </div>
                        
                        <div class="stats-mini-grid">
                            <div class="stat-mini-card">
                                <div class="stat-mini-icon primary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                </div>
                                <div class="stat-mini-content">
                                    <h4><?= number_format($totalUsers) ?></h4>
                                    <p>Usuários Totais</p>
                                </div>
                            </div>
                            
                            <div class="stat-mini-card">
                                <div class="stat-mini-icon secondary">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                        <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                                    </svg>
                                </div>
                                <div class="stat-mini-content">
                                    <h4><?= number_format($totalConversations) ?></h4>
                                    <p>Conversas</p>
                                </div>
                            </div>
                            
                            <div class="stat-mini-card">
                                <div class="stat-mini-icon warning">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="stat-mini-content">
                                    <h4><?= number_format($totalMessages) ?></h4>
                                    <p>Mensagens</p>
                                </div>
                            </div>
                            
                            <div class="stat-mini-card">
                                <div class="stat-mini-icon danger">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="stat-mini-content">
                                    <h4><?= $dbSize ?? 'N/A' ?></h4>
                                    <p>Banco de Dados</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-danger">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="alert-content">
                                <h5>Zona de Perigo</h5>
                                <p>As ações abaixo são irreversíveis. Proceda com extrema cautela.</p>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="btn-group" style="margin-top: 0; padding-top: 0; border-top: none;">
                                <button type="button" class="btn btn-secondary" onclick="clearCache()">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                    </svg>
                                    Limpar Cache
                                </button>
                                
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteOldConversations()">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Excluir Conversas Antigas
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Navegação entre seções
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remover active de todos
                document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
                document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
                
                // Adicionar active no clicado
                this.classList.add('active');
                
                // Mostrar seção correspondente
                const section = this.getAttribute('data-section');
                document.getElementById('section-' + section).classList.add('active');
            });
        });
        
        // Toggle Switch
        function toggleSwitch(element, fieldName) {
            element.classList.toggle('active');
            const input = element.querySelector('input[name="' + fieldName + '"]');
            input.value = element.classList.contains('active') ? '1' : '0';
        }
        
        // Salvar formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                
                fetch('api/save_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showNotification('Configurações salvas com sucesso!', 'success');
                    } else {
                        showNotification('Erro ao salvar configurações', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erro de conexão', 'error');
                });
            });
        });
        
        function clearCache() {
            if(confirm('Deseja realmente limpar o cache do sistema?')) {
                fetch('api/clear_cache.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showNotification('Cache limpo com sucesso!', 'success');
                    }
                });
            }
        }
        
        function confirmDeleteOldConversations() {
            if(confirm('⚠️ ATENÇÃO: Esta ação irá excluir permanentemente todas as conversas com mais de 90 dias. Esta ação não pode ser desfeita!\n\nDeseja continuar?')) {
                fetch('api/delete_old_conversations.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showNotification(`${data.deleted} conversas antigas foram excluídas`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    }
                });
            }
        }
        
        function showNotification(message, type) {
            alert(message);
        }
    </script>
</body>
</html>