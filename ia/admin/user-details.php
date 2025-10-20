<?php
// Habilitar exibição de erros (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verificar se config.php existe
if (!file_exists('config.php')) {
    die('Erro: Arquivo config.php não encontrado!');
}

require_once 'config.php';

// Verificar se é admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Verificar se PDO está configurado
if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Erro: Conexão com banco de dados não foi estabelecida no config.php');
}

// Pegar ID do usuário
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: users.php');
    exit;
}

// Inicializar variáveis
$user = null;
$stats = [
    'total_conversations' => 0,
    'total_messages' => 0,
    'last_activity' => null,
    'active_conversations' => 0
];
$recent_conversations = [];
$error = null;

// Buscar informações do usuário
try {
    // Verificar se a tabela users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        die('Erro: Tabela "users" não existe no banco de dados!');
    }
    
    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: users.php?error=user_not_found');
        exit;
    }
    
    // Garantir que campos existam (compatibilidade)
    $user['status'] = $user['status'] ?? 'active';
    $user['role'] = $user['role'] ?? 'user';
    $user['avatar'] = $user['avatar'] ?? null;
    $user['bio'] = $user['bio'] ?? '';
    $user['last_login'] = $user['last_login'] ?? null;
    
    // === ESTATÍSTICAS COM VERIFICAÇÃO DE TABELAS ===
    
    // 1. Total de conversas
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'conversations'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversations WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stats['total_conversations'] = (int)$stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $error .= "Erro ao contar conversas: " . $e->getMessage() . "<br>";
    }
    
    // 2. Total de mensagens
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM messages m
                INNER JOIN conversations c ON m.conversation_id = c.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $stats['total_messages'] = (int)$stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $error .= "Erro ao contar mensagens: " . $e->getMessage() . "<br>";
    }
    
    // 3. Última atividade
    try {
        $stmt = $pdo->prepare("
            SELECT MAX(m.created_at) as last_activity
            FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats['last_activity'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $error .= "Erro ao buscar última atividade: " . $e->getMessage() . "<br>";
    }
    
    // 4. Conversas ativas (últimas 7 dias)
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM conversations 
            WHERE user_id = ? 
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);
        $stats['active_conversations'] = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        $error .= "Erro ao contar conversas ativas: " . $e->getMessage() . "<br>";
    }
    
    // 5. Buscar conversas recentes
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'personalities'");
        $hasPersonalities = $stmt->rowCount() > 0;
        
        if ($hasPersonalities) {
            $stmt = $pdo->prepare("
                SELECT c.id, c.title, c.created_at, c.updated_at,
                       p.name as personality_name,
                       (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
                FROM conversations c
                LEFT JOIN personalities p ON c.personality_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.updated_at DESC
                LIMIT 10
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT c.id, c.title, c.created_at, c.updated_at,
                       NULL as personality_name,
                       (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
                FROM conversations c
                WHERE c.user_id = ?
                ORDER BY c.updated_at DESC
                LIMIT 10
            ");
        }
        
        $stmt->execute([$user_id]);
        $recent_conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error .= "Erro ao buscar conversas: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    die("Erro fatal no banco de dados: " . $e->getMessage());
} catch (Exception $e) {
    die("Erro fatal: " . $e->getMessage());
}

// Função para formatar data
function timeAgo($datetime) {
    if (!$datetime) return 'Nunca';
    
    $timestamp = strtotime($datetime);
    if (!$timestamp) return 'Nunca';
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . ' horas atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    
    return date('d/m/Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - <?php echo htmlspecialchars($user['name']); ?> | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Alert de erro */
        .alert-error {
            background: #fee;
            border: 2px solid #f66;
            color: #c00;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .header-title h1 {
            font-size: 1.8rem;
            color: #2d3748;
            font-weight: 700;
        }

        .header-title p {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        /* User Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .profile-avatar {
            position: relative;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 600;
        }

        .status-badge {
            position: absolute;
            bottom: -5px;
            right: -5px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            border: 3px solid white;
        }

        .status-badge.active {
            background: #48bb78;
            color: white;
        }

        .status-badge.inactive {
            background: #f56565;
            color: white;
        }

        .status-badge.banned {
            background: #000;
            color: white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h2 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
            color: #718096;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-meta-item i {
            color: #667eea;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        /* Conversations List */
        .conversations-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h3 {
            font-size: 1.5rem;
            color: #2d3748;
        }

        .conversation-item {
            background: #f7fafc;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .conversation-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }

        .conversation-info h4 {
            color: #2d3748;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .conversation-meta {
            display: flex;
            gap: 1.5rem;
            color: #718096;
            font-size: 0.85rem;
        }

        .conversation-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .conversation-actions {
            display: flex;
            gap: 0.5rem;
        }

        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            background: white;
            color: #718096;
        }

        .icon-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: #2d3748;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #718096;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        /* Loading */
        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem 3rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 2000;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .conversation-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="alert-error">
                <strong>⚠️ Avisos:</strong><br>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="users.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
                <div class="header-title">
                    <h1>Detalhes do Usuário</h1>
                    <p>Informações completas e estatísticas</p>
                </div>
            </div>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="avatar">
                    <?php else: ?>
                        <div class="avatar">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span class="status-badge <?php echo $user['status']; ?>">
                        <?php 
                        $statusLabels = [
                            'active' => 'Ativo',
                            'inactive' => 'Inativo',
                            'banned' => 'Banido'
                        ];
                        echo $statusLabels[$user['status']] ?? 'Ativo'; 
                        ?>
                    </span>
                </div>

                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <div class="profile-meta">
                        <div class="profile-meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Cadastrado em <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Último acesso: <?php echo timeAgo($user['last_login']); ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-shield"></i>
                            <span><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($user['bio'])): ?>
                        <p style="margin-top: 1rem; color: #4a5568;">
                            <?php echo htmlspecialchars($user['bio']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-actions">
                <button class="btn btn-primary" onclick="openEditModal()">
                    <i class="fas fa-edit"></i>
                    Editar Usuário
                </button>
                <?php if ($user['status'] === 'active'): ?>
                    <button class="btn btn-warning" onclick="toggleUserStatus('inactive')">
                        <i class="fas fa-ban"></i>
                        Suspender
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" onclick="toggleUserStatus('active')">
                        <i class="fas fa-check"></i>
                        Ativar
                    </button>
                <?php endif; ?>
                <button class="btn btn-danger" onclick="deleteUser()">
                    <i class="fas fa-trash"></i>
                    Excluir
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total_conversations']; ?></div>
                        <div class="stat-label">Total de Conversas</div>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['total_messages']; ?></div>
                        <div class="stat-label">Total de Mensagens</div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-message"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $stats['active_conversations']; ?></div>
                        <div class="stat-label">Conversas Ativas (7 dias)</div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo timeAgo($stats['last_activity']); ?></div>
                        <div class="stat-label">Última Atividade</div>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversations Section -->
        <div class="conversations-section">
            <div class="section-header">
                <h3><i class="fas fa-comments"></i> Conversas Recentes</h3>
            </div>

            <?php if (count($recent_conversations) > 0): ?>
                <?php foreach ($recent_conversations as $conv): ?>
                    <div class="conversation-item">
                        <div class="conversation-info">
                            <h4><?php echo htmlspecialchars($conv['title'] ?: 'Sem título'); ?></h4>
                            <div class="conversation-meta">
                                <span>
                                    <i class="fas fa-robot"></i>
                                    <?php echo htmlspecialchars($conv['personality_name'] ?: 'Padrão'); ?>
                                </span>
                                <span>
                                    <i class="fas fa-message"></i>
                                    <?php echo $conv['message_count']; ?> mensagens
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i>
                                    <?php echo timeAgo($conv['updated_at']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="conversation-actions">
                            <button class="icon-btn" onclick="viewConversation(<?php echo $conv['id']; ?>)" title="Ver conversa">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="icon-btn" onclick="deleteConversation(<?php echo $conv['id']; ?>)" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>Nenhuma conversa encontrada</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Usuário</h3>
                <button class="close-modal" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editUserForm">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" id="editName" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="editEmail" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="editStatus">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                        <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>Banido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Função</label>
                    <select id="editRole">
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Usuário</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>>Moderador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea id="editBio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p style="text-align: center; margin-top: 1rem; color: #718096;">Processando...</p>
    </div>

    <script>
        const userId = <?php echo $user_id; ?>;

        function openEditModal() {
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        document.getElementById('editUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                id: userId,
                name: document.getElementById('editName').value,
                email: document.getElementById('editEmail').value,
                status: document.getElementById('editStatus').value,
                role: document.getElementById('editRole').value,
                bio: document.getElementById('editBio').value
            };

            showLoading();

            try {
                const response = await fetch('api/update_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    alert('Usuário atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + (result.message || 'Falha ao atualizar usuário'));
                }
            } catch (error) {
                hideLoading();
                alert('Erro ao atualizar usuário: ' + error.message);
            }
        });

        async function toggleUserStatus(newStatus) {
            const action = newStatus === 'active' ? 'ativar' : 'suspender';
            
            if (!confirm(`Deseja realmente ${action} este usuário?`)) {
                return;
            }

            showLoading();

            try {
                const response = await fetch('api/update_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: userId,
                        status: newStatus
                    })
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    alert(`Usuário ${action === 'ativar' ? 'ativado' : 'suspenso'} com sucesso!`);
                    location.reload();
                } else {
                    alert('Erro: ' + (result.message || 'Falha ao atualizar status'));
                }
            } catch (error) {
                hideLoading();
                alert('Erro ao atualizar status: ' + error.message);
            }
        }

        async function deleteUser() {
            if (!confirm('⚠️ ATENÇÃO! Esta ação é IRREVERSÍVEL!\n\nTodas as conversas e mensagens deste usuário serão excluídas permanentemente.\n\nDeseja realmente excluir este usuário?')) {
                return;
            }

            showLoading();

            try {
                const response = await fetch('api/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: userId })
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    alert('Usuário excluído com sucesso!');
                    window.location.href = 'users.php';
                } else {
                    alert('Erro: ' + (result.message || 'Falha ao excluir usuário'));
                }
            } catch (error) {
                hideLoading();
                alert('Erro ao excluir usuário: ' + error.message);
            }
        }

        function viewConversation(convId) {
            window.open(`../chat.php?conversation_id=${convId}`, '_blank');
        }

        async function deleteConversation(convId) {
            if (!confirm('Deseja realmente excluir esta conversa?')) {
                return;
            }

            showLoading();

            try {
                const response = await fetch('api/delete_conversation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: convId })
                });

                const result = await response.json();
                hideLoading();

                if (result.success) {
                    alert('Conversa excluída com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + (result.message || 'Falha ao excluir conversa'));
                }
            } catch (error) {
                hideLoading();
                alert('Erro ao excluir conversa: ' + error.message);
            }
        }

        function showLoading() {
            document.getElementById('loading').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('active');
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>