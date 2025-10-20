<?php
/**
 * API: Upload de Foto para Grupos
 * Arquivo: api/upload_photo.php
 */

require_once '../config.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
    exit;
}

$type = $_POST['type'] ?? 'group'; // 'group' ou 'user'
$uploadDir = __DIR__ . '/../uploads/' . $type . 's/';

// Criar diretório se não existir
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['photo'];
$fileName = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$uploadPath = $uploadDir . $fileName;

// Validar tipo de arquivo
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido']);
    exit;
}

// Validar tamanho (máx 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máx 5MB)']);
    exit;
}

// Fazer upload
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $url = '/ia/uploads/' . $type . 's/' . $fileName;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'message' => 'Upload realizado com sucesso'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload']);
}
?>