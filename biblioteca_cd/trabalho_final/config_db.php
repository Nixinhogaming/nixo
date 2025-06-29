<?php
// FUTURAMENTE, APÓS INSTALAR O COMPOSER E O PHPMAILER:
// Descomente a linha abaixo para incluir o autoloader do Composer SE for usar bibliotecas via Composer.
// Certifique-se de que o caminho para 'vendor/autoload.php' está correto
// em relação à localização deste arquivo (config_db.php).
// require_once __DIR__ . '/../vendor/autoload.php'; // Comentado pois não estamos mais usando PHPMailer diretamente.

// Configurações do Banco de Dados
define('DB_SERVER', 'localhost'); // Ou o IP/host do seu servidor de DB
define('DB_USERNAME', 'root');    // Seu usuário do MySQL
define('DB_PASSWORD', '');        // Sua senha do MySQL
define('DB_NAME', 'biblioteca_login');

// Tentar conectar ao banco de dados MySQL
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar a conexão
if($mysqli === false){
    // Não exibir detalhes do erro em produção. Logar o erro.
    // die("ERRO: Não foi possível conectar ao banco de dados. " . $mysqli->connect_error);
    // Para desenvolvimento, pode ser útil:
    error_log("Erro de conexão com o BD: " . $mysqli->connect_error);
    // Redirecionar para uma página de erro genérica ou mostrar mensagem amigável
    header("location: registo.php?erro=Erro interno do servidor. Tente novamente mais tarde.");
    exit;
}

// Definir o charset para utf8mb4 para suportar uma vasta gama de caracteres
if (!$mysqli->set_charset("utf8mb4")) {
    // printf("Erro ao definir o charset utf8mb4: %s\n", $mysqli->error);
    error_log("Erro ao definir o charset utf8mb4: " . $mysqli->error);
    // Considerar como tratar este erro, pode não ser crítico para todas as apps
}

// Iniciar a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
