<?php
// product_page.php - Página de Detalhes do Produto com Galeria, Descrição e Contador de Views
header('Content-Type: text/html; charset=UTF-8');
$products_file = 'products.json'; 
$settings_file = 'store_settings.json'; 
$productKey = $_GET['produto'] ?? null;

// Funções utilitárias
function read_json($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }
    return [];
}

// --- 1. CARREGAMENTO DOS DADOS ---
$all_data = read_json($products_file);
$storeSettings = read_json($settings_file);
$productData = $all_data[$productKey] ?? null;
$productFound = $productData !== null;

// Configurações de exibição
$productTitle = $productData['title'] ?? "Produto Não Encontrado";
$productValue = $productData['value'] ?? "0,00";
$defaultPaymentMethod = $productData['paymentMethod'] ?? 'PIX'; 
$description = $productData['description'] ?? "Descrição detalhada indisponível.";
$images = $productData['images'] ?? [];
$category = $productData['category'] ?? 'Geral';

// Configurações da Loja
$primaryColor = $storeSettings['primaryColor'] ?? '#5B48CC';
$secondaryColor = $storeSettings['secondaryColor'] ?? '#28a745';
$headerColor = $storeSettings['headerColor'] ?? '#ffffff';
$headerTextColor = $storeSettings['headerTextColor'] ?? '#333333';
$footerColor = $storeSettings['footerColor'] ?? '#333333';
$footerTextColor = $storeSettings['footerTextColor'] ?? '#ffffff';
$storeTitle = $storeSettings['storeTitle'] ?? 'Kiwify Store';
$logoUrl = $storeSettings['logoUrl'] ?? 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Kiwify_Logo_-_Black.png';
// NOVO CAMPO
$faviconUrl = $storeSettings['faviconUrl'] ?? '';

// Valores Estáticos (Mock)
$rating = 4.8;
$reviewsCount = $productData['views'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($productTitle); ?> | <?php echo htmlspecialchars($storeTitle); ?></title>
    <?php if ($faviconUrl): ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* VARIÁVEIS DE CORES DINÂMICAS */
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --header-color: <?php echo $headerColor; ?>;
            --header-text-color: <?php echo $headerTextColor; ?>;
            --footer-color: <?php echo $footerColor; ?>;
            --footer-text-color: <?php echo $footerTextColor; ?>;
            --background-light: #f4f5f9; 
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --text-dark: #333;
            --text-medium: #666;
            --danger-color: #FF3B30;
            --warning-color: #FF9500;
        }

        /* RESET, BASE E HEADER (Largura Total) */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .header-container {
            width: 100%;
            background-color: var(--header-color);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-img { max-height: 35px; width: auto; }
        
        /* Menu Desktop */
        .nav-links-desktop { display: flex; align-items: center; }
        .nav-links-desktop a { color: var(--header-text-color); text-decoration: none; margin-left: 20px; font-weight: 600; padding: 8px 12px; border-radius: 6px; }
        .nav-links-desktop .favorites-icon { 
            margin-left: 20px; 
            color: var(--header-text-color); 
            text-decoration: none; 
            position: relative; 
            font-size: 1.2em;
        }
        .nav-links-desktop .favorites-icon #favoritesHeart {
            color: var(--header-text-color); 
            transition: color 0.3s;
        }
        .nav-links-desktop .favorites-icon #favoritesHeart.is-favorite { 
            color: var(--danger-color);
        }

        /* Ícone do Carrinho */
        .nav-links-desktop #cartIcon {
             font-size: 1.2em;
             margin-right: 5px;
             transition: transform 0.2s;
        }
        /* ANIMAÇÃO DE CONFIRMAÇÃO DO CARRINHO */
        @keyframes cart-pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.9; color: var(--secondary-color); }
            100% { transform: scale(1); opacity: 1; }
        }
        .cart-added {
             animation: cart-pulse 0.6s ease-in-out;
        }

        .nav-links-desktop .favorites-count, #cartCount { 
            background-color: var(--danger-color); 
            color: white; 
            padding: 2px 7px; 
            border-radius: 50%; 
            font-size: 0.8em; 
            font-weight: 700; 
            display: none; 
            line-height: 1.4; 
            position: absolute; 
            top: -10px; 
            right: -15px; 
        }
        .nav-links-desktop #cartCount { background-color: var(--secondary-color); }
        
        /* Ícones Mobile */
        .nav-links-mobile { display: none; gap: 15px; align-items: center; }

        .main-container {
            max-width: 1200px; 
            margin: 0 auto;
            padding: 0 20px;
            /* Adiciona padding inferior para compensar o CTA fixo */
            padding-bottom: 70px; 
        }
        
        /* Layout da Página de Produto */
        .product-layout {
            display: flex;
            gap: 40px;
            margin-top: 30px;
        }
        .gallery-section { flex: 1.5; min-width: 400px; }
        .main-image { width: 100%; height: 450px; background-size: cover; background-position: center; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 15px; }
        .thumbnail-gallery { display: flex; gap: 10px; }
        .thumbnail { width: 80px; height: 80px; background-size: cover; background-position: center; border-radius: 8px; border: 2px solid var(--border-color); cursor: pointer; transition: border-color 0.2s; }
        .thumbnail.active { border-color: var(--primary-color); }
        .details-section { flex: 1; }
        
        /* Título e Preço */
        h1 { font-size: 2.2em; font-weight: 800; margin: 0 0 5px 0; }
        .product-price { font-size: 3em; font-weight: 800; color: var(--secondary-color); margin: 10px 0 20px 0; }
        
        /* Ações (Botões) */
        .action-buttons button { display: block; width: 100%; padding: 15px; border-radius: 8px; font-size: 1.1em; font-weight: 700; cursor: pointer; border: none; transition: opacity 0.3s; margin-bottom: 15px; }
        .btn-add-to-cart { background-color: var(--primary-color); color: white; }
        .btn-favorite { background-color: var(--card-bg); color: var(--text-dark); border: 1px solid var(--border-color); }
        .btn-favorite.is-favorite { color: var(--danger-color); border-color: var(--danger-color); }

        /* Entrega */
        .delivery-info { border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 15px; }
        .delivery-info h4 { font-size: 1em; margin: 0 0 5px 0; color: var(--primary-color); }
        .delivery-info p { font-size: 0.9em; color: var(--text-medium); margin: 5px 0; }
        
        /* Mensagens */
        .message-alert { 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 15px;
            font-weight: 600;
            display: none;
        }
        .message-alert.success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .message-alert.favorite {
            background-color: #fce8e8; /* Fundo mais claro para favoritos */
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Descrição */
        .description-content { margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .description-content h3 { font-size: 1.5em; margin-bottom: 15px; }
        .description-content p { color: var(--text-medium); }

        /* FOOTER (Largura Total) */
        .footer-container { width: 100%; background-color: var(--footer-color); color: var(--footer-text-color); padding: 30px 0; margin-top: 50px; }
        .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .footer-grid { grid-template-columns: repeat(3, 1fr); gap: 30px; padding-bottom: 20px; display: grid; }
        
        /* CORREÇÃO AQUI: Garante que os links no rodapé usem a cor de texto definida (--footer-text-color) */
        .footer-container .footer-section a {
            color: var(--footer-text-color);
            opacity: 0.8;
            text-decoration: none; /* remove sublinhado padrão */
            transition: opacity 0.2s;
        }
        .footer-container .footer-section a:hover {
            opacity: 1;
        }
        
        /* --- BARRA CTA FIXA (Mobile/Sticky) --- */
        .sticky-footer-cta {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
            padding: 10px 20px; /* Adiciona padding lateral ao contêiner */
            box-sizing: border-box;
            z-index: 999;
            display: none; 
            align-items: center;
        }
        .cta-price-mobile { display: none; }
        .cta-button-mobile {
            width: 90%; 
            padding: 12px;
            border-radius: 8px;
            font-size: 1.1em; 
            font-weight: 700;
            cursor: pointer;
            border: none;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            margin: 0 auto; 
            display: block; 
        }


        /* Responsive */
        @media (max-width: 900px) {
            .product-layout { flex-direction: column; }
            .gallery-section { min-width: 100%; }
            .main-image { height: 300px; }
            .footer-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .nav-links-desktop { display: none; }
            .nav-links-mobile { display: flex; }
            .sticky-footer-cta { display: flex; }
            /* Os botões originais (.action-buttons) permanecem visíveis! */
        }
    </style>
</head>
<body>

<div class="header-container">
    <div class="header-content">
        <a href="index.html">
             <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo da Loja" class="logo-img">
        </a>
        
        <div class="nav-links-desktop">
            <?php foreach ($storeSettings['headerLinks'] ?? [] as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['name']); ?></a>
            <?php endforeach; ?>
            <a href="cart_page.html">
                <i class="bi bi-bag" id="cartIcon"></i> Carrinho <span id="cartCount"></span>
            </a>
            <a href="favoritos.html" class="favorites-icon">
                 <span id="favoritesHeart">&#9825;</span> <span id="favoritesCount" class="favorites-count"></span>
            </a>
            </div>

        <div class="nav-links-mobile">
            <button id="mobileMenuButton">☰</button>
            <a href="cart_page.html">
                <i class="bi bi-bag" id="cartIconMobile"></i> <span id="cartCountMobile"></span>
            </a>
        </div>
    </div>
    
    <div id="mobileMenuDropdown" style="display:none; padding: 10px 20px; background-color: var(--header-color); border-top: 1px solid var(--border-color);">
        <?php foreach ($storeSettings['headerLinks'] ?? [] as $link): ?>
            <a href="<?php echo htmlspecialchars($link['url']); ?>" style="display: block; color: var(--text-dark); padding: 5px 0;"><?php echo htmlspecialchars($link['name']); ?></a>
        <?php endforeach; ?>
        <a href="favoritos.html" style="display: block; color: var(--text-dark); padding: 5px 0;">❤️ Favoritos</a>
        </div>
</div>

<div class="main-container">
    
    <?php if (!$productFound || ($productFound && $productData['visibility'] === 'hidden')): ?>
        <div style="text-align: center; padding: 100px 0;">
            <h1 style="color: var(--danger-color);">Produto Não Encontrado ou Oculto!</h1>
            <p style="color: var(--text-medium);">O produto pode ter sido desativado ou o link está incorreto.</p>
            <a href="index.html" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-top: 20px; display: inline-block;">&leftarrow; Voltar ao Catálogo</a>
        </div>
    <?php else: ?>
        <p style="margin-top: 30px; color: var(--text-medium); font-size: 0.9em;">
            <a href="index.html" style="color: var(--text-medium); text-decoration: none;">Catálogo</a> &gt; <?php echo htmlspecialchars($category); ?>
        </p>
        
        <div class="product-layout">
            
            <div class="gallery-section">
                <div class="main-image" id="mainImage" style="background-image: url('<?php echo htmlspecialchars($images[0] ?? "https://picsum.photos/400/400?item=default"); ?>');"></div>
                
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery">
                        <?php foreach ($images as $index => $image_url): ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 style="background-image: url('<?php echo htmlspecialchars($image_url); ?>');" 
                                 onclick="changeMainImage('<?php echo htmlspecialchars($image_url); ?>', this)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="details-section">
                
                <h1><?php echo htmlspecialchars($productTitle); ?></h1>
                
                <div style="color: var(--warning-color); font-size: 1.1em; margin-bottom: 15px; font-weight: 600;">
                    ★★★★★ <?php echo $rating; ?>
                    <span style="color: var(--text-medium); font-weight: 400; margin-left: 10px;">(<?php echo htmlspecialchars($reviewsCount); ?> visualizações)</span>
                </div>
                
                <div class="product-price">
                    R$ <?php echo htmlspecialchars($productValue); ?>
                </div>

                <div class="message-alert favorite" id="favoriteMessage"></div>

                <div class="action-buttons">
                    <button class="btn-add-to-cart" id="addToCartButton">
                        ADICIONAR AO CARRINHO
                    </button>
                    
                    <button class="btn-favorite" id="favoriteButton">
                        🖤 ADICIONAR AOS FAVORITOS
                    </button>
                </div>

                <div class="delivery-info">
                    <h4>📦 Estimativa de Entrega</h4>
                    <p id="deliveryTimeMessage">Carregando estimativa...</p>
                </div>
            </div>
        </div>
        
        <div class="description-content">
             <h3>Detalhes e Especificações</h3>
             <div><?php echo $description; ?></div>
        </div>

    <?php endif; ?>
</div>

<div class="footer-container">
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-section">
                <h4>Navegação</h4>
                <?php foreach ($storeSettings['headerLinks'] ?? [] as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer-section">
                <h4>Políticas</h4>
                <?php foreach ($storeSettings['footerLinks'] ?? [] as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer-section">
                <h4>Contato e Endereço</h4>
                <p style="margin-bottom: 5px;">Email: suporte@kiwify-store.com</p>
                <p style="margin-bottom: 5px;">Telefone: (11) 98888-7777</p>
                <p>Endereço: Rua dos Comerciantes, 123, SP</p>
            </div>
        </div>
        <div class="copyright">
            &copy; 2025 <?php echo htmlspecialchars($storeTitle); ?>. Todos os direitos reservados.
        </div>
    </div>
</div>

<div class="sticky-footer-cta" id="stickyCta" style="display:none;">
    <button class="cta-button-mobile" id="addToCartButtonSticky">
        ADICIONAR AO CARRINHO
    </button>
</div>


<script>
    // Variáveis PHP injetadas
    const PRODUCT_KEY = "<?php echo addslashes($productKey); ?>";
    const PRODUCT_TITLE = "<?php echo addslashes($productTitle); ?>";
    const PRODUCT_VALUE = "<?php echo addslashes($productValue); ?>";
    const PRODUCT_MANAGER_ENDPOINT = '/product_manager.php';
    
    // --- Lógica de Galeria ---
    function changeMainImage(imageUrl, element) {
        document.getElementById('mainImage').style.backgroundImage = `url('${imageUrl}')`;
        document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
        element.classList.add('active');
    }

    // --- Lógica de Estimativa de Entrega (Baseada em IP/Cidade) ---
    async function getDeliveryEstimate() {
        const messageElement = document.getElementById('deliveryTimeMessage');
        messageElement.innerHTML = 'Buscando sua localização para estimativa...';

        try {
            const cities = ['São Paulo, SP', 'Rio de Janeiro, RJ', 'Belo Horizonte, MG', 'Curitiba, PR'];
            const randomCity = cities[Math.floor(Math.random() * cities.length)];
            const deliveryDays = (randomCity.includes('São Paulo')) ? '2 a 4 dias úteis' : '5 a 7 dias úteis';
            
            // Usando <strong> para negrito
            messageElement.innerHTML = `Para <strong>${randomCity}</strong>: Prazo estimado de <strong>${deliveryDays}</strong>.`;

        } catch (e) {
            messageElement.innerHTML = `Não foi possível calcular o prazo. Prazo padrão: <strong>5 a 7 dias úteis</strong>.`;
        }
    }
    
    // --- Lógica de Contador de Views ---
    async function incrementProductView() {
        if (!PRODUCT_KEY || !PRODUCT_MANAGER_ENDPOINT) return;
        try {
            await fetch(PRODUCT_MANAGER_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'increment_view', productKey: PRODUCT_KEY }),
            });
        } catch (e) { console.error("Falha ao registrar visualização:", e); }
    }


    // --- Lógica de Carrinho e Favoritos ---
    function getCartItems() { return JSON.parse(localStorage.getItem('cartItems')) || []; }
    function getFavoriteItems() { return JSON.parse(localStorage.getItem('favoriteItems')) || []; }
    
    // Função para acionar a animação do carrinho
    function triggerCartAnimation() {
         const iconDesktop = document.getElementById('cartIcon');
         const iconMobile = document.getElementById('cartIconMobile');
         
         if (iconDesktop) {
            iconDesktop.classList.add('cart-added');
            setTimeout(() => iconDesktop.classList.remove('cart-added'), 600);
         }
         if (iconMobile) {
            iconMobile.classList.add('cart-added');
            setTimeout(() => iconMobile.classList.remove('cart-added'), 600);
         }
    }
    
    function updateCounters() {
        const cartCount = getCartItems().length;
        const favCount = getFavoriteItems().length;

        // Contadores do Menu
        const cartCountElement = document.getElementById('cartCount');
        const favoritesCountElement = document.getElementById('favoritesCount');
        const favoritesHeart = document.getElementById('favoritesHeart');
        
        if (cartCountElement) {
             cartCountElement.textContent = cartCount;
             cartCountElement.style.display = cartCount > 0 ? 'inline-block' : 'none';
        }
        if (favoritesCountElement) {
             favoritesCountElement.textContent = favCount;
             favoritesCountElement.style.display = favCount > 0 ? 'inline-block' : 'none';
        }
        
        // Atualiza ícone do Coração
        if (favoritesHeart) {
            favoritesHeart.classList.toggle('is-favorite', favCount > 0);
            favoritesHeart.innerHTML = favCount > 0 ? '&#9829;' : '&#9825;';
        }
    }
    
    function addToCart() {
        let cartItems = getCartItems();
        const existingItem = cartItems.find(item => item.key === PRODUCT_KEY);
        
        if (existingItem) { existingItem.quantity += 1; } 
        else { cartItems.push({ key: PRODUCT_KEY, title: PRODUCT_TITLE, value: PRODUCT_VALUE, quantity: 1 }); }
        
        localStorage.setItem('cartItems', JSON.stringify(cartItems));
        
        updateCounters(); // ATUALIZA O CONTADOR DO CARRINHO
        triggerCartAnimation(); // ACIONA A ANIMAÇÃO
    }
    
    function checkFavoriteStatus() {
        const favoriteBtn = document.getElementById('favoriteButton');
        const favorites = getFavoriteItems();
        const isFavorite = favorites.includes(PRODUCT_KEY);
        
        if (favoriteBtn) {
            favoriteBtn.classList.toggle('is-favorite', isFavorite);
            favoriteBtn.innerHTML = isFavorite ? '❤️ REMOVER DOS FAVORITOS' : '🖤 ADICIONAR AOS FAVORITOS';
        }
    }

    function toggleFavorite() {
        let favorites = getFavoriteItems();
        const index = favorites.indexOf(PRODUCT_KEY);
        
        if (index > -1) {
            favorites.splice(index, 1);
            const element = document.getElementById('favoriteMessage');
            if (element) {
                element.textContent = `"${PRODUCT_TITLE}" removido dos favoritos.`;
                element.style.display = 'block';
                setTimeout(() => { element.style.display = 'none'; }, 4000);
            }
        } else {
            favorites.push(PRODUCT_KEY);
            const element = document.getElementById('favoriteMessage');
            if (element) {
                element.textContent = `"${PRODUCT_TITLE}" adicionado aos favoritos!`;
                element.style.display = 'block';
                setTimeout(() => { element.style.display = 'none'; }, 4000);
            }
        }

        localStorage.setItem('favoriteItems', JSON.stringify(favorites));
        checkFavoriteStatus();
        updateCounters(); 
    }
    
    // --- Lógica para mostrar/esconder o botão fixo ---
    function checkVisibility() {
        const stickyCta = document.getElementById('stickyCta');
        
        if (!stickyCta) return;

        const isMobile = window.matchMedia("(max-width: 768px)").matches;
        
        if (isMobile) {
            stickyCta.style.display = 'flex';
        } else {
            stickyCta.style.display = 'none';
        }
    }

    // --- Inicialização ---
    document.addEventListener('DOMContentLoaded', function() {
        if (!PRODUCT_KEY) return; 

        incrementProductView(); 
        getDeliveryEstimate(); 
        
        const addToCartBtn = document.getElementById('addToCartButton');
        const addToCartBtnSticky = document.getElementById('addToCartButtonSticky');
        const favoriteBtn = document.getElementById('favoriteButton');
        
        if(addToCartBtn) addToCartBtn.addEventListener('click', addToCart);
        if(addToCartBtnSticky) addToCartBtnSticky.addEventListener('click', addToCart);

        if(favoriteBtn) favoriteBtn.addEventListener('click', toggleFavorite);
        
        checkFavoriteStatus(); 
        updateCounters(); 
        
        checkVisibility();
        window.addEventListener('resize', checkVisibility);

        // Menu Mobile Toggle
        document.getElementById('mobileMenuButton').addEventListener('click', () => {
             const dropdown = document.getElementById('mobileMenuDropdown');
             dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        window.changeMainImage = changeMainImage;
    });
</script>
</body>
</html>