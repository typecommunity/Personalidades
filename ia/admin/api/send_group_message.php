<?php
header('Content-Type: application/json');
require_once '../config.php';

// Log de debug
error_log("=== SEND GROUP MESSAGE INICIADO ===");

if (!isLoggedIn()) {
    error_log("Erro: Usuário não autenticado");
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Receber dados
$data = json_decode(file_get_contents('php://input'), true);
$conversationId = $data['conversation_id'] ?? null;
$content = $data['content'] ?? null;
$userGender = $data['user_gender'] ?? 'neutral';
$userName = $data['user_name'] ?? 'Usuário';
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

error_log("Conversation ID: $conversationId");
error_log("User ID: $userId");
error_log("Content: " . substr($content, 0, 50));

if (!$conversationId || !$content) {
    error_log("Erro: Dados incompletos");
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verificar se a conversa existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$conversationId, $userId]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        throw new Exception('Conversa não encontrada');
    }
    
    error_log("Conversa encontrada: " . $conversation['title']);
    
    // Buscar informações do grupo
    $stmt = $pdo->prepare("SELECT * FROM group_conversations WHERE conversation_id = ? AND is_active = 1");
    $stmt->execute([$conversationId]);
    $group = $stmt->fetch();
    
    if (!$group) {
        throw new Exception('Grupo não encontrado');
    }
    
    error_log("Grupo encontrado: " . $group['name']);
    
    // Salvar mensagem do usuário
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, role, content, created_at) 
        VALUES (?, 'user', ?, NOW())
    ");
    $stmt->execute([$conversationId, $content]);
    error_log("Mensagem do usuário salva");
    
    // Buscar membros ativos do grupo
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM group_members gm
        JOIN personalities p ON gm.personality_id = p.id
        WHERE gm.group_id = ? AND gm.is_active = 1 AND p.is_active = 1
        ORDER BY RAND()
    ");
    $stmt->execute([$group['id']]);
    $members = $stmt->fetchAll();
    
    if (empty($members)) {
        throw new Exception('Nenhum membro ativo encontrado no grupo');
    }
    
    error_log("Membros encontrados: " . count($members));
    
    // Buscar histórico recente para contexto (últimas 10 mensagens)
    $stmt = $pdo->prepare("
        SELECT m.*, p.name as personality_name
        FROM messages m
        LEFT JOIN personalities p ON m.personality_id = p.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$conversationId]);
    $recentMessages = array_reverse($stmt->fetchAll());
    
    // Construir contexto do histórico
    $historyContext = "";
    foreach ($recentMessages as $msg) {
        if ($msg['role'] === 'user') {
            $historyContext .= "{$userName}: {$msg['content']}\n";
        } else {
            $senderName = $msg['personality_name'] ?? 'Assistente';
            $historyContext .= "{$senderName}: {$msg['content']}\n";
        }
    }
    
    // Detectar menções (@nome)
    $mentions = [];
    foreach ($members as $member) {
        if (stripos($content, '@' . $member['name']) !== false) {
            $mentions[] = $member;
        }
    }
    
    // Se houver menções, apenas os mencionados respondem
    // Caso contrário, 1-2 membros aleatórios respondem
    if (!empty($mentions)) {
        $respondingMembers = $mentions;
        error_log("Membros mencionados: " . count($mentions));
    } else {
        // Escolher 1 ou 2 membros aleatoriamente
        $numResponders = rand(1, min(2, count($members)));
        $respondingMembers = array_slice($members, 0, $numResponders);
        error_log("Membros aleatórios selecionados: " . $numResponders);
    }
    
    // Buscar API key do banco de dados
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'openai_api_key'");
    $stmt->execute();
    $apiKeyRow = $stmt->fetch();
    $apiKey = $apiKeyRow['value'] ?? null;
    
    if (!$apiKey) {
        throw new Exception('Chave da API OpenAI não configurada');
    }
    
    error_log("API Key encontrada");
    
    // Buscar modelo configurado
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'openai_model'");
    $stmt->execute();
    $modelRow = $stmt->fetch();
    $model = $modelRow['value'] ?? 'gpt-4-turbo-preview';
    
    foreach ($respondingMembers as $member) {
        error_log("Gerando resposta para: " . $member['name']);
        
        // Construir prompt para o membro
        $systemPrompt = "Você é {$member['name']}, uma personalidade em um chat de grupo chamado '{$group['name']}'.

SOBRE VOCÊ:
Nome: {$member['name']}
Descrição: {$member['description']}

INSTRUÇÕES DO SEU PERSONAGEM:
{$member['system_prompt']}

INSTRUÇÕES PARA CHAT EM GRUPO:
1. Responda SEMPRE como {$member['name']}, mantendo sua personalidade única
2. Use seu estilo de interação característico
3. Seja natural e conversacional
4. Se o usuário mencionar você (@{$member['name']}), responda diretamente
5. Interaja com outras personalidades no grupo quando relevante
6. Mantenha respostas concisas (2-4 frases normalmente)
7. Use emojis ocasionalmente se combinar com sua personalidade
8. Trate o usuário como '{$userName}'

CONTEXTO DO GRUPO:
{$group['description']}

HISTÓRICO RECENTE DA CONVERSA:
{$historyContext}

Responda à última mensagem de forma natural e coerente com sua personalidade.";

        $userPrompt = "{$userName} disse: {$content}";
        
        try {
            // Fazer requisição para OpenAI API
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            
            $payload = json_encode([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_tokens' => 1024,
                'temperature' => 0.7
            ]);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                throw new Exception("Erro cURL: " . $curlError);
            }
            
            if ($httpCode !== 200) {
                error_log("Erro HTTP $httpCode: $response");
                throw new Exception("Erro na API OpenAI (HTTP $httpCode)");
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['choices'][0]['message']['content'])) {
                error_log("Resposta inválida da API: " . print_r($result, true));
                throw new Exception("Resposta inválida da API");
            }
            
            $aiResponse = $result['choices'][0]['message']['content'];
            $tokensUsed = $result['usage']['total_tokens'] ?? 0;
            
            error_log("Resposta gerada: " . substr($aiResponse, 0, 50) . "...");
            
            // Salvar resposta da personalidade
            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, role, content, personality_id, tokens_used, created_at) 
                VALUES (?, 'assistant', ?, ?, ?, NOW())
            ");
            $stmt->execute([$conversationId, $aiResponse, $member['id'], $tokensUsed]);
            
            error_log("Resposta salva no banco");
            
            // Atualizar last_response_at do membro
            $stmt = $pdo->prepare("
                UPDATE group_members 
                SET last_response_at = NOW() 
                WHERE group_id = ? AND personality_id = ?
            ");
            $stmt->execute([$group['id'], $member['id']]);
            
            // Pequeno delay entre respostas para parecer mais natural
            if (count($respondingMembers) > 1) {
                usleep(500000); // 0.5 segundos
            }
            
        } catch (Exception $e) {
            error_log("Erro ao gerar resposta para {$member['name']}: " . $e->getMessage());
            // Continuar com os outros membros
        }
    }
    
    // Atualizar timestamp da conversa
    $stmt = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    $pdo->commit();
    
    error_log("=== SUCESSO ===");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensagens enviadas com sucesso',
        'responders' => array_column($respondingMembers, 'name')
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERRO CRÍTICO: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

error_log("=== SEND GROUP MESSAGE FINALIZADO ===");
?>