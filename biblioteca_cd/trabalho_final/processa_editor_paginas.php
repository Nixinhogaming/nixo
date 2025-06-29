<?php
session_start();
if (!isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: main.php");
    exit;
}
require_once "config_db.php";
$livro_id = intval($_POST['livro_id']);
$pagina_id = intval($_POST['pagina_id']);
$numero_pagina = intval($_POST['numero_pagina']);
$conteudo = $_POST['conteudo'];
$img_path = null;

// Upload imagem
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $img_path = "uploads/livros/" . uniqid() . "." . $ext;
    if (!is_dir("uploads/livros")) mkdir("uploads/livros", 0777, true);
    move_uploaded_file($_FILES['imagem']['tmp_name'], $img_path);
}

if ($_POST['acao'] == 'guardar') {
    if ($pagina_id > 0) {
        // Update
        $sql = "UPDATE livro_paginas SET conteudo=?, imagem=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $img_final = $img_path ? $img_path : null;
        if (!$img_final) {
            // manter imagem antiga
            $stmt2 = $mysqli->prepare("SELECT imagem FROM livro_paginas WHERE id=?");
            $stmt2->bind_param("i", $pagina_id);
            $stmt2->execute();
            $stmt2->bind_result($img_antiga);
            $stmt2->fetch();
            $img_final = $img_antiga;
            $stmt2->close();
        }
        $stmt->bind_param("ssi", $conteudo, $img_final, $pagina_id);
        $stmt->execute();
    } else {
        // Insert
        $sql = "INSERT INTO livro_paginas (livro_id, numero_pagina, conteudo, imagem) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiss", $livro_id, $numero_pagina, $conteudo, $img_path);
        $stmt->execute();
    }
} elseif ($_POST['acao'] == 'apagar' && $pagina_id > 0) {
    $sql = "DELETE FROM livro_paginas WHERE id=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $pagina_id);
    $stmt->execute();
} elseif ($_POST['acao'] == 'finalizar_livro_manual') {
    $livro_titulo_raw = isset($_POST['livro_titulo']) ? $_POST['livro_titulo'] : 'Livro'; // Recupera o título do livro
    // Poderia haver uma lógica aqui para marcar o livro como "completo" no banco de dados, se necessário.
    // Por exemplo: $stmt_finalizar = $mysqli->prepare("UPDATE livros SET status = 'completo' WHERE id = ?");
    // $stmt_finalizar->bind_param("i", $livro_id);
    // $stmt_finalizar->execute();
    // $stmt_finalizar->close();

    $_SESSION['mensagem_sucesso_pagina_criacao'] = "Livro '".htmlspecialchars($livro_titulo_raw)."' finalizado e salvo na estante!";
    header("location: criar_livro.php");
    exit;
}

// Redirecionamento padrão se não for 'finalizar_livro_manual'
// O header original era "editor_livro.php", mas deve ser "editor_paginas_livro.php"
header("location: editor_paginas_livro.php?id=$livro_id" . ($pagina_id ? "&pag=$numero_pagina" : "&nova=1"));
exit;
?>
