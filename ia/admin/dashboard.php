<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Buscar estatísticas
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'total_conversations' => $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn(),
    'total_messages' => $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
];

// Buscar usuários recentes
$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pipo Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* Melhorias visuais específicas para o dashboard */
        .dashboard-welcome {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            color: white;
            box-shadow: 0 10px 40px rgba(5, 150, 105, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .dashboard-welcome h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .dashboard-welcome p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card-modern {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--card-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card-modern:hover::before {
            transform: scaleY(1);
        }
        
        .stat-icon-modern {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            background: var(--icon-bg);
            box-shadow: 0 4px 12px var(--icon-shadow);
        }
        
        .stat-icon-modern svg {
            width: 28px;
            height: 28px;
            color: var(--icon-color);
        }
        
        .stat-card-modern.primary {
            --card-color: #059669;
            --icon-bg: linear-gradient(135deg, #059669 0%, #047857 100%);
            --icon-shadow: rgba(5, 150, 105, 0.3);
            --icon-color: white;
        }
        
        .stat-card-modern.gold {
            --card-color: #d4af37;
            --icon-bg: linear-gradient(135deg, #d4af37 0%, #b8963c 100%);
            --icon-shadow: rgba(212, 175, 55, 0.3);
            --icon-color: white;
        }
        
        .stat-card-modern.success {
            --card-color: #10b981;
            --icon-bg: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --icon-shadow: rgba(16, 185, 129, 0.3);
            --icon-color: white;
        }
        
        .stat-card-modern.info {
            --card-color: #3b82f6;
            --icon-bg: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --icon-shadow: rgba(59, 130, 246, 0.3);
            --icon-color: white;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .stat-trend.up {
            color: #10b981;
        }
        
        .stat-trend.down {
            color: #ef4444;
        }
        
        .card-modern {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .card-header-modern {
            padding: 24px 28px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: between;
            background: linear-gradient(to bottom, #fafbfc, white);
        }
        
        .card-title-modern {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        
        .table-modern {
            width: 100%;
        }
        
        .table-modern thead {
            background: #f9fafb;
        }
        
        .table-modern th {
            padding: 16px 24px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table-modern td {
            padding: 20px 24px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        
        .table-modern tr:hover {
            background: #fafbfc;
        }
        
        .table-modern tr:last-child td {
            border-bottom: none;
        }
        
        .badge-modern {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            gap: 4px;
        }
        
        .badge-modern.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-modern.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-modern.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-modern svg {
            width: 10px;
            height: 10px;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            transform: scale(1.1);
        }
        
        .btn-icon.view {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .btn-icon.view:hover {
            background: #bae6fd;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.3;
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
                <h1>Dashboard</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='personalities.php'">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Nova Personalidade
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            
            <!-- Welcome Banner -->
            <div class="dashboard-welcome">
                <h2>Bem-vindo de volta, <?= htmlspecialchars($_SESSION['admin_name']) ?>! 👋</h2>
                <p>Aqui está um resumo do seu sistema Pipo</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card-modern primary">
                    <div class="stat-icon-modern">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total de Usuários</div>
                    <div class="stat-trend up">
                        <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                        Crescendo
                    </div>
                </div>
                
                <div class="stat-card-modern success">
                    <div class="stat-icon-modern">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-number"><?= number_format($stats['active_users']) ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
                
                <div class="stat-card-modern gold">
                    <div class="stat-icon-modern">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_conversations']) ?></div>
                    <div class="stat-label">Conversas Iniciadas</div>
                </div>
                
                <div class="stat-card-modern info">
                    <div class="stat-icon-modern">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                        </svg>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_messages']) ?></div>
                    <div class="stat-label">Mensagens Trocadas</div>
                </div>
            </div>
            
            <!-- Usuários Recentes -->
            <div class="card-modern">
                <div class="card-header-modern" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title-modern">Usuários Recentes</h3>
                    <a href="users.php" class="btn btn-secondary btn-sm">
                        Ver Todos
                        <svg fill="currentColor" viewBox="0 0 20 20" style="width: 14px; height: 14px;">
                            <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>
                
                <?php if (empty($recentUsers)): ?>
                    <div class="empty-state">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <p style="font-size: 16px; margin-bottom: 8px;">Nenhum usuário cadastrado ainda</p>
                        <p style="font-size: 14px;">Os usuários aparecerão aqui quando começarem a usar o Pipo</p>
                    </div>
                <?php else: ?>
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th style="text-align: center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #059669, #d4af37); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: #111827;">
                                                <?= htmlspecialchars($user['name'] ?? 'Sem nome') ?>
                                            </div>
                                            <div style="font-size: 12px; color: #6b7280;">
                                                ID: #<?= $user['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: #6b7280;">
                                    <?= htmlspecialchars($user['email']) ?>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge-modern success">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="3"/>
                                            </svg>
                                            Ativo
                                        </span>
                                    <?php elseif ($user['status'] === 'inactive'): ?>
                                        <span class="badge-modern warning">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="3"/>
                                            </svg>
                                            Inativo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-modern danger">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="3"/>
                                            </svg>
                                            Bloqueado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #6b7280;">
                                    <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                    <br>
                                    <span style="font-size: 12px; color: #9ca3af;">
                                        <?= date('H:i', strtotime($user['created_at'])) ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn-icon view" onclick="viewUser(<?= $user['id'] ?>)" title="Ver detalhes">
                                        <svg fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>