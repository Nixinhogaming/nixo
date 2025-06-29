<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
if (!isset($_SESSION["faixa_etaria"])) {
    // Buscar da BD se não estiver na sessão
    require_once "config_db.php";
    $stmt = $mysqli->prepare("SELECT faixa_etaria FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION["id"]);
    $stmt->execute();
    $stmt->bind_result($faixa);
    if ($stmt->fetch()) $_SESSION["faixa_etaria"] = $faixa;
    $stmt->close();
}
if (isset($_POST['so_sua_faixa'])) {
    $_SESSION['so_sua_faixa'] = $_POST['so_sua_faixa'] === '1';
}
$so_sua_faixa = isset($_SESSION['so_sua_faixa']) ? $_SESSION['so_sua_faixa'] : false;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Perfil</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="form-frame">
            <h2>Perfil</h2>
            <form method="post">
                <label>
                    <input type="checkbox" name="so_sua_faixa" value="1" <?php if($so_sua_faixa) echo 'checked'; ?>>
                    Ver apenas livros da minha faixa etária
                </label>
                <button type="submit" class="btn">Atualizar Preferência</button>
            </form>
            <p>Faixa etária do seu perfil: <strong><?php echo htmlspecialchars($_SESSION["faixa_etaria"]); ?></strong></p>
        </div>
    </div>
</body>
</html>
