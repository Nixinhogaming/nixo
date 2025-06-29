<?php
session_start();
require_once "config_db.php";

$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Visitante";
$is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

// Lógica de boas-vindas para primeira visita (usando localStorage no cliente)
// A mensagem será exibida via JavaScript
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Leitura - Biblioteca de Coimbra</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .boas-vindas-leitura {
            background-color: #fdfaf6;
            border: 1px solid #e0d8c7;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.1em;
            color: #5a4a3b;
        }
        .boas-vindas-leitura strong {
            color: #4a3b32;
        }
        /* Estilos para barra de pesquisa e grid de livros podem ser herdados
           de .barra-pesquisa-simples e .livros-grid-encantado ou definidos/ajustados aqui */
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if(basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <div class="dropdown-categorias">
                <a href="#" class="dropdown-toggle">Categorias</a>
                <div class="dropdown-menu">
                    <a href="livros_biblioteca.php">Lugar Encantado dos Livros</a>
                    <a href="leitura.php" <?php if(basename($_SERVER['PHP_SELF']) == 'leitura.php') echo 'class="active"'; ?>>Leitura</a>
                    <?php if ($is_admin): ?>
                        <a href="estante_livros_encantados.php">Estante (Admin)</a>
                        <a href="criar_livro.php">Criar Livro (Admin)</a>
                        <a href="meus_livros_manuais.php">Manuais (Admin)</a>
                    <?php endif; ?>
                </div>
            </div>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
            <?php if (!$is_admin): ?>
                <a href="livros_biblioteca.php" <?php if(basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php' && !isset($_GET['categoria'])) echo 'class="active"';?>>Livros</a>
                <a href="configuracoes_perfil.php" <?php if(basename($_SERVER['PHP_SELF']) == 'configuracoes_perfil.php') echo 'class="active"';?>>Configurações</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php elseif ($user_id): // Mostra config apenas se logado e não admin ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link <?php if(basename($_SERVER['PHP_SELF']) == 'configuracoes_perfil.php') echo 'active';?>" title="Configurações do Perfil">&#9881;</a>
            <?php endif; ?>
            <?php if ($user_id): ?>
                <a href="logout.php" class="logout-btn">Terminar Sessão</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="padding: 8px 15px; text-transform: none; font-size:0.95em;">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content-area">
        <div id="mensagem-boas-vindas-container" style="display:none;">
            <div class="boas-vindas-leitura">
                Bem-vindo(a) à nossa área de Leitura, <strong><?php echo $primeiro_nome; ?></strong>! Explore os nossos livros e esperamos que encontre ótimas aventuras literárias.
            </div>
        </div>

        <h1 class="titulo-encantado" style="text-align:center;">Espaço de Leitura</h1>

        <!-- Barra de Pesquisa -->
        <form class="barra-pesquisa-simples" method="get" action="leitura.php" style="margin-top: 20px; margin-bottom: 40px;">
            <input type="text" name="q" placeholder="Pesquisar por título ou autor..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            <button type="submit">Pesquisar</button>
        </form>

        <!-- Listagem de Livros -->
        <div class="livros-grid-encantado" id="lista-livros-leitura">
            <?php
            $filtro_texto_leitura = isset($_GET['q']) ? trim($_GET['q']) : '';
            $sql_livros = "SELECT id, titulo, autor, imagem, resumo, faixa_etaria, idade_livro FROM livros";
            $conditions_livros = [];
            $params_livros = [];
            $types_livros = '';

            // Aplicar filtro de faixa etária para usuários não-admin
            if ($user_id && !$is_admin) {
                $stmt_user_pref_leitura = $mysqli->prepare("SELECT faixa_etaria_utilizador, preferencia_ver_faixas_inferiores FROM usuarios WHERE id = ?");
                if ($stmt_user_pref_leitura) {
                    $stmt_user_pref_leitura->bind_param("i", $user_id);
                    $stmt_user_pref_leitura->execute();
                    $result_user_pref_leitura = $stmt_user_pref_leitura->get_result();
                    if ($user_data_pref_leitura = $result_user_pref_leitura->fetch_assoc()) {
                        $faixa_etaria_usuario_leitura = $user_data_pref_leitura['faixa_etaria_utilizador'];
                        $preferencia_ver_inferiores_leitura = (bool)$user_data_pref_leitura['preferencia_ver_faixas_inferiores'];

                        $faixa_ordem_leitura = ["Livre" => 0, "6-8" => 1, "9-12" => 2, "13-17" => 3, "18+" => 4];
                        $nivel_usuario_leitura = $faixa_ordem_leitura[$faixa_etaria_usuario_leitura] ?? 4;
                        $faixas_permitidas_sql_leitura = ["'Livre'"];

                        if ($preferencia_ver_inferiores_leitura) {
                            foreach ($faixa_ordem_leitura as $faixa => $ordem) {
                                if ($ordem <= $nivel_usuario_leitura) {
                                    $faixas_permitidas_sql_leitura[] = "'" . $mysqli->real_escape_string($faixa) . "'";
                                }
                            }
                        } else {
                            $faixas_permitidas_sql_leitura[] = "'" . $mysqli->real_escape_string($faixa_etaria_usuario_leitura) . "'";
                        }
                        $faixas_permitidas_sql_str_leitura = implode(",", array_unique($faixas_permitidas_sql_leitura));
                        if (!empty($faixas_permitidas_sql_str_leitura)) {
                            $conditions_livros[] = "faixa_etaria IN (" . $faixas_permitidas_sql_str_leitura . ")";
                        }
                    }
                    $stmt_user_pref_leitura->close();
                }
            }

            if ($filtro_texto_leitura) {
                $conditions_livros[] = "(titulo LIKE ? OR autor LIKE ?)";
                $params_livros[] = "%$filtro_texto_leitura%";
                $params_livros[] = "%$filtro_texto_leitura%";
                $types_livros .= 'ss';
            }

            if (!empty($conditions_livros)) {
                $sql_livros .= " WHERE " . implode(" AND ", $conditions_livros);
            }
            $sql_livros .= " ORDER BY criado_em DESC";

            $stmt_livros_leitura = $mysqli->prepare($sql_livros);
            if ($stmt_livros_leitura) {
                if (!empty($types_livros)) {
                    $stmt_livros_leitura->bind_param($types_livros, ...$params_livros);
                }
                $stmt_livros_leitura->execute();
                $result_livros_leitura = $stmt_livros_leitura->get_result();

                if ($result_livros_leitura->num_rows > 0) {
                    while($livro = $result_livros_leitura->fetch_assoc()): ?>
                        <div class="book-card">
                            <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/140x200/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                            <h4><?php echo htmlspecialchars($livro['titulo']); ?></h4>
                            <p class="autor"><?php echo htmlspecialchars($livro['autor']); ?></p>
                            <div class="resumo-destaque-container" style="display:block; text-align:justify;"> <!-- Resumo sempre visível -->
                                <p class="resumo-destaque"><?php echo nl2br(htmlspecialchars(mb_strimwidth($livro['resumo'], 0, 150, "..."))); ?></p> <!-- Limitar resumo e justificar -->
                            </div>
                            <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="btn btn-primary btn-small" style="margin-top:10px;">Abrir Livro</a>
                        </div>
                    <?php endwhile;
                } else {
                    echo '<p class="nenhum-livro-aviso" style="grid-column: 1 / -1;">Nenhum livro encontrado com os critérios atuais.</p>';
                }
                $stmt_livros_leitura->close();
            } else {
                echo '<p class="error-message" style="grid-column: 1 / -1;">Erro ao buscar livros: ' . $mysqli->error . '</p>';
            }
            ?>
        </div>
    </div>

    <footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bemVindoContainer = document.getElementById('mensagem-boas-vindas-container');
            const jaVisitouLeitura = localStorage.getItem('jaVisitouPaginaLeitura');

            if (!jaVisitouLeitura && <?php echo $user_id ? 'true' : 'false'; ?>) { // Mostra apenas para usuários logados na primeira visita
                bemVindoContainer.style.display = 'block';
                localStorage.setItem('jaVisitouPaginaLeitura', 'true');
            }

            // TODO: Implementar a busca e exibição dos livros aqui
            // (Similar à lógica de livros_biblioteca.php, mas adaptada para esta página)
            // Deverá buscar todos os livros, respeitar filtro de faixa etária do usuário,
            // ordenar por mais recente, e para cada livro mostrar: capa, título, autor, resumo, botão "Abrir Livro".
        });
    </script>
</body>
</html>
