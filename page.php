<?php
// page.php - Renderiza as páginas customizadas criadas no CMS
header('Content-Type: text/html; charset=UTF-8');

$pages_file = 'custom_pages.json';
$settings_file = 'store_settings.json'; 
$pageUrl = $_GET['url'] ?? null;

function read_json($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }
    return [];
}

$all_pages = read_json($pages_file);
$storeSettings = read_json($settings_file);
$page = $all_pages[$pageUrl] ?? null;

// Configurações de exibição (Usadas no menu e rodapé)
$primaryColor = $storeSettings['primaryColor'] ?? '#5B48CC';
$secondaryColor = $storeSettings['secondaryColor'] ?? '#28a745';
$headerColor = $storeSettings['headerColor'] ?? '#ffffff';
$footerColor = $storeSettings['footerColor'] ?? '#333333';
$storeTitle = $storeSettings['storeTitle'] ?? 'Kiwify Store';
$logoUrl = $storeSettings['logoUrl'] ?? 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Kiwify_Logo_-_Black.png';
// NOVO CAMPO
$faviconUrl = $storeSettings['faviconUrl'] ?? '';


$pageTitle = "Página Não Encontrada";
$pageContent = "<h1 style='color: var(--danger-color);'>Erro 404: Página Não Encontrada</h1><p>A URL que você tentou acessar não existe no CMS da loja.</p>";

if ($page && $page['visibility'] === 'visible') {
    $pageTitle = $page['title'];
    $pageContent = $page['content'];
}

// Obtém os links e garante que são arrays
$headerLinks = $storeSettings['headerLinks'] ?? [];
$footerLinks = $storeSettings['footerLinks'] ?? [];

if (!is_array($headerLinks)) $headerLinks = [];
if (!is_array($footerLinks)) $footerLinks = [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($storeTitle); ?></title>
    <?php if ($faviconUrl): ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* VARIÁVEIS DE CORES DINÂMICAS */
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --header-color: <?php echo $headerColor; ?>;
            --footer-color: <?php echo $footerColor; ?>;
            --background-light: #f4f5f9; 
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --text-dark: #333;
            --text-medium: #666;
            --danger-color: #FF3B30;
        }

        /* RESET, BASE E HEADER (Largura Total) */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark); 
            margin: 0;
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
        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
            transition: color 0.3s, background-color 0.3s;
        }
        .nav-links a:hover { color: var(--primary-color); background-color: #f0f0f0; }
        #cartCount { background-color: var(--secondary-color); color: white; padding: 2px 7px; border-radius: 50%; font-size: 0.8em; margin-left: 5px; display: none; }
        
        /* CONTEÚDO DA PÁGINA */
        .page-content-wrapper {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .page-content-wrapper h1 {
            color: var(--primary-color);
            font-size: 2em;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        /* FOOTER (Largura Total) */
        .footer-container {
            width: 100%;
            background-color: var(--footer-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .footer-grid {
             display: grid;
             grid-template-columns: repeat(3, 1fr); /* 3 colunas no desktop */
             gap: 30px;
             padding-bottom: 20px;
        }
        .footer-section h4 { color: var(--secondary-color); font-size: 1.2em; margin-bottom: 10px; }
        /* CORREÇÃO: Garante que os links usem a cor definida no store_settings */
        .footer-section a { 
            display: block; 
            color: var(--footer-text-color); 
            text-decoration: none; 
            margin-bottom: 5px; 
            font-size: 0.9em; 
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .footer-section a:hover {
            opacity: 1;
        }
        .copyright { border-top: 1px solid #555; padding-top: 20px; text-align: center; font-size: 0.8em; color: #aaa; }
        
        /* Responsive (Mobile Menu + Footer) */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .footer-grid { grid-template-columns: 1fr; }
            .header-content { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<div class="header-container">
    <div class="header-content">
        <a href="index.html">
             <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo da Loja" class="logo-img">
        </a>
        <div class="nav-links">
            <?php foreach ($headerLinks as $link): ?>
                <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['name']); ?></a>
            <?php endforeach; ?>
            <a href="cart_page.html">🛒 Carrinho <span id="cartCount"></span></a>
            <a href="favoritos.html">❤️ Favoritos</a>
        </div>
        <div style="display: none;">
            <button>☰</button>
            <a href="cart_page.html">🛒</a>
        </div>
    </div>
</div>

<div class="page-content-wrapper">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="custom-content">
        <?php echo $pageContent; ?>
    </div>
</div>

<div class="footer-container">
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-section">
                <h4>Navegação</h4>
                <?php foreach ($headerLinks as $link): ?>
                    <a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['name']); ?></a>
                <?php endforeach; ?>
                </div>
            <div class="footer-section">
                <h4>Políticas</h4>
                <?php foreach ($footerLinks as $link): ?>
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

<script>
    // Lógica simples para atualizar a contagem do carrinho no cabeçalho
    function getCartItems() { return JSON.parse(localStorage.getItem('cartItems')) || []; }
    function updateCartUI() {
        const cartCount = document.getElementById('cartCount');
        const count = getCartItems().length;
        if (cartCount) {
             cartCount.textContent = count;
             cartCount.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }
    document.addEventListener('DOMContentLoaded', updateCartUI);
</script>
</body>
</html>