<?php
/**
 * Script de Teste - Verificar Acesso às Imagens
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    die('❌ Acesso negado');
}

echo "<h1>🖼️ Teste de Acesso às Imagens</h1>";
echo "<hr>";

// Buscar grupos
$stmt = $pdo->query("SELECT id, name, avatar_url FROM group_conversations WHERE avatar_url IS NOT NULL AND avatar_url != ''");
$groups = $stmt->fetchAll();

if (empty($groups)) {
    echo "<p>ℹ️ Nenhum grupo com imagem cadastrado.</p>";
} else {
    echo "<h2>Testando Imagens dos Grupos</h2>";
    echo "<table border='1' cellpadding='15' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>URL no Banco</th><th>Preview</th><th>Status</th></tr>";
    
    foreach ($groups as $group) {
        $url = $group['avatar_url'];
        $fileName = basename($url);
        
        // Caminho físico relativo
        $physicalPaths = [
            'admin/uploads/groups/' . $fileName,
            'uploads/groups/' . $fileName,
            'admin/uploads/' . $fileName,
            'uploads/' . $fileName
        ];
        
        $found = false;
        $foundPath = null;
        
        foreach ($physicalPaths as $path) {
            if (file_exists($path)) {
                $found = true;
                $foundPath = $path;
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>{$group['id']}</td>";
        echo "<td>" . htmlspecialchars($group['name']) . "</td>";
        echo "<td><code>" . htmlspecialchars($url) . "</code></td>";
        
        // Preview da imagem
        echo "<td style='text-align: center;'>";
        if ($found) {
            // Mostrar preview usando o caminho físico encontrado
            $webPath = '/' . $foundPath;
            echo "<img src='$webPath' style='max-width: 100px; max-height: 100px; border-radius: 5px;' onerror=\"this.src=''; this.alt='❌ Erro ao carregar';\">";
            echo "<br><small style='color: green;'>Arquivo encontrado em:<br><code>$foundPath</code></small>";
        } else {
            echo "<div style='width: 100px; height: 100px; background: #fee2e2; display: flex; align-items: center; justify-content: center; border-radius: 5px;'>";
            echo "❌";
            echo "</div>";
            echo "<small style='color: red;'>Arquivo não encontrado</small>";
        }
        echo "</td>";
        
        // Status
        echo "<td style='text-align: center;'>";
        if ($found) {
            echo "<span style='color: green; font-size: 20px;'>✅</span><br>";
            echo "<small>Arquivo existe</small><br><br>";
            
            // Testar URL pública
            $publicUrl = 'https://' . $_SERVER['HTTP_HOST'] . $url;
            echo "<a href='$publicUrl' target='_blank' style='padding: 5px 10px; background: #3b82f6; color: white; text-decoration: none; border-radius: 3px; font-size: 11px;'>Testar URL</a>";
        } else {
            echo "<span style='color: red; font-size: 20px;'>❌</span><br>";
            echo "<small>Não encontrado</small>";
        }
        echo "</td>";
        
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<hr>";

// Sugestões
echo "<h2>💡 Próximos Passos</h2>";

$foundAny = false;
foreach ($groups as $group) {
    $fileName = basename($group['avatar_url']);
    $physicalPaths = [
        'admin/uploads/groups/' . $fileName,
        'uploads/groups/' . $fileName,
        'admin/uploads/' . $fileName,
    ];
    
    foreach ($physicalPaths as $path) {
        if (file_exists($path)) {
            $foundAny = true;
            break 2;
        }
    }
}

if ($foundAny) {
    echo "<div style='padding: 15px; background: #d1fae5; border-left: 4px solid #10b981; margin: 10px 0;'>";
    echo "✅ <strong>Imagens encontradas no servidor!</strong><br>";
    echo "📋 <strong>Ação:</strong> Execute o SQL de correção no phpMyAdmin:<br><br>";
    echo "<code style='display: block; padding: 10px; background: white;'>";
    echo "UPDATE group_conversations<br>";
    echo "SET avatar_url = REPLACE(avatar_url, '/ia/uploads/groups/', '/ia/admin/uploads/groups/')<br>";
    echo "WHERE avatar_url LIKE '/ia/uploads/groups/%';";
    echo "</code>";
    echo "</div>";
} else {
    echo "<div style='padding: 15px; background: #fee2e2; border-left: 4px solid #ef4444; margin: 10px 0;'>";
    echo "❌ <strong>Nenhuma imagem encontrada!</strong><br>";
    echo "📋 <strong>Ações necessárias:</strong><br>";
    echo "1. Verificar via FTP/SSH onde as imagens realmente estão<br>";
    echo "2. Mover para: <code>admin/uploads/groups/</code><br>";
    echo "3. Dar permissão: <code>chmod 755 admin/uploads/groups</code><br>";
    echo "4. Executar o SQL de correção<br>";
    echo "</div>";
}

echo "<hr>";
echo "<a href='diagnostic.php' style='padding: 10px 20px; background: #f59e0b; color: white; text-decoration: none; border-radius: 5px;'>Ver Diagnóstico Completo</a>";
echo " ";
echo "<a href='chat.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Voltar ao Chat</a>";

echo "<style>
body { 
    font-family: Arial, sans-serif; 
    padding: 20px; 
    max-width: 1400px; 
    margin: 0 auto;
    background: #f9fafb;
}
h1, h2 { color: #1f2937; }
code { 
    background: #e5e7eb; 
    padding: 2px 6px; 
    border-radius: 3px;
    color: #1f2937;
    font-size: 12px;
}
table { 
    margin: 20px 0;
    background: white;
}
th { 
    background: #3b82f6; 
    color: white;
    padding: 12px;
}
td {
    padding: 15px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
hr {
    border: none;
    border-top: 2px solid #e5e7eb;
    margin: 30px 0;
}
</style>";