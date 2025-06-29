<?php
session_start();
require_once "config_db.php";

// Apenas admins podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: login.php");
    exit;
}

$livro_id = null;
$livro = null;
$erros_edicao = [];
$form_data = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $livro_id = intval($_GET['id']);

    $stmt = $mysqli->prepare("SELECT titulo, autor, idade_livro, faixa_etaria, resumo, imagem, ficheiro FROM livros WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $livro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $livro = $result->fetch_assoc();
            // Preencher form_data com os dados atuais do livro para o formulário
            $form_data = $livro;
        } else {
            $_SESSION['mensagem_erro_estante'] = "Livro não encontrado.";
            header("location: estante_livros.php");
            exit;
        }
        $stmt->close();
    } else {
        // Em produção, logar o erro
        $_SESSION['mensagem_erro_estante'] = "Erro ao preparar consulta para buscar livro.";
        header("location: estante_livros.php");
        exit;
    }
} else {
    $_SESSION['mensagem_erro_estante'] = "ID do livro inválido ou não fornecido.";
    header("location: estante_livros.php");
    exit;
}

// Verificar se há erros da submissão anterior e repopular form_data
if (isset($_SESSION['erros_editar_livro'])) {
    $erros_edicao = $_SESSION['erros_editar_livro'];
    if (isset($_SESSION['form_data_editar_livro'])) {
        $form_data = array_merge($form_data, $_SESSION['form_data_editar_livro']); // Mescla, dando prioridade aos dados da sessão
    }
    unset($_SESSION['erros_editar_livro']);
    unset($_SESSION['form_data_editar_livro']);
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Editar Livro - <?php echo htmlspecialchars($form_data['titulo'] ?? 'Livro'); ?></title>
    <link rel="stylesheet" href="estilo.css">
    <!-- Incluir CDN do TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea#resumo', // Apontar para o ID do textarea do resumo
        plugins: 'lists link image table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic backcolor | \
                  alignleft aligncenter alignright alignjustify | \
                  bullist numlist outdent indent | removeformat | help',
        height: 350, // Altura ajustada
        menubar: false,
      });
    </script>
    <?php /* Estilos específicos podem ser adicionados em estilo.css se necessário */ ?>
</head>
<body>
    <?php
        $primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Staff";
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
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <span class="admin-indicator">(Admin)</span>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

<div class="main-content-area form-admin-container">
    <div class="form-frame">
        <h2>Editar Livro: <?php echo htmlspecialchars($livro['titulo']); ?></h2>
        <?php
        if (!empty($erros_edicao)) {
            echo '<div class="error-message">';
            foreach ($erros_edicao as $erro_msg) {
                echo htmlspecialchars($erro_msg) . '<br>';
            }
            echo '</div>';
        }
        ?>
        <form id="form-editar-livro" action="processa_editar_livro.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="livro_id" value="<?php echo $livro_id; ?>">

            <div class="input-group">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($form_data['titulo'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="autor">Autor</label>
                <input type="text" id="autor" name="autor" value="<?php echo htmlspecialchars($form_data['autor'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="idade_livro">Idade Recomendada</label>
                <input type="number" id="idade_livro" name="idade_livro" value="<?php echo htmlspecialchars($form_data['idade_livro'] ?? ''); ?>" min="0" max="150">
                <div style="margin-top: 5px;">
                    <input type="checkbox" id="sem_idade_especifica" name="sem_idade_especifica" value="1" <?php echo (!isset($form_data['idade_livro']) || is_null($form_data['idade_livro']) || $form_data['idade_livro'] === '') ? 'checked' : ''; ?>>
                    <label for="sem_idade_especifica" style="font-weight: normal; font-size: 0.9em;">Sem indicação de idade específica (para "Livros Criados Por Nós")</label>
                </div>
            </div>
            <div class="input-group">
                <label for="faixa_etaria">Faixa Etária (Livro)</label>
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
                <label for="resumo">Resumo (Obrigatório)</label>
                <textarea id="resumo" name="resumo" rows="5" required><?php echo htmlspecialchars($form_data['resumo'] ?? ''); ?></textarea>
            </div>

            <div class="input-group">
                <label for="imagem">Nova Imagem da Capa (Opcional - substitui a atual)</label>
                <?php if (!empty($livro['imagem']) && file_exists($livro['imagem'])): ?>
                    <p><img src="<?php echo htmlspecialchars($livro['imagem']); ?>" alt="Capa atual" style="max-width: 100px; max-height: 150px; display:block; margin-bottom:10px;"></p>
                <?php elseif (!empty($livro['imagem'])): ?>
                    <p style="color:red;">Capa atual não encontrada no caminho: <?php echo htmlspecialchars($livro['imagem']); ?></p>
                <?php else: ?>
                    <p>Nenhuma capa atual.</p>
                <?php endif; ?>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/gif">
            </div>

            <div class="input-group">
                <label for="ficheiro">Novo Ficheiro do Livro (PDF ou TXT - Opcional - substitui o atual)</label>
                <?php if (!empty($livro['ficheiro']) && file_exists($livro['ficheiro'])): ?>
                    <p>Ficheiro atual: <a href="<?php echo htmlspecialchars($livro['ficheiro']); ?>" target="_blank"><?php echo basename($livro['ficheiro']); ?></a></p>
                    <label for="remover_ficheiro_existente">Remover ficheiro existente?</label>
                    <input type="checkbox" name="remover_ficheiro_existente" id="remover_ficheiro_existente" value="1">
                <?php elseif (!empty($livro['ficheiro'])): ?>
                     <p style="color:red;">Ficheiro atual não encontrado no caminho: <?php echo htmlspecialchars($livro['ficheiro']); ?></p>
                <?php else: ?>
                    <p>Nenhum ficheiro atualmente associado.</p>
                <?php endif; ?>
                <input type="file" id="ficheiro" name="ficheiro" accept=".pdf,.txt">
            </div>

            <button type="submit" class="btn btn-primary" name="editar_livro_submit">Salvar Alterações</button>

            <div style="margin-top:20px; text-align:center;">
                <a href="main.php" class="btn btn-outline">Cancelar e Voltar para Início (Admin)</a>
            </div>
        </form>
    </div>
</div>
<footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
    <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const idadeInput = document.getElementById('idade_livro');
    const semIdadeCheckbox = document.getElementById('sem_idade_especifica');

    function toggleIdadeInput() {
        if (semIdadeCheckbox.checked) {
            idadeInput.disabled = true;
            idadeInput.value = ''; // Limpa o valor para garantir que não seja submetido
            // O atributo 'required' foi removido do input, então não precisamos nos preocupar com ele aqui.
        } else {
            idadeInput.disabled = false;
        }
    }

    // Verifica o estado inicial ao carregar a página
    toggleIdadeInput();

    // Adiciona listener para mudanças no checkbox
    semIdadeCheckbox.addEventListener('change', toggleIdadeInput);

    // Adiciona listener para o formulário para garantir que, se desabilitado, o valor não seja enviado.
    // A maioria dos navegadores não envia valores de campos desabilitados, mas é uma garantia.
    const form = document.getElementById('form-editar-livro');
    if (form) {
        form.addEventListener('submit', function() {
            if (idadeInput.disabled) {
                // Se estiver desabilitado, explicitamente defina como vazio para o caso de algum navegador ainda tentar enviar.
                // No entanto, o backend (PHP) deve priorizar o checkbox 'sem_idade_especifica'.
                idadeInput.value = '';
            }
        });
    }
});
</script>
</body>
</html>
