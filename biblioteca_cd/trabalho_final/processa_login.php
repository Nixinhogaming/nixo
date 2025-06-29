<?php
// Incluir o arquivo de configuração do banco de dados e iniciar sessão (já feito em config_db.php)
require_once "config_db.php";

// Definir variáveis e inicializar com valores vazios
$utilizador = ""; // No nosso caso, será o primeiro_nome
$senha = "";
$erros = [];

// Processar dados do formulário quando o formulário é submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validar nome de utilizador (primeiro_nome)
    $utilizador = trim($_POST["utilizador"]);
    if (empty($utilizador)) {
        $erros[] = "Por favor, insira o seu nome de utilizador (primeiro nome).";
    }

    // Validar senha
    $senha_submetida = $_POST["senha"]; // Renomeado para evitar conflito com a senha do BD
    if (empty($senha_submetida)) {
        $erros[] = "Por favor, insira a sua senha.";
    }

    // Login especial para Staff/Admin
    if ($utilizador === "ADMINSTAFF" && $senha_submetida === "ADMINSTAFF*") {
        $_SESSION["loggedin"] = true;
        $_SESSION["primeiro_nome"] = "Staff";
        $_SESSION["is_admin"] = true;
        $_SESSION["id"] = 0;
        $_SESSION["email"] = "staff@biblioteca.pt";
        header("location: main.php");
        exit;
    }

    // Se não houver erros de input básicos, tentar autenticar
    if (empty($erros)) {
        // Preparar uma declaração select simplificada (sem email_verificado, primeiro_login_realizado)
        $sql = "SELECT id, primeiro_nome, ultimo_nome, idade, email, senha, is_admin FROM usuarios WHERE primeiro_nome = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_utilizador);
            $param_utilizador = $utilizador;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // Bind dos resultados das colunas simplificado
                    $stmt->bind_result($id, $db_primeiro_nome, $db_ultimo_nome, $db_idade, $db_email, $hashed_password, $db_is_admin);
                    if ($stmt->fetch()) {
                        if (password_verify($senha_submetida, $hashed_password)) {
                            // Senha está correta, login bem-sucedido

                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["primeiro_nome"] = $db_primeiro_nome;
                            $_SESSION["ultimo_nome"] = $db_ultimo_nome;
                            $_SESSION["idade"] = $db_idade;
                            $_SESSION["email"] = $db_email;
                            $_SESSION["is_admin"] = (bool)$db_is_admin;
                            // Não precisamos mais de 'primeiro_login_realizado' na sessão para este fluxo simplificado

                            header("location: main.php");
                            exit;
                        } else {
                            // Senha não é válida
                            $erros[] = "A senha que inseriu não é válida.";
                        }
                    }
                } else {
                    // Utilizador não existe
                    $erros[] = "Nenhuma conta encontrada com esse nome de utilizador (primeiro nome).";
                }
            } else {
                error_log("Erro ao executar login: " . $stmt->error);
                $erros[] = "Oops! Algo correu mal. Por favor, tente novamente mais tarde.";
            }
            // Fechar statement
            $stmt->close();
        } else {
            error_log("Erro ao preparar login: " . $mysqli->error);
            $erros[] = "Oops! Algo correu mal (DB Prepare). Por favor, tente novamente mais tarde.";
        }
    }

    // Se houver erros, redirecionar de volta para o formulário de login com os erros
    if (!empty($erros)) {
        $erros_str = implode("<br>", $erros);
        // Adicionar os erros na sessão para exibi-los de forma mais segura e persistente
        // ou passar via GET como antes, mas pode ser mais limitado.
        // Por consistência com registo, vamos usar GET por enquanto.
        // Seria melhor usar $_SESSION['login_error'] = $erros_str; e limpar depois de exibir.
        header("location: login.php?erro=" . urlencode($erros_str));
        exit();
    }

    // Fechar conexão
    $mysqli->close();

} else {
    // Se alguém tentar aceder a este script diretamente sem POST, redirecionar
    header("location: login.php");
    exit;
}
?>
