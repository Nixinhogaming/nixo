<?php
session_start();
if (!isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: main.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Livro</title>
    <link rel="stylesheet" href="estilo.css">
    <!-- <style> movido para estilo.css ou coberto por classes globais -->
</head>
<body>
    <?php
        // Incluir navbar
        $primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Staff";
        // $is_admin já verificado no topo
        // Simular $active_page para a navbar, se necessário, ou definir diretamente no include
    ?>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Estante dos Livros Encantados</a>
            <a href="criar_livro.php" <?php if (basename($_SERVER['PHP_SELF']) == 'criar_livro.php' || basename($_SERVER['PHP_SELF']) == 'form_meta_manual.php' || basename($_SERVER['PHP_SELF']) == 'form_upload_completo.php') echo 'class="active"'; ?>>Criar Novo Livro</a>
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
        <h2>Como Deseja Criar o Novo Livro?</h2>
        <?php
        // Exibir mensagem de sucesso, se houver (da criação manual ou por upload)
        if (isset($_SESSION['mensagem_sucesso_pagina_criacao'])) {
            echo '<div class="success-message" style="margin-top: 15px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['mensagem_sucesso_pagina_criacao']) . '</div>';
            unset($_SESSION['mensagem_sucesso_pagina_criacao']); // Limpar após exibir
        }
        ?>
        <p style="text-align: center; margin-bottom: 30px;">Escolha uma das opções abaixo para prosseguir:</p>

        <div class="escolha-criacao-container">
            <a href="form_meta_manual.php" class="btn btn-primary btn-escolha-criacao">Escrever Livro Manualmente<br><small>(Página a Página)</small></a>
            <a href="form_upload_completo.php" class="btn btn-success btn-escolha-criacao">Adicionar Livro Existente<br><small>(Upload de Ficheiro e Metadados)</small></a>
        </div>

        <div style="margin-top:40px; text-align:center; padding-top:20px; border-top:1px solid #eee;">
            <a href="livros_biblioteca.php" class="btn btn-outline">Cancelar e Voltar para Estante</a>
        </div>
    </div>
</div>
<footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
    <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
</footer>
</body>
</html>
