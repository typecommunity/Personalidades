<?php
/**
 * API: Enviar Mensagem e Obter Resposta da IA
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);

$conversationId = $data['conversation_id'] ?? null;
$content = $data['content'] ?? null;

// Validações
if (!$conversationId || !$content) {
    echo json_encode([
        'success' => false,
        'message' => 'Conversa e conteúdo são obrigatórios'
    ]);
    exit;
}

try {
    // Buscar conversa e validar acesso
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.system_prompt
        FROM conversations c
        JOIN personalities p ON c.personality_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode([
            'success' => false,
            'message' => 'Conversa não encontrada ou sem permissão'
        ]);
        exit;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Salvar mensagem do usuário
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, role, content, created_at)
        VALUES (?, 'user', ?, NOW())
    ");
    $stmt->execute([$conversationId, $content]);
    
    // Buscar histórico de mensagens (últimas 20)
    $stmt = $pdo->prepare("
        SELECT role, content
        FROM messages
        WHERE conversation_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$conversationId]);
    $history = array_reverse($stmt->fetchAll());
    
    // Montar mensagens para OpenAI
    // ✅ AJUSTE: Adicionar instruções para respostas curtas
    $systemPrompt = $conversation['system_prompt'] . "\n\nIMPORTANTE: Suas respostas devem ser CURTAS e DIRETAS, como em uma conversa de WhatsApp. Máximo 2-3 parágrafos curtos ou 4-5 linhas. Seja conciso, natural e conversacional. Evite listas numeradas longas. Prefira fazer perguntas e criar diálogo ao invés de escrever textos longos.";
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Buscar configurações da API
    $apiProvider = getSetting('api_provider', 'openai');
    $apiKey = getSetting('openai_api_key', '');
    $model = getSetting('openai_model', 'gpt-4-turbo-preview');
    $maxTokens = (int)getSetting('max_tokens', 500); // ✅ REDUZIDO de 2000 para 500
    $temperature = (float)getSetting('temperature', 0.7);
    
    if (empty($apiKey)) {
        throw new Exception('API key não configurada');
    }
    
    // Chamar OpenAI API
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Erro na API OpenAI: ' . $response);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Resposta inválida da API');
    }
    
    $aiResponse = $result['choices'][0]['message']['content'];
    $tokensUsed = $result['usage']['total_tokens'] ?? 0;
    
    // Salvar resposta da IA
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, role, content, tokens_used, created_at)
        VALUES (?, 'assistant', ?, ?, NOW())
    ");
    $stmt->execute([$conversationId, $aiResponse, $tokensUsed]);
    
    // Commit
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso',
        'ai_response' => $aiResponse,
        'tokens_used' => $tokensUsed
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
    ]);
}