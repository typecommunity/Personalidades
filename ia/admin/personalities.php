<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Buscar todas personalidades
$personalities = $pdo->query("SELECT * FROM personalities ORDER BY sort_order ASC, created_at ASC")->fetchAll();

// Estatísticas
$totalPersonalities = count($personalities);
$activePersonalities = count(array_filter($personalities, fn($p) => $p['is_active']));
$totalConversations = $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalidades - Pipo Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        :root {
            --purple-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --green-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --blue-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --orange-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        
        /* ===== STATS PREMIUM ===== */
        .stats-premium-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 28px;
            margin-bottom: 48px;
        }
        
        .stat-premium {
            position: relative;
            background: white;
            border-radius: 24px;
            padding: 36px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
        }
        
        .stat-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0;
            transition: opacity 0.4s;
            z-index: 0;
        }
        
        .stat-premium.gradient-purple::before {
            background: var(--purple-gradient);
        }
        
        .stat-premium.gradient-green::before {
            background: var(--green-gradient);
        }
        
        .stat-premium.gradient-blue::before {
            background: var(--blue-gradient);
        }
        
        .stat-premium:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.12);
        }
        
        .stat-premium:hover::before {
            opacity: 0.08;
        }
        
        .stat-premium-top {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }
        
        .stat-premium-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        
        .gradient-purple .stat-premium-icon {
            background: var(--purple-gradient);
        }
        
        .gradient-green .stat-premium-icon {
            background: var(--green-gradient);
        }
        
        .gradient-blue .stat-premium-icon {
            background: var(--blue-gradient);
        }
        
        .stat-premium-icon svg {
            width: 36px;
            height: 36px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .stat-premium-number {
            font-size: 52px;
            font-weight: 800;
            background: linear-gradient(135deg, #111827 0%, #374151 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            letter-spacing: -0.02em;
        }
        
        .stat-premium-content {
            position: relative;
            z-index: 1;
        }
        
        .stat-premium-label {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }
        
        .stat-premium-sublabel {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .stat-premium-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 16px;
            position: relative;
            z-index: 1;
        }
        
        .gradient-purple .stat-premium-badge {
            background: rgba(102, 126, 234, 0.1);
            color: #5b21b6;
        }
        
        .gradient-green .stat-premium-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
        }
        
        .gradient-blue .stat-premium-badge {
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
        }
        
        /* ===== PERSONALITY GRID ===== */
        .personality-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
            margin-bottom: 40px;
        }
        
        .personality-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .personality-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--card-color);
        }
        
        .personality-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
            border-color: var(--card-color);
        }
        
        .personality-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }
        
        .personality-avatar-big {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 26px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
            letter-spacing: -0.02em;
            overflow: hidden;
            position: relative;
        }
        
        .personality-avatar-big img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .personality-avatar-big .avatar-letter {
            position: relative;
            z-index: 1;
        }
        
        .personality-info h3 {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }
        
        .personality-order-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .personality-order-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }
        
        .order-input {
            width: 56px;
            padding: 6px 10px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
            font-weight: 700;
            color: #374151;
            transition: all 0.2s;
        }
        
        .order-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .personality-description {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .prompt-preview {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
            max-height: 90px;
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
        }
        
        .prompt-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(to bottom, transparent, #f9fafb);
        }
        
        .personality-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            background: #f3f4f6;
            color: #6b7280;
            letter-spacing: 0.01em;
        }
        
        .meta-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .meta-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .meta-badge svg {
            width: 14px;
            height: 14px;
        }
        
        .personality-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-card {
            flex: 1;
            padding: 12px 18px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }
        
        .btn-card.edit {
            background: var(--green-gradient);
            color: white;
        }
        
        .btn-card.edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }
        
        .btn-card.delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-card.delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }
        
        .btn-card svg {
            width: 16px;
            height: 16px;
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state-personalities {
            text-align: center;
            padding: 100px 32px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
        }
        
        .empty-state-personalities svg {
            width: 140px;
            height: 140px;
            margin: 0 auto 32px;
            opacity: 0.12;
        }
        
        .empty-state-personalities h3 {
            font-size: 26px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        
        .empty-state-personalities p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 32px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        /* ===== MODAL STYLES ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        
        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 32px 36px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .modal-title {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.02em;
        }
        
        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            background: #e5e7eb;
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 36px;
        }
        
        .form-group {
            margin-bottom: 28px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
            letter-spacing: -0.01em;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            color: #1f2937;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 200px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .form-group input[type="color"] {
            width: 100px;
            height: 52px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .form-group input[type="color"]:hover {
            border-color: #d1d5db;
        }
        
        .form-group input[type="checkbox"] {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #059669;
        }
        
        .form-group small {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-top: 8px;
            line-height: 1.5;
        }
        
        .color-preview-row {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .color-code {
            font-size: 15px;
            font-weight: 700;
            color: #374151;
            font-family: 'Courier New', monospace;
        }
        
        /* ===== UPLOAD DE IMAGEM ===== */
        .image-upload-container {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        
        .image-preview-box {
            width: 140px;
            height: 140px;
            border-radius: 18px;
            border: 3px dashed #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
            background: #f9fafb;
            transition: all 0.3s;
        }
        
        .image-preview-box:hover {
            border-color: #059669;
            background: #f0fdf4;
        }
        
        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview-placeholder {
            text-align: center;
            color: #9ca3af;
        }
        
        .image-preview-placeholder svg {
            width: 48px;
            height: 48px;
            margin-bottom: 8px;
        }
        
        .image-preview-placeholder p {
            font-size: 12px;
            font-weight: 600;
        }
        
        .image-upload-controls {
            flex: 1;
        }
        
        .btn-upload {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: var(--green-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 12px;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }
        
        .btn-upload svg {
            width: 18px;
            height: 18px;
        }
        
        .btn-remove-image {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-remove-image:hover {
            background: #fecaca;
        }
        
        .btn-remove-image svg {
            width: 16px;
            height: 16px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 24px 36px;
            border-top: 2px solid #f3f4f6;
            background: #f9fafb;
            border-radius: 0 0 24px 24px;
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
        
        .btn-primary {
            background: var(--green-gradient);
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
        
        .btn svg {
            width: 18px;
            height: 18px;
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
        
        /* ===== LOADING OVERLAY ===== */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 18px;
            font-weight: 700;
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
                <h1>🎭 Personalidades</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openPersonalityModal()">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Nova Personalidade
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            
            <!-- Stats Premium -->
            <div class="stats-premium-grid">
                <div class="stat-premium gradient-purple">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= $totalPersonalities ?></div>
                    </div>
                    <div class="stat-premium-content">
                        <div class="stat-premium-label">Personalidades</div>
                        <div class="stat-premium-sublabel">Criadas no sistema</div>
                        <div class="stat-premium-badge">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3"/>
                            </svg>
                            Total registrado
                        </div>
                    </div>
                </div>
                
                <div class="stat-premium gradient-green">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= $activePersonalities ?></div>
                    </div>
                    <div class="stat-premium-content">
                        <div class="stat-premium-label">Ativas</div>
                        <div class="stat-premium-sublabel">Disponíveis aos usuários</div>
                        <div class="stat-premium-badge">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3"/>
                            </svg>
                            Em funcionamento
                        </div>
                    </div>
                </div>
                
                <div class="stat-premium gradient-blue">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= number_format($totalConversations) ?></div>
                    </div>
                    <div class="stat-premium-content">
                        <div class="stat-premium-label">Conversas</div>
                        <div class="stat-premium-sublabel">Iniciadas pelos usuários</div>
                        <div class="stat-premium-badge">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3"/>
                            </svg>
                            Interações totais
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($personalities)): ?>
                <!-- Empty State -->
                <div class="empty-state-personalities">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                    </svg>
                    <h3>Nenhuma personalidade criada ainda</h3>
                    <p>Crie sua primeira personalidade para começar a oferecer diferentes perspectivas e estilos de conversa aos seus usuários</p>
                    <button class="btn btn-primary" onclick="openPersonalityModal()">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Criar Primeira Personalidade
                    </button>
                </div>
            <?php else: ?>
                <!-- Grid de Personalidades -->
                <div class="personality-grid">
                    <?php foreach ($personalities as $p): ?>
                        <div class="personality-card" style="--card-color: <?= htmlspecialchars($p['avatar_color']) ?>">
                            <div class="personality-header">
                                <div class="personality-avatar-big" style="background: <?= htmlspecialchars($p['avatar_color']) ?>">
                                    <?php if (!empty($p['avatar_image'])): ?>
                                        <img src="<?= htmlspecialchars($p['avatar_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                    <?php else: ?>
                                        <span class="avatar-letter"><?= strtoupper(substr($p['name'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="personality-info">
                                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                                    <div class="personality-order-row">
                                        <span class="personality-order-label">Ordem:</span>
                                        <input 
                                            type="number" 
                                            class="order-input" 
                                            value="<?= $p['sort_order'] ?>" 
                                            onchange="updateOrder(<?= $p['id'] ?>, this.value)"
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <div class="personality-description">
                                <?= htmlspecialchars($p['description']) ?>
                            </div>
                            
                            <div class="prompt-preview" title="Clique em Editar para ver o prompt completo">
                                <?= htmlspecialchars(substr($p['system_prompt'], 0, 180)) ?>...
                            </div>
                            
                            <div class="personality-meta">
                                <?php if ($p['is_active']): ?>
                                    <span class="meta-badge active">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="4"/>
                                        </svg>
                                        Ativa
                                    </span>
                                <?php else: ?>
                                    <span class="meta-badge inactive">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="4"/>
                                        </svg>
                                        Inativa
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($p['avatar_image'])): ?>
                                    <span class="meta-badge">
                                        📸 Com Foto
                                    </span>
                                <?php else: ?>
                                    <span class="meta-badge">
                                        🎨 <?= htmlspecialchars($p['avatar_color']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="personality-actions">
                                <button class="btn-card edit" onclick='editPersonality(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                    Editar
                                </button>
                                
                                <button class="btn-card delete" onclick="deletePersonality(<?= $p['id'] ?>)">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Excluir
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- Modal Criar/Editar Personalidade -->
    <div class="modal" id="personalityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Nova Personalidade</h3>
                <button class="modal-close" onclick="closePersonalityModal()">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            
            <form id="personalityForm" onsubmit="savePersonality(event)">
                <input type="hidden" id="personality_id" name="id">
                <input type="hidden" id="current_avatar_image" name="current_avatar_image">
                
                <div class="modal-body">
                    
                    <!-- Upload de Imagem -->
                    <div class="form-group">
                        <label>📸 Foto da Personalidade</label>
                        <div class="image-upload-container">
                            <div class="image-preview-box" id="imagePreview">
                                <div class="image-preview-placeholder" id="imagePlaceholder">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                    <p>Sem foto</p>
                                </div>
                            </div>
                            <div class="image-upload-controls">
                                <input type="file" id="avatar_image_input" accept="image/*" onchange="previewImage(event)">
                                <button type="button" class="btn-upload" onclick="document.getElementById('avatar_image_input').click()">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/>
                                        <path d="M9 13h2v5a1 1 0 11-2 0v-5z"/>
                                    </svg>
                                    Escolher Foto
                                </button>
                                <button type="button" class="btn-remove-image" id="removeImageBtn" style="display: none;" onclick="removeImage()">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Remover Foto
                                </button>
                                <small>
                                    💡 <strong>Tamanho recomendado:</strong> 400x400px ou superior (formato quadrado)<br>
                                    📌 Formatos aceitos: JPG, PNG, GIF (máx. 2MB)
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Nome da Personalidade *</label>
                        <input type="text" id="name" name="name" required maxlength="100" placeholder="Ex: O Sábio, A Intuição, O Estrategista...">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Descrição Curta</label>
                        <input type="text" id="description" name="description" maxlength="255" placeholder="Uma breve descrição dessa personalidade">
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar_color">Cor do Avatar (Fallback)</label>
                        <div class="color-preview-row">
                            <input type="color" id="avatar_color" name="avatar_color" value="#059669">
                            <span class="color-code" id="colorPreview">#059669</span>
                        </div>
                        <small>💡 Esta cor será usada se não houver foto ou como cor de destaque</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_order">Ordem de Exibição</label>
                        <input type="number" id="sort_order" name="sort_order" value="0" min="0" style="width: 140px;">
                        <small>💡 Quanto menor o número, mais no topo aparece</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="system_prompt">System Prompt (Instruções para a IA) *</label>
                        <textarea id="system_prompt" name="system_prompt" required rows="14" placeholder="Digite aqui as instruções completas de como essa personalidade deve se comportar. Não há limite de tamanho - seja o mais detalhado que precisar!"></textarea>
                        <small>
                            💡 <strong>Dica:</strong> Quanto mais detalhado o prompt, melhor será a experiência. Sem limites de caracteres!
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <span style="font-weight: 600; color: #374151;">Personalidade ativa (visível para usuários)</span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePersonalityModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Salvar Personalidade
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Processando...</div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Variável global para armazenar a imagem carregada
        let uploadedImageFile = null;
        
        // Atualizar preview da cor
        document.getElementById('avatar_color').addEventListener('input', function(e) {
            document.getElementById('colorPreview').textContent = e.target.value;
        });
        
        // Preview da imagem
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Validar tamanho (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('❌ A imagem deve ter no máximo 2MB');
                    event.target.value = '';
                    return;
                }
                
                // Validar tipo
                if (!file.type.match('image.*')) {
                    alert('❌ Por favor, selecione apenas imagens');
                    event.target.value = '';
                    return;
                }
                
                uploadedImageFile = file;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewBox = document.getElementById('imagePreview');
                    previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    document.getElementById('removeImageBtn').style.display = 'inline-flex';
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Remover imagem
        function removeImage() {
            uploadedImageFile = null;
            document.getElementById('avatar_image_input').value = '';
            document.getElementById('current_avatar_image').value = '';
            document.getElementById('imagePreview').innerHTML = `
                <div class="image-preview-placeholder" id="imagePlaceholder">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                    <p>Sem foto</p>
                </div>
            `;
            document.getElementById('removeImageBtn').style.display = 'none';
        }
        
        function openPersonalityModal() {
            document.getElementById('modalTitle').textContent = 'Nova Personalidade';
            document.getElementById('personalityForm').reset();
            document.getElementById('personality_id').value = '';
            document.getElementById('current_avatar_image').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('avatar_color').value = '#059669';
            document.getElementById('colorPreview').textContent = '#059669';
            removeImage();
            document.getElementById('personalityModal').classList.add('active');
        }
        
        function closePersonalityModal() {
            document.getElementById('personalityModal').classList.remove('active');
            uploadedImageFile = null;
        }
        
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
            
            // Configurar imagem atual
            if (personality.avatar_image) {
                document.getElementById('current_avatar_image').value = personality.avatar_image;
                document.getElementById('imagePreview').innerHTML = `<img src="${personality.avatar_image}" alt="${personality.name}">`;
                document.getElementById('removeImageBtn').style.display = 'inline-flex';
            } else {
                removeImage();
            }
            
            document.getElementById('personalityModal').classList.add('active');
        }
        
        async function updateOrder(id, order) {
            try {
                const response = await fetch('api/update_personality_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        sort_order: order
                    })
                });
                
                const text = await response.text();
                console.log('Update Order Response:', text);
                
                const data = JSON.parse(text);
                
                if(data.success) {
                    alert('✅ Ordem atualizada com sucesso!');
                } else {
                    alert('❌ Erro ao atualizar ordem: ' + data.message);
                }
            } catch(error) {
                console.error('Erro ao atualizar ordem:', error);
                alert('❌ Erro ao atualizar ordem');
            }
        }
        
        async function deletePersonality(id) {
            if(confirm('⚠️ Tem certeza que deseja excluir esta personalidade?\n\nEsta ação não pode ser desfeita.')) {
                document.getElementById('loadingOverlay').classList.add('active');
                
                try {
                    const response = await fetch('api/delete_personality.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const text = await response.text();
                    console.log('Delete Response:', text);
                    
                    const data = JSON.parse(text);
                    
                    document.getElementById('loadingOverlay').classList.remove('active');
                    
                    if(data.success) {
                        alert('✅ Personalidade excluída com sucesso!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('❌ Erro ao excluir personalidade: ' + data.message);
                    }
                } catch(error) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    console.error('Erro ao excluir:', error);
                    alert('❌ Erro ao excluir personalidade');
                }
            }
        }
        
        // ⭐ VERSÃO COM DEBUG COMPLETO
        async function savePersonality(e) {
            e.preventDefault();
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            const formData = new FormData(e.target);
            
            // Se houver uma nova imagem, fazer upload primeiro
            if (uploadedImageFile) {
                const imageFormData = new FormData();
                imageFormData.append('image', uploadedImageFile);
                
                try {
                    console.log('📤 Fazendo upload da imagem...');
                    
                    const uploadResponse = await fetch('api/upload_personality_image.php', {
                        method: 'POST',
                        body: imageFormData
                    });
                    
                    console.log('📊 Upload Status:', uploadResponse.status);
                    
                    const uploadText = await uploadResponse.text();
                    console.log('📄 Upload Response RAW:', uploadText);
                    
                    const uploadData = JSON.parse(uploadText);
                    console.log('✅ Upload Data:', uploadData);
                    
                    if (uploadData.success) {
                        formData.set('avatar_image', uploadData.image_url);
                    } else {
                        throw new Error(uploadData.message || 'Erro ao fazer upload da imagem');
                    }
                } catch (error) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    console.error('❌ Erro no upload:', error);
                    alert('❌ ' + error.message);
                    return;
                }
            }
            
            // Converter FormData para objeto
            const data = Object.fromEntries(formData.entries());
            data.is_active = document.getElementById('is_active').checked ? 1 : 0;
            
            // Se removeu a imagem, enviar vazio
            if (!uploadedImageFile && !document.getElementById('current_avatar_image').value) {
                data.avatar_image = '';
            }
            
            const url = data.id ? 'api/update_personality.php' : 'api/create_personality.php';
            
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('📤 ENVIANDO PARA:', url);
            console.log('📦 DADOS:', data);
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.log('📊 STATUS:', response.status, response.statusText);
                console.log('📋 CONTENT-TYPE:', response.headers.get('content-type'));
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                
                // ⭐ LER RESPOSTA COMO TEXTO PRIMEIRO
                const responseText = await response.text();
                console.log('📄 RESPOSTA RAW:');
                console.log(responseText);
                console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                
                document.getElementById('loadingOverlay').classList.remove('active');
                
                // Verificar se resposta está vazia
                if (!responseText || responseText.trim() === '') {
                    console.error('❌ RESPOSTA VAZIA!');
                    alert('❌ Servidor retornou resposta vazia. Verifique os logs do PHP.');
                    return;
                }
                
                // Tentar fazer parse
                try {
                    const result = JSON.parse(responseText);
                    console.log('✅ JSON PARSEADO:');
                    console.log(result);
                    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    
                    if (result.success) {
                        alert('✅ ' + result.message);
                        
                        // Se tiver warning sobre coluna
                        if (result.warning) {
                            console.warn('⚠️ ' + result.warning);
                        }
                        
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('❌ ' + result.message);
                        if (result.error) {
                            console.error('🔴 Erro detalhado:', result.error);
                        }
                    }
                } catch (parseError) {
                    console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    console.error('❌ ERRO AO FAZER PARSE DO JSON!');
                    console.error('Erro:', parseError.message);
                    console.error('Texto recebido:', responseText);
                    console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                    
                    alert('❌ Resposta inválida do servidor!\n\nAbra o Console (F12) e veja os detalhes.');
                }
                
            } catch (error) {
                document.getElementById('loadingOverlay').classList.remove('active');
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                console.error('❌ ERRO NA REQUISIÇÃO!');
                console.error(error);
                console.error('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
                alert('❌ Erro: ' + error.message);
            }
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('personalityModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closePersonalityModal();
            }
        });
    </script>
</body>
</html>