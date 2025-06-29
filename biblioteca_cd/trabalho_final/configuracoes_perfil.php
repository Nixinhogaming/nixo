<?php
session_start();
require_once "config_db.php";

// Verificar se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$preferencia_atual = true; // Default

// Buscar a preferência atual do usuário
$stmt_pref = $mysqli->prepare("SELECT preferencia_ver_faixas_inferiores FROM usuarios WHERE id = ?");
if ($stmt_pref) {
    $stmt_pref->bind_param("i", $user_id);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result();
    if ($result_pref->num_rows === 1) {
        $user_config = $result_pref->fetch_assoc();
        $preferencia_atual = (bool)$user_config['preferencia_ver_faixas_inferiores'];
    }
    $stmt_pref->close();
}

$mensagem_sucesso = '';
if (isset($_SESSION['mensagem_sucesso_config'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso_config'];
    unset($_SESSION['mensagem_sucesso_config']);
}
$mensagem_erro = '';
if (isset($_SESSION['mensagem_erro_config'])) {
    $mensagem_erro = $_SESSION['mensagem_erro_config'];
    unset($_SESSION['mensagem_erro_config']);
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Perfil - Biblioteca de Coimbra</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .config-container {
            max-width: 700px;
        }
        .config-option {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #fdfbf7;
            border: 1px solid #e0d8c7;
            border-radius: 8px;
        }
        .config-option label {
            font-weight: bold;
            color: #5a4a3b;
            display: block;
            margin-bottom: 10px;
        }
        .config-option .descricao {
            font-size: 0.9em;
            color: #777;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php
        $primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Utilizador";
        $is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;
    ?>
     <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <div class="dropdown-categorias">
                <a href="#" class="dropdown-toggle">Categorias</a>
                <div class="dropdown-menu">
                    <a href="livros_biblioteca.php">Lugar Encantado dos Livros</a>
                    <?php if ($is_admin): ?>
                        <a href="estante_livros.php">Estante de Livros (Admin)</a>
                        <a href="criar_livro.php">Criar Livro (Admin)</a>
                    <?php endif; ?>
                    <?php
                        // Futuramente, buscar categorias do BD e listar aqui
                        // Ex: $sql_cat = "SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria";
                        // $res_cat = $mysqli->query($sql_cat);
                        // while ($cat = $res_cat->fetch_assoc()) {
                        // echo '<a href="livros_biblioteca.php?categoria=' . $cat['id_categoria'] . '">' . htmlspecialchars($cat['nome_categoria']) . '</a>';
                        // }
                    ?>
                </div>
            </div>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
            <?php if (!$is_admin): // Usuários normais veem o link Livros e Configurações ?>
                <a href="livros_biblioteca.php" <?php if(basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"';?>>Livros</a>
                <a href="configuracoes_perfil.php" <?php if(basename($_SERVER['PHP_SELF']) == 'configuracoes_perfil.php') echo 'class="active"';?>>Configurações</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php else: ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link <?php if(basename($_SERVER['PHP_SELF']) == 'configuracoes_perfil.php') echo 'active';?>" title="Configurações do Perfil">&#9881;</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

    <div class="main-content-area config-container">
        <div class="form-frame">
            <h2>Configurações do Perfil</h2>

            <?php if ($mensagem_sucesso): ?>
                <div class="success-message"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
            <?php endif; ?>
            <?php if ($mensagem_erro): ?>
                <div class="error-message"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>

            <form action="processa_configuracoes_perfil.php" method="POST">
                <div class="config-option">
                    <label for="ver_faixas_inferiores">Preferência de Visualização de Livros</label>
                    <p class="descricao">
                        Por padrão, você vê livros adequados para sua faixa etária e também para faixas etárias inferiores,
                        para ter acesso a um conteúdo mais abrangente.
                        Desmarque esta opção se preferir uma visualização mais restrita, mostrando apenas
                        livros da sua faixa etária ou superiores (quando aplicável).
                    </p>
                    <input type="checkbox" id="ver_faixas_inferiores" name="preferencia_ver_faixas_inferiores" value="1" <?php echo $preferencia_atual ? 'checked' : ''; ?>>
                    <label for="ver_faixas_inferiores" style="font-weight:normal; display:inline;">Incluir livros de faixas etárias inferiores à minha.</label>
                </div>

                <button type="submit" class="btn btn-primary" name="salvar_configuracoes">Salvar Configurações</button>
            </form>
        </div>
    </div>

    <footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
