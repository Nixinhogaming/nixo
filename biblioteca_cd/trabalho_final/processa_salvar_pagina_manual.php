<?php
session_start();
require_once "config_db.php";
header('Content-Type: application/json'); // Define o tipo de resposta como JSON

$resposta = ['success' => false, 'message' => 'Acesso inválido.'];

// Apenas admins podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    $resposta['message'] = 'Acesso negado.';
    echo json_encode($resposta);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $livro_id = isset($_POST['livro_id']) ? intval($_POST['livro_id']) : 0;
    // Esperamos receber dados como:
    // 'paginas' => [
    //     ['numero' => 1, 'conteudo' => 'Texto da pág 1'],
    //     ['numero' => 2, 'conteudo' => 'Texto da pág 2'] // Opcional, se duas páginas forem salvas de uma vez
    // ]
    $paginas_data = isset($_POST['paginas']) ? json_decode($_POST['paginas'], true) : null;

    if ($livro_id > 0 && !empty($paginas_data) && is_array($paginas_data)) {
        $mysqli->begin_transaction(); // Iniciar transação para garantir consistência
        $sucesso_geral = true;
        $mensagens_operacao = [];

        foreach ($paginas_data as $pagina) {
            if (!isset($pagina['numero']) || !is_numeric($pagina['numero']) || intval($pagina['numero']) <= 0) {
                $sucesso_geral = false;
                $mensagens_operacao[] = "Número de página inválido fornecido.";
                break;
            }
            $numero_pagina = intval($pagina['numero']);
            $conteudo = isset($pagina['conteudo']) ? $pagina['conteudo'] : ''; // Conteúdo pode ser HTML do TinyMCE

            // Verificar se a página já existe para decidir entre INSERT ou UPDATE
            $stmt_check = $mysqli->prepare("SELECT id FROM livro_paginas WHERE livro_id = ? AND numero_pagina = ?");
            if (!$stmt_check) {
                $sucesso_geral = false;
                $mensagens_operacao[] = "Erro ao preparar verificação da página: " . $mysqli->error;
                break;
            }
            $stmt_check->bind_param("ii", $livro_id, $numero_pagina);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $pagina_existente_id = ($result_check->num_rows > 0) ? $result_check->fetch_assoc()['id'] : null;
            $stmt_check->close();

            if ($pagina_existente_id) { // UPDATE
                $stmt_save = $mysqli->prepare("UPDATE livro_paginas SET conteudo = ? WHERE id = ?");
                if (!$stmt_save) {
                    $sucesso_geral = false;
                    $mensagens_operacao[] = "Erro ao preparar atualização da página $numero_pagina: " . $mysqli->error;
                    break;
                }
                $stmt_save->bind_param("si", $conteudo, $pagina_existente_id);
            } else { // INSERT
                // Adicionar lógica para imagem da página aqui se necessário no futuro
                $imagem_pagina_placeholder = null;
                $stmt_save = $mysqli->prepare("INSERT INTO livro_paginas (livro_id, numero_pagina, conteudo, imagem) VALUES (?, ?, ?, ?)");
                if (!$stmt_save) {
                    $sucesso_geral = false;
                    $mensagens_operacao[] = "Erro ao preparar inserção da página $numero_pagina: " . $mysqli->error;
                    break;
                }
                $stmt_save->bind_param("iiss", $livro_id, $numero_pagina, $conteudo, $imagem_pagina_placeholder);
            }

            if (!$stmt_save->execute()) {
                $sucesso_geral = false;
                $mensagens_operacao[] = "Erro ao salvar página $numero_pagina: " . $stmt_save->error;
                $stmt_save->close();
                break;
            }
            $stmt_save->close();
            $mensagens_operacao[] = "Página $numero_pagina salva.";
        }

        if ($sucesso_geral) {
            $mysqli->commit();
            $resposta['success'] = true;
            $resposta['message'] = "Página(s) salva(s) com sucesso!";
            $resposta['details'] = $mensagens_operacao;
        } else {
            $mysqli->rollback();
            $resposta['message'] = "Falha ao salvar página(s).";
            $resposta['details'] = $mensagens_operacao;
        }

    } else {
        $resposta['message'] = "Dados inválidos para salvar página(s). Livro ID: $livro_id, Paginas Data: " . json_encode($paginas_data);
    }
}

echo json_encode($resposta);
exit;
?>
