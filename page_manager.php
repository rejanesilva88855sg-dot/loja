<?php
// page_manager.php - Gerencia o cadastro e edição de páginas customizadas (CMS)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$file = 'custom_pages.json';

function read_json_pages($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }
    return [];
}

function write_json_pages($file, $data) {
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        return true;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao escrever no arquivo JSON. Verifique as permissões (CHMOD 777) no custom_pages.json.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? null;
    $all_data = read_json_pages($file);

    switch ($action) {
        
        case 'save_page':
            if (empty($data['title']) || empty($data['url']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Título, URL e Conteúdo são obrigatórios.']);
                exit;
            }
            
            // A chave já deve vir formatada do JS, mas limpa novamente
            $pageUrl = $data['url']; 
            $originalUrl = $data['originalUrl'] ?? null;
            
            // Se for edição e a URL mudou, remove a versão antiga
            if ($originalUrl && $originalUrl !== $pageUrl && isset($all_data[$originalUrl])) {
                 unset($all_data[$originalUrl]);
            }
            // Verifica duplicidade (apenas se for nova criação ou se a URL for diferente da original)
            if (isset($all_data[$pageUrl]) && $pageUrl !== $originalUrl) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'message' => 'Erro: Esta URL já está sendo usada por outra página.']);
                 exit;
            }


            $all_data[$pageUrl] = [
                'title' => $data['title'],
                'content' => $data['content'],
                'url' => $pageUrl,
                'visibility' => $data['visibility'] ?? 'visible'
            ];

            if (write_json_pages($file, $all_data)) {
                echo json_encode(['success' => true, 'message' => 'Página salva com sucesso!', 'pageUrl' => "/page.php?url=" . $pageUrl]);
            }
            break;

        case 'delete_page':
            $pageUrl = $data['url'] ?? null;
            if ($pageUrl && isset($all_data[$pageUrl])) {
                unset($all_data[$pageUrl]);
                if (write_json_pages($file, $all_data)) {
                    echo json_encode(['success' => true, 'message' => 'Página excluída com sucesso.']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Página não encontrada.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
     $action = $_GET['action'] ?? null;
     if ($action === 'load_all_pages') {
         $all_data = read_json_pages($file);
         // Retorna a estrutura correta para o JS
         echo json_encode(['success' => true, 'data' => $all_data]);
     } else {
         // Retorna o JSON bruto (fallback)
         $all_data = read_json_pages($file);
         echo json_encode($all_data);
     }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>