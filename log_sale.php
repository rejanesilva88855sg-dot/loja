<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$log_file = 'sales_log.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (empty($data['packageKey']) || empty($data['value'])) {
        echo json_encode(['success' => false, 'message' => 'Dados de venda incompletos (packageKey ou value ausentes).']);
        http_response_code(400);
        exit;
    }

    // 1. Estrutura do novo registro de venda
    $new_sale = [
        'timestamp' => time(),
        'date' => date('Y-m-d'),
        'packageKey' => $data['packageKey'],
        'value' => $data['value']
    ];

    // 2. Tenta ler e decodificar o log existente
    $log_array = [];
    if (file_exists($log_file)) {
        $current_log = file_get_contents($log_file);
        if ($current_log === false) {
             echo json_encode(['success' => false, 'message' => 'Erro ao ler o log de vendas existente.']);
             http_response_code(500);
             exit;
        }
        
        // Tenta decodificar o JSON com tratamento de erro
        $log_array = json_decode($current_log, true);
        if ($log_array === null && json_last_error() !== JSON_ERROR_NONE) {
            // Se o JSON falhar, assume um array vazio para não perder a venda atual
            $log_array = [];
        }
    }

    // Garante que é um array antes de adicionar
    if (!is_array($log_array)) {
         $log_array = [];
    }

    // 3. Adiciona a nova venda
    $log_array[] = $new_sale;

    // 4. Tenta escrever de volta no arquivo
    $json_output = json_encode($log_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if ($json_output === false) {
         echo json_encode(['success' => false, 'message' => 'Erro ao codificar dados para JSON.']);
         http_response_code(500);
         exit;
    }

    if (file_put_contents($log_file, $json_output) !== false) {
        echo json_encode(['success' => true, 'message' => 'Venda registrada com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao escrever no log. Verifique permissões (CHMOD 777) no sales_log.json.']);
        http_response_code(500);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    http_response_code(405);
}
?>