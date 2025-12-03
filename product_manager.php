<?php
// product_manager.php - Gerencia o cadastro, edição e exclusão de produtos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$file = 'products.json';
$sales_log_file = 'sales_log.json';

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
        echo json_encode(['success' => false, 'message' => 'Erro ao escrever no arquivo JSON. Verifique as permissões (CHMOD 777) no ' . basename($file) . '.']);
        exit;
    }
}

// Função para gerar chaves rotativas vazias para novos produtos
function generate_initial_keys($numKeys, $paymentMethod) {
    $keys = [];
    $placeholder = $paymentMethod === 'PIX' ? 'CHAVE-PIX-A-SER-CONFIGURADA' : 'CODIGO-DE-BARRAS-A-SER-CONFIGURADO';
    
    for ($i = 0; $i < $numKeys; $i++) {
        $keys[] = [
            // Usa um ID único baseado no tempo para evitar colisões
            'id' => uniqid("chave_", true), 
            'pixKey' => $placeholder . "-" . ($i + 1) 
        ];
    }
    return $keys;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? null;
    $all_data = read_json($file);

    switch ($action) {
        
        case 'create_product':
        case 'update_product':
            
            // Validação
            if (empty($data['title']) || empty($data['value']) || empty($data['paymentMethod'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Título, Valor e Método de Pagamento são obrigatórios.']);
                exit;
            }

            $originalKey = $data['originalKey'] ?? null;
            
            // Cria uma chave amigável baseada no título
            $productKey = strtolower(str_replace(' ', '-', preg_replace('/[^\w\s-]/', '', $data['title'])));

            // Se for atualização, mantém a chave original
            if ($originalKey) {
                $productKey = $originalKey;
                $existingKeys = $all_data[$productKey]['keys'] ?? []; 
                $numKeys = (int)($data['numKeys'] ?? count($existingKeys)); // Número desejado de chaves
                
                if (count($existingKeys) < $numKeys) {
                    $newKeys = generate_initial_keys($numKeys - count($existingKeys), $data['paymentMethod']);
                    $keys = array_merge($existingKeys, $newKeys);
                } else if (count($existingKeys) > $numKeys) {
                     $keys = array_slice($existingKeys, 0, $numKeys);
                } else {
                    $keys = $existingKeys;
                }
            } else {
                 // Criação de produto: verifica se a chave já existe
                 if (isset($all_data[$productKey])) {
                      $productKey .= "-" . time(); // Adiciona timestamp para garantir a unicidade
                 }
                 $numKeys = (int)($data['numKeys'] ?? 1);
                 // Cria as chaves iniciais
                 $keys = generate_initial_keys($numKeys, $data['paymentMethod']);
            }
            
            $all_data[$productKey] = [
                'title' => $data['title'],
                'value' => $data['value'],
                'paymentMethod' => $data['paymentMethod'],
                'keys' => $keys,
                'category' => $data['category'] ?? 'Geral',
                'description' => $data['description'] ?? '',
                'images' => $data['images'] ?? [],
                'visibility' => $data['visibility'] ?? 'visible',
                'views' => $all_data[$productKey]['views'] ?? 0 
            ];

            if (write_json($file, $all_data)) {
                echo json_encode(['success' => true, 'message' => 'Produto salvo/atualizado com sucesso!', 'productKey' => $productKey]);
            }
            break;

        case 'delete_product':
            $productKey = $data['productKey'] ?? null;
            if ($productKey && isset($all_data[$productKey])) {
                unset($all_data[$productKey]);
                if (write_json($file, $all_data)) {
                    echo json_encode(['success' => true, 'message' => 'Produto excluído com sucesso.']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado.']);
            }
            break;
            
        case 'increment_view':
            $productKey = $data['productKey'] ?? null;
            if ($productKey && isset($all_data[$productKey])) {
                $all_data[$productKey]['views'] = ($all_data[$productKey]['views'] ?? 0) + 1;
                // Não precisa retornar JSON neste caso, apenas salvar
                write_json($file, $all_data); 
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado para visualização.']);
            }
            break;
            
        case 'update_pix_key':
            $productKey = $data['productKey'] ?? null;
            $keyId = $data['keyId'] ?? null;
            $pixKey = $data['pixKey'] ?? null;
            
            if ($productKey && $keyId && isset($all_data[$productKey])) {
                $keys = $all_data[$productKey]['keys'] ?? [];
                $found = false;
                
                foreach ($keys as $index => $keyData) {
                    if ($keyData['id'] === $keyId) {
                        $all_data[$productKey]['keys'][$index]['pixKey'] = $pixKey;
                        $found = true;
                        break;
                    }
                }
                
                if ($found && write_json($file, $all_data)) {
                    echo json_encode(['success' => true, 'message' => 'Chave atualizada.']);
                } else if ($found) {
                     http_response_code(500);
                     echo json_encode(['success' => false, 'message' => 'Chave encontrada, mas falha ao escrever no arquivo.']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'ID da chave não encontrado para o produto.']);
                }
                
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Produto ou dados da chave ausentes.']);
            }
            break;

        case 'reset_metrics':
            // 1. Zera Visualizações de Produtos
            $products_reset = read_json($file);
            foreach ($products_reset as $key => &$product) {
                $product['views'] = 0;
            }
            unset($product);
            $views_success = write_json($file, $products_reset);

            // 2. Zera o Log de Vendas
            $log_success = write_json($sales_log_file, []);

            if ($views_success && $log_success) {
                echo json_encode(['success' => true, 'message' => 'Métricas e visualizações resetadas com sucesso!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao resetar dados. Verifique as permissões dos arquivos products.json e sales_log.json.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação desconhecida.']);
            break;
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
     // Retorna o JSON bruto (se necessário)
     $all_data = read_json($file);
     echo json_encode($all_data);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>