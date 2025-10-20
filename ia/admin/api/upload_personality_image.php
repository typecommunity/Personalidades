<?php
/**
 * API: Upload de Imagem de Personalidade
 * Arquivo: /ia/admin/api/upload_personality_image.php
 */

// Desabilitar warnings
error_reporting(0);
ini_set('display_errors', 0);

// Buffer de output
ob_start();

require_once '../config.php';

// Limpar qualquer output anterior
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Verificar se é admin
if (!isAdmin()) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado'
    ]);
    ob_end_flush();
    exit;
}

// Verificar se há arquivo enviado
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Nenhuma imagem foi enviada';
    
    if (isset($_FILES['image']['error'])) {
        switch($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMsg = 'Arquivo maior que o permitido no servidor';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'Arquivo maior que o permitido no formulário';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'Upload incompleto';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = 'Pasta temporária não encontrada';
                break;
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $errorMsg
    ]);
    ob_end_flush();
    exit;
}

$file = $_FILES['image'];

// Validações
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Validar tipo MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Formato não permitido. Use JPG, PNG, GIF ou WEBP'
    ]);
    ob_end_flush();
    exit;
}

// Validar tamanho
if ($file['size'] > $maxSize) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo muito grande. Máximo: 2MB'
    ]);
    ob_end_flush();
    exit;
}

try {
    // Pasta de uploads
    $uploadDir = '../uploads/personalities/';
    
    // Verificar se pasta existe
    if (!is_dir($uploadDir)) {
        throw new Exception('Pasta uploads/personalities não existe. Crie manualmente com permissão 777');
    }
    
    // Verificar se pasta é gravável
    if (!is_writable($uploadDir)) {
        throw new Exception('Pasta uploads/personalities sem permissão de escrita. Execute: chmod 777 /ia/admin/uploads/personalities');
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'personality_' . uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Erro ao salvar arquivo no servidor');
    }
    
    // Redimensionar (se GD disponível)
    $resized = false;
    if (extension_loaded('gd')) {
        try {
            redimensionarImagem($filePath, 400, 400);
            $resized = true;
        } catch (Exception $e) {
            // Ignora erro de redimensionamento
        }
    }
    
    // URL da imagem
    $imageUrl = '/ia/admin/uploads/personalities/' . $fileName;
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Upload realizado com sucesso',
        'image_url' => $imageUrl,
        'file_name' => $fileName,
        'resized' => $resized
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();

/**
 * Redimensionar imagem
 */
function redimensionarImagem($arquivo, $larguraMax, $alturaMax) {
    $info = getimagesize($arquivo);
    if (!$info) {
        throw new Exception('Não foi possível ler a imagem');
    }
    
    list($largura, $altura, $tipo) = $info;
    
    // Já está no tamanho certo
    if ($largura <= $larguraMax && $altura <= $alturaMax) {
        return;
    }
    
    // Calcular proporção
    $ratio = min($larguraMax / $largura, $alturaMax / $altura);
    $novaLargura = (int)($largura * $ratio);
    $novaAltura = (int)($altura * $ratio);
    
    // Criar imagem origem
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $origem = @imagecreatefromjpeg($arquivo);
            break;
        case IMAGETYPE_PNG:
            $origem = @imagecreatefrompng($arquivo);
            break;
        case IMAGETYPE_GIF:
            $origem = @imagecreatefromgif($arquivo);
            break;
        case IMAGETYPE_WEBP:
            $origem = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($arquivo) : false;
            break;
        default:
            throw new Exception('Tipo de imagem não suportado');
    }
    
    if (!$origem) {
        throw new Exception('Erro ao criar imagem origem');
    }
    
    // Criar imagem destino
    $destino = imagecreatetruecolor($novaLargura, $novaAltura);
    
    if (!$destino) {
        imagedestroy($origem);
        throw new Exception('Erro ao criar imagem destino');
    }
    
    // Preservar transparência
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        $transparente = imagecolorallocatealpha($destino, 255, 255, 255, 127);
        imagefilledrectangle($destino, 0, 0, $novaLargura, $novaAltura, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled($destino, $origem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
    
    // Salvar
    $salvo = false;
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $salvo = imagejpeg($destino, $arquivo, 90);
            break;
        case IMAGETYPE_PNG:
            $salvo = imagepng($destino, $arquivo, 9);
            break;
        case IMAGETYPE_GIF:
            $salvo = imagegif($destino, $arquivo);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $salvo = imagewebp($destino, $arquivo, 90);
            }
            break;
    }
    
    // Liberar memória
    imagedestroy($origem);
    imagedestroy($destino);
    
    if (!$salvo) {
        throw new Exception('Erro ao salvar imagem redimensionada');
    }
}