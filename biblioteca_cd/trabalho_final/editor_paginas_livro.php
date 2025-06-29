<?php
session_start();
if (!isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: main.php");
    exit;
}
require_once "config_db.php";
$livro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($livro_id <= 0) { echo "Livro inválido."; exit; }

// Buscar info do livro
$stmt = $mysqli->prepare("SELECT * FROM livros WHERE id=?");
$stmt->bind_param("i", $livro_id);
$stmt->execute();
$livro = $stmt->get_result()->fetch_assoc();
if (!$livro) { echo "Livro não encontrado."; exit; }

// Buscar páginas do livro
$stmt2 = $mysqli->prepare("SELECT * FROM livro_paginas WHERE livro_id=? ORDER BY numero_pagina ASC");
$stmt2->bind_param("i", $livro_id);
$stmt2->execute();
$paginas = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Editor de Páginas: <?php echo htmlspecialchars($livro['titulo']); ?></title>
    <link rel="stylesheet" href="estilo.css">
    <?php /* Estilos inline foram movidos para estilo.css com as classes:
              .editor-paginas-container, .editor-paginas-frame,
              .pagina-lista, .pagina-lista a, .pagina-lista strong,
              .pagina-editor-area, .pagina-editor-area label, .pagina-editor-area textarea, .pagina-editor-area input[type="file"],
              .pagina-editor-area img.preview-pagina,
              .pagina-controls, .pagina-controls .btn
    */ ?>
</head>
<body>
    <?php
        // Incluir navbar
        $primeiro_nome_nav = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Staff";
    ?>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <a href="livros_biblioteca.php" <?php
                $estante_paginas = ['livros_biblioteca.php', 'editar_livro.php', 'editor_paginas_livro.php'];
                if (in_array(basename($_SERVER['PHP_SELF']), $estante_paginas)) echo 'class="active"';
            ?>>Estante dos Livros Encantados</a>
            <a href="criar_livro.php" <?php if (basename($_SERVER['PHP_SELF']) == 'criar_livro.php') echo 'class="active"'; ?>>Criar Novo Livro</a>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome_nav; ?>!</span>
            <span class="admin-indicator">(Admin)</span>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

    <div class="main-content-area editor-paginas-container">
        <div class="form-frame editor-paginas-frame">
            <h2>Editor de Páginas: <?php echo htmlspecialchars($livro['titulo']); ?></h2>

            <div class="pagina-lista">
                <strong>Páginas Existentes:</strong>
                <?php foreach ($paginas as $p): ?>
                    <a href="?id=<?php echo $livro_id; ?>&pag=<?php echo $p['numero_pagina']; ?>">[<?php echo $p['numero_pagina']; ?>]</a>
                <?php endforeach; ?>
                <a href="?id=<?php echo $livro_id; ?>&nova=1" style="color:green;">+ Nova Página</a>
            </div>
            <?php
            // Página selecionada
            $pagina_atual = 1;
            $conteudo = '';
            $img = '';
            $pagina_id = 0;
            if (isset($_GET['pag'])) {
                foreach ($paginas as $p) {
                    if ($p['numero_pagina'] == intval($_GET['pag'])) {
                        $pagina_atual = $p['numero_pagina'];
                        $conteudo = $p['conteudo'];
                        $img = $p['imagem'];
                        $pagina_id = $p['id'];
                        break;
                    }
                }
            } elseif (isset($_GET['nova'])) {
                $pagina_atual = count($paginas) + 1;
            } elseif (count($paginas) > 0) {
                $pagina_atual = $paginas[0]['numero_pagina'];
                $conteudo = $paginas[0]['conteudo'];
                $img = $paginas[0]['imagem'];
                $pagina_id = $paginas[0]['id'];
            }
            ?>
            <form action="processa_editor_paginas.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="livro_id" value="<?php echo $livro_id; ?>">
                <input type="hidden" name="pagina_id" value="<?php echo $pagina_id; ?>">
                <input type="hidden" name="numero_pagina" value="<?php echo $pagina_atual; ?>">
                <div class="pagina-editor">
                    <label>Conteúdo da Página <?php echo $pagina_atual; ?>:</label>
                    <textarea name="conteudo" rows="8" style="width:100%;"><?php echo htmlspecialchars($conteudo); ?></textarea>
                    <br>
                    <label>Imagem (opcional):</label>
                    <?php if ($img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" style="max-width:120px;display:block;">
                    <?php endif; ?>
                    <input type="file" name="imagem">
                </div>
                <div class="pagina-controls">
                    <button type="submit" name="acao" value="guardar" class="btn">Guardar Página</button>
                    <?php if ($pagina_id): ?>
                        <button type="submit" name="acao" value="apagar" class="btn" style="background:#dc3545;">Apagar Página</button>
                    <?php endif; ?>
                </div>
            </form>
            <div style="margin-top: 30px; text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
                <form action="processa_editor_paginas.php" method="POST" style="display: inline;">
                    <input type="hidden" name="livro_id" value="<?php echo $livro_id; ?>">
                    <input type="hidden" name="livro_titulo" value="<?php echo htmlspecialchars($livro['titulo']); // Passar o título para a mensagem de sucesso ?>">
                    <button type="submit" name="acao" value="finalizar_livro_manual" class="btn btn-success">Finalizar Livro e Voltar</button>
                </form>
                <a href="livros_biblioteca.php" class="btn btn-outline" style="margin-left:10px;">Voltar para Estante (sem finalizar)</a>
            </div>
        </div>
    </div>
</body>
</html>
