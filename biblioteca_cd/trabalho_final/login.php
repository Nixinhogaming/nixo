<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container login-container">
        <div class="form-frame">
            <h2>Login</h2>
            <?php
            // Iniciar sessão se ainda não estiver ativa (para mensagens da sessão e limpeza do localStorage)
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Limpar localStorage do formulário de registro se vindo de um registro concluído
            if (isset($_SESSION['registro_concluido']) && $_SESSION['registro_concluido'] === true) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const fieldsToClear = ['registo_primeiro_nome', 'registo_ultimo_nome', 'registo_idade', 'registo_email'];
                        fieldsToClear.forEach(function(key) {
                            localStorage.removeItem(key);
                            // console.log('Limpou do localStorage: ' + key); // Para depuração
                        });
                    });
                </script>";
                unset($_SESSION['registro_concluido']); // Limpar a flag da sessão
            }

            // Exibir mensagem de sucesso após redefinição de senha - REMOVIDO pois a funcionalidade foi removida
            // if (isset($_SESSION['login_mensagem_sucesso'])) {
            //     echo '<div class="success-message">' . htmlspecialchars($_SESSION['login_mensagem_sucesso']) . '</div>';
            //     unset($_SESSION['login_mensagem_sucesso']); // Limpar após exibir
            // }

            // Verificar se existem mensagens de erro ou sucesso na URL (do registo ou login anterior)
            if (isset($_GET['erro'])) {
                // Filtrar mensagens de erro que não são mais relevantes (ex: email não verificado)
                // Por agora, vamos exibir todas, mas idealmente seriam mais genéricas vindas do processa_login
                echo '<div class="error-message">' . htmlspecialchars($_GET['erro']) . '</div>';
            }
            if (isset($_GET['sucesso'])) { // Mensagem de sucesso vinda do registo
                echo '<div class="success-message">' . htmlspecialchars($_GET['sucesso']) . '</div>';
            }
            ?>
            <form action="processa_login.php" method="POST">
                <div class="input-group">
                    <label for="utilizador">Utilizador (Primeiro Nome):</label>
                    <input type="text" id="utilizador" name="utilizador" required>
                </div>
                <div class="input-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <!-- Link "Esqueceu-se da sua palavra-passe?" REMOVIDO -->
                <button type="submit" class="btn">Entrar</button>
            </form>
            <!-- Formulário de login de Staff específico REMOVIDO por questões de segurança e redundância -->
            <p class="center-text" style="margin-top: 20px;">Ainda não tem conta? <a href="registo.php">Crie uma aqui</a>.</p>
        </div>
    </div>
</body>
</html>
