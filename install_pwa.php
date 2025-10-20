<?php
/**
 * Instalador PWA - Com Verificação de Permissões
 */

$LOGO_SOURCE = 'assets/icons/logo.png';
$errors = [];
$success = [];
$warnings = [];
$permissions_ok = true;

// Função para verificar permissões
function checkPermissions($path) {
    if (!file_exists($path)) {
        $parent = dirname($path);
        return is_writable($parent);
    }
    return is_writable($path);
}

// Verificar permissões das pastas
$folders = [
    '.' => 'Pasta raiz',
    'assets' => 'Pasta assets',
    'assets/icons' => 'Pasta assets/icons',
    'assets/css' => 'Pasta assets/css',
    'assets/js' => 'Pasta assets/js'
];

$permissions_info = [];
foreach ($folders as $folder => $name) {
    $exists = file_exists($folder);
    $writable = checkPermissions($folder);
    $permissions_info[$folder] = [
        'name' => $name,
        'exists' => $exists,
        'writable' => $writable,
        'path' => realpath($folder) ?: $folder
    ];
    
    if (!$writable) {
        $permissions_ok = false;
    }
}

// Processar instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    
    if (!$permissions_ok) {
        $errors[] = "❌ Corrija as permissões antes de instalar";
    } else {
        
        // Criar pastas
        foreach ($folders as $folder => $name) {
            if (!file_exists($folder)) {
                if (@mkdir($folder, 0755, true)) {
                    $success[] = "✅ $name criada";
                } else {
                    $errors[] = "❌ Erro ao criar $name";
                }
            }
        }

        // Verificar logo
        if (!file_exists($LOGO_SOURCE)) {
            $warnings[] = "⚠️ Logo não encontrada em: $LOGO_SOURCE";
        } else {
            // Processar logo
            if (extension_loaded('gd')) {
                $imageInfo = getimagesize($LOGO_SOURCE);
                if ($imageInfo) {
                    $source = null;
                    switch ($imageInfo['mime']) {
                        case 'image/png':
                            $source = @imagecreatefrompng($LOGO_SOURCE);
                            break;
                        case 'image/jpeg':
                        case 'image/jpg':
                            $source = @imagecreatefromjpeg($LOGO_SOURCE);
                            break;
                        case 'image/webp':
                            $source = @imagecreatefromwebp($LOGO_SOURCE);
                            break;
                    }

                    if ($source) {
                        $sourceWidth = imagesx($source);
                        $sourceHeight = imagesy($source);

                        $sizes = [
                            'apple-touch-icon.png' => 180,
                            'favicon-96x96.png' => 96,
                            'web-app-manifest-192x192.png' => 192,
                            'web-app-manifest-512x512.png' => 512
                        ];

                        foreach ($sizes as $filename => $size) {
                            $dest = imagecreatetruecolor($size, $size);
                            imagealphablending($dest, false);
                            imagesavealpha($dest, true);
                            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
                            imagefill($dest, 0, 0, $transparent);
                            imagealphablending($dest, true);
                            
                            imagecopyresampled($dest, $source, 0, 0, 0, 0, $size, $size, $sourceWidth, $sourceHeight);
                            
                            $iconPath = "assets/icons/$filename";
                            if (@imagepng($dest, $iconPath, 9)) {
                                @chmod($iconPath, 0644);
                                $success[] = "✅ $filename criado";
                            } else {
                                $errors[] = "❌ Erro ao salvar $filename (permissão negada)";
                            }
                            
                            imagedestroy($dest);
                        }
                        imagedestroy($source);
                    }
                }
            } else {
                $warnings[] = "⚠️ GD não instalado. Use https://www.pwabuilder.com/imageGenerator";
            }
        }

        // Criar manifest.json
        if (empty($errors)) {
            $manifest = [
                "name" => "Pipo - Consciousness Chatbot",
                "short_name" => "Pipo",
                "start_url" => "/ia/admin/login.php",
                "display" => "standalone",
                "background_color" => "#ffffff",
                "theme_color" => "#059669",
                "scope" => "/",
                "icons" => [
                    ["src" => "/assets/icons/favicon-96x96.png", "sizes" => "96x96", "type" => "image/png"],
                    ["src" => "/assets/icons/web-app-manifest-192x192.png", "sizes" => "192x192", "type" => "image/png"],
                    ["src" => "/assets/icons/web-app-manifest-512x512.png", "sizes" => "512x512", "type" => "image/png"],
                    ["src" => "/assets/icons/apple-touch-icon.png", "sizes" => "180x180", "type" => "image/png"]
                ]
            ];

            if (@file_put_contents('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                @chmod('manifest.json', 0644);
                $success[] = "✅ manifest.json criado";
            } else {
                $errors[] = "❌ Erro ao criar manifest.json (permissão negada)";
            }

            $browserconfig = '<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square70x70logo src="/assets/icons/favicon-96x96.png"/>
            <square150x150logo src="/assets/icons/web-app-manifest-192x192.png"/>
            <TileColor>#059669</TileColor>
        </tile>
    </msapplication>
</browserconfig>';

            if (@file_put_contents('browserconfig.xml', $browserconfig)) {
                @chmod('browserconfig.xml', 0644);
                $success[] = "✅ browserconfig.xml criado";
            } else {
                $errors[] = "❌ Erro ao criar browserconfig.xml (permissão negada)";
            }
        }
    }
}

$logoExists = file_exists($LOGO_SOURCE);
$currentPath = getcwd();
$webUser = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'www-data';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador PWA - Correção de Permissões</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .content { padding: 40px; }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.8;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fecaca; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .permissions-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .permissions-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .status-ok { color: #059669; font-weight: 600; }
        .status-error { color: #ef4444; font-weight: 600; }
        .code-block {
            background: #1f2937;
            color: #10b981;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            font-family: 'Courier New', monospace;
            margin: 12px 0;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #059669;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover { background: #047857; }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .step { margin: 24px 0; padding: 20px; background: #f9fafb; border-radius: 12px; border-left: 4px solid #059669; }
        .step h3 { margin-bottom: 12px; color: #1f2937; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Instalador PWA - Diagnóstico</h1>
            <p>Verificação de permissões e instalação automática</p>
        </div>

        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>❌ Erros:</strong>
                    <ul style="margin-top: 8px; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <strong>✅ Sucesso!</strong>
                    <ul style="margin-top: 8px; padding-left: 20px;">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($warnings)): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Avisos:</strong>
                    <ul style="margin-top: 8px; padding-left: 20px;">
                        <?php foreach ($warnings as $warning): ?>
                            <li><?php echo $warning; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="step">
                <h3>📊 Status das Permissões</h3>
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th>Pasta</th>
                            <th>Existe?</th>
                            <th>Gravável?</th>
                            <th>Caminho Completo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions_info as $info): ?>
                            <tr>
                                <td><strong><?php echo $info['name']; ?></strong></td>
                                <td class="<?php echo $info['exists'] ? 'status-ok' : 'status-error'; ?>">
                                    <?php echo $info['exists'] ? '✅ Sim' : '❌ Não'; ?>
                                </td>
                                <td class="<?php echo $info['writable'] ? 'status-ok' : 'status-error'; ?>">
                                    <?php echo $info['writable'] ? '✅ Sim' : '❌ Não'; ?>
                                </td>
                                <td><code><?php echo $info['path']; ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$permissions_ok): ?>
                <div class="alert alert-error">
                    <strong>❌ PROBLEMA: Sem permissão de escrita!</strong>
                    <p style="margin-top: 8px;">O servidor web não tem permissão para criar/modificar arquivos.</p>
                </div>

                <div class="step">
                    <h3>🔧 SOLUÇÃO 1: Via SSH (Recomendado)</h3>
                    <p style="margin-bottom: 12px;">Execute estes comandos no terminal SSH:</p>
                    
                    <div class="code-block">
                        <button class="copy-btn" onclick="copyCode(this)">Copiar</button>
<pre># Entrar na pasta do projeto
cd <?php echo $currentPath; ?>

# Dar permissão nas pastas
sudo chmod -R 755 assets/
sudo chmod -R 755 .

# Dar permissão ao usuário web
sudo chown -R <?php echo $webUser; ?>:<?php echo $webUser; ?> assets/
sudo chown <?php echo $webUser; ?>:<?php echo $webUser; ?> .

# Verificar permissões
ls -la</pre>
                    </div>
                </div>

                <div class="step">
                    <h3>🔧 SOLUÇÃO 2: Via FTP/Painel</h3>
                    <ol style="padding-left: 20px; line-height: 1.8;">
                        <li>Abra seu cliente FTP (FileZilla, etc)</li>
                        <li>Navegue até: <code><?php echo $currentPath; ?></code></li>
                        <li>Clique com botão direito em <code>assets/</code></li>
                        <li>Selecione "Permissões de arquivo" ou "CHMOD"</li>
                        <li>Configure para <strong>755</strong> ou marque:
                            <ul style="margin-top: 8px;">
                                <li>✅ Proprietário: Ler, Escrever, Executar</li>
                                <li>✅ Grupo: Ler, Executar</li>
                                <li>✅ Público: Ler, Executar</li>
                            </ul>
                        </li>
                        <li>Marque "Aplicar em subpastas"</li>
                        <li>Clique OK e recarregue esta página</li>
                    </ol>
                </div>

                <div class="step">
                    <h3>🔧 SOLUÇÃO 3: Instalação Manual</h3>
                    <p style="margin-bottom: 12px;">Se não conseguir corrigir permissões, baixe os arquivos e faça upload manual:</p>
                    <ol style="padding-left: 20px; line-height: 1.8;">
                        <li>Baixe todos os arquivos PWA que criei para você</li>
                        <li>Faça upload via FTP para as pastas corretas</li>
                        <li>Gere os ícones em: <a href="https://www.pwabuilder.com/imageGenerator" target="_blank">PWA Builder</a></li>
                        <li>Faça upload dos ícones para <code>assets/icons/</code></li>
                    </ol>
                </div>

            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✅ Permissões OK!</strong>
                    <p style="margin-top: 8px;">O servidor tem permissão de escrita. Você pode instalar o PWA.</p>
                </div>

                <?php if (!$logoExists): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Adicione sua logo</strong>
                        <p style="margin-top: 8px;">
                            1. Faça upload da sua logo para: <code><?php echo $LOGO_SOURCE; ?></code><br>
                            2. Formato: PNG, JPG ou WebP<br>
                            3. Tamanho mínimo: 512x512px<br>
                            4. Recarregue esta página
                        </p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>✅ Logo encontrada!</strong>
                        <p style="margin-top: 8px;">
                            <img src="<?php echo $LOGO_SOURCE; ?>" style="max-width: 150px; border-radius: 8px; margin-top: 8px;">
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="install" class="btn" <?php echo !$logoExists ? 'disabled' : ''; ?>>
                        🚀 <?php echo $logoExists ? 'Gerar Ícones PWA' : 'Adicione a logo primeiro'; ?>
                    </button>
                </form>
            <?php endif; ?>

            <div class="step" style="margin-top: 40px;">
                <h3>📞 Informações do Sistema</h3>
                <ul style="padding-left: 20px; line-height: 1.8; color: #4b5563;">
                    <li><strong>Pasta atual:</strong> <code><?php echo $currentPath; ?></code></li>
                    <li><strong>Usuário web:</strong> <code><?php echo $webUser; ?></code></li>
                    <li><strong>PHP GD:</strong> <?php echo extension_loaded('gd') ? '✅ Instalado' : '❌ Não instalado'; ?></li>
                    <li><strong>Permissões:</strong> <?php echo $permissions_ok ? '✅ OK' : '❌ Bloqueadas'; ?></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function copyCode(btn) {
            const pre = btn.nextElementSibling;
            const text = pre.textContent;
            navigator.clipboard.writeText(text).then(() => {
                btn.textContent = '✅ Copiado!';
                setTimeout(() => {
                    btn.textContent = 'Copiar';
                }, 2000);
            });
        }
    </script>
</body>
</html>