<?php
require_once "config_db.php"; // Inclui conexão BD e session_start() seguro

// Apenas admins podem acessar e precisam estar logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    $_SESSION['login_error'] = "Acesso restrito a administradores.";
    header("location: login.php");
    exit;
}

// Verificar se o ID do admin está na sessão
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    error_log("Erro crítico em admin_processa_adicionar_livro.php: Admin logado (" . ($_SESSION['primeiro_nome'] ?? 'Nome não encontrado') . ") sem ID na sessão. Sessão: " . print_r($_SESSION, true));
    $_SESSION['erros_adicionar_livro'] = ["Erro crítico de sessão: ID do administrador não encontrado. Por favor, faça login novamente."];
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    header("location: login.php?erro=" . urlencode("Erro crítico de sessão. Faça login novamente."));
    exit;
}
$criado_por_id = $_SESSION["id"];

$erros = [];
$form_data = $_POST; // Para repopular em caso de erro

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_livro_submit'])) {

    // Validação do Título
    $titulo = trim($_POST["titulo"] ?? '');
    if (empty($titulo)) {
        $erros[] = "O título é obrigatório.";
    }

    // Validação do Autor
    $autor = trim($_POST["autor"] ?? '');
    if (empty($autor)) {
        $erros[] = "O autor é obrigatório.";
    }

    // Validação do Resumo
    $resumo = trim($_POST["resumo"] ?? '');
    if (empty($resumo)) {
        $erros[] = "O resumo é obrigatório.";
    }

    // Validação da Idade Recomendada (Opcional)
    $idade_livro_para_db = null;
    if (isset($_POST["idade_livro"]) && $_POST["idade_livro"] !== '') {
        if (!is_numeric($_POST["idade_livro"]) || intval($_POST["idade_livro"]) < 0 || intval($_POST["idade_livro"]) > 150) {
            $erros[] = "A idade recomendada deve ser um número válido entre 0 e 150, ou deixada em branco.";
        } else {
            $idade_livro_para_db = intval($_POST["idade_livro"]);
        }
    }

    // Validação da Faixa Etária
    $faixa_etaria = $_POST["faixa_etaria"] ?? '';
    $faixas_validas = ["Livre", "6-8", "9-12", "13-17", "18+"];
    if (empty($faixa_etaria) || !in_array($faixa_etaria, $faixas_validas)) {
        $erros[] = "A faixa etária selecionada é inválida.";
    }

    $upload_dir = "uploads/livros/";
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $erros[] = "Falha crítica: Não foi possível criar o diretório de uploads. Verifique as permissões do servidor.";
        }
    }

    // Processamento da Imagem da Capa (Obrigatório)
    $img_path = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            $img_original_name = basename($_FILES['imagem']['name']);
            $img_ext = strtolower(pathinfo($img_original_name, PATHINFO_EXTENSION));
            $valid_img_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($img_ext, $valid_img_exts)) {
                $erros[] = "Formato de imagem inválido. Use JPG, JPEG, PNG ou GIF.";
            } elseif ($_FILES['imagem']['size'] > 5242880) { // 5MB Limite
                $erros[] = "A imagem da capa não pode exceder 5MB.";
            } else {
                $sanitized_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($img_original_name, PATHINFO_FILENAME));
                $img_path = $upload_dir . uniqid('capa_', true) . '_' . $sanitized_name . "." . $img_ext;
                if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $img_path)) {
                    $erros[] = "Falha ao guardar a imagem da capa. Código do erro: " . $_FILES['imagem']['error'];
                    $img_path = null;
                }
            }
        } else {
            $erros[] = "Diretório de uploads não encontrado ou sem permissão de escrita.";
        }
    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] != UPLOAD_ERR_NO_FILE) {
         $erros[] = "Erro no upload da imagem da capa. Código: " . $_FILES['imagem']['error'];
    } else {
         $erros[] = "A imagem da capa é obrigatória.";
    }

    // Processamento do Ficheiro do Livro (Opcional)
    $ficheiro_path = null;
    if (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] == UPLOAD_ERR_OK) {
        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            $file_original_name = basename($_FILES['ficheiro']['name']);
            $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
            $valid_file_exts = ['pdf', 'txt'];
            if (!in_array($file_ext, $valid_file_exts)) {
                $erros[] = "Formato de ficheiro inválido. Use PDF ou TXT.";
            } elseif ($_FILES['ficheiro']['size'] > 10485760) { // 10MB
                $erros[] = "O ficheiro do livro não pode exceder 10MB.";
            } else {
                $sanitized_file_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file_original_name, PATHINFO_FILENAME));
                $ficheiro_path = $upload_dir . uniqid('livro_', true) . '_' . $sanitized_file_name . "." . $file_ext;
                if (!move_uploaded_file($_FILES['ficheiro']['tmp_name'], $ficheiro_path)) {
                    $erros[] = "Falha ao guardar o ficheiro do livro. Código do erro: " . $_FILES['ficheiro']['error'];
                    $ficheiro_path = null;
                }
            }
        } else {
             $erros[] = "Diretório de uploads para ficheiros não encontrado ou sem permissão de escrita.";
        }
    } elseif (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['ficheiro']['error'] != UPLOAD_ERR_OK) {
        $erros[] = "Ocorreu um erro ao enviar o ficheiro do livro. Código: " . $_FILES['ficheiro']['error'];
    }

    if (empty($erros)) {
        $tipo_criacao = 'admin_simples'; // Identificador para este fluxo
        $sql = "INSERT INTO livros (titulo, autor, idade_livro, faixa_etaria, imagem, resumo, ficheiro, criado_por_id, tipo_criacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            // Para idade_livro, se for opcional e não fornecido, passamos NULL.
            // O bind_param para 'i' (integer) não aceita NULL diretamente de forma simples com ?.
            // Uma forma é não incluir no SQL e ter um valor DEFAULT NULL na tabela, ou ajustar a query.
            // Ou, se a coluna aceita NULL, passar $idade_livro_para_db que já é null se não preenchido.
            // 's' para string, 'i' para integer, 'd' para double, 'b' para blob.
            $stmt->bind_param("ssissssis",
                $titulo, $autor, $idade_livro_para_db, $faixa_etaria,
                $img_path, $resumo, $ficheiro_path,
                $criado_por_id, $tipo_criacao);

            if ($stmt->execute()) {
                $_SESSION['sucesso_adicionar_livro'] = "Livro '".htmlspecialchars($titulo)."' adicionado com sucesso!";
                header("location: admin_adicionar_livro.php");
                exit;
            } else {
                $erros[] = "Erro ao inserir livro na base de dados: (" . $stmt->errno . ") " . $stmt->error;
                error_log("Erro DB ao adicionar livro: " . $stmt->error);
                if ($img_path && file_exists($img_path)) { unlink($img_path); }
                if ($ficheiro_path && file_exists($ficheiro_path)) { unlink($ficheiro_path); }
            }
            $stmt->close();
        } else {
            $erros[] = "Erro ao preparar a query para a base de dados: (" . $mysqli->errno . ") " . $mysqli->error;
            error_log("Erro DB prepare ao adicionar livro: " . $mysqli->error);
        }
    }

    // Se houver erros, guardar na sessão e redirecionar de volta para o formulário
    if (!empty($erros)) {
        $_SESSION['erros_adicionar_livro'] = $erros;
        $_SESSION['form_data_adicionar_livro'] = $form_data;
        header("location: admin_adicionar_livro.php");
        exit;
    }

} else {
    // Acesso não POST ou botão não pressionado
    $_SESSION['erros_adicionar_livro'] = ["Acesso inválido ao processamento."];
    header("location: admin_adicionar_livro.php");
    exit;
}
?>
