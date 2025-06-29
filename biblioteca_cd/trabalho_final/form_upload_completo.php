<?php
session_start();
// Apenas admins podem acessar
if (!isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: main.php");
    exit;
}
require_once "config_db.php";
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Livro Existente (Upload)</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <?php
        $primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Staff";
    ?>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Estante dos Livros Encantados</a>
            <a href="criar_livro.php" <?php
                $criar_paginas = ['criar_livro.php', 'form_meta_manual.php', 'form_upload_completo.php'];
                if (in_array(basename($_SERVER['PHP_SELF']), $criar_paginas)) echo 'class="active"';
            ?>>Criar Novo Livro</a>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <span class="admin-indicator">(Admin)</span>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

<div class="main-content-area form-admin-container">
    <div class="form-frame">
        <h2>Passo 1: Informações e Upload do Livro</h2>
        <?php
        // Exibir mensagens de erro da sessão (se o processa_criar_livro.php redirecionar para cá com erro)
        // Usaremos a mesma chave de sessão 'erros_criar_livro' e 'form_data_criar_livro' que 'processa_criar_livro.php' já usa.
        if (isset($_SESSION['erros_criar_livro']) && !empty($_SESSION['erros_criar_livro'])) {
            echo '<div class="error-message">';
            foreach ($_SESSION['erros_criar_livro'] as $erro_msg) {
                echo htmlspecialchars($erro_msg) . '<br>';
            }
            echo '</div>';
            unset($_SESSION['erros_criar_livro']);
        }

        // Mensagem de sucesso não é usual aqui, pois o sucesso redireciona para estante_livros.php

        $form_data = isset($_SESSION['form_data_criar_livro']) ? $_SESSION['form_data_criar_livro'] : [];
        if (!isset($_SESSION['erros_criar_livro'])) {
            unset($_SESSION['form_data_criar_livro']);
        }
        ?>
        <form action="processa_criar_livro.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="titulo">Título do Livro</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($form_data['titulo'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="autor">Autor</label>
                <input type="text" id="autor" name="autor" value="<?php echo htmlspecialchars($form_data['autor'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="idade_livro">Idade Recomendada (Ex: 10)</label>
                <input type="number" id="idade_livro" name="idade_livro" value="<?php echo htmlspecialchars($form_data['idade_livro'] ?? ''); ?>" min="0" max="150" required>
            </div>
            <div class="input-group">
                <label for="faixa_etaria">Faixa Etária do Livro</label>
                <select id="faixa_etaria" name="faixa_etaria" required>
                    <option value="">Selecione...</option>
                    <?php
                    $faixas = ["Livre" => "Livre para todas as idades", "6-8" => "6-8 anos", "9-12" => "9-12 anos", "13-17" => "13-17 anos", "18+" => "18+ anos (Adulto)"];
                    foreach ($faixas as $valor => $texto) {
                        $selected = (isset($form_data['faixa_etaria']) && $form_data['faixa_etaria'] == $valor) ? 'selected' : '';
                        echo "<option value=\"".htmlspecialchars($valor)."\" $selected>".htmlspecialchars($texto)."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <label for="imagem">Imagem da Capa (Obrigatório)</label>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/gif" required>
            </div>
            <div class="input-group">
                <label for="resumo">Breve Resumo (Obrigatório)</label>
                <textarea id="resumo" name="resumo" rows="5" required><?php echo htmlspecialchars($form_data['resumo'] ?? ''); ?></textarea>
            </div>
            <div class="input-group">
                <label for="ficheiro">Ficheiro do Livro (PDF ou TXT - Opcional)</label>
                <input type="file" id="ficheiro" name="ficheiro" accept=".pdf,.txt">
            </div>

            <button type="submit" class="btn btn-success" name="criar_livro_submit">Adicionar Livro ao Sistema</button>
            <!-- <p style="text-align:center; margin-top:20px; font-weight:bold; color: #c0392b;">SUBMISSÃO DESABILITADA PARA TESTE DE FORMULÁRIO.</p>
            <p style="text-align:center; margin-bottom:20px;">(Quando reativado, este botão salvará o livro com os metadados e arquivos fornecidos)</p> -->

            <div style="margin-top:20px; text-align:center;">
                <a href="criar_livro.php" class="btn btn-outline">Voltar à Escolha do Tipo de Criação</a>
            </div>
        </form>
    </div>
</div>
<footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
    <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
</footer>
</body>
</html>
