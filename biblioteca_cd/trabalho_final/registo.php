<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Biblioteca</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container registo-container">
        <div class="form-frame">
            <h2>Criar Nova Conta</h2>
            <?php
            if (session_status() == PHP_SESSION_NONE) { // Garantir que a sessão está iniciada
                session_start();
            }

            // Exibir mensagens de erro da sessão
            if (isset($_SESSION['erros_registo']) && !empty($_SESSION['erros_registo'])) {
                echo '<div class="error-message">';
                foreach ($_SESSION['erros_registo'] as $erro_msg) {
                    echo htmlspecialchars($erro_msg) . '<br>';
                }
                echo '</div>';
                unset($_SESSION['erros_registo']); // Limpar erros após exibir
            }

            // Exibir mensagem de sucesso vinda do login.php (após registro bem-sucedido)
            if (isset($_GET['sucesso'])) {
                echo '<div class="success-message">' . htmlspecialchars($_GET['sucesso']) . '</div>';
            }

            // Recuperar dados do formulário da sessão para repopulação
            $form_data = isset($_SESSION['form_data_registo']) ? $_SESSION['form_data_registo'] : [];
            // Limpar dados do formulário da sessão depois de recuperá-los,
            // exceto se houver erros (para permitir que o usuário corrija)
            if (!isset($_SESSION['erros_registo'])) { // Se não houve erros na submissão anterior que está sendo exibida
                 unset($_SESSION['form_data_registo']);
            }

            ?>
            <form action="processa_registo.php" method="POST">
                <div class="input-group">
                    <label for="primeiro_nome">Primeiro Nome:</label>
                    <input type="text" id="primeiro_nome" name="primeiro_nome" value="<?php echo htmlspecialchars($form_data['primeiro_nome'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="ultimo_nome">Último Nome:</label>
                    <input type="text" id="ultimo_nome" name="ultimo_nome" value="<?php echo htmlspecialchars($form_data['ultimo_nome'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="idade">Idade:</label>
                    <input type="number" id="idade" name="idade" value="<?php echo htmlspecialchars($form_data['idade'] ?? ''); ?>" required min="1" max="150">
                </div>
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required minlength="8">
                </div>
                <div class="input-group">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
                </div>

                <button type="submit" class="btn">Criar Conta</button>
            </form>
            <p class="center-text">Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formFieldsToPersist = [
        'primeiro_nome',
        'ultimo_nome',
        'idade',
        'email'
        // Não incluir 'senha' ou 'confirmar_senha' por segurança
        // Não incluir 'aceitar_termos' pois foi removido
    ];

    // Ao carregar a página, preencher os campos com valores do localStorage
    formFieldsToPersist.forEach(function(fieldId) {
        const LSTORAGE_KEY = 'registo_' + fieldId;
        const savedValue = localStorage.getItem(LSTORAGE_KEY);
        const fieldElement = document.getElementById(fieldId);
        if (savedValue !== null && fieldElement) {
            fieldElement.value = savedValue;
        }
    });

    // Adicionar event listeners para guardar alterações no localStorage
    formFieldsToPersist.forEach(function(fieldId) {
        const fieldElement = document.getElementById(fieldId);
        if (fieldElement) {
            fieldElement.addEventListener('input', function() {
                const LSTORAGE_KEY = 'registo_' + fieldId;
                localStorage.setItem(LSTORAGE_KEY, fieldElement.value);
            });
        }
    });
});

// Função global para limpar os dados do formulário de registro do localStorage
// Esta função será chamada pelo script injetado em login.php (se o fluxo for para lá e a flag estiver ativa)
// ou pela página aguardar_confirmacao.php
function limparDadosRegistoLocalStorage() {
    const formFieldsToPersist = ['primeiro_nome', 'ultimo_nome', 'idade', 'email'];
    formFieldsToPersist.forEach(function(fieldId) {
        const LSTORAGE_KEY = 'registo_' + fieldId;
        localStorage.removeItem(LSTORAGE_KEY);
        // console.log('Limpou ' + LSTORAGE_KEY); // Para depuração
    });
}
</script>
</body>
</html>
