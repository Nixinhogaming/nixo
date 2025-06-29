<?php
session_start();
require_once "config_db.php";

// Verificar se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_configuracoes'])) {
    $user_id = $_SESSION["id"];

    // O valor do checkbox será '1' se marcado, ou não será enviado se desmarcado.
    // Convertemos para booleano (0 ou 1 para o banco de dados)
    $nova_preferencia = isset($_POST['preferencia_ver_faixas_inferiores']) ? 1 : 0;

    $stmt = $mysqli->prepare("UPDATE usuarios SET preferencia_ver_faixas_inferiores = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $nova_preferencia, $user_id);
        if ($stmt->execute()) {
            $_SESSION['mensagem_sucesso_config'] = "Preferências atualizadas com sucesso!";
        } else {
            $_SESSION['mensagem_erro_config'] = "Erro ao atualizar preferências: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem_erro_config'] = "Erro ao preparar para atualizar preferências: " . $mysqli->error;
    }
    $mysqli->close();
} else {
    // Se o acesso não for via POST correto
    $_SESSION['mensagem_erro_config'] = "Acesso inválido.";
}

header("location: configuracoes_perfil.php");
exit;
?>
