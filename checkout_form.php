<?php
// checkout_form.php - O NOVO CHECKOUT PRINCIPAL COM SUPORTE A CARRINHO
header('Content-Type: text/html; charset=UTF-8');
$file = 'products.json'; // RENOMEADO

// --- 1. CARREGAMENTO DOS DADOS DO PRODUTO/CARRINHO ---
// Novo: Pega a string do carrinho no formato: key1,qty1|key2,qty2
$cartString = $_GET['cart'] ?? null;

$productsInCart = [];
$totalValueNumber = 0;
$productTitle = "Pedido Múltiplo";
$productValueDisplay = "0,00"; 
$randomKey = "Erro: Carrinho vazio ou produto ausente."; 
$defaultPaymentMethod = "PIX"; 
$mainProductKey = null; // Usaremos a chave do primeiro item para puxar a chave PIX/BOLETO

if ($cartString) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $all_data = json_decode($content, true);

        // Função para limpar e converter valor
        $valueToNumber = function($valueString) {
            return (float)str_replace(',', '.', str_replace('.', '', $valueString));
        };

        $itemStrings = explode('|', $cartString);
        
        foreach ($itemStrings as $itemString) {
            list($key, $qty) = explode(',', $itemString);
            $qty = (int)$qty;

            if (isset($all_data[$key]) && $qty > 0) {
                $product = $all_data[$key];
                $priceNumber = $valueToNumber($product['value']);
                $itemTotal = $priceNumber * $qty;
                $totalValueNumber += $itemTotal;
                
                $productsInCart[] = [
                    'key' => $key,
                    'title' => $product['title'],
                    'price' => $product['value'],
                    'quantity' => $qty,
                    'total' => number_format($itemTotal, 2, ',', '.')
                ];
                
                // Define o primeiro produto como o principal para fins de chave PIX/BOLETO e método padrão
                if (!$mainProductKey) {
                    $mainProductKey = $key;
                    $defaultPaymentMethod = $product['paymentMethod'] ?? 'PIX';
                    
                    // Sorteia a chave aleatória do primeiro produto (para o pagamento)
                    if (!empty($product['keys'])) {
                        $randomIndex = array_rand($product['keys']);
                        $randomKeyObject = $product['keys'][$randomIndex];
                        $randomKey = $randomKeyObject['pixKey']; 
                    } else {
                        $randomKey = "Erro: Nenhuma chave/código configurado.";
                    }
                }
            }
        }
        
        // Atualiza o display final do valor e título
        $productValueDisplay = number_format($totalValueNumber, 2, ',', '.');
        if (count($productsInCart) === 1) {
             $productTitle = $productsInCart[0]['title'];
        } else {
             $productTitle = "Pedido com " . count($productsInCart) . " itens";
        }
    }
} else {
     // Fallback (Mantém a compatibilidade com o checkout antigo de 1 produto se 'produto' estiver na URL)
     $productKey = $_GET['produto'] ?? null;
     if ($productKey) {
         if (file_exists($file)) {
             $content = file_get_contents($file);
             $all_data = json_decode($content, true);
             if (isset($all_data[$productKey])) {
                 $productData = $all_data[$productKey];
                 $productTitle = $productData['title'];
                 $productValueDisplay = $productData['value'];
                 $defaultPaymentMethod = $productData['paymentMethod'] ?? 'PIX'; 
                 $mainProductKey = $productKey;
                 
                 if (!empty($productData['keys'])) {
                     $randomIndex = array_rand($productData['keys']);
                     $randomKeyObject = $productData['keys'][$randomIndex];
                     $randomKey = $randomKeyObject['pixKey']; 
                 } else {
                     $randomKey = "Erro: Nenhuma chave/código configurado.";
                 }
                 
                 // Adiciona ao productsInCart para o resumo
                 $productsInCart[] = [
                     'key' => $productKey,
                     'title' => $productData['title'],
                     'price' => $productData['value'],
                     'quantity' => 1,
                     'total' => $productData['value']
                 ];
             }
         }
     }
}

// Lógica de Pagamento (Textos e Logos)
$logoUrlPix = "https://www.advocacianunes.com.br/wp-content/uploads/2022/04/logo-pix-icone-1024.png";
$logoUrlBoleto = "https://laboratoriowestrupp.com.br/wp-content/uploads/2014/07/boleto.png";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro - <?php echo htmlspecialchars($productTitle); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Variáveis de Cores */
        :root {
            --primary-color: #5B48CC; /* Roxo Moderno (Ação/Yampi style) */
            --primary-green: #28a745; 
            --dark-green: #218838;
            --text-dark: #333; 
            --text-light: #666;
            --red-urgency: #dc3545;
            --background-light: #f4f5f9; /* Fundo mais suave */
            --card-bg: #ffffff;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --input-bg: #ffffff; /* Fundo do input branco para contraste */
        }

        /* Reset e Base */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            margin: 0;
            padding: 20px 10px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .elementor-heading-title.elementor-size-default, 
        .entry-header, .page-title {
            display: none !important;
        }

        /* Container Principal (Layout de Duas Colunas para Desktop) */
        .main-container {
            width: 100%;
            max-width: 1100px; 
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Coluna do Formulário (Esquerda) */
        .form-column {
            flex: 3; /* Ocupa mais espaço */
            min-width: 300px;
        }
        
        /* Coluna de Resumo (Direita) */
        .summary-column {
            flex: 1; /* Ocupa menos espaço */
            min-width: 280px;
        }

        /* Card do Formulário */
        .checkout-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            border: 1px solid #eee; 
        }

        /* Títulos */
        h2.section-title {
            font-size: 1.5em; 
            color: var(--text-dark);
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 5px;
        }
        
        /* Campos do Formulário */
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        label {
            font-size: 0.9em;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            display: block;
        }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            background-color: var(--input-bg);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
            outline: none;
        }

        /* --- Coluna de Resumo (Produto) --- */
        .product-summary-card {
            position: sticky; /* Fica fixo ao rolar */
            top: 20px;
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid #eee;
            margin-top: 20px; /* Para alinhar com o topo do formulário no mobile */
        }
        .product-summary-card h3 {
            font-size: 1.1em;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-name-summary {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-dark);
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #f0f0f0;
        }
        .total-row .label {
            font-size: 1.2em;
            font-weight: 700;
            color: var(--text-dark);
        }
        .total-row .value {
            font-size: 1.8em;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        /* Escolha de Pagamento */
        .payment-choice-title {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text-dark);
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 2px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .payment-option:hover {
            background-color: #f8f8f8;
        }
        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: #f1effe; /* Fundo mais claro para o selecionado */
        }
        .payment-option input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.3);
            accent-color: var(--primary-color);
        }
        .payment-option img {
            width: 40px;
            height: auto;
            margin-right: 15px;
        }
        .payment-option span {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.05em;
        }
        
        /* Botão Final */
        #submitButton {
            display: block;
            margin: 30px 0 10px 0;
            padding: 18px 35px;
            font-size: 1.3em;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
        }
        #submitButton:hover { 
            background-color: #4f3da6;
        }


        /* --- RESPONSIVIDADE (Mobile) --- */
        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
                gap: 10px;
                margin-top: 0;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .checkout-card {
                padding: 20px;
            }
            .product-summary-card {
                position: static;
                margin-top: 0;
                order: -1; /* Coloca o resumo acima do formulário no mobile */
            }
        }
        
        /* --- Modal (Pop-up) --- ESTILOS MANTIDOS DO CHECKOUT.PHP */
        .modal {
            display: none;
            position: fixed;
            z-index: 99999; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            padding-top: 20px;
        }
        .modal-content {
            font-family: 'Poppins', sans-serif !important;
            background-color: var(--card-bg);
            margin: 2% auto;
            padding: 35px;
            border-radius: 15px;
            width: 90%;
            max-width: 480px; 
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            border: 5px solid var(--primary-green); 
        }
        .close-btn { color: var(--text-light); float: right; font-size: 38px; font-weight: 300; line-height: 1; transition: color 0.2s; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: var(--red-urgency); }
        .urgency-title-modal { color: var(--text-dark); font-size: 1.6em; font-weight: 700; margin-bottom: 10px; }
        #countdown { font-size: 3.2em; color: var(--red-urgency); font-weight: 700; display: inline-block; padding: 0; border-radius: 0; margin: 15px 0 25px 0; }
        .pix-value-label { color: var(--text-light); font-size: 1em; font-weight: 600; margin-top: 20px; }
        .pix-info-value { font-size: 3em; color: var(--primary-green); font-weight: 800; margin-bottom: 30px; display: block; }
        .pix-container { border: 2px dashed #ccc; background-color: var(--background-light); padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .pix-container h3 { color: var(--text-dark); font-size: 1.1em; margin-top: 0; }
        .pix-key-display { font-size: 1em; color: var(--text-dark); word-break: break-all; margin-bottom: 10px; font-weight: 500; max-height: 2.8em; overflow: hidden; border: 1px solid #ddd; background-color: #ffffff; padding: 10px; border-radius: 4px; }
        #copyPixBtn { padding: 12px 30px; background-color: var(--primary-green); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 1.1em; transition: background-color 0.3s ease, transform 0.2s ease; }
        #copyPixBtn:hover { background-color: var(--dark-green); transform: translateY(-1px); }
        .copy-message { margin-top: 8px; color: var(--primary-green); font-weight: 600; font-size: 0.9em; }
        .instruction-text { color: var(--text-dark); font-size: 0.95em; margin-top: -15px; margin-bottom: 25px; line-height: 1.4; }

    </style>
</head>
<body>

<div class="main-container">
    
    <div class="form-column">
        <h1 style="color: var(--primary-color); font-size: 2.5em; margin-bottom: 10px;">🔒 Checkout Seguro</h1>
        <p style="margin-top: -10px; color: var(--text-light); font-size: 1.1em;">Finalize sua compra em 3 passos rápidos.</p>

        <div class="checkout-card">
            <form id="checkoutForm">
                
                <h2 class="section-title">1. Dados Pessoais</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Nome Completo</label>
                        <input type="text" id="fullName" name="fullName" required placeholder="Seu nome completo">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required placeholder="seunome@exemplo.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefone</label>
                        <input type="tel" id="phone" name="phone" required placeholder="(99) 99999-9999">
                    </div>
                </div>

                <h2 class="section-title">2. Endereço de Cobrança</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" required placeholder="00000-000">
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label for="street">Rua / Avenida</label>
                        <input type="text" id="street" name="street" required placeholder="Nome da Rua/Avenida">
                    </div>
                    <div class="form-group" style="flex: 0.8;">
                        <label for="number">Número</label>
                        <input type="number" id="number" name="number" required placeholder="123">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="neighborhood">Bairro</label>
                        <input type="text" id="neighborhood" name="neighborhood" required placeholder="Seu Bairro">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Cidade</label>
                        <input type="text" id="city" name="city" required placeholder="Sua Cidade">
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label for="state">Estado</label>
                        <input type="text" id="state" name="state" required placeholder="UF" maxlength="2">
                    </div>
                </div>
                
                <h2 class="section-title">3. Pagamento</h2>
                
                <?php 
                    // Se apenas um método estiver configurado, oculta o título "Selecione o método:"
                    $isPixOnly = $defaultPaymentMethod === 'PIX';
                    $isBoletoOnly = $defaultPaymentMethod === 'BOLETO';
                    if (!$isPixOnly && !$isBoletoOnly): 
                ?>
                    <div class="payment-choice-title">Selecione o método:</div>
                <?php endif; ?>
                
                <div class="payment-option" data-payment="PIX" id="optionPix"
                     style="<?php echo ($isBoletoOnly) ? 'display: none;' : ''; ?>">
                    <input type="radio" id="payPix" name="paymentMethod" value="PIX" 
                           <?php echo ($isPixOnly || !$isBoletoOnly) ? 'checked' : ''; ?>>
                    <img src="<?php echo htmlspecialchars($logoUrlPix); ?>" alt="Pix Logo">
                    <span>PIX - Rápido e Seguro</span>
                </div>

                <div class="payment-option" data-payment="BOLETO" id="optionBoleto"
                     style="<?php echo ($isPixOnly) ? 'display: none;' : ''; ?>">
                    <input type="radio" id="payBoleto" name="paymentMethod" value="BOLETO"
                           <?php echo ($isBoletoOnly) ? 'checked' : ''; ?>>
                    <img src="<?php echo htmlspecialchars($logoUrlBoleto); ?>" alt="Boleto Logo">
                    <span>BOLETO - Compensação em D+1</span>
                </div>

                <button id="submitButton" type="submit">FINALIZAR E PAGAR COM <span id="paymentTypeDisplay">PIX</span></button>

            </form>
        </div>
    </div>


    <div class="summary-column">
        <div class="product-summary-card">
            <h3>🛒 Resumo do Pedido</h3>
            
            <?php if (empty($productsInCart)): ?>
                 <p style="font-size: 0.9em; color: var(--red-urgency); text-align: center;">Nenhum item no pedido. Volte ao carrinho.</p>
            <?php else: ?>
                <div style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                    <?php foreach ($productsInCart as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9em; border-bottom: 1px dashed #ddd; padding: 5px 0;">
                            <span style="font-weight: 500; color: var(--text-dark); max-width: 60%;"><?php echo htmlspecialchars($item['title']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span style="font-weight: 600; color: var(--text-dark);">R$ <?php echo htmlspecialchars($item['total']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="total-row">
                <span class="label">TOTAL:</span>
                <span class="value">R$ <?php echo htmlspecialchars($productValueDisplay); ?></span>
            </div>
        </div>
    </div>
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            
            <div class="modal-header">
                <img src="" alt="Logo Pagamento" class="payment-logo-main" id="modalLogo" style="width: 80px;">
                
                <div id="pixCountdownContainer">
                    <p style="font-size: 1.1em; color: var(--text-dark); margin-bottom: 5px;">Seu pagamento expira em:</p>
                    <div id="countdown">05:00</div>
                </div>
                
                <p class="instruction-text" id="boletoInstruction" style="display:none;">
                    Copie o código de barras abaixo e pague-o no aplicativo do seu banco (opção "Pagar Boleto") ou em qualquer agência bancária/lotérica.
                </p>

                <h2 class="urgency-title-modal" id="modalTitle">FINALIZE SEU PAGAMENTO</h2>
            </div>
            
            <div class="package-details">
                <p style="color: var(--text-light);">Pedido: <strong><?php echo htmlspecialchars($productTitle); ?></strong></p>
            </div>

            <div class="pix-value-label">VALOR TOTAL DO PEDIDO:</div>
            <div class="pix-info-value" id="modalValueDisplay">R$ <?php echo htmlspecialchars($productValueDisplay); ?></div>

            <div class="pix-container">
                <h3 id="keyLabel">Chave Pix (Copia e Cola):</h3>
                <div class="pix-key-display" id="pixKeyDisplay"><?php echo htmlspecialchars($randomKey); ?></div>
                <button id="copyPixBtn">COPIAR CHAVE PIX</button>
                <div class="copy-message" id="copyMessage"></div>
            </div>
            
            <p style="font-size: 0.85em; color: var(--text-light); margin-top: 15px;" id="compensationTime">
                Pagamento instantâneo. Confirmação imediata.
            </p>
        </div>
    </div>

</div>

<script>
    // Constantes e Variáveis PHP injetadas
    const PRODUCT_KEY = "<?php echo addslashes($mainProductKey); ?>"; // Chave do 1º item ou null
    const PRODUCT_VALUE = "<?php echo addslashes($productValueDisplay); ?>"; // Valor Total
    const RANDOM_KEY = "<?php echo addslashes($randomKey); ?>";
    const PIX_LOGO = "<?php echo addslashes($logoUrlPix); ?>";
    const BOLETO_LOGO = "<?php echo addslashes($logoUrlBoleto); ?>";
    const DEFAULT_METHOD = "<?php echo addslashes($defaultPaymentMethod); ?>"; 
    const CUSTOMER_MANAGER_ENDPOINT = '/customer_manager.php'; 
    const PRODUCT_TITLE = "<?php echo addslashes($productTitle); ?>"; // Título Total/Pedido
    
    let countdownInterval; 
    const timerDuration = 300; // 5 minutos (para Pix)

    // --- UTILS (Mantidos) ---
    
    function maskCPF(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2')
            .replace(/(-\d{2})\d+?$/, '$1') 
    }

    function maskPhone(value) {
         return value
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4,5})(\d{4})$/, '$1-$2')
            .replace(/(-\d{4})\d+?$/, '$1')
    }

    function maskCEP(value) {
         return value
            .replace(/\D/g, '')
            .replace(/^(\d{5})(\d)/, '$1-$2')
            .replace(/(-\d{3})\d+?$/, '$1')
    }
    
    // Lógica para selecionar método de pagamento (AJUSTADA)
    function setupPaymentChoice() {
        const pixOption = document.getElementById('optionPix');
        const boletoOption = document.getElementById('optionBoleto');
        const payPix = document.getElementById('payPix');
        const payBoleto = document.getElementById('payBoleto');
        const submitButton = document.getElementById('submitButton');

        // Função interna para atualizar a UI
        function updateSelection(selectedOption) {
            // Se as opções estiverem visíveis, a classe selected é aplicada, se não, não faz mal.
            if(pixOption && pixOption.style.display !== 'none') pixOption.classList.remove('selected');
            if(boletoOption && boletoOption.style.display !== 'none') boletoOption.classList.remove('selected');
            
            selectedOption.classList.add('selected');
            
            const method = selectedOption.dataset.payment;
            submitButton.innerHTML = `FINALIZAR E PAGAR COM <span>${method}</span>`;
            
            // Ajusta a cor do botão baseado no método
            if (method === 'PIX') {
                 submitButton.style.backgroundColor = 'var(--primary-green)';
            } else {
                 submitButton.style.backgroundColor = 'var(--primary-color)';
            }
        }
        
        // --- Lógica Principal de Inicialização ---
        const pixVisible = pixOption && pixOption.style.display !== 'none';
        const boletoVisible = boletoOption && boletoOption.style.display !== 'none';
        
        if (pixVisible && boletoVisible) {
             // Caso 1: Ambos Visíveis (Opção de escolha)
            pixOption.addEventListener('click', () => {
                payPix.checked = true;
                updateSelection(pixOption);
            });

            boletoOption.addEventListener('click', () => {
                payBoleto.checked = true;
                updateSelection(boletoOption);
            });

            // Inicializa a seleção padrão
            if (payPix.checked) {
                 updateSelection(pixOption);
            } else if (payBoleto.checked) {
                 updateSelection(boletoOption);
            }
            
        } else if (pixVisible) {
             // Caso 2: Apenas PIX
             payPix.checked = true;
             updateSelection(pixOption);
             // Remove listeners para evitar tentativa de alternar
             pixOption.style.cursor = 'default';
        } else if (boletoVisible) {
             // Caso 3: Apenas BOLETO
             payBoleto.checked = true;
             updateSelection(boletoOption);
             // Remove listeners para evitar tentativa de alternar
             boletoOption.style.cursor = 'default';
        } else {
             // Default/Fallback
             submitButton.innerHTML = 'FINALIZAR E PAGAR';
             submitButton.style.backgroundColor = 'var(--primary-color)';
        }
    }
    
    // --- LÓGICA DE PAGAMENTO (MODAL) ---

    function startCountdown(duration) {
        clearInterval(countdownInterval);
        const countdownElement = document.getElementById('countdown');
        const copyPixBtn = document.getElementById('copyPixBtn');
        const copyMessage = document.getElementById('copyMessage');
        
        if (!countdownElement) return;

        let timer = duration;
        
        if(copyPixBtn) {
            copyPixBtn.disabled = false;
            copyPixBtn.textContent = "COPIAR CHAVE PIX";
            copyPixBtn.style.backgroundColor = 'var(--primary-green)';
        }
        if(copyMessage) copyMessage.textContent = '';


        countdownInterval = setInterval(function () {
            let minutes = parseInt(timer / 60, 10);
            let seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            countdownElement.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(countdownInterval);
                countdownElement.textContent = "EXPIRADO!";
                if(copyPixBtn) {
                    copyPixBtn.disabled = true; 
                    copyPixBtn.textContent = "CHAVE PIX EXPIRADA";
                    copyPixBtn.style.backgroundColor = '#6c757d'; 
                }
                if(copyMessage) copyMessage.textContent = 'Por favor, feche e reabra para gerar uma nova chave.';
            }
        }, 1000);
    }
    
    function showPaymentModal(paymentMethod) {
        const isPix = paymentMethod === 'PIX';
        const modal = document.getElementById("paymentModal");
        
        const modalTitle = document.getElementById('modalTitle');
        const modalLogo = document.getElementById('modalLogo');
        const keyLabel = document.getElementById('keyLabel');
        const copyPixBtn = document.getElementById('copyPixBtn');
        const compensationTime = document.getElementById('compensationTime');
        const pixCountdownContainer = document.getElementById('pixCountdownContainer');
        const boletoInstruction = document.getElementById('boletoInstruction');
        
        if (isPix) {
            modalTitle.textContent = "FINALIZE SEU PAGAMENTO PIX";
            modalLogo.src = PIX_LOGO;
            keyLabel.textContent = "Chave Pix (Copia e Cola):";
            copyPixBtn.textContent = "COPIAR CHAVE PIX";
            compensationTime.textContent = "Pagamento instantâneo. Confirmação imediata.";
            pixCountdownContainer.style.display = 'block';
            boletoInstruction.style.display = 'none';
            modal.querySelector('.modal-content').style.borderColor = 'var(--primary-green)';
            startCountdown(timerDuration);
        } else {
            modalTitle.textContent = "FINALIZE COM O BOLETO";
            modalLogo.src = BOLETO_LOGO;
            keyLabel.textContent = "Código de Barras (Linha Digitável):";
            copyPixBtn.textContent = "COPIAR CÓDIGO DE BARRAS";
            compensationTime.textContent = "O prazo de compensação do Boleto é de até 3 dias úteis.";
            pixCountdownContainer.style.display = 'none';
            boletoInstruction.style.display = 'block';
            modal.querySelector('.modal-content').style.borderColor = '#3F51B5'; 
            clearInterval(countdownInterval);
        }
        
        modal.style.display = "block";
    }

    function setupCopyLogic() {
        const copyPixBtn = document.getElementById('copyPixBtn');
        const copyMessage = document.getElementById('copyMessage');
        const pixKeyDisplay = document.getElementById('pixKeyDisplay');
        const modal = document.getElementById("paymentModal");
        const closeSpan = document.getElementsByClassName("close-btn")[0];
        
        if(copyPixBtn) {
            copyPixBtn.onclick = function() {
                const keyToCopy = pixKeyDisplay.textContent;
                // Usa o método que está *checked* no formulário (já garantido como PIX ou BOLETO)
                const paymentMethodInput = document.querySelector('input[name="paymentMethod"]:checked');
                const paymentMethod = paymentMethodInput ? paymentMethodInput.value : DEFAULT_METHOD;
                const isPix = paymentMethod === 'PIX';

                const expired = isPix && document.getElementById('countdown') && document.getElementById('countdown').textContent === "EXPIRADO!";
                const keyIsValid = keyToCopy.indexOf('Erro') === -1;
                
                if (!copyPixBtn.disabled && keyIsValid && !expired) {
                    navigator.clipboard.writeText(keyToCopy).then(function() {
                        if(copyMessage) copyMessage.textContent = `${isPix ? 'Chave Pix' : 'Código de Barras'} copiado com sucesso!`;
                        setTimeout(() => { if(copyMessage) copyMessage.textContent = ''; }, 3000); 
                    }, function(err) {
                        if(copyMessage) copyMessage.textContent = 'Erro ao copiar: Seu navegador pode não suportar esta função. Copie manualmente o código.';
                    });
                } else if (expired) {
                    if(copyMessage) copyMessage.textContent = 'A chave Pix expirou. Feche e reabra o modal.';
                } else {
                    if(copyMessage) copyMessage.textContent = 'Não há código válido para copiar.';
                }
            };
        }
        
        if(closeSpan) {
            closeSpan.onclick = function() {
                if(modal) modal.style.display = "none";
                clearInterval(countdownInterval); 
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                if(modal) modal.style.display = "none";
                clearInterval(countdownInterval); 
            }
        }
    }


    // --- LÓGICA DO FORMULÁRIO (SALVAR CLIENTE) ---
    
    function clearCartOnSuccess() {
        // NOVO: Limpa o carrinho após a venda ser registrada
        localStorage.removeItem('cartItems');
    }

    async function saveCustomerData(formData) {
        // PRODUCT_KEY é a chave do primeiro item (para o log)
        formData.append('productKey', PRODUCT_KEY); 
        // PRODUCT_TITLE agora é o título do pedido completo
        formData.append('productTitle', PRODUCT_TITLE); 
        // PRODUCT_VALUE agora é o valor total
        formData.append('productValue', PRODUCT_VALUE); 
        formData.append('randomKey', RANDOM_KEY); 
        
        // Pega o método de pagamento selecionado (o único visível e marcado como checked)
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        formData.append('paymentMethodChosen', paymentMethod); 

        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(CUSTOMER_MANAGER_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_customer',
                    ...data
                }),
            });

            const result = await response.json();
            
            if (result.success) {
                console.log("Dados do cliente salvos e log de venda iniciado. Carrinho será limpo.");
                clearCartOnSuccess(); // NOVO: Limpa o carrinho
                return true;
            } else {
                console.error("Erro ao salvar cliente/log:", result.message);
                // Permite continuar para mostrar o modal mesmo com erro no log
                return true; 
            }
        } catch (e) {
            console.error("Erro de rede ao salvar cliente:", e);
            return true;
        }
    }
    
    function setupFormSubmission() {
        const form = document.getElementById('checkoutForm');
        
        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(form);
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
            
            // Garante que o método de pagamento seja o que está marcado (ou o único visível)
            const saveSuccessful = await saveCustomerData(formData);
            
            if (saveSuccessful) {
                showPaymentModal(paymentMethod);
            }
        });
    }


    // --- INICIALIZAÇÃO GERAL ---
    
    window.addEventListener('load', function() {
        // Aplica as máscaras
        const cpfInput = document.getElementById('cpf');
        const phoneInput = document.getElementById('phone');
        const cepInput = document.getElementById('cep');

        if(cpfInput) cpfInput.addEventListener('input', (e) => e.target.value = maskCPF(e.target.value));
        if(phoneInput) phoneInput.addEventListener('input', (e) => e.target.value = maskPhone(e.target.value));
        if(cepInput) cepInput.addEventListener('input', (e) => e.target.value = maskCEP(e.target.value));

        setupPaymentChoice();
        setupFormSubmission();
        setupCopyLogic();
    });

</script>
</body>
</html>