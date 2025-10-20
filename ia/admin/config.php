<?php
/**
 * Configuração Central do Sistema Pipo
 * VERSÃO CORRIGIDA - Resolve loops de redirect
 */

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
    session_start();
}

// =========================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ataw_pipo');
define('DB_USER', 'ataw_pipo');
define('DB_PASS', 'KkMq^fg4gMgo1C0D');
define('DB_CHARSET', 'utf8mb4');

// =========================================
// CONFIGURAÇÕES DE SESSÃO
// =========================================
define('SESSION_LIFETIME', 7200); // 2 horas em segundos

// =========================================
// CONEXÃO COM BANCO DE DADOS (PDO)
// =========================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// =========================================
// FUNÇÕES DE VALIDAÇÃO DE SESSÃO
// =========================================

/**
 * Verifica se é um admin logado
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Verifica se é um usuário comum logado
 * @return bool
 */
function isUser() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se há alguém logado (admin ou usuário)
 * @return bool
 */
function isLoggedIn() {
    return isAdmin() || isUser();
}

/**
 * Obtém o tipo de usuário logado
 * @return string|null 'admin', 'user' ou null
 */
function getUserType() {
    if (isAdmin()) {
        return 'admin';
    }
    if (isUser()) {
        return 'user';
    }
    return null;
}

/**
 * Obtém o ID do usuário logado (admin ou user)
 * @return int|null
 */
function getLoggedUserId() {
    if (isAdmin()) {
        return $_SESSION['admin_id'];
    }
    if (isUser()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Obtém o nome do usuário logado
 * @return string|null
 */
function getLoggedUserName() {
    if (isAdmin()) {
        return $_SESSION['admin_name'] ?? 'Admin';
    }
    if (isUser()) {
        return $_SESSION['user_name'] ?? 'Usuário';
    }
    return null;
}

/**
 * Obtém dados completos do usuário atual
 * @return array|null
 */
function getCurrentUser() {
    if (isAdmin()) {
        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'name' => $_SESSION['admin_name'] ?? 'Admin',
            'email' => $_SESSION['admin_email'] ?? null,
            'avatar' => null
        ];
    }
    if (isUser()) {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? 'Usuário',
            'email' => $_SESSION['user_email'] ?? null,
            'avatar' => null // Tabela users não tem coluna avatar
        ];
    }
    return null;
}

/**
 * Redireciona para uma URL (CORRIGIDO)
 * Aceita tanto caminhos relativos quanto absolutos
 * @param string $url
 */
function redirect($url) {
    // Se começa com http:// ou https://, usar como está
    if (preg_match('/^https?:\/\//', $url)) {
        header('Location: ' . $url);
        exit;
    }
    
    // Remove barra inicial se existir (força caminho relativo)
    $url = ltrim($url, '/');
    
    // Redirecionar para arquivo na mesma pasta
    header('Location: ' . $url);
    exit;
}

/**
 * Valida a sessão do admin verificando no banco
 * @return bool
 */
function validateAdminSession() {
    global $pdo;
    
    if (!isAdmin()) {
        return false;
    }
    
    // Se não tem session_id, considera válido (sessão simples)
    if (!isset($_SESSION['session_id'])) {
        return true;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, a.id as admin_id, a.name, a.email 
            FROM sessions s
            JOIN admins a ON s.admin_id = a.id
            WHERE s.id = ? 
            AND s.user_type = 'admin'
            AND s.expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['session_id']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Sessão inválida ou expirada
            destroySession();
            return false;
        }
        
        // Atualizar dados da sessão se necessário
        $_SESSION['admin_id'] = $session['admin_id'];
        $_SESSION['admin_name'] = $session['name'];
        $_SESSION['admin_email'] = $session['email'];
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erro ao validar sessão admin: " . $e->getMessage());
        // Em caso de erro, mantém sessão se admin_id existe
        return isAdmin();
    }
}

/**
 * Valida a sessão do usuário verificando no banco
 * @return bool
 */
function validateUserSession() {
    global $pdo;
    
    if (!isUser()) {
        return false;
    }
    
    // Se não tem session_id, considera válido (sessão simples)
    if (!isset($_SESSION['session_id'])) {
        return true;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.id as user_id, u.name, u.email 
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ? 
            AND s.user_type = 'user'
            AND s.expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['session_id']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Sessão inválida ou expirada
            destroySession();
            return false;
        }
        
        // Atualizar dados da sessão se necessário
        $_SESSION['user_id'] = $session['user_id'];
        $_SESSION['user_name'] = $session['name'];
        $_SESSION['user_email'] = $session['email'];
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erro ao validar sessão user: " . $e->getMessage());
        // Em caso de erro, mantém sessão se user_id existe
        return isUser();
    }
}

/**
 * Destrói a sessão atual
 */
function destroySession() {
    global $pdo;
    
    // Remover do banco se houver session_id
    if (isset($_SESSION['session_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$_SESSION['session_id']]);
        } catch (PDOException $e) {
            error_log("Erro ao remover sessão do banco: " . $e->getMessage());
        }
    }
    
    // Limpar todas as variáveis de sessão
    $_SESSION = [];
    
    // Destruir cookie de sessão
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir sessão
    session_destroy();
}

/**
 * Redireciona para login se não for admin (CORRIGIDO)
 * @param string $loginFile Nome do arquivo de login (sem barra)
 */
function requireAdmin($loginFile = 'login.php') {
    if (!isAdmin()) {
        redirect($loginFile);
    }
    // Valida sessão mas não redireciona se falhar
    validateAdminSession();
}

/**
 * Redireciona para login se não for usuário (CORRIGIDO)
 * @param string $loginFile Nome do arquivo de login
 */
function requireUser($loginFile = 'login.php') {
    if (!isUser()) {
        redirect($loginFile);
    }
    // Valida sessão mas não redireciona se falhar
    validateUserSession();
}

/**
 * Redireciona para login se não estiver logado (CORRIGIDO)
 * @param string $loginFile Nome do arquivo de login
 */
function requireLogin($loginFile = 'login.php') {
    if (!isLoggedIn()) {
        redirect($loginFile);
    }
}

// =========================================
// FUNÇÃO HELPER PARA CONFIGURAÇÕES
// =========================================

/**
 * Buscar uma configuração do banco
 * @param string $key Chave da configuração
 * @param mixed $default Valor padrão se não existir
 * @return mixed Valor da configuração
 */
function getSetting($key, $default = null) {
    global $pdo;
    
    // Cache estático para evitar queries repetidas
    static $cache = [];
    
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        $value = $result !== false ? $result : $default;
        $cache[$key] = $value;
        
        return $value;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Salvar uma configuração no banco
 * @param string $key Chave
 * @param mixed $value Valor
 * @return bool Sucesso
 */
function setSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (`key`, `value`) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE `value` = ?
        ");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// =========================================
// LIMPEZA AUTOMÁTICA DE SESSÕES EXPIRADAS
// =========================================
// Limpa sessões expiradas 5% das vezes (probabilístico)
if (rand(1, 100) <= 5) {
    try {
        $pdo->exec("DELETE FROM sessions WHERE expires_at < NOW()");
    } catch (PDOException $e) {
        error_log("Erro ao limpar sessões expiradas: " . $e->getMessage());
    }
}