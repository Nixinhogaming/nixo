<?php
session_start();
require_once "config_db.php";

$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Visitante";
$is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

$livro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$livro = null;
$paginas_livro_manual = [];

if ($livro_id <= 0) {
    // Redirecionar ou mostrar mensagem de erro se o ID for inválido
    header("location: livros_biblioteca.php?erro=livro_invalido");
    exit;
}

// Buscar dados do livro
$stmt_livro = $mysqli->prepare("SELECT * FROM livros WHERE id = ?");
if ($stmt_livro) {
    $stmt_livro->bind_param("i", $livro_id);
    $stmt_livro->execute();
    $result_livro = $stmt_livro->get_result();
    if ($result_livro->num_rows === 1) {
        $livro = $result_livro->fetch_assoc();
    } else {
        header("location: livros_biblioteca.php?erro=livro_nao_encontrado");
        exit;
    }
    $stmt_livro->close();
} else {
    die("Erro ao preparar consulta do livro.");
}

// Se for livro manual, buscar suas páginas
if ($livro && $livro['tipo_criacao'] == 'manual') {
    $stmt_paginas = $mysqli->prepare("SELECT numero_pagina, conteudo, imagem FROM livro_paginas WHERE livro_id = ? ORDER BY numero_pagina ASC");
    if ($stmt_paginas) {
        $stmt_paginas->bind_param("i", $livro_id);
        $stmt_paginas->execute();
        $result_paginas = $stmt_paginas->get_result();
        while($pagina_data = $result_paginas->fetch_assoc()){
            $paginas_livro_manual[] = $pagina_data;
        }
        $stmt_paginas->close();
    }
}

// Lógica de filtro de faixa etária para este livro específico (se o usuário não for admin)
if ($user_id && !$is_admin && $livro) {
    $stmt_user_pref_detalhe = $mysqli->prepare("SELECT faixa_etaria_utilizador, preferencia_ver_faixas_inferiores FROM usuarios WHERE id = ?");
    if ($stmt_user_pref_detalhe) {
        $stmt_user_pref_detalhe->bind_param("i", $user_id);
        $stmt_user_pref_detalhe->execute();
        $result_user_pref_detalhe = $stmt_user_pref_detalhe->get_result();
        if ($user_data_pref_detalhe = $result_user_pref_detalhe->fetch_assoc()) {
            $faixa_etaria_usuario_detalhe = $user_data_pref_detalhe['faixa_etaria_utilizador'];
            $preferencia_ver_inferiores_detalhe = (bool)$user_data_pref_detalhe['preferencia_ver_faixas_inferiores'];

            $faixa_ordem_detalhe = ["Livre" => 0, "6-8" => 1, "9-12" => 2, "13-17" => 3, "18+" => 4];
            $nivel_usuario_detalhe = $faixa_ordem_detalhe[$faixa_etaria_usuario_detalhe] ?? 4;
            $nivel_livro_detalhe = $faixa_ordem_detalhe[$livro['faixa_etaria']] ?? 4;

            $permitido = false;
            if ($livro['faixa_etaria'] == 'Livre') {
                $permitido = true;
            } elseif ($preferencia_ver_inferiores_detalhe) {
                if ($nivel_livro_detalhe <= $nivel_usuario_detalhe) {
                    $permitido = true;
                }
            } else { // Só pode ver da própria faixa ou "Livre"
                if ($livro['faixa_etaria'] == $faixa_etaria_usuario_detalhe) {
                    $permitido = true;
                }
            }
            if (!$permitido) {
                header("location: livros_biblioteca.php?erro=faixa_etaria_nao_permitida");
                exit;
            }
        }
        $stmt_user_pref_detalhe->close();
    }
}


?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($livro['titulo'] ?? 'Detalhes do Livro'); ?> - Biblioteca de Coimbra</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .detalhe-livro-container {
            display: flex;
            flex-wrap: wrap; /* Para responsividade */
            gap: 30px;
        }
        .detalhe-livro-coluna-esquerda {
            flex: 0 0 300px; /* Largura fixa para capa e resumo */
        }
        .detalhe-livro-capa img {
            width: 100%;
            max-width: 280px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detalhe-livro-resumo h3 {
            margin-top: 0;
            color: #4a3b32;
            border-bottom: 1px solid #e0d8c7;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .detalhe-livro-resumo p {
            font-size: 0.95em;
            line-height: 1.6;
            text-align: justify;
        }
        .detalhe-livro-coluna-direita {
            flex: 1; /* Ocupa o restante do espaço */
            min-width: 300px; /* Para não esmagar muito em telas menores */
        }
        .conteudo-leitura-area {
            background-color: #fdfbf7;
            border: 1px solid #e0d8c7;
            padding: 20px;
            border-radius: 8px;
            min-height: 400px; /* Altura mínima para a área de leitura */
            margin-bottom: 20px;
            overflow-y: auto; /* Scroll se o conteúdo for muito grande */
            max-height: 70vh; /* Limitar altura máxima para não ocupar a tela toda */
        }
        .conteudo-leitura-area .pagina-manual {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #d2c8b6;
        }
        .conteudo-leitura-area .pagina-manual:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .conteudo-leitura-area .pagina-manual h4 { /* Título da página, se houver */
            margin-top: 0;
            color: #6b5b4c;
        }
        .conteudo-leitura-area .pagina-manual img { /* Imagem dentro da página manual */
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .controles-livro-detalhe {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .detalhe-livro-coluna-esquerda {
                flex: 1 1 100%; /* Ocupa largura total em telas pequenas */
                text-align: center; /* Centralizar capa */
            }
            .detalhe-livro-capa img {
                max-width: 220px; /* Reduzir um pouco a capa */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <?php if ($is_admin): ?>
                <a href="livros_biblioteca.php" <?php
                    $estante_paginas = ['livros_biblioteca.php', 'editar_livro.php', 'editor_paginas_livro.php', 'ver_livro_detalhe.php'];
                    if (in_array(basename($_SERVER['PHP_SELF']), $estante_paginas)) echo 'class="active"';
                ?>>Estante dos Livros Encantados</a>
                <a href="criar_livro.php" <?php if (basename($_SERVER['PHP_SELF']) == 'criar_livro.php') echo 'class="active"'; ?>>Criar Novo Livro</a>
            <?php endif; ?>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
            <?php if (!$is_admin): ?>
                <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php' || basename($_SERVER['PHP_SELF']) == 'ver_livro_detalhe.php') echo 'class="active"'; ?>>Lugar Encantado dos Livros</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php elseif ($user_id): ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link" title="Configurações do Perfil">&#9881;</a>
            <?php endif; ?>
            <?php if ($user_id): ?>
                <a href="logout.php" class="logout-btn">Terminar Sessão</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="padding: 8px 15px; text-transform: none; font-size:0.95em;">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content-area">
        <?php if ($livro): ?>
            <h1 style="text-align:center;"><?php echo htmlspecialchars($livro['titulo']); ?></h1>
            <p style="text-align:center; font-style:italic; margin-bottom:30px;">por <?php echo htmlspecialchars($livro['autor']); ?></p>

            <div class="detalhe-livro-container">
                <div class="detalhe-livro-coluna-esquerda">
                    <div class="detalhe-livro-capa">
                        <img src="<?php echo htmlspecialchars($livro['imagem'] && file_exists($livro['imagem']) ? $livro['imagem'] : 'https://via.placeholder.com/280x400/eee/7a6a56?text=Sem+Capa'); ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                    </div>
                    <div class="detalhe-livro-resumo">
                        <h3>Resumo</h3>
                        <p><?php echo nl2br(htmlspecialchars($livro['resumo'])); ?></p>
                    </div>
                </div>

                <div class="detalhe-livro-coluna-direita">
                    <h3>Conteúdo do Livro</h3>
                    <div class="conteudo-leitura-area">
                        <?php if ($livro['tipo_criacao'] == 'manual' && !empty($paginas_livro_manual)): ?>
                            <?php foreach($paginas_livro_manual as $pagina): ?>
                                <div class="pagina-manual">
                                    <h4>Página <?php echo htmlspecialchars($pagina['numero_pagina']); ?></h4>
                                    <?php if (!empty($pagina['imagem']) && file_exists($pagina['imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($pagina['imagem']); ?>" alt="Imagem da página <?php echo htmlspecialchars($pagina['numero_pagina']); ?>" class="preview-pagina">
                                    <?php endif; ?>
                                    <div><?php echo $pagina['conteudo']; // Conteúdo HTML do TinyMCE, não escapar ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($livro['tipo_criacao'] == 'upload' && !empty($livro['ficheiro']) && file_exists($livro['ficheiro'])): ?>
                            <p>Este livro está disponível como um ficheiro para visualização/download.</p>
                            <p>
                                <?php
                                $ext_ficheiro = strtolower(pathinfo($livro['ficheiro'], PATHINFO_EXTENSION));
                                if ($ext_ficheiro == 'pdf'):
                                ?>
                                    <object data="<?php echo htmlspecialchars($livro['ficheiro']); ?>" type="application/pdf" width="100%" height="500px">
                                        <p>Parece que você não tem um visualizador de PDF no seu navegador.
                                        Você pode <a href="<?php echo htmlspecialchars($livro['ficheiro']); ?>" download>descarregar o PDF aqui</a>.</p>
                                    </object>
                                <?php elseif ($ext_ficheiro == 'txt'):
                                    $conteudo_txt = file_get_contents($livro['ficheiro']);
                                ?>
                                    <pre style="white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($conteudo_txt); ?></pre>
                                <?php else: ?>
                                    <p>Formato de ficheiro não suportado para visualização direta.</p>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p>O conteúdo deste livro não está disponível para leitura online no momento.</p>
                        <?php endif; ?>
                    </div>
                    <div class="controles-livro-detalhe">
                        <?php
                        $caminho_ficheiro_detalhe = $livro['ficheiro'] ?? null;
                        $ext_ficheiro_detalhe = $caminho_ficheiro_detalhe ? strtolower(pathinfo($caminho_ficheiro_detalhe, PATHINFO_EXTENSION)) : '';

                        if ($caminho_ficheiro_detalhe && file_exists($caminho_ficheiro_detalhe) && $ext_ficheiro_detalhe == 'pdf'):
                        ?>
                            <a href="download_livro.php?id=<?php echo $livro['id']; ?>" class="btn btn-success">Baixar PDF</a>
                        <?php elseif ($livro['tipo_criacao'] == 'manual' && !empty($paginas_livro_manual)): ?>
                             <p><em>A leitura deste livro é feita diretamente nesta página.</em></p>
                        <?php endif; ?>
                         <a href="livros_biblioteca.php" class="btn btn-outline" style="margin-left:10px;">Voltar ao Lugar Encantado</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="error-message">O livro solicitado não foi encontrado ou não está acessível.</p>
        <?php endif; ?>
    </div>

    <footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
