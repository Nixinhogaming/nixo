<?php
session_start();
require_once "config_db.php";

// Apenas admins podem acessar e apagar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    $_SESSION['mensagem_erro_estante'] = "Acesso negado.";
    header("location: main.php"); // Ou login.php
    exit;
}

$livro_id = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $livro_id = intval($_GET['id']);
} else {
    $_SESSION['mensagem_erro_estante'] = "ID do livro inválido ou não fornecido para exclusão.";
    header("location: estante_livros.php");
    exit;
}

// 1. Buscar informações do livro, especialmente caminhos de imagem e ficheiro, antes de apagar do DB
$caminho_imagem_para_apagar = null;
$caminho_ficheiro_para_apagar = null;
$titulo_livro_apagado = "Livro Desconhecido"; // Default

$stmt_select = $mysqli->prepare("SELECT titulo, imagem, ficheiro FROM livros WHERE id = ?");
if ($stmt_select) {
    $stmt_select->bind_param("i", $livro_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select->num_rows === 1) {
        $livro_info = $result_select->fetch_assoc();
        $titulo_livro_apagado = $livro_info['titulo'];
        $caminho_imagem_para_apagar = $livro_info['imagem'];
        $caminho_ficheiro_para_apagar = $livro_info['ficheiro'];
    } else {
        $_SESSION['mensagem_erro_estante'] = "Livro com ID $livro_id não encontrado para exclusão.";
        header("location: estante_livros.php");
        exit;
    }
    $stmt_select->close();
} else {
    $_SESSION['mensagem_erro_estante'] = "Erro ao preparar consulta para buscar dados do livro antes de apagar.";
    header("location: estante_livros.php");
    exit;
}

// 2. Apagar o registro do livro da tabela `livros`
$stmt_delete = $mysqli->prepare("DELETE FROM livros WHERE id = ?");
if ($stmt_delete) {
    $stmt_delete->bind_param("i", $livro_id);
    if ($stmt_delete->execute()) {
        // Se a exclusão do DB for bem-sucedida, tentar apagar os arquivos físicos
        $arquivos_apagados_msg = [];

        if ($caminho_imagem_para_apagar && file_exists($caminho_imagem_para_apagar)) {
            if (unlink($caminho_imagem_para_apagar)) {
                $arquivos_apagados_msg[] = "Imagem da capa apagada.";
            } else {
                $arquivos_apagados_msg[] = "Falha ao apagar imagem da capa (verifique permissões).";
            }
        }
        if ($caminho_ficheiro_para_apagar && file_exists($caminho_ficheiro_para_apagar)) {
            if (unlink($caminho_ficheiro_para_apagar)) {
                $arquivos_apagados_msg[] = "Ficheiro do livro apagado.";
            } else {
                $arquivos_apagados_msg[] = "Falha ao apagar ficheiro do livro (verifique permissões).";
            }
        }

        $msg_final_sucesso = "Livro '".htmlspecialchars($titulo_livro_apagado)."' apagado com sucesso do banco de dados.";
        if (!empty($arquivos_apagados_msg)) {
            $msg_final_sucesso .= " " . implode(" ", $arquivos_apagados_msg);
        }
        $_SESSION['mensagem_sucesso_estante'] = $msg_final_sucesso;

    } else {
        $_SESSION['mensagem_erro_estante'] = "Erro ao apagar o livro do banco de dados: " . $stmt_delete->error;
    }
    $stmt_delete->close();
} else {
    $_SESSION['mensagem_erro_estante'] = "Erro ao preparar a exclusão do livro: " . $mysqli->error;
}

$mysqli->close();
header("location: livros_biblioteca.php?admin_view=true"); // Redirecionar para a nova estante (visão admin)
exit;
?>
