<?php
require_once "config_db.php"; // Primeira linha, já lida com session_start()

// Apenas admins podem acessar e precisam estar logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    $_SESSION['login_error'] = "Acesso negado. Faça login como administrador.";
    header("location: login.php");
    exit;
}

// Verificar se o ID do admin (editor) está na sessão
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    error_log("Erro crítico em processa_editar_livro.php: Admin logado (" . ($_SESSION['primeiro_nome'] ?? 'Nome não encontrado') . ") sem ID na sessão. Sessão: " . print_r($_SESSION, true));

    $redirect_url = "login.php";
    $error_message = "Erro crítico de sessão. Faça login novamente.";

    if (isset($_POST['livro_id']) && is_numeric($_POST['livro_id'])) {
        $_SESSION['erros_editar_livro'] = ["Erro crítico de sessão: ID do administrador não encontrado. Por favor, faça login novamente."];
        $redirect_url = "editar_livro.php?id=" . intval($_POST['livro_id']);
    } else {
        $_SESSION['login_error'] = $error_message;
    }

    // Limpar sessão potencialmente corrompida
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    header("location: " . $redirect_url);
    exit;
}
$editado_por_id_sessao = $_SESSION["id"]; // ID do admin que está editando

$livro_id = null; // Será definido a partir do POST
$titulo = "";
$autor = "";
// $idade_livro = 0; // Removido, será $idade_livro_para_db
$faixa_etaria = "";
$resumo = "";
$erros = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_livro_submit'])) {
    if (empty($_POST['livro_id']) || !is_numeric($_POST['livro_id'])) {
        // Este erro deve idealmente ser tratado de forma mais robusta,
        // talvez redirecionando para uma página de erro ou a estante com uma mensagem genérica,
        // pois sem ID do livro, não podemos nem voltar ao formulário de edição específico.
        $_SESSION['mensagem_erro_estante'] = "ID do livro inválido para edição.";
        header("location: livros_biblioteca.php?admin_view=true"); // Redireciona para a estante
        exit;
    }
    $livro_id = intval($_POST['livro_id']); // Agora $livro_id está definido para o resto do script

    // Validação dos campos (similar a processa_criar_livro.php)
    if (empty(trim($_POST["titulo"]))) {
        $erros[] = "O título é obrigatório.";
    } else {
        $titulo = trim($_POST["titulo"]);
    }
    if (empty(trim($_POST["autor"]))) {
        $erros[] = "O autor é obrigatório.";
    } else {
        $autor = trim($_POST["autor"]);
    }

    // Processamento da Idade Recomendada
    $idade_livro_para_db = null; // Default para NULL
    if (isset($_POST['sem_idade_especifica']) && $_POST['sem_idade_especifica'] == '1') {
        $idade_livro_para_db = null;
    } elseif (isset($_POST["idade_livro"]) && $_POST["idade_livro"] !== '') {
        if (!is_numeric($_POST["idade_livro"]) || intval($_POST["idade_livro"]) < 0 || intval($_POST["idade_livro"]) > 150) {
            $erros[] = "A idade recomendada deve ser um número válido entre 0 e 150, ou marcada como não específica.";
        } else {
            $idade_livro_para_db = intval($_POST["idade_livro"]);
        }
    }
    // Se nem o checkbox está marcado, nem um valor válido foi enviado, $idade_livro_para_db permanece null,
    // o que é o comportamento desejado para "sem idade" se o campo for opcional e não 'required'.

    $faixas_validas = ["Livre", "6-8", "9-12", "13-17", "18+"];
    if (empty($_POST["faixa_etaria"]) || !in_array($_POST["faixa_etaria"], $faixas_validas)) {
        $erros[] = "A faixa etária selecionada é inválida.";
    } else {
        $faixa_etaria = $_POST["faixa_etaria"];
    }
    if (empty(trim($_POST["resumo"]))) {
        $erros[] = "O resumo é obrigatório.";
    } else {
        $resumo = trim($_POST["resumo"]);
    }

    // Obter caminhos atuais da imagem e ficheiro para possível exclusão
    $stmt_select = $mysqli->prepare("SELECT imagem, ficheiro FROM livros WHERE id = ?");
    $caminho_imagem_atual = null;
    $caminho_ficheiro_atual = null;
    if ($stmt_select) {
        $stmt_select->bind_param("i", $livro_id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        if ($result_select->num_rows === 1) {
            $livro_atual = $result_select->fetch_assoc();
            $caminho_imagem_atual = $livro_atual['imagem'];
            $caminho_ficheiro_atual = $livro_atual['ficheiro'];
        }
        $stmt_select->close();
    } else {
        $erros[] = "Não foi possível buscar os dados atuais do livro.";
    }


    $novo_img_path = $caminho_imagem_atual; // Mantém o atual por padrão
    // Processamento da Nova Imagem da Capa (Opcional)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "uploads/livros/";
        // (Validações de diretório, tipo, tamanho - como em processa_criar_livro.php)
        $img_original_name = basename($_FILES['imagem']['name']);
        $img_ext = strtolower(pathinfo($img_original_name, PATHINFO_EXTENSION));
        $valid_img_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($img_ext, $valid_img_exts)) {
            $erros[] = "Formato de nova imagem inválido.";
        // } elseif ($_FILES['imagem']['size'] > 2097152) { // Limite Removido para Staff
        //     $erros[] = "A nova imagem da capa não pode exceder 2MB.";
        } else {
            $sanitized_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($img_original_name, PATHINFO_FILENAME));
            $novo_img_path_temporario = $upload_dir . uniqid('capa_edit_', true) . '_' . $sanitized_name . "." . $img_ext;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $novo_img_path_temporario)) {
                if ($caminho_imagem_atual && file_exists($caminho_imagem_atual)) {
                    unlink($caminho_imagem_atual); // Apaga a imagem antiga
                }
                $novo_img_path = $novo_img_path_temporario; // Define o novo caminho
            } else {
                $erros[] = "Falha ao guardar a nova imagem da capa.";
            }
        }
    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] != UPLOAD_ERR_NO_FILE) {
        $erros[] = "Erro no upload da nova imagem: Código " . $_FILES['imagem']['error'];
    }

    $novo_ficheiro_path = $caminho_ficheiro_atual; // Mantém o atual por padrão
    $remover_ficheiro = isset($_POST['remover_ficheiro_existente']) && $_POST['remover_ficheiro_existente'] == '1';

    if ($remover_ficheiro) {
        if ($caminho_ficheiro_atual && file_exists($caminho_ficheiro_atual)) {
            unlink($caminho_ficheiro_atual);
        }
        $novo_ficheiro_path = null; // Define como nulo para o DB
    } elseif (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] == UPLOAD_ERR_OK) {
        // Processa novo ficheiro (lógica similar ao upload de imagem, com validação de PDF/TXT e tamanho)
        $upload_dir = "uploads/livros/";
        $file_original_name = basename($_FILES['ficheiro']['name']);
        $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
        $valid_file_exts = ['pdf', 'txt'];

        if (!in_array($file_ext, $valid_file_exts)) {
            $erros[] = "Formato de novo ficheiro inválido (apenas PDF ou TXT).";
        } elseif ($_FILES['ficheiro']['size'] > 10485760) { // 10MB
            $erros[] = "O novo ficheiro do livro não pode exceder 10MB.";
        } else {
            $sanitized_file_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file_original_name, PATHINFO_FILENAME));
            $novo_ficheiro_path_temporario = $upload_dir . uniqid('livro_edit_', true) . '_' . $sanitized_file_name . "." . $file_ext;
            if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $novo_ficheiro_path_temporario)) {
                if ($caminho_ficheiro_atual && file_exists($caminho_ficheiro_atual) && !$remover_ficheiro) { // Não apaga se já foi marcado para remover
                    unlink($caminho_ficheiro_atual);
                }
                $novo_ficheiro_path = $novo_ficheiro_path_temporario;
            } else {
                $erros[] = "Falha ao guardar o novo ficheiro do livro.";
            }
        }
    } elseif (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] != UPLOAD_ERR_NO_FILE) {
         $erros[] = "Erro no upload do novo ficheiro: Código " . $_FILES['ficheiro']['error'];
    }


    // A verificação de $_SESSION["id"] já foi feita no topo.
    // A variável $editado_por_id_sessao já está definida.
    // Apenas precisamos garantir que não há outros erros de formulário ANTES de tentar o UPDATE.
    if (empty($erros)) { // Verifica APENAS erros de validação do formulário aqui
        // A coluna editado_em será atualizada para NOW() diretamente na query.
        // A coluna criado_por_id não é alterada aqui, apenas editado_por_id.

        // Verificar se o ID do editor (da sessão) corresponde a um admin válido no BD.
        $stmt_check_user = $mysqli->prepare("SELECT id FROM usuarios WHERE id = ? AND is_admin = 1");
        if ($stmt_check_user) {
            $stmt_check_user->bind_param("i", $editado_por_id_sessao);
            $stmt_check_user->execute();
            $result_check_user = $stmt_check_user->get_result();
            if ($result_check_user->num_rows == 0) {
                // ID da sessão não corresponde a um admin válido no BD. Isso é crítico.
                $erros[] = "Erro de integridade: ID do administrador da sessão não é válido ou não é administrador.";
                error_log("Falha de integridade em processa_editar_livro.php: ID de sessão admin " . $editado_por_id_sessao . " não encontrado como admin no BD.");
            }
            $stmt_check_user->close();
        } else {
            $erros[] = "Erro ao verificar ID do editor (preparação): " . $mysqli->error;
        }

        // Prosseguir com o UPDATE apenas se não houver erros de validação do ID do editor no BD
        if (empty($erros)) {
            $sql_update = "UPDATE livros SET
                            titulo = ?,
                            autor = ?,
                            idade_livro = ?,
                            faixa_etaria = ?,
                            imagem = ?,
                            resumo = ?,
                            ficheiro = ?,
                            editado_por_id = ?,
                            editado_em = NOW()
                          WHERE id = ?";

            $stmt_update = $mysqli->prepare($sql_update);
            if ($stmt_update) {
                // Tipos: s (titulo), s (autor), i (idade_livro), s (faixa_etaria),
                //        s (novo_img_path), s (resumo), s (novo_ficheiro_path),
                //        i (editado_por_id_sessao), i (livro_id)
                $stmt_update->bind_param("ssissssii",
                                         $titulo, $autor, $idade_livro_para_db, $faixa_etaria,
                                         $novo_img_path, $resumo, $novo_ficheiro_path,
                                         $editado_por_id_sessao, $livro_id);

                if ($stmt_update->execute()) {
                    $_SESSION['mensagem_sucesso_estante'] = "Livro '".htmlspecialchars($titulo)."' atualizado com sucesso!";
                    header("location: livros_biblioteca.php?admin_view=true"); // Redirecionar para a nova estante (visão admin)
                    exit; // Importante: Sair após o redirecionamento de sucesso
                } else {
                    $erros[] = "Erro ao atualizar livro na base de dados: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $erros[] = "Erro ao preparar a atualização na base de dados: " . $mysqli->error;
            }
        }
    } // Fim do if (empty($erros)) que engloba a lógica de DB

    // Se, após todas as tentativas, houver erros, guardar na sessão e redirecionar de volta
    if (!empty($erros)) {
        $_SESSION['erros_editar_livro'] = $erros;
        $_SESSION['form_data_editar_livro'] = $_POST; // Guarda os dados submetidos para repopular
        header("location: editar_livro.php?id=" . $livro_id);
        exit;
    }

} else { // Corresponde a if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_livro_submit']))
    $_SESSION['mensagem_erro_estante'] = "Acesso inválido ao processamento de edição.";
    header("location: estante_livros.php");
    exit;
}
?>
