<?php
// customer_manager.php - Gerencia o cadastro de clientes e pedidos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$log_file = 'customer_orders.json';
$sales_log_file = 'sales_log.json'; 

// Função utilitária para leitura/decodificação
function read_json_orders($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Tenta decodificar. Se falhar, retorna array vazio.
        return json_decode($content, true) ?? [];
    }
    return [];
}

// Função utilitária para escrita
function write_json_orders($file, $data) {
    $json_output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_output === false) {
         return false;
    }
    // file_put_contents retorna false em caso de falha.
    return file_put_contents($file, $json_output) !== false;
}

// FUNÇÃO: Para registrar a venda no sales_log.json
function log_sale_entry($sales_log_file, $packageKey, $value) {
    $new_sale = [
        'timestamp' => time(),
        'date' => date('Y-m-d'),
        'packageKey' => $packageKey,
        'value' => $value
    ];

    $log_array = [];
    if (file_exists($sales_log_file)) {
        $current_log = file_get_contents($sales_log_file);
        $log_array = json_decode($current_log, true) ?? [];
    }

    if (!is_array($log_array)) { $log_array = []; }
    $log_array[] = $new_sale;
    return write_json_orders($sales_log_file, $log_array);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? null;

    switch ($action) {
        
        case 'save_customer':
            
            // Validação mínima
            if (empty($data['fullName']) || empty($data['email']) || empty($data['productKey'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dados de cliente/produto incompletos.']);
                exit;
            }

            $current_orders = read_json_orders($log_file);
            $productValueClean = $data['productValue'];
            
            // Estrutura do novo pedido - SALVA TODOS OS DADOS DO FORMULÁRIO
            $new_order = [
                'timestamp' => time(),
                'date' => date('Y-m-d H:i:s'),
                'packageKey' => $data['productKey'],
                'productTitle' => $data['productTitle'],
                'productValue' => $productValueClean,
                'customerData' => [
                    'fullName' => $data['fullName'],
                    'email' => $data['email'],
                    'cpf' => $data['cpf'] ?? 'N/A',
                    'phone' => $data['phone'] ?? 'N/A',
                    'cep' => $data['cep'] ?? 'N/A',
                    'street' => $data['street'] ?? 'N/A',
                    'number' => $data['number'] ?? 'N/A',
                    'neighborhood' => $data['neighborhood'] ?? 'N/A',
                    'city' => $data['city'] ?? 'N/A',
                    'state' => $data['state'] ?? 'N/A',
                ],
                'paymentDetails' => [
                    'method' => $data['paymentMethodChosen'] ?? 'PIX',
                    'keyUsed' => $data['randomKey'] ?? 'N/A',
                    'status' => 'PENDENTE',
                    'cardDetails' => []
                ]
            ];
            
            if (!is_array($current_orders)) { $current_orders = []; }
            // Adiciona no início (unshift) para os mais novos ficarem primeiro
            array_unshift($current_orders, $new_order);

            // 1. Tenta Registrar a Venda no Log Geral (sales_log.json)
            $log_success = log_sale_entry($sales_log_file, $data['productKey'], $productValueClean);
            
            // 2. Tenta Registrar o Pedido no Log de Clientes (customer_orders.json)
            if (write_json_orders($log_file, $current_orders)) {
                if ($log_success) {
                    echo json_encode(['success' => true, 'message' => 'Pedido e log de venda registrados com sucesso.']);
                } else {
                    // É importante que o pedido do cliente seja salvo, mesmo que o log de venda falhe
                    echo json_encode(['success' => true, 'message' => 'Pedido registrado. AVISO: Falha ao atualizar log de vendas.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao escrever no log. Verifique permissões (CHMOD 777) no customer_orders.json.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar todos os Clientes/Pedidos
    $all_orders = read_json_orders($log_file);
    echo json_encode(['success' => true, 'data' => $all_orders]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>