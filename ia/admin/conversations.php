<?php
/**
 * Admin - Gerenciamento de Conversas
 * Permite visualizar, filtrar e gerenciar todas as conversas do sistema
 */

require_once 'config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filtros
$search = $_GET['search'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$personality_id = $_GET['personality_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Construir query base
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.title LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($user_id) {
    $where_conditions[] = "c.user_id = ?";
    $params[] = $user_id;
}

if ($personality_id) {
    $where_conditions[] = "c.personality_id = ?";
    $params[] = $personality_id;
}

if ($date_from) {
    $where_conditions[] = "DATE(c.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(c.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar total de registros
$count_query = "
    SELECT COUNT(*) as total 
    FROM conversations c
    JOIN users u ON c.user_id = u.id
    JOIN personalities p ON c.personality_id = p.id
    $where_clause
";

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_conversations = $stmt->fetchColumn();
$total_pages = ceil($total_conversations / $per_page);

// Buscar conversas com paginação
$query = "
    SELECT 
        c.*,
        u.name as user_name,
        u.email as user_email,
        u.avatar_url as user_avatar,
        p.name as personality_name,
        p.avatar_color,
        p.avatar_image,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count,
        (SELECT MAX(created_at) FROM messages WHERE conversation_id = c.id) as last_message_at,
        (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM conversations c
    JOIN users u ON c.user_id = u.id
    JOIN personalities p ON c.personality_id = p.id
    $where_clause
    ORDER BY c.updated_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar lista de usuários para filtro
$users_list = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll();

// Buscar lista de personalidades para filtro
$personalities_list = $pdo->query("SELECT id, name FROM personalities WHERE is_active = 1 ORDER BY name")->fetchAll();

// Estatísticas
$stats = [
    'total_today' => $pdo->query("SELECT COUNT(*) FROM conversations WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'active_week' => $pdo->query("SELECT COUNT(*) FROM conversations WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'avg_messages' => $pdo->query("SELECT AVG(msg_count) FROM (SELECT COUNT(*) as msg_count FROM messages GROUP BY conversation_id) as t")->fetchColumn() ?: 0,
];

// Função auxiliar para formatar tempo
function timeAgo($datetime) {
    if (!$datetime) return 'Nunca';
    
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . ' h atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    return date('d/m/Y', $time);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversas - Pipo Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estatísticas Premium */
        .stats-premium-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .stat-premium.gradient-blue {
            background: linear-gradient(135deg, #667eea 0%, #4c63b6 100%);
        }
        
        .stat-premium.gradient-green {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .stat-premium.gradient-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .stat-premium-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .stat-premium-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-premium-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .stat-premium-number {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        
        .stat-premium-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        /* Filtros */
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .filter-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .filters-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-filter.primary {
            background: #059669;
            color: white;
        }
        
        .btn-filter.primary:hover {
            background: #047857;
        }
        
        .btn-filter.secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-filter.secondary:hover {
            background: #e5e7eb;
        }
        
        /* Tabela de Conversas */
        .conversations-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        
        .table-info {
            font-size: 14px;
            color: #6b7280;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead th {
            text-align: left;
            padding: 16px;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tbody td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        /* User Info Cell */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-details {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Personality Info */
        .personality-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .personality-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .personality-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Message Preview */
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #6b7280;
            font-size: 13px;
        }
        
        /* Stats Badge */
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #f3f4f6;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }
        
        .stats-badge svg {
            width: 14px;
            height: 14px;
        }
        
        /* Actions */
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .btn-action {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-action svg {
            width: 14px;
            height: 14px;
        }
        
        .btn-action.view {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .btn-action.view:hover {
            background: #bae6fd;
        }
        
        .btn-action.delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-action.delete:hover {
            background: #fecaca;
        }
        
        /* Paginação */
        .pagination-container {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .pagination-info {
            font-size: 14px;
            color: #6b7280;
        }
        
        .pagination-buttons {
            display: flex;
            gap: 8px;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .pagination-btn.active {
            background: #059669;
            color: white;
            border-color: #059669;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Empty State */
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
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: #e5e7eb;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        /* Messages Container */
        .messages-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .message {
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .message.user .message-content {
            background: #059669;
            color: white;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .message.user .message-header {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .message-text {
            font-size: 14px;
            line-height: 1.5;
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
                <h1>💬 Conversas</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            
            <!-- Stats Premium -->
            <div class="stats-premium-grid">
                <div class="stat-premium gradient-blue">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= number_format($total_conversations) ?></div>
                    </div>
                    <div class="stat-premium-label">Total de Conversas</div>
                </div>
                
                <div class="stat-premium gradient-green">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= number_format($stats['total_today']) ?></div>
                    </div>
                    <div class="stat-premium-label">Conversas Hoje</div>
                </div>
                
                <div class="stat-premium gradient-orange">
                    <div class="stat-premium-top">
                        <div class="stat-premium-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="stat-premium-number"><?= number_format($stats['active_week']) ?></div>
                    </div>
                    <div class="stat-premium-label">Ativas (7 dias)</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="search">🔍 Pesquisar</label>
                            <input type="text" id="search" name="search" placeholder="Título, usuário ou personalidade..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="user_id">👤 Usuário</label>
                            <select id="user_id" name="user_id">
                                <option value="">Todos os usuários</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user_id == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="personality_id">🎭 Personalidade</label>
                            <select id="personality_id" name="personality_id">
                                <option value="">Todas as personalidades</option>
                                <?php foreach ($personalities_list as $personality): ?>
                                    <option value="<?= $personality['id'] ?>" <?= $personality_id == $personality['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($personality['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">📅 Data Inicial</label>
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">📅 Data Final</label>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                    </div>
                    
                    <div class="filters-actions">
                        <button type="submit" class="btn-filter primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="conversations.php" class="btn-filter secondary">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Conversas -->
            <div class="conversations-table">
                <div class="table-header">
                    <h3 class="table-title">Lista de Conversas</h3>
                    <span class="table-info"><?= number_format($total_conversations) ?> conversas encontradas</span>
                </div>
                
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                        <h3>Nenhuma conversa encontrada</h3>
                        <p>Ajuste os filtros ou aguarde novas conversas dos usuários</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Personalidade</th>
                                <th>Título</th>
                                <th>Última Mensagem</th>
                                <th>Mensagens</th>
                                <th>Atualizada</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conversations as $conv): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <?php if ($conv['user_avatar']): ?>
                                                <img src="<?= htmlspecialchars($conv['user_avatar']) ?>" alt="" class="user-avatar">
                                            <?php else: ?>
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($conv['user_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="user-details">
                                                <div class="user-name"><?= htmlspecialchars($conv['user_name']) ?></div>
                                                <div class="user-email"><?= htmlspecialchars($conv['user_email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="personality-info">
                                            <?php if ($conv['avatar_image']): ?>
                                                <img src="<?= htmlspecialchars($conv['avatar_image']) ?>" alt="" class="personality-avatar">
                                            <?php else: ?>
                                                <div class="personality-avatar" style="background-color: <?= htmlspecialchars($conv['avatar_color']) ?>;">
                                                    <?= mb_substr($conv['personality_name'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="personality-name"><?= htmlspecialchars($conv['personality_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($conv['title']) ?></td>
                                    <td>
                                        <div class="message-preview">
                                            <?= htmlspecialchars($conv['last_message'] ?: 'Sem mensagens') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stats-badge">
                                            <svg fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"/>
                                            </svg>
                                            <?= number_format($conv['message_count']) ?>
                                        </div>
                                    </td>
                                    <td><?= timeAgo($conv['last_message_at'] ?: $conv['updated_at']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action view" onclick="viewConversation(<?= $conv['id'] ?>)">
                                                <svg fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Ver
                                            </button>
                                            <button class="btn-action delete" onclick="deleteConversation(<?= $conv['id'] ?>)">
                                                <svg fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Paginação -->
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Mostrando <?= $offset + 1 ?> - <?= min($offset + $per_page, $total_conversations) ?> de <?= $total_conversations ?> conversas
                        </div>
                        <div class="pagination-buttons">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter(['search' => $search, 'user_id' => $user_id, 'personality_id' => $personality_id, 'date_from' => $date_from, 'date_to' => $date_to])) ?>" class="pagination-btn">
                                    ← Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?page=<?= $i ?>&<?= http_build_query(array_filter(['search' => $search, 'user_id' => $user_id, 'personality_id' => $personality_id, 'date_from' => $date_from, 'date_to' => $date_to])) ?>" 
                                   class="pagination-btn <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter(['search' => $search, 'user_id' => $user_id, 'personality_id' => $personality_id, 'date_from' => $date_from, 'date_to' => $date_to])) ?>" class="pagination-btn">
                                    Próxima →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <!-- Modal de Visualização -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Visualizar Conversa</h3>
                <button class="modal-close" onclick="closeModal()">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="messagesContainer" class="messages-container">
                    <!-- Mensagens serão carregadas aqui via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Função para visualizar conversa
        async function viewConversation(conversationId) {
            const modal = document.getElementById('viewModal');
            const messagesContainer = document.getElementById('messagesContainer');
            
            // Mostrar modal
            modal.classList.add('active');
            
            // Mostrar loading
            messagesContainer.innerHTML = '<div style="text-align: center; padding: 40px;">Carregando mensagens...</div>';
            
            try {
                // Buscar mensagens via API
                const response = await fetch(`api/get_conversation_messages.php?id=${conversationId}`);
                const data = await response.json();
                
                if (data.success && data.messages) {
                    // Renderizar mensagens
                    let html = '';
                    data.messages.forEach(msg => {
                        const isUser = msg.role === 'user';
                        html += `
                            <div class="message ${isUser ? 'user' : 'assistant'}">
                                <div class="message-content">
                                    <div class="message-header">
                                        <span>${isUser ? 'Usuário' : data.personality_name}</span>
                                        <span>${formatDate(msg.created_at)}</span>
                                    </div>
                                    <div class="message-text">${escapeHtml(msg.content)}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    messagesContainer.innerHTML = html || '<div style="text-align: center; padding: 40px;">Nenhuma mensagem nesta conversa</div>';
                } else {
                    messagesContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;">Erro ao carregar mensagens</div>';
                }
            } catch (error) {
                console.error('Erro:', error);
                messagesContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;">Erro ao carregar mensagens</div>';
            }
        }
        
        // Fechar modal
        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        // Excluir conversa
        async function deleteConversation(conversationId) {
            if (!confirm('Tem certeza que deseja excluir esta conversa? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            try {
                const response = await fetch('api/delete_conversation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ conversation_id: conversationId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao excluir conversa: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir conversa');
            }
        }
        
        // Formatar data
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>