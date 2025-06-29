<?php
// 1. Incluir configurações e iniciar sessão (se não iniciado)
require_once "config_db.php"; // config_db.php já tem session_start() seguro

// 2. Verificar se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?erro=" . urlencode("Acesso negado. Por favor, faça login.")); // Adicionar mensagem de erro
    exit;
}

// 3. Recuperar informações do usuário da sessão
// Verificar se o ID existe antes de usá-lo para evitar erros se não estiver definido
if (!isset($_SESSION["id"])) {
    // Isso indicaria um problema sério no processo de login ou na gestão da sessão
    error_log("Erro crítico em main.php: Usuário logado mas ID da sessão não encontrado. Sessão: " . print_r($_SESSION, true));
    // Destruir a sessão potencialmente corrompida e redirecionar para login
    session_unset();
    session_destroy();
    header("location: login.php?erro=" . urlencode("Erro de sessão (ID não encontrado). Por favor, faça login novamente."));
    exit;
}
$user_id = $_SESSION["id"];
$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Utilizador";
$email_usuario = isset($_SESSION["email"]) ? htmlspecialchars($_SESSION["email"]) : "Email não disponível";
$is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;
// A variável de sessão 'primeiro_login_realizado' não é mais usada aqui.

// Mensagem de boas-vindas padrão
$mensagem_boas_vindas_main = "Bem-vindo(a) à Biblioteca de Coimbra, <strong>" . $primeiro_nome . "</strong>!";

// Função para obter livros em destaque
function getLivrosDestaque($mysqli) {
    $sql = "SELECT l.*, d.ordem FROM destaques_semana d JOIN livros l ON d.livro_id = l.id ORDER BY d.ordem ASC";
    $res = $mysqli->query($sql);
    $livros = [];
    while($row = $res->fetch_assoc()) $livros[] = $row;
    return $livros;
}

// Atualizar destaques (admin)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destaque1'], $_POST['destaque2'], $_POST['destaque3'])) {
    $ids = [];
    if ($_POST['destaque1']) $ids[] = intval($_POST['destaque1']);
    if ($_POST['destaque2'] && $_POST['destaque2'] != $_POST['destaque1']) $ids[] = intval($_POST['destaque2']);
    if ($_POST['destaque3'] && $_POST['destaque3'] != $_POST['destaque1'] && $_POST['destaque3'] != $_POST['destaque2']) $ids[] = intval($_POST['destaque3']);
    $mysqli->query("DELETE FROM destaques_semana");
    $ordem = 1;
    foreach ($ids as $id) {
        $stmt = $mysqli->prepare("INSERT INTO destaques_semana (livro_id, ordem) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $ordem);
            $stmt->execute();
            $stmt->close();
            $ordem++;
        }
    }
    header("Location: main.php");
    exit;
}
$livros_destaque = getLivrosDestaque($mysqli);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Coimbra - Início</title>
    <link rel="stylesheet" href="estilo.css"> <!-- O CSS principal -->
    <style>
        /* Estilos específicos para main.php - Layout Básico */
        /* A maioria dos estilos da navbar foi movida para estilo.css */

        .main-content-area {
            /* Mantido para especificidade da página, se necessário, ou pode ser generalizado em estilo.css */
            text-align: center; /* Centralizar texto no conteúdo principal por padrão */
        }

        .main-content-area h1 {
            font-family: 'Lora', serif;
            color: #5a4a3b;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
         .main-content-area h1::after { /* Ornamento do título */
            content: "❦";
            font-size: 0.5em;
            color: #8c7b6a;
            margin-left: 10px;
            vertical-align: middle;
        }

        .main-content-area p {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2em;
            line-height: 1.7;
            color: #3a3a3a;
            margin-bottom: 15px;
        }
        .user-details-main {
            background-color: #fdfaf6;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 25px auto;
            border-left: 5px solid #8c7b6a;
            text-align: left;
            max-width: 600px;
        }
        .user-details-main strong {
            color: #6b5b4c;
        }
        .admin-notice-main {
            background-color: #fff9c4;
            color: #795548;
            padding: 12px 18px;
            border-radius: 5px;
            margin: 20px auto;
            border: 1px dashed #c1b28A;
            max-width: 600px;
        }

        /* Estilos para Novas Seções */
        .gallery-section, .featured-books-section {
            margin-top: 40px;
            margin-bottom: 40px;
            width: 100%;
        }

        .gallery-section h2, .featured-books-section h2 {
            font-family: 'Lora', serif;
            color: #5a4a3b;
            text-align: center;
            font-size: 2em;
            margin-bottom: 30px;
            border-bottom: 2px solid #d2c8b6;
            padding-bottom: 10px;
            display: inline-block;
        }

        .gallery-images {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 20px;
        }

        .gallery-image-container {
            flex-basis: calc(50% - 20px);
            max-width: calc(50% - 20px);
            text-align: center;
            margin-bottom: 20px;
        }

        .gallery-image-container img {
            width: 100%;
            max-width: 450px;
            height: auto;
            border-radius: 15px 50px 15px 50px;
            border: 3px solid #d2c8b6;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            object-fit: cover;
        }

        .image-caption {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
            color: #6b5b4c;
            margin-top: 10px;
        }

        .shortcut-button-container {
            text-align: center;
            margin: 30px 0;
        }

        .btn-highlight {
            background-color: #8c7b6a;
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .btn-highlight:hover {
            background-color: #7a6a56;
        }

        .book-cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 25px;
        }

        .book-card {
            background-color: #fdfaf6;
            border: 1px solid #e0d8c7;
            border-radius: 10px;
            padding: 20px;
            width: calc(33.333% - 30px);
            min-width: 220px;
            max-width: 280px;
            box-shadow: 0 3px 7px rgba(0,0,0,0.07);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .book-card img {
            width: 100%;
            max-width: 150px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .book-card h4 {
            font-family: 'Lora', serif;
            font-size: 1.25em;
            color: #5a4a3b;
            margin-bottom: 8px;
            min-height: 2.5em;
        }

        .book-card p {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
            color: #6b5b4c;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9em;
            margin-top: auto;
        }

        footer { /* Estilo do footer pode ser globalizado em estilo.css se for igual em todas as páginas */
            width: 100%;
            text-align: center;
            padding: 20px 0;
            background-color: #e4d9c5;
            color: #5a4a3b;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
            margin-top: 40px;
            border-top: 1px solid #d2c8b6;
        }

        /* Media queries específicas da página, se necessárias, ou generalizar em estilo.css */
        @media (max-width: 992px) {
            .gallery-image-container {
                flex-basis: calc(50% - 15px);
                max-width: calc(50% - 15px);
            }
            .book-card {
                width: calc(50% - 20px);
                max-width: none;
            }
        }
        @media (max-width: 768px) {
             .gallery-image-container {
                flex-basis: 100%;
                max-width: 80%;
                margin-left: auto;
                margin-right: auto;
            }
            .gallery-image-container img {
                 max-width: 100%;
            }
            .book-card {
                width: calc(100% - 20px);
                 max-width: 320px;
                 margin-left: auto;
                 margin-right: auto;
            }
            .featured-books-section h2, .gallery-section h2 {
                font-size: 1.8em;
            }
            /* Ajustes de responsividade para a navbar já estão em estilo.css */
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <?php if ($is_admin): ?>
                <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Estante dos Livros Encantados</a>
                <a href="criar_livro.php" <?php if (basename($_SERVER['PHP_SELF']) == 'criar_livro.php') echo 'class="active"'; ?>>Criar Novo Livro</a>
            <?php endif; ?>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
            <?php if (!$is_admin): ?>
                <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Lugar Encantado dos Livros</a>
            <?php endif; ?>

        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php else: ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link" title="Configurações do Perfil">&#9881;</a> <!-- Símbolo de engrenagem -->
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

    <div class="main-content-area">
        <h1><?php echo $mensagem_boas_vindas_main; ?></h1>
        <div class="user-details-main">
            <p><strong>O seu email registado:</strong> <?php echo $email_usuario; ?></p>
        </div>
        <?php if ($is_admin): ?>
            <div class="admin-notice-main">
                <p><strong>Acesso de Administrador:</strong> Tem permissões elevadas no sistema.</p>
                <!-- Painel de Destaques Semana -->
                <div class="destaque-admin-form">
                    <form method="post" action="main.php">
                        <label for="destaque1">Livro em Destaque 1:</label>
                        <select name="destaque1" id="destaque1" required>
                            <option value="">Selecione...</option>
                            <?php
                            $res = $mysqli->query("SELECT id, titulo FROM livros ORDER BY titulo ASC");
                            while($row = $res->fetch_assoc()) {
                                echo '<option value="'.$row['id'].'"';
                                if (isset($livros_destaque[0]) && $livros_destaque[0]['id'] == $row['id']) echo ' selected';
                                echo '>'.htmlspecialchars($row['titulo']).'</option>';
                            }
                            ?>
                        </select>
                        <label for="destaque2" style="margin-left:20px;">Livro em Destaque 2:</label>
                        <select name="destaque2" id="destaque2">
                            <option value="">Selecione...</option>
                            <?php
                            $res = $mysqli->query("SELECT id, titulo FROM livros ORDER BY titulo ASC");
                            while($row = $res->fetch_assoc()) {
                                echo '<option value="'.$row['id'].'"';
                                if (isset($livros_destaque[1]) && $livros_destaque[1]['id'] == $row['id']) echo ' selected';
                                echo '>'.htmlspecialchars($row['titulo']).'</option>';
                            }
                            ?>
                        </select>
                        <label for="destaque3" style="margin-left:20px;">Livro em Destaque 3:</label>
                        <select name="destaque3" id="destaque3">
                            <option value="">Selecione...</option>
                            <?php
                            $res = $mysqli->query("SELECT id, titulo FROM livros ORDER BY titulo ASC");
                            while($row = $res->fetch_assoc()) {
                                echo '<option value="'.$row['id'].'"';
                                if (isset($livros_destaque[2]) && $livros_destaque[2]['id'] == $row['id']) echo ' selected';
                                echo '>'.htmlspecialchars($row['titulo']).'</option>';
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn btn-success" style="margin-left:20px;">Atualizar Destaques</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Seção Galeria de Fotos -->
        <section class="gallery-section">
            <h2>Conheça a Nossa Biblioteca</h2>
            <div class="gallery-images">
                <div class="gallery-image-container">
                    <img src="imagem-fora.png" alt="Foto da entrada da Biblioteca de Coimbra - Fachada Histórica">
                    <p class="image-caption">Biblioteca De Coimbra</p>
                </div>
                <div class="gallery-image-container">
                    <img src="imagem-dentro.png" alt="Foto da sala de leitura principal da Biblioteca de Coimbra">
                    <p class="image-caption">Ambiente relaxante para uma leitura</p>
                </div>
            </div>
        </section>

        <!-- Botão de Atalho -->
        <div class="shortcut-button-container" style="text-align: center; margin-top: 30px; margin-bottom: 30px;">
            <a href="#livros-da-semana" class="btn btn-highlight">Descubra os Livros em Destaque</a>
        </div>

        <!-- Seção Livros da Semana (Destaques) -->
        <section id="livros-da-semana" class="featured-books-section">
            <h2>Em Destaque Esta Semana</h2>
            <div class="book-cards-container">
                <?php if (count($livros_destaque) == 0): ?>
                    <div style="color:#7a6a56; font-size:1.1em;">Nenhum livro em destaque esta semana.</div>
                <?php else: foreach ($livros_destaque as $livro): ?>
                    <div class="book-card">
                        <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/150x220/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa do Livro <?php echo htmlspecialchars($livro['titulo']); ?>">
                        <h4><?php echo htmlspecialchars($livro['titulo']); ?></h4>
                        <p class="autor-destaque"><?php echo htmlspecialchars($livro['autor']); ?></p>
                        <button type="button" class="btn btn-small btn-info btn-toggle-resumo" data-livro-id="<?php echo $livro['id']; ?>">Ver Resumo</button>
                        <div class="resumo-destaque-container" id="resumo-livro-<?php echo $livro['id']; ?>" style="display:none;">
                            <p class="resumo-destaque"><?php echo nl2br(htmlspecialchars($livro['resumo'])); ?></p>
                        </div>
                        <?php if ($is_admin): ?>
                            <div class="admin-controls-destaque" style="margin-top:10px;">
                                <a href="editar_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-small btn-info">Editar Detalhes</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
        <!-- ...resto da página... -->
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleResumoButtons = document.querySelectorAll('.btn-toggle-resumo');
            toggleResumoButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const livroId = this.dataset.livroId;
                    const resumoContainer = document.getElementById('resumo-livro-' + livroId);
                    if (resumoContainer) {
                        if (resumoContainer.style.display === 'none') {
                            resumoContainer.style.display = 'block';
                            this.textContent = 'Ocultar Resumo';
                        } else {
                            resumoContainer.style.display = 'none';
                            this.textContent = 'Ver Resumo';
                        }
                    }
                });
            });
        });
    </script>

</body>
</html>
    </footer>

</body>
</html>
</html>
