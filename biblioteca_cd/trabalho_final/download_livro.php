<?php
session_start();
require_once "config_db.php";

// Verificar se o usuário está logado (essencial para qualquer acesso a downloads)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Pode redirecionar para o login ou mostrar uma mensagem de erro mais genérica
    // header("location: login.php?erro=nao_logado_download");
    http_response_code(403); // Forbidden
    die("Acesso negado. Você precisa estar logado para descarregar ficheiros.");
}

$livro_id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $livro_id = intval($_GET['id']);
}

if ($livro_id <= 0) {
    http_response_code(400); // Bad Request
    die("ID do livro inválido.");
}

// Buscar o caminho do ficheiro do livro no banco de dados
$stmt = $mysqli->prepare("SELECT titulo, ficheiro FROM livros WHERE id = ?");
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    error_log("Erro ao preparar statement para buscar ficheiro: " . $mysqli->error);
    die("Erro ao processar o seu pedido. Tente novamente mais tarde.");
}

$stmt->bind_param("i", $livro_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $livro = $result->fetch_assoc();
    $caminho_ficheiro = $livro['ficheiro'];
    $titulo_livro = $livro['titulo'];

    if (empty($caminho_ficheiro)) {
        http_response_code(404); // Not Found
        die("Este livro não possui um ficheiro associado para download.");
    }

    // Validar extensão do ficheiro (apenas PDF neste caso)
    $extensao = strtolower(pathinfo($caminho_ficheiro, PATHINFO_EXTENSION));
    if ($extensao !== 'pdf') {
        http_response_code(400); // Bad Request
        die("O formato do ficheiro não é PDF e não pode ser descarregado por este meio.");
    }

    // Verificar se o ficheiro existe no servidor
    // É importante usar o caminho completo do sistema de ficheiros se $caminho_ficheiro for relativo.
    // Assumindo que $caminho_ficheiro já é um caminho acessível pelo script PHP.
    if (file_exists($caminho_ficheiro)) {
        // Limpar qualquer saída anterior para evitar corromper o ficheiro
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Definir cabeçalhos para forçar o download
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        // Sanitizar o nome do ficheiro para o download
        $nome_ficheiro_download = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $titulo_livro) . '.pdf';
        header('Content-Disposition: attachment; filename="' . $nome_ficheiro_download . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($caminho_ficheiro));

        // Ler o ficheiro e enviá-lo para o output
        readfile($caminho_ficheiro);
        exit;
    } else {
        http_response_code(404); // Not Found
        error_log("Ficheiro não encontrado no servidor para o livro ID $livro_id: $caminho_ficheiro");
        die("Ficheiro do livro não encontrado no servidor.");
    }
} else {
    http_response_code(404); // Not Found
    die("Livro não encontrado.");
}

$stmt->close();
$mysqli->close();
?>
