<?php
require_once "config_db.php"; // Inclui conexão BD e session_start() seguro

// Apenas admins podem acessar e precisam estar logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    $_SESSION['login_error'] = "Acesso restrito a administradores.";
    header("location: login.php");
    exit;
}

$erros_adicionar = [];
$form_data = []; // Para repopular o formulário em caso de erro

// Verificar se há erros da submissão anterior e repopular form_data
if (isset($_SESSION['erros_adicionar_livro'])) {
    $erros_adicionar = $_SESSION['erros_adicionar_livro'];
    if (isset($_SESSION['form_data_adicionar_livro'])) {
        $form_data = $_SESSION['form_data_adicionar_livro'];
    }
    unset($_SESSION['erros_adicionar_livro']);
    unset($_SESSION['form_data_adicionar_livro']);
}

// Verificar mensagem de sucesso
$mensagem_sucesso = "";
if (isset($_SESSION['sucesso_adicionar_livro'])) {
    $mensagem_sucesso = $_SESSION['sucesso_adicionar_livro'];
    unset($_SESSION['sucesso_adicionar_livro']);
}

$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Admin";
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Novo Livro</title>
    <link rel="stylesheet" href="estilo.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea#resumo',
        plugins: 'lists link image table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic backcolor | \
                  alignleft aligncenter alignright alignjustify | \
                  bullist numlist outdent indent | removeformat | help',
        height: 300,
        menubar: false,
      });
    </script>
</head>
<body>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php">Início</a>
            <a href="livros_biblioteca.php?admin_view=true">Estante (Admin)</a>
            <a href="admin_adicionar_livro.php" class="active">Adicionar Livro</a>
            <!-- Outros links de admin podem ser adicionados aqui -->
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <span class="admin-indicator">(Admin)</span>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

<div class="main-content-area form-admin-container">
    <div class="form-frame">
        <h2>Adicionar Novo Livro (Simples)</h2>

        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="success-message"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>

        <?php if (!empty($erros_adicionar)): ?>
            <div class="error-message">
                <strong>Foram encontrados os seguintes erros:</strong><br>
                <?php foreach ($erros_adicionar as $erro): ?>
                    <?php echo htmlspecialchars($erro); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="admin_processa_adicionar_livro.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($form_data['titulo'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="autor">Autor</label>
                <input type="text" id="autor" name="autor" value="<?php echo htmlspecialchars($form_data['autor'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="resumo">Resumo</label>
                <textarea id="resumo" name="resumo" rows="6" required><?php echo htmlspecialchars($form_data['resumo'] ?? ''); ?></textarea>
            </div>
            <div class="input-group">
                <label for="idade_livro">Idade Recomendada (Opcional)</label>
                <input type="number" id="idade_livro" name="idade_livro" value="<?php echo htmlspecialchars($form_data['idade_livro'] ?? ''); ?>" min="0" max="150" placeholder="Ex: 7 (deixe em branco se não aplicável)">
            </div>
            <div class="input-group">
                <label for="faixa_etaria">Faixa Etária</label>
                <select id="faixa_etaria" name="faixa_etaria" required>
                    <option value="">Selecione...</option>
                    <?php
                    $faixas_select = ["Livre" => "Livre para todas as idades", "6-8" => "6-8 anos", "9-12" => "9-12 anos", "13-17" => "13-17 anos", "18+" => "18+ anos (Adulto)"];
                    foreach ($faixas_select as $valor => $texto) {
                        $selected = (isset($form_data['faixa_etaria']) && $form_data['faixa_etaria'] == $valor) ? 'selected' : '';
                        echo "<option value=\"".htmlspecialchars($valor)."\" $selected>".htmlspecialchars($texto)."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <label for="imagem">Imagem da Capa (JPG, PNG, GIF)</label>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/gif" required>
            </div>
            <div class="input-group">
                <label for="ficheiro">Ficheiro do Livro (PDF, TXT - Opcional)</label>
                <input type="file" id="ficheiro" name="ficheiro" accept=".pdf,.txt">
            </div>

            <button type="submit" class="btn btn-primary" name="adicionar_livro_submit">Adicionar Livro</button>
             <div style="margin-top:20px; text-align:center;">
                <a href="main.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
    <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
</footer>

</body>
</html>
