<?php
session_start();
require_once "config_db.php"; // Garante que a conexão com o BD está disponível

// Apenas admins podem acessar esta página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: login.php"); // Redireciona para login se não for admin ou não logado
    exit;
}

$titulo = "";
$autor = "";
$idade_livro = 0;
$faixa_etaria = "";
$resumo = "";
$img_path = null;
$ficheiro_path = null;
$erros = [];
$form_data = []; // Para repopular o formulário em caso de erro

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['criar_livro_submit'])) {
    // Guardar dados do formulário para repopulação em caso de erro
    $form_data = $_POST;

    // Validação do Título
    if (empty(trim($_POST["titulo"]))) {
        $erros[] = "O título é obrigatório.";
    } else {
        $titulo = trim($_POST["titulo"]);
    }

    // Validação do Autor
    if (empty(trim($_POST["autor"]))) {
        $erros[] = "O autor é obrigatório.";
    } else {
        $autor = trim($_POST["autor"]);
    }

    // Validação da Idade Recomendada
    if (!isset($_POST["idade_livro"]) || !is_numeric($_POST["idade_livro"]) || intval($_POST["idade_livro"]) < 0 || intval($_POST["idade_livro"]) > 150) {
        $erros[] = "A idade recomendada deve ser um número entre 0 e 150.";
    } else {
        $idade_livro = intval($_POST["idade_livro"]);
    }

    // Validação da Faixa Etária
    $faixas_validas = ["Livre", "6-8", "9-12", "13-17", "18+"];
    if (empty($_POST["faixa_etaria"]) || !in_array($_POST["faixa_etaria"], $faixas_validas)) {
        $erros[] = "A faixa etária selecionada é inválida.";
    } else {
        $faixa_etaria = $_POST["faixa_etaria"];
    }

    // Validação do Resumo
    if (empty(trim($_POST["resumo"]))) {
        $erros[] = "O resumo é obrigatório.";
    } else {
        $resumo = trim($_POST["resumo"]);
    }

    // Processamento da Imagem da Capa (Obrigatório)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "uploads/livros/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) { // Tenta criar o diretório recursivamente
                $erros[] = "Falha crítica: Não foi possível criar o diretório de uploads. Verifique as permissões do servidor.";
            }
        }

        if (is_dir($upload_dir) && is_writable($upload_dir)) { // Verifica se o diretório existe e tem permissão de escrita
            $img_original_name = basename($_FILES['imagem']['name']); // basename para segurança
            $img_ext = strtolower(pathinfo($img_original_name, PATHINFO_EXTENSION));
            $valid_img_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($img_ext, $valid_img_exts)) {
                $erros[] = "Formato de imagem inválido. Use JPG, JPEG, PNG ou GIF.";
            // } elseif ($_FILES['imagem']['size'] > 2097152) { // 2MB Limite Removido para Staff
            //     $erros[] = "A imagem da capa não pode exceder 2MB.";
            } else {
                // Sanitizar nome do arquivo para evitar problemas e usar uniqid para garantir nome único
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
    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['imagem']['error'] != UPLOAD_ERR_OK) {
         $erros[] = "Erro no upload da imagem da capa. Código: " . $_FILES['imagem']['error'];
    } else {
         $erros[] = "A imagem da capa é obrigatória.";
    }

    // Processamento do Ficheiro do Livro (Opcional)
    if (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "uploads/livros/"; // Pode ser a mesma ou outra pasta
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $erros[] = "Falha crítica: Não foi possível criar o diretório de uploads para o ficheiro.";
            }
        }

        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            $file_original_name = basename($_FILES['ficheiro']['name']);
            $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
            $valid_file_exts = ['pdf', 'txt'];
            if (!in_array($file_ext, $valid_file_exts)) {
                $erros[] = "Formato de ficheiro inválido. Use PDF ou TXT.";
            } elseif ($_FILES['ficheiro']['size'] > 10485760) { // 10MB = 10 * 1024 * 1024 bytes
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

    // Se não houver erros de validação ATÉ AGORA, tentar inserir na base de dados
    if (empty($erros)) {
        // // 1. Verificar se o ID da sessão do admin está presente - TEMPORARIAMENTE REMOVIDO
        // if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
        //     $erros[] = "Erro de sessão: ID do administrador não encontrado na sessão. Faça login novamente.";
        // } else {
        //     $criado_por_id_sessao = $_SESSION["id"]; // ID do admin que está a criar

        //     // 2. Verificar se este ID de admin existe na tabela 'usuarios' e é de um admin
        //     $stmt_check_user_upload = $mysqli->prepare("SELECT id FROM usuarios WHERE id = ? AND is_admin = 1");
        //     if ($stmt_check_user_upload) {
        //         $stmt_check_user_upload->bind_param("i", $criado_por_id_sessao);
        //         $stmt_check_user_upload->execute();
        //         $result_check_user_upload = $stmt_check_user_upload->get_result();
        //         if ($result_check_user_upload->num_rows == 0) {
        //             // Esta situação é crítica: a sessão diz que é admin, tem ID, mas esse ID não é de um admin no banco.
        //             $erros[] = "Erro de integridade de dados: O ID de administrador da sessão não corresponde a um administrador válido no sistema.";
        //         }
        //         $stmt_check_user_upload->close();
        //     } else {
        //         $erros[] = "Erro ao verificar ID do administrador (preparação): " . $mysqli->error; // Erro de preparação do statement
        //     }
        // }

        // Temporariamente, definir criado_por_id como NULL
        $criado_por_id_para_db = null;

        // Prosseguir com a inserção apenas se não houver erros de validação anteriores (ex: campos obrigatórios)
        if (empty($erros)) {
            $tipo_criacao = 'upload'; // Definir o tipo de criação para este fluxo
            $sql = "INSERT INTO livros (titulo, autor, idade_livro, faixa_etaria, imagem, resumo, ficheiro, criado_por_id, tipo_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("ssissssis", $titulo, $autor, $idade_livro, $faixa_etaria, $img_path, $resumo, $ficheiro_path, $criado_por_id_para_db, $tipo_criacao);
                if ($stmt->execute()) {
                    // Mensagem para ser exibida em livros_biblioteca.php ou na estante
                    // $_SESSION['mensagem_sucesso_estante'] = "Livro '".htmlspecialchars($titulo)."' adicionado com sucesso!";

                    // Mensagem para ser exibida na página criar_livro.php após o redirecionamento
                    $_SESSION['mensagem_sucesso_pagina_criacao'] = "Livro '".htmlspecialchars($titulo)."' adicionado com sucesso!";
                    header("location: criar_livro.php"); // Redireciona para a página de escolha de criação
                    exit;
                } else {
                    $erros[] = "Erro ao inserir livro na base de dados: (" . $stmt->errno . ") " . $stmt->error;
                    // Se houver erro no DB e um arquivo foi salvo, idealmente deveria ser removido para consistência.
                    if ($img_path && file_exists($img_path)) { unlink($img_path); }
                    if ($ficheiro_path && file_exists($ficheiro_path)) { unlink($ficheiro_path); }
                }
                $stmt->close();
            } else {
                $erros[] = "Erro ao preparar a query para a base de dados: (" . $mysqli->errno . ") " . $mysqli->error;
            }
        }
    }

    // Se, após todas as tentativas, houver erros, guardar na sessão e redirecionar de volta para o formulário de upload
    if (!empty($erros)) {
        $_SESSION['erros_criar_livro'] = $erros;
        $_SESSION['form_data_criar_livro'] = $form_data; // Guarda os dados submetidos para repopular
        header("location: form_upload_completo.php"); // Redirecionar para o formulário de upload em caso de erro
        exit;
    }

} else { // Corresponde ao if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['criar_livro_submit']))
    // Se o acesso não for via POST ou o botão correto não for pressionado, redireciona.
    $_SESSION['erros_criar_livro'] = ["Acesso inválido ao processamento."];
    header("location: criar_livro.php");
    exit;
}
?>
