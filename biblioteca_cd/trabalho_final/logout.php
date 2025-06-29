<?php
// Iniciar a sessão (ou continuar a sessão existente para poder destruí-la)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Desfazer todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão.
if (session_destroy()) {
    // Sessão destruída com sucesso.
    // Se quisermos remover o cookie de sessão também (opcional, mas mais completo):
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
} else {
    // Se houver algum problema ao destruir a sessão (raro)
    // Podemos logar um erro aqui, mas para o usuário, o comportamento
    // de redirecionar para o login geralmente é suficiente.
    error_log("Falha ao tentar destruir a sessão.");
}

// Redirecionar para a página de login
header("location: login.php?logout=1"); // Adiciona um parâmetro para possível feedback
exit;
?>
