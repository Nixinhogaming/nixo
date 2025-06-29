<?php
session_start();
require_once "config_db.php";

// Apenas admins podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    header("location: login.php");
    exit;
}

$livro_id_manual = isset($_GET['livro_id']) ? intval($_GET['livro_id']) : null;
$titulo_livro_manual = "Novo Livro Manual";

if ($livro_id_manual) {
    $stmt_livro = $mysqli->prepare("SELECT id, titulo FROM livros WHERE id = ?");
    if ($stmt_livro) {
        $stmt_livro->bind_param("i", $livro_id_manual);
        $stmt_livro->execute();
        $result_livro = $stmt_livro->get_result();
        if ($result_livro->num_rows === 1) {
            $livro_data = $result_livro->fetch_assoc();
            $titulo_livro_manual = $livro_data['titulo'];
        } else {
            $_SESSION['mensagem_erro_estante'] = "Livro com ID $livro_id_manual não encontrado para edição manual.";
            header("location: livros_biblioteca.php?admin_view=true"); // Ajustado
            exit;
        }
        $stmt_livro->close();
    } else {
        die("Erro ao buscar dados do livro.");
    }
} else {
    $_SESSION['erros_criar_livro'] = ["Nenhum livro selecionado para edição manual."];
    header("location: criar_livro.php");
    exit;
}

$pagina_numero_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_numero_atual < 1) $pagina_numero_atual = 1;

$conteudo_pagina_direita = "";
$conteudo_pagina_esquerda = "";

$stmt_pagina = $mysqli->prepare("SELECT conteudo FROM livro_paginas WHERE livro_id = ? AND numero_pagina = ?");
if ($stmt_pagina) {
    // Página Direita
    $stmt_pagina->bind_param("ii", $livro_id_manual, $pagina_numero_atual);
    $stmt_pagina->execute();
    $result_pagina_dir = $stmt_pagina->get_result();
    if ($data_pagina_dir = $result_pagina_dir->fetch_assoc()) {
        $conteudo_pagina_direita = $data_pagina_dir['conteudo'];
    }
    $stmt_pagina->reset();

    // Página Esquerda (se aplicável)
    if ($pagina_numero_atual > 1) {
        $num_esq_buscar = $pagina_numero_atual - 1;
        $stmt_pagina->bind_param("ii", $livro_id_manual, $num_esq_buscar);
        $stmt_pagina->execute();
        $result_pagina_esq = $stmt_pagina->get_result();
        if ($data_pagina_esq = $result_pagina_esq->fetch_assoc()) {
            $conteudo_pagina_esquerda = $data_pagina_esq['conteudo'];
        }
    }
    $stmt_pagina->close();
}
$mysqli->close(); // Fechar conexão após buscar dados
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Escrever Livro: <?php echo htmlspecialchars($titulo_livro_manual); ?></title>
    <link rel="stylesheet" href="estilo.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea.editor-pagina',
        plugins: 'lists link image table code help wordcount autoresize',
        toolbar: 'undo redo | blocks | bold italic underline | \
                  alignleft aligncenter alignright alignjustify | \
                  bullist numlist outdent indent | removeformat | help',
        height: 500,
        menubar: false,
      });
    </script>
    <style>
        .editor-manual-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }
        .controles-superiores-editor {
            width:100%;
            max-width:1000px;
            text-align:right;
            margin-bottom:15px;
            padding-right: 10px;
            box-sizing: border-box;
        }
        .livro-simulado {
            display: flex;
            justify-content: center;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            gap: 10px;
            align-items: stretch; /* Alterado para stretch */
            margin-bottom: 20px;
        }
        .seta-nav {
            font-size: 3em;
            color: #8c7b6a;
            text-decoration: none;
            padding: 10px;
            align-self: center;
            cursor: pointer;
            user-select: none;
        }
        .seta-nav:hover {
            color: #5a4a3b;
        }
        .pagina-simulada {
            background-color: #fffaf0;
            border: 1px solid #d2c8b6;
            box-shadow: 3px 3px 8px rgba(0,0,0,0.1);
            width: 45%;
            min-height: 580px;
            padding: 0; /* Removido padding para o textarea ocupar tudo */
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .pagina-simulada.direita-sozinha {
            margin-left: auto;
        }
        .pagina-simulada textarea.editor-pagina {
            width: 100%;
            flex-grow: 1; /* Faz o textarea ocupar o espaço */
            border: none; /* Removida borda do textarea */
            padding: 15px; /* Padding interno do textarea */
            box-sizing: border-box;
            font-family: 'Lora', serif;
            font-size: 1.05em;
            line-height: 1.6;
            background-color: transparent;
            resize: none;
        }
        .controles-editor-manual {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: 1000px;
            padding: 0 10px;
            box-sizing: border-box;
            margin-top:15px;
        }
    </style>
</head>
<body>
    <?php
        $primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Staff";
    ?>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php">Início</a>
            <div class="dropdown-categorias" tabindex="0">
                <a href="#" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">Categorias</a>
                <div class="dropdown-menu">
                    <a href="livros_biblioteca.php?admin_view=true">Gerenciar Acervo</a>
                    <a href="criar_livro.php" class="active">Criar Novo Livro</a>
                </div>
            </div>
            <a href="historia.php">História</a>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <span class="admin-indicator">(Admin)</span>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

    <div class="main-content-area editor-manual-container">
        <h2 style="text-align:center;">Escrevendo: <?php echo htmlspecialchars($titulo_livro_manual); ?></h2>

        <div class="controles-superiores-editor">
            <button type="button" id="btn-concluir-livro" class="btn btn-success">Concluir e Ver na Estante</button>
        </div>

        <div class="livro-simulado">
            <a href="#" class="seta-nav" id="seta-esquerda" style="display:none;">&lt;</a>
            <div class="pagina-simulada" id="pagina-esquerda" style="display:none;">
                <textarea id="editor_pagina_esquerda" class="editor-pagina" placeholder="Página esquerda..."></textarea>
            </div>
            <div class="pagina-simulada" id="pagina-direita">
                <textarea id="editor_pagina_direita" class="editor-pagina" placeholder="Comece a escrever a primeira página aqui..."></textarea>
            </div>
            <a href="#" class="seta-nav" id="seta-direita">&gt;</a>
        </div>

        <div class="controles-editor-manual">
             <div style="text-align: right;">
                <a href="livros_biblioteca.php?admin_view=true" class="btn btn-outline">Cancelar Edição (Sem Salvar Página Atual)</a>
            </div>
        </div>
    </div>

    <footer style="text-align: center; margin-top: 30px; padding:15px; background-color: #e4d9c5; color: #5a4a3b;">
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

    <script>
        const paginaEsquerdaDiv = document.getElementById('pagina-esquerda');
        const paginaDireitaDiv = document.getElementById('pagina-direita');
        const idTextAreaEsquerda = 'editor_pagina_esquerda';
        const idTextAreaDireita = 'editor_pagina_direita';
        const setaEsquerda = document.getElementById('seta-esquerda');
        const setaDireita = document.getElementById('seta-direita');
        const btnConcluirLivro = document.getElementById('btn-concluir-livro');

        let livroIdManualJS = <?php echo json_encode($livro_id_manual); ?>;
        let paginaNumeroAtualDireitaJS = <?php echo json_encode($pagina_numero_atual); ?>;

        function getEditorContent(editorId) {
            const editor = tinymce.get(editorId);
            return editor ? editor.getContent() : document.getElementById(editorId)?.value || "";
        }

        function setEditorContent(editorId, content) {
            const editor = tinymce.get(editorId);
            if (editor) {
                editor.setContent(content || "");
            } else {
                const textarea = document.getElementById(editorId);
                if (textarea) textarea.value = content || "";
            }
        }

        function ajustarLayoutPaginas() {
            if (paginaNumeroAtualDireitaJS === 1) {
                paginaEsquerdaDiv.style.display = 'none';
                setEditorContent(idTextAreaEsquerda, '');
                paginaDireitaDiv.classList.add('direita-sozinha');
                paginaDireitaDiv.classList.remove('par');
                setaEsquerda.style.display = 'none';
            } else {
                paginaEsquerdaDiv.style.display = 'flex';
                paginaDireitaDiv.classList.remove('direita-sozinha');
                setaEsquerda.style.display = 'inline-block';
            }
        }

        async function salvarPaginasVisiveis() {
            let paginasParaSalvar = [];

            // Sempre salva a página da direita
            paginasParaSalvar.push({
                numero: paginaNumeroAtualDireitaJS,
                conteudo: getEditorContent(idTextAreaDireita)
            });

            // Salva a página da esquerda se estiver visível (não é a primeira página)
            if (paginaNumeroAtualDireitaJS > 1) {
                paginasParaSalvar.push({
                    numero: paginaNumeroAtualDireitaJS - 1,
                    conteudo: getEditorContent(idTextAreaEsquerda)
                });
            }

            if (paginasParaSalvar.length === 0) return true;
            console.log("Salvando:", paginasParaSalvar);

            try {
                const formData = new FormData();
                formData.append('livro_id', livroIdManualJS);
                formData.append('paginas', JSON.stringify(paginasParaSalvar));

                const response = await fetch('processa_salvar_pagina_manual.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    console.log(result.message);
                    return true;
                } else {
                    alert('Erro ao salvar: ' + (result.message || 'Erro desconhecido.') + (result.details ? '\\nDetalhes: ' + JSON.stringify(result.details) : ''));
                    return false;
                }
            } catch (error) {
                console.error('Erro na requisição AJAX:', error);
                alert('Erro de comunicação ao tentar salvar as páginas.');
                return false;
            }
        }

        setaDireita.addEventListener('click', async function(e) {
            e.preventDefault();
            const salvo = await salvarPaginasVisiveis();
            if (salvo) {
                let proximaPaginaReferenciaDireita = paginaNumeroAtualDireitaJS === 1 ? 2 : paginaNumeroAtualDireitaJS + 2;
                window.location.href = `escrever_livro_manual.php?livro_id=${livroIdManualJS}&pagina=${proximaPaginaReferenciaDireita}`;
            }
        });

        setaEsquerda.addEventListener('click', async function(e) {
            e.preventDefault();
            if (paginaNumeroAtualDireitaJS <= 1) return;

            const salvo = await salvarPaginasVisiveis();
            if (salvo) {
                let paginaAnteriorReferenciaDireita = paginaNumeroAtualDireitaJS - 2;
                if (paginaAnteriorReferenciaDireita < 1) paginaAnteriorReferenciaDireita = 1;
                window.location.href = `escrever_livro_manual.php?livro_id=${livroIdManualJS}&pagina=${paginaAnteriorReferenciaDireita}`;
            }
        });

        if(btnConcluirLivro) {
            btnConcluirLivro.addEventListener('click', async function(e) {
                e.preventDefault();
                const salvo = await salvarPaginasVisiveis();
                if (salvo) {
                    window.location.href = 'livros_biblioteca.php?admin_view=true&msg_sucesso=Livro salvo e concluído!';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            setEditorContent(idTextAreaDireita, <?php echo json_encode($conteudo_pagina_direita); ?>);
            if (paginaNumeroAtualDireitaJS > 1) {
                 setEditorContent(idTextAreaEsquerda, <?php echo json_encode($conteudo_pagina_esquerda); ?>);
            }
            ajustarLayoutPaginas();
        });
    </script>
</body>
</html>
