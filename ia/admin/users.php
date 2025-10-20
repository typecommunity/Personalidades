<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Filtros
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Query base
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

// Filtro de status
if ($status !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

// Filtro de busca
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Estatísticas
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$blockedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
$paidUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE subscription_status = 'paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Pipo Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* Estilos modernos */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        .stat-card-modern {
            background: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
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
            background: var(--accent-color);
        }
        
        .stat-card-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-modern.green { --accent-color: #059669; }
        .stat-card-modern.gold { --accent-color: #d4af37; }
        .stat-card-modern.red { --accent-color: #ef4444; }
        .stat-card-modern.blue { --accent-color: #3b82f6; }
        
        .stat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .stat-icon-small {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--icon-bg);
        }
        
        .stat-icon-small svg {
            width: 22px;
            height: 22px;
            color: white;
        }
        
        .stat-card-modern.green .stat-icon-small { background: linear-gradient(135deg, #059669, #047857); }
        .stat-card-modern.gold .stat-icon-small { background: linear-gradient(135deg, #d4af37, #b8963c); }
        .stat-card-modern.red .stat-icon-small { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card-modern.blue .stat-icon-small { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        
        .stat-number-big {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }
        
        .stat-label-small {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            margin-top: 4px;
        }
        
        .filter-card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0f0f0;
            margin-bottom: 24px;
        }
        
        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form input {
            flex: 1;
            min-width: 250px;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .filter-form input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .filter-form select {
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-form select:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .table-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0f0f0;
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-title {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }
        
        .table-modern {
            width: 100%;
        }
        
        .table-modern thead {
            background: #fafbfc;
        }
        
        .table-modern th {
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table-modern td {
            padding: 18px 20px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
        }
        
        .table-modern tr:hover {
            background: #fafbfc;
        }
        
        .table-modern tr:last-child td {
            border-bottom: none;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #d4af37);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .user-info-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .user-info-id {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .badge-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            gap: 4px;
        }
        
        .badge-pill svg {
            width: 8px;
            height: 8px;
        }
        
        .badge-pill.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-pill.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-pill.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-pill.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-pill.gray {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-action.view {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .btn-action.view:hover {
            background: #bae6fd;
        }
        
        .btn-action.block {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-action.block:hover {
            background: #fecaca;
        }
        
        .btn-action.unblock {
            background: #d1fae5;
            color: #065f46;
        }
        
        .btn-action.unblock:hover {
            background: #a7f3d0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            opacity: 0.2;
        }
        
        .empty-state h3 {
            font-size: 18px;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #9ca3af;
            font-size: 14px;
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
                <h1>Gerenciar Usuários</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openUserModal()">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 18px; height: 18px;">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                    </svg>
                    Novo Usuário
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card-modern green">
                    <div class="stat-header">
                        <div class="stat-icon-small">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="stat-number-big"><?= number_format($totalUsers) ?></div>
                            <div class="stat-label-small">Total de Usuários</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card-modern blue">
                    <div class="stat-header">
                        <div class="stat-icon-small">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="stat-number-big"><?= number_format($activeUsers) ?></div>
                            <div class="stat-label-small">Usuários Ativos</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card-modern gold">
                    <div class="stat-header">
                        <div class="stat-icon-small">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="stat-number-big"><?= number_format($paidUsers) ?></div>
                            <div class="stat-label-small">Assinantes Pagos</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card-modern red">
                    <div class="stat-header">
                        <div class="stat-icon-small">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div class="stat-number-big"><?= number_format($blockedUsers) ?></div>
                            <div class="stat-label-small">Usuários Bloqueados</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="🔍 Buscar por nome ou email..." 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                    
                    <select name="status">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>📊 Todos os Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>✅ Ativos</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>⏸️ Inativos</option>
                        <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>🚫 Bloqueados</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        Filtrar
                    </button>
                    
                    <?php if ($search || $status !== 'all'): ?>
                        <a href="users.php" class="btn btn-secondary">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Tabela -->
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Lista de Usuários (<?= count($users) ?>)</h3>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Tente ajustar os filtros ou adicione um novo usuário</p>
                    </div>
                <?php else: ?>
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Assinatura</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="user-info-name"><?= htmlspecialchars($user['name'] ?? 'Sem nome') ?></div>
                                            <div class="user-info-id">ID #<?= $user['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: #6b7280;"><?= htmlspecialchars($user['email']) ?></td>
                                <td style="color: #6b7280;"><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge-pill success">
                                            <svg fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                            Ativo
                                        </span>
                                    <?php elseif ($user['status'] === 'inactive'): ?>
                                        <span class="badge-pill warning">
                                            <svg fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                            Inativo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill danger">
                                            <svg fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="4"/></svg>
                                            Bloqueado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['subscription_status'] === 'paid'): ?>
                                        <span class="badge-pill success">💳 Pago</span>
                                    <?php elseif ($user['subscription_status'] === 'trial'): ?>
                                        <span class="badge-pill info">🎁 Trial</span>
                                    <?php elseif ($user['subscription_status'] === 'expired'): ?>
                                        <span class="badge-pill danger">⏰ Expirado</span>
                                    <?php else: ?>
                                        <span class="badge-pill gray">Grátis</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #6b7280;">
                                    <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                    <br>
                                    <span style="font-size: 12px; color: #9ca3af;"><?= date('H:i', strtotime($user['created_at'])) ?></span>
                                </td>
                                <td style="color: #6b7280;">
                                    <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?>
                                </td>
                                <td>
                                    <div class="action-buttons" style="justify-content: center;">
                                        <button class="btn-action view" onclick="viewUserDetails(<?= $user['id'] ?>)" title="Ver detalhes">
                                            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                        <?php if ($user['status'] !== 'blocked'): ?>
                                            <button class="btn-action block" onclick="toggleUserStatus(<?= $user['id'] ?>, 'blocked')" title="Bloquear">
                                                <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                                                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action unblock" onclick="toggleUserStatus(<?= $user['id'] ?>, 'active')" title="Desbloquear">
                                                <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <!-- Modal Criar Usuário -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Novo Usuário</h3>
                <button class="modal-close" onclick="closeUserModal()">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width: 24px; height: 24px;">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            
            <form id="userForm" onsubmit="createUser(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_name">Nome Completo *</label>
                        <input type="text" id="user_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_email">Email *</label>
                        <input type="email" id="user_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_password">Senha *</label>
                        <input type="password" id="user_password" name="password" required minlength="6">
                        <small style="color: var(--text-secondary); font-size: 12px;">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_phone">Telefone</label>
                        <input type="tel" id="user_phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="user_status">Status</label>
                        <select id="user_status" name="status">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                        </svg>
                        Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        function openUserModal() {
            document.getElementById('userModal').classList.add('active');
        }
        
        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
            document.getElementById('userForm').reset();
        }
        
        async function createUser(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            try {
                const response = await fetch('api/users/create.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Usuário criado com sucesso!');
                    closeUserModal();
                    location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                alert('Erro ao criar usuário: ' + error.message);
            }
        }
        
        function viewUserDetails(userId) {
            alert('Detalhes do usuário #' + userId + ' (em desenvolvimento)');
        }
    </script>
</body>
</html>