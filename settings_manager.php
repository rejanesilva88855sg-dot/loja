<?php
// settings_manager.php - Gerencia as configurações globais da loja
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$file = 'store_settings.json';

// Função utilitária para leitura/decodificação
function read_json($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }
    return [];
}

// Função utilitária para escrita
function write_json($file, $data) {
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        return true;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao escrever no arquivo JSON. Verifique as permissões (CHMOD 777) no store_settings.json.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? null;

    switch ($action) {
        
        case 'update_settings':
            if (empty($data['storeTitle']) || empty($data['primaryColor'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Título e Cor Primária são obrigatórios.']);
                exit;
            }
            
            $layout = $data['layout'] ?? [];

            $settings = [
                'storeTitle' => $data['storeTitle'],
                'logoUrl' => $data['logoUrl'],
                'bannerUrl' => $data['bannerUrl'] ?? '', 
                'primaryColor' => $data['primaryColor'],
                'secondaryColor' => $data['secondaryColor'],
                'headerColor' => $data['headerColor'] ?? '#ffffff', 
                'footerColor' => $data['footerColor'] ?? '#333333', 
                'headerTextColor' => $data['headerTextColor'] ?? '#333333', 
                'footerTextColor' => $data['footerTextColor'] ?? '#ffffff', 
                'cardBgColor' => $data['cardBgColor'] ?? '#ffffff', 
                // NOVOS CAMPOS SALVOS
                'customCopyrightText' => $data['customCopyrightText'] ?? '',
                'faviconUrl' => $data['faviconUrl'] ?? '',
                'layout' => [
                    'headerWidth' => $layout['headerWidth'] ?? 'full',
                    'footerWidth' => $layout['footerWidth'] ?? 'full',
                    'productsPerLineDesktop' => $layout['productsPerLineDesktop'] ?? 4,
                    'productsPerLineMobile' => $layout['productsPerLineMobile'] ?? 2,
                ],
                'headerLinks' => $data['headerLinks'] ?? [],
                'footerLinks' => $data['footerLinks'] ?? []
            ];

            if (write_json($file, $settings)) {
                echo json_encode(['success' => true, 'message' => 'Configurações atualizadas com sucesso!']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>