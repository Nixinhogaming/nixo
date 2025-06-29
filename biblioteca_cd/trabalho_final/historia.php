<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado, caso contrário redirecionar para a página de login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Recuperar informações do usuário da sessão para exibição
$primeiro_nome = isset($_SESSION["primeiro_nome"]) ? htmlspecialchars($_SESSION["primeiro_nome"]) : "Utilizador";
$is_admin = isset($_SESSION["is_admin"]) ? (bool)$_SESSION["is_admin"] : false;

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>História - Biblioteca de Coimbra</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        /* Estilos da Navbar já estão em estilo.css */
        /* body já está estilizado globalmente em estilo.css */

        .main-content-area { /* Estilos globais de .main-content-area em estilo.css */
            max-width: 1000px; /* Específico para esta página, para melhor leitura de texto longo */
            text-align: left; /* Alinhar texto à esquerda */
        }

        .main-content-area h1 {
            font-family: 'Lora', serif;
            color: #5a4a3b;
            font-size: 2.8em;
            margin-bottom: 30px;
            text-align: center;
            margin-top: 0; /* .main-content-area já tem margem */
        }
         .main-content-area h1::after {
            content: "📜";
            font-size: 0.5em;
            color: #8c7b6a;
            margin-left: 10px;
            vertical-align: middle;
        }

        .main-content-area h2 {
            font-family: 'Lora', serif;
            color: #5a4a3b;
            font-size: 2em;
            margin-top: 40px;
            margin-bottom: 15px;
            border-bottom: 2px solid #d2c8b6;
            padding-bottom: 8px;
        }

        .main-content-area p {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.15em;
            line-height: 1.8;
            color: #3a3a3a;
            margin-bottom: 20px;
            text-align: justify;
        }

        .main-content-area .highlight-text {
            background-color: #fdfaf6;
            padding: 15px;
            border-left: 4px solid #8c7b6a;
            margin: 20px 0;
            font-style: italic;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 30px auto;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            width: 3px;
            height: 100%;
            background: #d2c8b6;
            transform: translateX(-50%);
        }
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            background: inherit;
            width: 50%;
            box-sizing: border-box; /* Adicionado para melhor cálculo de largura */
        }
        .timeline-item:nth-child(odd) {
            left: 0;
            padding-right: 60px;
            text-align: right;
        }
        .timeline-item:nth-child(even) {
            left: 50%;
            padding-left: 60px;
        }
        .timeline-item::after { /* Pontos na timeline */
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: #f4f1ea;
            border: 4px solid #8c7b6a;
            top: 20px;
            border-radius: 50%;
            z-index: 1;
        }
        .timeline-item:nth-child(odd)::after {
            right: -10px;
             transform: translateX(0%); /* Removido ajuste desnecessário se o right está correto */
        }
        .timeline-item:nth-child(even)::after {
            left: -10px;
             transform: translateX(0%); /* Removido ajuste desnecessário */
        }

        .timeline-content {
            padding: 15px 20px;
            background: #fdfaf6;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            border: 1px solid #e0d8c7;
        }
        .timeline-content h3 {
            font-family: 'Lora', serif;
            color: #7a6a56;
            margin-top: 0;
            font-size: 1.4em;
        }
        .timeline-content p {
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 0;
            text-align: left;
        }

        /* Footer style from estilo.css */
        footer {
            width: 100%;
            text-align: center;
            padding: 20px 0;
            background-color: #e4d9c5;
            color: #5a4a3b;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
            margin-top: 40px;
            border-top: 1px solid #d2c8b6;
        }

        /* Responsividade Específica da Página História */
        @media (max-width: 768px) {
            /* Navbar responsiva já tratada em estilo.css */
            .main-content-area {
                padding: 20px 15px; /* Menos padding lateral em telas pequenas */
            }
            .main-content-area h1 {
                font-size: 2.2em;
            }
            .main-content-area h2 {
                font-size: 1.7em;
            }
            .main-content-area p {
                font-size: 1.05em;
            }

            .timeline::before {
                left: 10px; /* Linha da timeline para a esquerda */
            }
            .timeline-item,
            .timeline-item:nth-child(odd),
            .timeline-item:nth-child(even) {
                width: 100%; /* Itens ocupam largura total */
                padding-left: 50px; /* Espaço para o ponto e conteúdo */
                padding-right: 10px;
                left: 0;
                text-align: left;
            }
            .timeline-item::after,
            .timeline-item:nth-child(odd)::after,
            .timeline-item:nth-child(even)::after {
                left: 0px; /* Ponto da timeline para a esquerda */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="main.php" class="navbar-brand">Biblioteca de Coimbra</a>
        <div class="navbar-links">
            <a href="main.php" <?php if (basename($_SERVER['PHP_SELF']) == 'main.php') echo 'class="active"'; ?>>Início</a>
            <?php if ($is_admin): ?>
                <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Estante dos Livros Encantados</a>
                <a href="criar_livro.php" <?php if (basename($_SERVER['PHP_SELF']) == 'criar_livro.php') echo 'class="active"'; ?>>Criar Novo Livro</a>
            <?php endif; ?>
            <a href="historia.php" <?php if (basename($_SERVER['PHP_SELF']) == 'historia.php') echo 'class="active"'; ?>>História</a>
            <?php if (!$is_admin): ?>
                <a href="livros_biblioteca.php" <?php if (basename($_SERVER['PHP_SELF']) == 'livros_biblioteca.php') echo 'class="active"'; ?>>Lugar Encantado dos Livros</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <span>Olá, <?php echo $primeiro_nome; ?>!</span>
            <?php if ($is_admin): ?>
                <span class="admin-indicator">(Admin)</span>
            <?php else: ?>
                <a href="configuracoes_perfil.php" class="navbar-config-link" title="Configurações do Perfil">&#9881;</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Terminar Sessão</a>
        </div>
    </nav>

    <div class="main-content-area">
        <h1>A História da Biblioteca de Coimbra</h1>

        <p>A Biblioteca de Coimbra, um tesouro de conhecimento e cultura, possui uma história rica e fascinante que se entrelaça com a própria história da cidade e da sua venerável Universidade. Desde as suas origens humildes até se tornar uma instituição de renome internacional, a biblioteca tem sido um farol para estudiosos, estudantes e amantes de livros por séculos.</p>

        <p class="highlight-text"><em>"Um livro é um sonho que você segura na mão." – Neil Gaiman. E esta biblioteca tem guardado milhões de sonhos ao longo dos tempos.</em></p>

        <h2>Fundação e Primeiros Anos</h2>
        <p>As raízes da Biblioteca de Coimbra remontam ao século XII, logo após a fundação da nacionalidade portuguesa. Inicialmente, o acervo era modesto, composto principalmente por manuscritos religiosos e obras clássicas, guardados em mosteiros e na então incipiente Universidade. A invenção da prensa por Gutenberg no século XV revolucionou o acesso ao conhecimento, e a biblioteca começou a expandir gradualmente o seu acervo com livros impressos.</p>

        <h2>A Era Joanina e o Esplendor Barroco</h2>
        <p>Um dos períodos mais marcantes na história da biblioteca é o reinado de D. João V, no século XVIII. Conhecido como o "Magnânimo", o rei investiu vastas somas na cultura e nas artes, e a Biblioteca Joanina, construída entre 1717 e 1728, é o testemunho mais eloquente desse período. Com a sua arquitetura barroca deslumbrante, talha dourada e frescos magníficos, a Biblioteca Joanina não é apenas um repositório de livros raros, mas uma obra de arte em si mesma. O seu acervo inclui obras de valor inestimável, abrangendo teologia, filosofia, direito, história e ciências.</p>
        <p>Curiosamente, a Biblioteca Joanina alberga uma colónia de morcegos que, durante a noite, ajudam a proteger os livros, alimentando-se dos insetos que poderiam danificar os preciosos volumes. Esta simbiose natural é um exemplo fascinante de conservação ao longo dos séculos.</p>

        <h2>Expansão e Modernização</h2>
        <p>Com o passar dos séculos, a biblioteca continuou a crescer. A extinção das ordens religiosas no século XIX resultou na incorporação de vastos acervos provenientes de conventos e mosteiros, enriquecendo significativamente as suas coleções. No século XX, a necessidade de espaço e de modernização das instalações levou à construção do Edifício Novo da Biblioteca Geral da Universidade de Coimbra, inaugurado em 1962. Este novo espaço permitiu uma melhor organização do acervo crescente e a oferta de serviços mais adequados às necessidades dos utilizadores.</p>

        <h2>A Biblioteca Hoje: Um Legado Vivo</h2>
        <p>Atualmente, a Biblioteca de Coimbra é um complexo que integra a histórica Biblioteca Joanina e o moderno Edifício Novo, juntamente com diversas bibliotecas departamentais. Continua a ser um centro vital de estudo e investigação, servindo a comunidade académica e o público em geral. O seu acervo, que ultrapassa um milhão de volumes, abrange todas as áreas do saber e inclui coleções raras, manuscritos, incunábulos e uma vasta coleção de periódicos.</p>
        <p>A biblioteca abraçou também a era digital, com projetos de digitalização do seu património e a disponibilização de recursos eletrónicos, garantindo que o conhecimento acumulado ao longo de séculos continue acessível às futuras gerações, onde quer que estejam.</p>

        <h2>Marcos Históricos</h2>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Século XII</h3>
                    <p>Origens da coleção bibliográfica ligada à Universidade e instituições eclesiásticas.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>1717-1728</h3>
                    <p>Construção da Biblioteca Joanina sob o patrocínio de D. João V. Um ícone do barroco português.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Século XIX</h3>
                    <p>Incorporação de importantes acervos após a extinção das ordens religiosas.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>1962</h3>
                    <p>Inauguração do Edifício Novo da Biblioteca Geral, expandindo a capacidade e modernizando os serviços.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h3>Século XXI</h3>
                    <p>Digitalização do acervo e expansão dos recursos digitais, afirmando-se como uma biblioteca do futuro com raízes no passado.</p>
                </div>
            </div>
        </div>

        <p>Visitar a Biblioteca de Coimbra é mais do que percorrer corredores repletos de livros; é fazer uma viagem no tempo, testemunhar a dedicação à preservação do conhecimento e inspirar-se na beleza que pode advir da união entre saber e arte.</p>

        <div style="text-align: center; margin-top: 40px; padding-bottom: 20px;">
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'});" class="btn btn-outline">Voltar ao Topo</button>
            <a href="main.php" class="btn btn-primary" style="margin-left: 15px;">Voltar à Página Inicial</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Biblioteca de Coimbra. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
