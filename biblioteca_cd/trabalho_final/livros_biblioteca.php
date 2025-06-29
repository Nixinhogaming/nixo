<?php
session_start();
require_once "config_db.php";
$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Utilizador";
$is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;

// Filtros simples
$filtro_texto = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM livros";
$conditions = [];
$params = [];
$types = '';
$conditions = []; // Renomeado de $conditions para evitar conflito com a de cima se não for admin

// Lógica de Faixa Etária (Apenas para não-admins)
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

if ($user_id && !$is_admin) {
    $faixa_etaria_usuario = null;
    $preferencia_ver_inferiores = true; // Default

    $stmt_user_pref = $mysqli->prepare("SELECT faixa_etaria_utilizador, preferencia_ver_faixas_inferiores FROM usuarios WHERE id = ?");
    if ($stmt_user_pref) {
        $stmt_user_pref->bind_param("i", $user_id);
        $stmt_user_pref->execute();
        $result_user_pref = $stmt_user_pref->get_result();
        if ($user_data_pref = $result_user_pref->fetch_assoc()) {
            $faixa_etaria_usuario = $user_data_pref['faixa_etaria_utilizador'];
            $preferencia_ver_inferiores = (bool)$user_data_pref['preferencia_ver_faixas_inferiores'];
        }
        $stmt_user_pref->close();
    }

    $faixa_ordem = [
        "Livre" => 0, "6-8" => 1, "9-12" => 2, "13-17" => 3, "18+" => 4
    ];

    if ($faixa_etaria_usuario) {
        $nivel_usuario = $faixa_ordem[$faixa_etaria_usuario] ?? 4;
        $faixas_permitidas_sql = ["'Livre'"];

        if ($preferencia_ver_inferiores) {
            foreach ($faixa_ordem as $faixa => $ordem) {
                if ($ordem <= $nivel_usuario) {
                    $faixas_permitidas_sql[] = "'" . $mysqli->real_escape_string($faixa) . "'";
                }
            }
        } else {
            $faixas_permitidas_sql[] = "'" . $mysqli->real_escape_string($faixa_etaria_usuario) . "'";
        }
        $faixas_permitidas_sql_str = implode(",", array_unique($faixas_permitidas_sql));
        if (!empty($faixas_permitidas_sql_str)) {
            $conditions[] = "faixa_etaria IN (" . $faixas_permitidas_sql_str . ")";
        }
    }
}

// Filtro A-Z (Apenas para Admins nesta página agora)
$letra_filtro = '';
if ($is_admin) {
    $letra_filtro = isset($_GET['letra']) ? trim(strtoupper($_GET['letra'])) : '';
    if (preg_match('/^[A-Z]$/', $letra_filtro)) {
        $conditions[] = "titulo LIKE ?";
        $params[] = $letra_filtro . "%";
        $types .= 's';
    }
}

if ($filtro_texto) {
    $conditions[] = "(titulo LIKE ? OR autor LIKE ?)";
    $params[] = "%$filtro_texto%";
    $params[] = "%$filtro_texto%";
    $types .= 'ss';
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY criado_em DESC";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Tratar erro de preparação do statement
    echo "Erro ao preparar a consulta de livros: " . $mysqli->error;
    $result = null; // Para evitar erros no while loop abaixo
}

// Preparar arrays para as duas seções de livros se não for admin
$livros_com_idade = [];
$livros_criados_pela_biblioteca = [];

if ($result && !$is_admin) {
    while ($livro_temp = $result->fetch_assoc()) {
        if (!is_null($livro_temp['idade_livro']) && $livro_temp['idade_livro'] !== '') { // Considera 0 como idade válida
            $livros_com_idade[] = $livro_temp;
        } else {
            $livros_criados_pela_biblioteca[] = $livro_temp;
        }
    }
    // Se for admin, $result será usado diretamente no loop mais abaixo.
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Lugar Encantado dos Livros</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        /* Estilos específicos para livros_biblioteca.php */
        /* A maioria dos estilos de .book-card, .navbar, etc., virá de estilo.css */

        /* .barra-pesquisa-simples e seus sub-estilos foram movidos para estilo.css e renomeados para .barra-pesquisa-staff */

        .livros-grid-encantado {
            display: grid;
            /* Usar a classe .book-cards-container de estilo.css se for aplicável,
               ou manter esta classe se houver necessidade de layout de grid específico aqui.
               Para consistência, é melhor usar uma classe global de container de cards.
               Assumindo que .book-card já é estilizado globalmente. */
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Cards um pouco menores para caber mais */
            gap: 30px; /* Espaçamento entre cards */
        }

        .nenhum-livro-aviso { /* Estilo para a mensagem de nenhum livro */
            text-align:center;
            color:#7a6a56;
            font-size:1.2em;
            margin-top:40px;
            font-family: 'Lora', serif;
        }
        /* Footer style from estilo.css */

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
                <a href="livros_biblioteca.php" <?php if(basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"';?>>Lugar Encantado dos Livros</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php else: ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link" title="Configurações do Perfil">&#9881;</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>
    <div class="main-content-area encantado-container">
        <h1 class="titulo-encantado"><?php echo $is_admin ? "Estante dos Livros Encantados" : "Lugar Encantado dos Livros"; ?></h1>

        <div class="filtros-container">
            <form class="barra-pesquisa-staff" method="get" action="livros_biblioteca.php">
                <input type="text" name="q" placeholder="Pesquisar por título ou autor..." value="<?php echo htmlspecialchars($filtro_texto); ?>">
                <?php if ($is_admin && $letra_filtro): ?>
                    <input type="hidden" name="letra" value="<?php echo htmlspecialchars($letra_filtro); ?>">
                <?php endif; ?>
                <button type="submit">Pesquisar</button>
            </form>

            <?php if ($is_admin):
                $alfabeto = range('A', 'Z'); // Definir alfabeto para o admin
            ?>
                <div class="filtro-az">
                    <span class="filtro-az-label">Filtrar por Letra:</span>
                    <a href="livros_biblioteca.php?<?php echo $filtro_texto ? 'q='.urlencode($filtro_texto) : ''; ?>" class="filtro-az-link <?php echo empty($letra_filtro) ? 'active' : ''; ?>">Todos</a>
                    <?php foreach ($alfabeto as $letra_loop): ?>
                        <a href="livros_biblioteca.php?letra=<?php echo $letra_loop; ?>&amp;<?php echo $filtro_texto ? 'q='.urlencode($filtro_texto) : ''; ?>" class="filtro-az-link <?php echo $letra_filtro == $letra_loop ? 'active' : ''; ?>">
                            <?php echo $letra_loop; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($is_admin): ?>
            <div class="livros-grid-encantado">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php mysqli_data_seek($result, 0); // Reset pointer se $result foi usado antes para popular os arrays ?>
                    <?php while($livro = $result->fetch_assoc()): ?>
                        <div class="book-card">
                            <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="book-card-link-imagem">
                                <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/140x200/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                            </a>
                            <h4><a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>"><?php echo htmlspecialchars($livro['titulo']); ?></a></h4>
                            <p class="autor"><?php echo htmlspecialchars($livro['autor']); ?></p>
                            <div class="book-card-details">
                                <?php if (isset($livro['idade_livro']) && $livro['idade_livro'] !== '' && !is_null($livro['idade_livro'])): ?>
                                    <div class="detalhe-livro"><strong>Idade Rec.:</strong> <?php echo htmlspecialchars($livro['idade_livro']); ?> anos</div>
                                <?php else: ?>
                                    <div class="detalhe-livro"><strong>Idade Rec.:</strong> Não Definida</div>
                                <?php endif; ?>
                                <div class="detalhe-livro"><strong>Faixa Etária:</strong> <?php echo htmlspecialchars($livro['faixa_etaria']); ?></div>
                                <div class="detalhe-livro"><strong>Tipo:</strong> <?php echo htmlspecialchars(ucfirst($livro['tipo_criacao'] ?? 'N/A')); ?></div>
                                <div class="detalhe-livro"><strong>Adicionado em:</strong> <?php echo htmlspecialchars(date("d/m/Y", strtotime($livro['criado_em']))); ?></div>
                                <!-- Adicionar criado_por_id ou nome do criador se desejar -->
                            </div>
                            <div class="resumo-curto">
                                 <?php
                                    $resumo_curto = nl2br(htmlspecialchars(mb_strimwidth($livro['resumo'], 0, 120, "")));
                                    echo $resumo_curto;
                                    if (mb_strlen($livro['resumo']) > 120) {
                                        echo '... <a href="#" class="link-ver-resumo" data-livro-id="'.$livro['id'].'"> (+ Ver Resumo Completo)</a>';
                                    }
                                 ?>
                            </div>
                            <div class="resumo-completo" id="resumo-completo-<?php echo $livro['id']; ?>" style="display:none; text-align:justify; margin-top:10px; font-size:0.9em;">
                                <?php echo nl2br(htmlspecialchars($livro['resumo'])); ?>
                                <a href="#" class="link-ocultar-resumo" data-livro-id="<?php echo $livro['id']; ?>">(Ocultar Resumo)</a>
                            </div>
                            <div class="livro-botoes admin-controls-card" style="margin-top:15px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                                <a href="editar_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-warning btn-small">Editar Dados</a>
                                <?php if (isset($livro['tipo_criacao']) && $livro['tipo_criacao'] == 'manual'): ?>
                                    <a href="editor_paginas_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-info btn-small">Editar Páginas</a>
                                <?php endif; ?>
                                <?php
                                $caminho_ficheiro_admin = $livro['ficheiro'] ?? null;
                                if ($caminho_ficheiro_admin && file_exists($caminho_ficheiro_admin) && strtolower(pathinfo($caminho_ficheiro_admin, PATHINFO_EXTENSION)) == 'pdf'):
                                ?>
                                    <a href="download_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-success btn-small">Baixar PDF</a>
                                <?php endif; ?>
                                <a href="apagar_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Tem a certeza que deseja apagar este livro e todas as suas páginas? Esta ação é irreversível.');">Apagar</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="nenhum-livro-aviso" style="grid-column: 1 / -1;">Nenhum livro encontrado com os filtros atuais.</p>
                <?php endif; ?>
            </div>
        <?php else: // Visualização para NÃO-ADMINS (separada) ?>
            <h2 class="titulo-secao-livros">Livros</h2>
            <div class="livros-grid-encantado">
                <?php if (!empty($livros_com_idade)): ?>
                    <?php foreach($livros_com_idade as $livro): ?>
                        <div class="book-card">
                            <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="book-card-link-imagem">
                                <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/140x200/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                            </a>
                            <h4><a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>"><?php echo htmlspecialchars($livro['titulo']); ?></a></h4>
                            <p class="autor"><?php echo htmlspecialchars($livro['autor']); ?></p>
                            <div class="book-card-details">
                                <?php if (isset($livro['idade_livro']) && $livro['idade_livro'] !== '' && !is_null($livro['idade_livro'])): ?>
                                    <div class="detalhe-livro"><strong>Idade:</strong> <?php echo htmlspecialchars($livro['idade_livro']); ?> anos</div>
                                <?php endif; ?>
                                <div class="detalhe-livro"><strong>Faixa Etária:</strong> <?php echo htmlspecialchars($livro['faixa_etaria']); ?></div>
                            </div>
                            <div class="resumo-curto">
                                 <?php
                                    $resumo_curto = nl2br(htmlspecialchars(mb_strimwidth($livro['resumo'], 0, 120, "")));
                                    echo $resumo_curto;
                                    if (mb_strlen($livro['resumo']) > 120) {
                                        echo '... <a href="#" class="link-ver-resumo" data-livro-id="'.$livro['id'].'"> (+ Ver Resumo Completo)</a>';
                                    }
                                 ?>
                            </div>
                            <div class="resumo-completo" id="resumo-completo-<?php echo $livro['id']; ?>" style="display:none; text-align:justify; margin-top:10px; font-size:0.9em;">
                                <?php echo nl2br(htmlspecialchars($livro['resumo'])); ?>
                                <a href="#" class="link-ocultar-resumo" data-livro-id="<?php echo $livro['id']; ?>">(Ocultar Resumo)</a>
                            </div>
                            <div class="livro-botoes user-controls-card" style="margin-top:15px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                                <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="btn btn-primary btn-small">Ler Livro</a>
                                <?php
                                $caminho_ficheiro_livro = $livro['ficheiro'] ?? null;
                                if ($caminho_ficheiro_livro && file_exists($caminho_ficheiro_livro) && strtolower(pathinfo($caminho_ficheiro_livro, PATHINFO_EXTENSION)) == 'pdf'):
                                ?>
                                    <a href="download_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-info btn-small">Baixar PDF</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="nenhum-livro-aviso" style="grid-column: 1 / -1;">Nenhum livro com indicação de idade encontrado.</p>
                <?php endif; ?>
            </div>

            <h2 class="titulo-secao-livros" style="margin-top: 40px;">Livros Criados Por Nós</h2>
            <div class="livros-grid-encantado">
                <?php if (!empty($livros_criados_pela_biblioteca)): ?>
                    <?php foreach($livros_criados_pela_biblioteca as $livro): ?>
                         <div class="book-card">
                            <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="book-card-link-imagem">
                                <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/140x200/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                            </a>
                            <h4><a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>"><?php echo htmlspecialchars($livro['titulo']); ?></a></h4>
                            <p class="autor"><?php echo htmlspecialchars($livro['autor']); ?></p>
                            <div class="book-card-details">
                                <div class="detalhe-livro"><strong>Faixa Etária:</strong> <?php echo htmlspecialchars($livro['faixa_etaria']); ?></div>
                                <div class="detalhe-livro" style="font-style: italic; color: #5a4a3b;">Uma criação especial da nossa biblioteca!</div>
                            </div>
                             <div class="resumo-curto">
                                 <?php
                                    $resumo_curto = nl2br(htmlspecialchars(mb_strimwidth($livro['resumo'], 0, 120, "")));
                                    echo $resumo_curto;
                                    if (mb_strlen($livro['resumo']) > 120) {
                                        echo '... <a href="#" class="link-ver-resumo" data-livro-id="'.$livro['id'].'"> (+ Ver Resumo Completo)</a>';
                                    }
                                 ?>
                            </div>
                            <div class="resumo-completo" id="resumo-completo-<?php echo $livro['id']; ?>" style="display:none; text-align:justify; margin-top:10px; font-size:0.9em;">
                                <?php echo nl2br(htmlspecialchars($livro['resumo'])); ?>
                                <a href="#" class="link-ocultar-resumo" data-livro-id="<?php echo $livro['id']; ?>">(Ocultar Resumo)</a>
                            </div>
                            <div class="livro-botoes user-controls-card" style="margin-top:15px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                                <a href="ver_livro_detalhe.php?id=<?php echo $livro['id']; ?>" class="btn btn-primary btn-small">Ler Livro</a>
                                <?php
                                $caminho_ficheiro_livro_criado = $livro['ficheiro'] ?? null;
                                if ($caminho_ficheiro_livro_criado && file_exists($caminho_ficheiro_livro_criado) && strtolower(pathinfo($caminho_ficheiro_livro_criado, PATHINFO_EXTENSION)) == 'pdf'):
                                ?>
                                    <a href="download_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-info btn-small">Baixar PDF</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="nenhum-livro-aviso" style="grid-column: 1 / -1;">Nenhum livro criado por nós encontrado (ainda!).</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script para expandir/ocultar resumo
        const linksVerResumo = document.querySelectorAll('.link-ver-resumo');
        const linksOcultarResumo = document.querySelectorAll('.link-ocultar-resumo');

        linksVerResumo.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const livroId = this.dataset.livroId;
                const resumoCurto = this.closest('.resumo-curto');
                const resumoCompleto = document.getElementById('resumo-completo-' + livroId);

                if (resumoCurto) resumoCurto.style.display = 'none';
                if (resumoCompleto) resumoCompleto.style.display = 'block';
            });
        });

        linksOcultarResumo.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const livroId = this.dataset.livroId;
                const resumoCompleto = this.closest('.resumo-completo');
                const resumoCurto = resumoCompleto.previousElementSibling; // Assume que .resumo-curto é o irmão anterior

                if (resumoCompleto) resumoCompleto.style.display = 'none';
                if (resumoCurto && resumoCurto.classList.contains('resumo-curto')) {
                    resumoCurto.style.display = 'block';
                }
            });
        });
    });
    </script>
</body>
</html>
