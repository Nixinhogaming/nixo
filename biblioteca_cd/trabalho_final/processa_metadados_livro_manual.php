<?php
session_start();
require_once "config_db.php";

// Apenas admins podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: login.php");
    exit;
}

$titulo = "";
$autor = "";
$faixa_etaria = "";
$resumo = "";
$img_path = null;
$erros = [];
$form_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['iniciar_criacao_manual_submit'])) {
    $form_data = $_POST;

    // Validação (similar a processa_criar_livro.php, mas sem idade_livro e ficheiro)
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

    // Processamento da Imagem da Capa (Obrigatório)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "uploads/livros/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $erros[] = "Falha crítica: Não foi possível criar o diretório de uploads.";
            }
        }

        if (empty($erros) && is_dir($upload_dir) && is_writable($upload_dir)) {
            $img_original_name = basename($_FILES['imagem']['name']);
            $img_ext = strtolower(pathinfo($img_original_name, PATHINFO_EXTENSION));
            $valid_img_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($img_ext, $valid_img_exts)) {
                $erros[] = "Formato de imagem inválido. Use JPG, JPEG, PNG ou GIF.";
            }
            // Limite de tamanho removido para staff
            // elseif ($_FILES['imagem']['size'] > 2097152) {
            //     $erros[] = "A imagem da capa não pode exceder 2MB.";
            // }
            else {
                $sanitized_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($img_original_name, PATHINFO_FILENAME));
                $img_path = $upload_dir . uniqid('capa_manual_', true) . '_' . $sanitized_name . "." . $img_ext;

                if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $img_path)) {
                    $erros[] = "Falha ao guardar a imagem da capa. Código do erro: " . $_FILES['imagem']['error'];
                    $img_path = null;
                }
            }
        } elseif(empty($erros)) { // Só adiciona este erro se não houver erro de criação de diretório
            $erros[] = "Diretório de uploads não encontrado ou sem permissão de escrita.";
        }
    } elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['imagem']['error'] != UPLOAD_ERR_OK) {
         $erros[] = "Erro no upload da imagem da capa. Código: " . $_FILES['imagem']['error'];
    } else {
         $erros[] = "A imagem da capa é obrigatória.";
    }

    if (empty($erros)) {
        // // 1. Verificar se o ID da sessão do admin está presente - TEMPORARIAMENTE REMOVIDO
        // if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
        //     $erros[] = "Erro de sessão: ID do administrador não encontrado na sessão. Faça login novamente.";
        // } else {
        //     $criado_por_id_sessao = $_SESSION["id"];
        //     // 2. Verificar se este ID de admin existe na tabela 'usuarios' e é de um admin
        //     $stmt_check_user_manual = $mysqli->prepare("SELECT id FROM usuarios WHERE id = ? AND is_admin = 1");
        //     if ($stmt_check_user_manual) {
        //         $stmt_check_user_manual->bind_param("i", $criado_por_id_sessao);
        //         $stmt_check_user_manual->execute();
        //         $result_check_user_manual = $stmt_check_user_manual->get_result();
        //         if ($result_check_user_manual->num_rows == 0) {
        //             $erros[] = "Erro de integridade de dados: O ID de administrador da sessão não corresponde a um administrador válido no sistema.";
        //         }
        //         $stmt_check_user_manual->close();
        //     } else {
        //         $erros[] = "Erro ao verificar ID do administrador (preparação): " . $mysqli->error;
        //     }
        // }

        // Temporariamente, definir criado_por_id como NULL
        $criado_por_id_para_db = null;

        // Prosseguir apenas se não houver erros de validação anteriores
        if (empty($erros)) {
            $tipo_criacao = 'manual';
            $idade_livro_manual = null;
            $ficheiro_manual = null;

            $sql = "INSERT INTO livros (titulo, autor, faixa_etaria, imagem, resumo, criado_por_id, idade_livro, ficheiro, tipo_criacao)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);

            if ($stmt) {
                // Tipos: ssssisiss (titulo, autor, faixa_etaria, imagem, resumo, criado_por_id, idade_livro, ficheiro, tipo_criacao)
                $stmt->bind_param("sssssisss", $titulo, $autor, $faixa_etaria, $img_path, $resumo, $criado_por_id_para_db, $idade_livro_manual, $ficheiro_manual, $tipo_criacao);

                if ($stmt->execute()) {
                    $novo_livro_id = $mysqli->insert_id;
                    // $_SESSION['mensagem_sucesso_editor_manual'] = "Metadados do livro '".htmlspecialchars($titulo)."' salvos! Agora escreva o conteúdo.";
                    // Redirecionar para o novo editor de páginas conforme o plano
                    header("location: editor_paginas_livro.php?livro_id=" . $novo_livro_id);
                    exit; // Importante: parar a execução após o redirecionamento
                } else {
                    $erros[] = "Erro ao salvar metadados do livro: (" . $stmt->errno . ") " . $stmt->error;
                    if ($img_path && file_exists($img_path)) { unlink($img_path); }
                }
                $stmt->close();
            } else {
                $erros[] = "Erro ao preparar para salvar metadados: (" . $mysqli->errno . ") " . $mysqli->error;
            }
        }
    }

    // Se, após todas as tentativas, houver erros, guardar na sessão e redirecionar de volta para o formulário de metadados manuais
    if (!empty($erros)) {
        $_SESSION['erros_form_meta_manual'] = $erros; // Chave de erro específica
        $_SESSION['form_data_meta_manual'] = $form_data; // Chave de dados específica
        header("location: form_meta_manual.php"); // Redireciona para o formulário de origem
        exit;
    }

} else { // Corresponde ao if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['iniciar_criacao_manual_submit']))
    $_SESSION['erros_form_meta_manual'] = ["Acesso inválido ao processamento de metadados."];
    header("location: form_meta_manual.php");
    exit;
}
?>
