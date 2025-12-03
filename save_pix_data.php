<?php
// ATENÇÃO: Adicione estas linhas para ver erros no navegador (DEPOIS DE USAR, REMOVA!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// FIM DO DEBUG

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$file = 'pix_data.json';

// Tenta continuar com o código...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (empty($data['packageKey']) || empty($data['key']) || empty($data['value'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        http_response_code(400);
        exit;
    }

    // Tenta ler o arquivo JSON existente
    if (file_exists($file)) {
        $current_data = file_get_contents($file);
        // Verifica se a leitura funcionou antes de decodificar
        if ($current_data === false) {
             echo json_encode(['success' => false, 'message' => 'Erro ao ler o arquivo JSON existente.']);
             http_response_code(500);
             exit;
        }
        $all_data = json_decode($current_data, true);
    } else {
        $all_data = [];
    }
    
    // Atualiza o dado específico
    $all_data[$data['packageKey']] = [
        'key' => $data['key'],
        'value' => $data['value']
    ];

    // Tenta escrever de volta no arquivo
    if (file_put_contents($file, json_encode($all_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(['success' => true, 'message' => 'Dados Pix atualizados globalmente.']);
    } else {
        // Se a escrita falhar, reporta o erro
        echo json_encode(['success' => false, 'message' => 'Erro ao escrever no arquivo JSON. Verifique as permissões de pasta (CHMOD 777).']);
        http_response_code(500);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    http_response_code(405);
}
?>