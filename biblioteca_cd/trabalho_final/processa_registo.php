<?php
session_start(); // Adicionar session_start() no início
require_once "config_db.php";

$primeiro_nome = $ultimo_nome = $idade_str = $email = $senha = $confirmar_senha = "";
$idade = null; // Inicializar idade
$faixa_etaria = ''; // Inicializar faixa_etaria
$erros = [];
$_SESSION['form_data_registo'] = []; // Para repopular o formulário

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['form_data_registo'] = $_POST; // Guardar dados para repopulação

    $primeiro_nome = trim($_POST["primeiro_nome"]);
    if (empty($primeiro_nome)) {
        $erros[] = "Por favor, insira o seu primeiro nome.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ú\s'-]+$/u", $primeiro_nome)) {
        $erros[] = "O primeiro nome pode conter apenas letras, espaços, hífens e apóstrofos.";
    }

    // Validar último nome
    $ultimo_nome = trim($_POST["ultimo_nome"]);
    if (empty($ultimo_nome)) {
        $erros[] = "Por favor, insira o seu último nome.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ú\s'-]+$/u", $ultimo_nome)) {
        $erros[] = "O último nome pode conter apenas letras, espaços, hífens e apóstrofos.";
    }

    // Validar idade
    $idade_str = trim($_POST["idade"]);
    if (empty($idade_str)) {
        $erros[] = "Por favor, insira a sua idade.";
    } else {
        $idade = filter_var($idade_str, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 150]]);
        if ($idade === false) {
            $erros[] = "Por favor, insira uma idade válida (entre 1 e 150).";
        } else {
            // Calcular faixa etária apenas se a idade for válida
            if ($idade >= 6 && $idade <= 8) $faixa_etaria = '6-8';
            elseif ($idade >= 9 && $idade <= 12) $faixa_etaria = '9-12';
            elseif ($idade >= 13 && $idade <= 17) $faixa_etaria = '13-17';
            elseif ($idade >= 18) $faixa_etaria = '18+';
            else $faixa_etaria = 'Livre'; // Default para idades menores ou não especificadas claramente
        }
    }

    $email = trim($_POST["email"]);
    if (empty($email)) {
        $erros[] = "Por favor, insira o seu email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Por favor, insira um formato de email válido.";
    }

    // Validar senha
    $senha = $_POST["senha"];
    if (empty($senha)) {
        $erros[] = "Por favor, insira uma senha.";
    } elseif (strlen($senha) < 8) {
        $erros[] = "A senha deve ter pelo menos 8 caracteres.";
    }
    // Poderia adicionar mais validações de complexidade de senha aqui (ex: maiúsculas, números, símbolos)

    // Validar confirmação de senha
    $confirmar_senha = $_POST["confirmar_senha"];
    if (empty($confirmar_senha)) {
        $erros[] = "Por favor, confirme a sua senha.";
    } elseif ($senha != $confirmar_senha) {
        $erros[] = "As senhas não coincidem.";
    }

    // Verificar se o nome de usuário (primeiro nome) e o email já existem
    if (empty($erros)) { // Só faz as consultas se não houver outros erros de validação

        // 1. Verificar primeiro nome
        $sql_check_nome = "SELECT id FROM usuarios WHERE primeiro_nome = ?";
        if ($stmt_check_nome = $mysqli->prepare($sql_check_nome)) {
            $stmt_check_nome->bind_param("s", $param_primeiro_nome);
            $param_primeiro_nome = $primeiro_nome;

            if ($stmt_check_nome->execute()) {
                $stmt_check_nome->store_result();
                if ($stmt_check_nome->num_rows == 1) {
                    $erros[] = "Este primeiro nome já está em uso. Por favor, escolha outro.";
                }
            } else {
                error_log("Erro ao verificar nome de usuário: " . $stmt_check_nome->error);
                $erros[] = "Algo correu mal ao verificar o nome. Tente novamente mais tarde.";
            }
            $stmt_check_nome->close();
        } else {
            error_log("Erro ao preparar a consulta de verificação de usuário: " . $mysqli->error);
            $erros[] = "Algo correu mal (DB Prepare User). Tente novamente mais tarde.";
        }

        // 2. Verificar email (apenas se o primeiro nome estiver OK, para evitar erros redundantes)
        if (empty($erros) && !empty($email)) { // Adicionado !empty($email) para não verificar se já houve erro no email
            $sql_check_email = "SELECT id FROM usuarios WHERE email = ?";
            if ($stmt_check_email = $mysqli->prepare($sql_check_email)) {
                $stmt_check_email->bind_param("s", $param_email);
                $param_email = $email;

                if ($stmt_check_email->execute()) {
                    $stmt_check_email->store_result();
                    if ($stmt_check_email->num_rows == 1) {
                        $erros[] = "Este email já está registado. Por favor, utilize outro ou tente fazer login.";
                    }
                } else {
                    error_log("Erro ao verificar email: " . $stmt_check_email->error);
                    $erros[] = "Algo correu mal ao verificar o email. Tente novamente mais tarde.";
                }
                $stmt_check_email->close();
            } else {
                error_log("Erro ao preparar a consulta de verificação de email: " . $mysqli->error);
                $erros[] = "Algo correu mal (DB Prepare Email). Tente novamente mais tarde.";
            }
        }
    }


    // Se não houver erros, inserir no banco de dados
    if (empty($erros)) {
        // Query de inserção atualizada para incluir faixa_etaria_utilizador
        // A coluna preferencia_ver_faixas_inferiores usará o DEFAULT TRUE do banco de dados.
        $sql_insert = "INSERT INTO usuarios (primeiro_nome, ultimo_nome, idade, faixa_etaria_utilizador, email, senha) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            // O tipo para faixa_etaria_utilizador é 's' (string)
            $stmt_insert->bind_param("ssisss", $param_primeiro_nome, $param_ultimo_nome, $param_idade, $param_faixa_etaria_utilizador, $param_email, $param_senha_hashed);

            // Definir parâmetros
            $param_primeiro_nome = $primeiro_nome;
            $param_ultimo_nome = $ultimo_nome;
            $param_idade = $idade;
            $param_faixa_etaria_utilizador = $faixa_etaria; // A variável $faixa_etaria calculada anteriormente
            $param_email = $email;
            $param_senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

            if ($stmt_insert->execute()) {
                // Registo bem-sucedido.
                // Limpar dados do formulário do localStorage (se existirem na sessão, o que não é o caso agora)
                // A limpeza principal do localStorage foi movida para aguardar_confirmacao.php,
                // mas como essa página será removida, a limpeza do localStorage do registro
                // será feita em login.php se definirmos a flag $_SESSION['registro_concluido'].
                $_SESSION['registro_concluido'] = true;
                header("location: login.php?sucesso=" . urlencode("Conta criada com sucesso! Faça login."));
                exit();
            } else {
                error_log("Erro ao inserir usuário: " . $stmt_insert->error);
                $erros[] = "Algo correu mal ao criar a conta. Tente novamente mais tarde.";
            }
            $stmt_insert->close();
        } else {
            error_log("Erro ao preparar a query de inserção: " . $mysqli->error);
            $erros[] = "Algo correu mal (DB Prepare Insert). Tente novamente mais tarde.";
        }
    }

    // Se houver erros de validação ou de banco de dados
    if (!empty($erros)) {
        $_SESSION['erros_registo'] = $erros; // Salvar erros na sessão
        // $_SESSION['form_data_registo'] já foi salvo no início do POST
        header("location: registo.php");
        exit();
    }

    $mysqli->close(); // Fechar conexão apenas se foi aberta e usada

} else {
    // Se não for POST, redirecionar para a página de registro
    header("location: registo.php");
    exit;
}
?>
