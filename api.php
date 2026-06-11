<?php
// api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';

// Helper para buscar configuração
function getConfig($key, $conn) {
    $stmt = $conn->prepare("SELECT config_value FROM site_config WHERE config_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['config_value'] : null;
}

// Helper para atualizar configuração
function setConfig($key, $value, $conn) {
    $stmt = $conn->prepare("INSERT INTO site_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

// -------------------- GET: buscar todos os dados --------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dados = [];

    // Configurações gerais
    $keys = [
        'nome_profissional', 'sub_nome', 'bio_curta', 'email_contato', 'telefone_contato',
        'tiktok_views', 'tiktok_desc', 'tiktok_percent',
        'reels_reach', 'reels_desc', 'reels_percent',
        'comunidade_num', 'comunidade_desc',
        'live_views', 'live_desc',
        'genero_porcentagem', 'genero_texto', 'idade_range', 'idade_texto',
        'citacao', 'texto_proposta', 'subtitulo_proposta'
    ];
    foreach ($keys as $k) {
        $dados[$k] = getConfig($k, $conn);
    }

    // Portfólio
    $result = $conn->query("SELECT id, imagem_url, legenda, ordem FROM portfolio ORDER BY ordem");
    $dados['portfolio'] = [];
    while ($row = $result->fetch_assoc()) {
        $dados['portfolio'][] = $row;
    }

    echo json_encode(['sucesso' => true, 'dados' => $dados]);
    exit;
}

// -------------------- POST: salvar alterações --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
        exit;
    }

    // Atualizar configurações
    $configKeys = [
        'nome_profissional', 'sub_nome', 'bio_curta', 'email_contato', 'telefone_contato',
        'tiktok_views', 'tiktok_desc', 'tiktok_percent',
        'reels_reach', 'reels_desc', 'reels_percent',
        'comunidade_num', 'comunidade_desc',
        'live_views', 'live_desc',
        'genero_porcentagem', 'genero_texto', 'idade_range', 'idade_texto',
        'citacao', 'texto_proposta', 'subtitulo_proposta'
    ];
    foreach ($configKeys as $key) {
        if (isset($input[$key])) {
            setConfig($key, $input[$key], $conn);
        }
    }

    // Atualizar portfólio: recebe um array 'portfolio' com {id, imagem_url, legenda, ordem}
    if (isset($input['portfolio']) && is_array($input['portfolio'])) {
        // Primeiro, remover todos os itens atuais (opcional: fazer merge, mas vamos substituir)
        $conn->query("DELETE FROM portfolio");
        foreach ($input['portfolio'] as $item) {
            $stmt = $conn->prepare("INSERT INTO portfolio (id, imagem_url, legenda, ordem) VALUES (?, ?, ?, ?)");
            $id = isset($item['id']) ? (int)$item['id'] : null;
            $imagem = $item['imagem_url'];
            $legenda = $item['legenda'];
            $ordem = (int)$item['ordem'];
            $stmt->bind_param("issi", $id, $imagem, $legenda, $ordem);
            $stmt->execute();
        }
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Dados salvos com sucesso!']);
    exit;
}

echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
?>
