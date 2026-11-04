<?php
// Carrega o CSS do Astra
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_assets' );
function astra_child_enqueue_assets() {
    wp_enqueue_style( 'astra-parent-style', get_template_directory_uri() . '/style.css' );

    // Enfileira o JavaScript do Assis
    wp_enqueue_script(
        'resolucao-questoes',
        get_stylesheet_directory_uri() . '/assets/js/resolucao-questoes.js',
        array(),
        '1.0.0',
        true
    );

    wp_enqueue_script(
        'validacao-cadastro-questao',
        get_stylesheet_directory_uri() . '/assets/js/validacao-cadastro-questao.js',
        array(),
        '1.0.0',
        true
    );
}

if ( file_exists( get_stylesheet_directory() . '/importar-questoes.php' ) ) {
    require_once get_stylesheet_directory() . '/importar-questoes.php';
    // Dispara a importação quando você acessar: /wp-admin/?rodar_importacao=1
    add_action( 'admin_init', function() {
        if ( isset( $_GET['rodar_importacao'] ) && current_user_can( 'manage_options' ) ) {
            $resultado = unilab_importar_questoes_seed();
            wp_die( 
                '<h2>Resultado da Importação:</h2>' . 
                '<p>' . esc_html( $resultado ) . '</p>' . 
                '<p><a href="' . admin_url( 'edit.php?post_type=questao' ) . '">👉 Ir para Lista de Questões</a></p>' 
            );
        }
    });
}

// ==============================================================================
// 3. CARREGA O ARQUIVO HTML EXTERNO E PROCESSA NO BANCO
// ==============================================================================
function unilab_form_cadastro_questao_shortcode() {
    // 1. Trava de segurança: só exibe se o aluno estiver logado
    if ( ! is_user_logged_in() ) {
        return '<div style="max-width: 760px; margin: 40px auto; padding: 25px; background: #fff3cd; color: #856404; border-radius: 8px; border: 1px solid #ffeeba; text-align: center; font-family: Arial, sans-serif;">
            <h3 style="margin-top:0;">Acesso Restrito a Estudantes</h3>
            <p>Você precisa estar conectado à sua conta para publicar uma questão.</p>
            <a href="/log-in/" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">Fazer Login</a>
        </div>';
    }

    $mensagem = '';

    // 2. Se o formulário foi enviado, salva a questão no banco de dados
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['assunto'] ) ) {
        $assunto = sanitize_text_field( $_POST['assunto'] );
        $enunciado = wp_kses_post( $_POST['enunciado'] );

        $post_id = wp_insert_post( array(
            'post_title'   => $assunto,
            'post_content' => $enunciado,
            'post_type'    => 'questao',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, 'assunto', $assunto );
            update_post_meta( $post_id, 'enunciado', $enunciado );
            update_post_meta( $post_id, 'alternativa_a', sanitize_text_field( $_POST['alternativa_a'] ) );
            update_post_meta( $post_id, 'alternativa_b', sanitize_text_field( $_POST['alternativa_b'] ) );
            update_post_meta( $post_id, 'alternativa_c', sanitize_text_field( $_POST['alternativa_c'] ) );
            update_post_meta( $post_id, 'alternativa_d', sanitize_text_field( $_POST['alternativa_d'] ) );
            update_post_meta( $post_id, 'alternativa_e', sanitize_text_field( $_POST['alternativa_e'] ) );
            update_post_meta( $post_id, 'resposta_correta', sanitize_text_field( $_POST['resposta_correta'] ) );
            update_post_meta( $post_id, 'explicacao', sanitize_textarea_field( $_POST['explicacao'] ) );
            update_post_meta( $post_id, 'link_externo', esc_url_raw( $_POST['link_externo'] ) );

            if ( ! empty( $_POST['categoria'] ) ) {
                wp_set_object_terms( $post_id, sanitize_text_field( $_POST['categoria'] ), 'categoria_disciplina', true );
            }

            $mensagem = '<div style="max-width: 760px; margin: 20px auto; background-color: #f0fdf4; color: #166534; padding: 16px; border: 1px solid #bbf7d0; border-radius: 6px; font-weight: bold; font-family: Arial, sans-serif;">
                🎉 Questão publicada com sucesso! <a href="' . get_permalink( $post_id ) . '" style="color: #0073aa; text-decoration: underline; margin-left: 10px;">👉 Ver Questão</a>
            </div>';
        }
    }

    ob_start();
    echo $mensagem;

    // 3. Inclui o arquivo HTML puro que você salvou na pasta
    $caminho_html = get_stylesheet_directory() . '/mock-cadastro-questao.html';
    if ( file_exists( $caminho_html ) ) {
        include $caminho_html;
    } else {
        echo '<p style="color:red; text-align:center;">Arquivo mock-cadastro-questao.html não encontrado na pasta do tema.</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'formulario_cadastro_questao', 'unilab_form_cadastro_questao_shortcode' );

// ==============================================================================
// 4. VITRINE / FEED DE QUESTÕES DA PÁGINA INICIAL (HOME)
// ==============================================================================
function unilab_feed_questoes_home_shortcode() {
    ob_start();

    // 1. Detecta o status do usuário
    $usuario_atual = wp_get_current_user();
    $esta_logado = is_user_logged_in();

    // 2. Encontra o link da página de cadastro automaticamente
    $paginas_cadastro = get_pages( array(
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'template-registro.php'
    ) );
    if ( ! empty( $paginas_cadastro ) ) {
        $link_criar_conta = get_permalink( $paginas_cadastro[0]->ID );
    } else {
        $link_criar_conta = home_url( '/cadastrar-estudante/' );
    }

    // 3. Busca as questões cadastradas no banco
    $args = array(
        'post_type'      => 'questao',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    if ( ! empty( $_GET['busca_q'] ) ) {
        $args['s'] = sanitize_text_field( $_GET['busca_q'] );
    }

    $query = new WP_Query( $args );
    ?>

    <style>
      .unilab-home-container {
        width: 100%;
        max-width: 1140px;
        margin: 0 auto;
        padding: 30px 24px;
        box-sizing: border-box;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: #1e293b;
      }
      .top-user-bar { 
        display: flex; 
        justify-content: flex-end; 
        align-items: center; 
        gap: 12px; 
        margin-bottom: 25px; 
        font-size: 14px; 
      }
      .top-user-bar a { text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; }
      .btn-login { background: #e2e8f0; color: #1e293b; }
      .btn-registro { background: #0073aa; color: #fff !important; }
      .btn-logout { background: #fee2e2; color: #991b1b; }

      .home-hero { 
        text-align: center; 
        padding: 45px 25px; 
        background: linear-gradient(135deg, #0073aa, #005177); 
        color: #fff; 
        border-radius: 12px; 
        margin-bottom: 35px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      }
      .home-hero h1 { font-size: 32px; margin: 0 0 12px 0; font-weight: 700; color: #fff; }
      .home-hero p { font-size: 16px; opacity: 0.9; max-width: 650px; margin: 0 auto 25px; line-height: 1.5; color: #f0f7fb; }
      
      .busca-wrapper { max-width: 550px; margin: 0 auto; display: flex; gap: 10px; }
      .busca-wrapper input { flex: 1; padding: 12px 16px; border-radius: 6px; border: none; font-size: 15px; outline: none; }
      .busca-wrapper button { background: #ffb900; color: #222; border: none; padding: 12px 22px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: 0.2s; }
      .busca-wrapper button:hover { background: #e0a300; }
      
      .grid-questoes { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
        gap: 24px; 
      }
      .card-preview { 
        background: #ffffff; 
        border: 1px solid #e2e8f0; 
        border-radius: 12px; 
        padding: 24px; 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.04); 
        transition: transform 0.2s, box-shadow 0.2s; 
      }
      .card-preview:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
      
      .card-tag { 
        display: inline-block; 
        background: #e0f2fe; 
        color: #0369a1; 
        font-size: 12px; 
        font-weight: 700; 
        padding: 4px 12px; 
        border-radius: 20px; 
        margin-bottom: 14px; 
        text-transform: uppercase;
        align-self: flex-start; 
      }
      .card-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 10px 0; line-height: 1.4; }
      .card-snippet { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; flex-grow: 1; }
      
      .btn-resolver { 
        display: block; 
        text-align: center; 
        background: #0073aa; 
        color: #ffffff !important; 
        text-decoration: none; 
        padding: 12px; 
        border-radius: 6px; 
        font-weight: 700; 
        font-size: 14px; 
        transition: background 0.2s; 
      }
      .btn-resolver:hover { background: #005177; }
    </style>

    <div class="unilab-home-container">
      <!-- 1. Barra no topo direito com status do Login -->
      <div class="top-user-bar">
        <?php if ( $esta_logado ) : ?>
          <span>👤 Conectado como: <strong><?php echo esc_html( $usuario_atual->display_name ); ?></strong></span>
          <a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn-logout">Sair</a>
        <?php else : ?>
          <a href="/log-in/" class="btn-login">Entrar</a>
          <!-- Link inteligente automático -->
          <a href="<?php echo esc_url( $link_criar_conta ); ?>" class="btn-registro">Criar Conta</a>
        <?php endif; ?>
      </div>

      <!-- 2. Seção de Boas-vindas e Busca -->
      <div class="home-hero">
        <h1>Banco de Questões Acadêmicas</h1>
        <p>Pratique seus conhecimentos, responda questões das disciplinas da UNILAB e compartilhe conteúdos com seus colegas.</p>
        
        <form method="GET" action="" class="busca-wrapper">
          <input type="text" name="busca_q" placeholder="Buscar por assunto ou palavra-chave..." value="<?php echo isset($_GET['busca_q']) ? esc_attr($_GET['busca_q']) : ''; ?>">
          <button type="submit">Buscar</button>
        </form>
      </div>

      <!-- 3. Botão para publicar questão -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="font-size: 22px; color: #1e293b; margin: 0;">Questões Disponíveis</h2>
        <a href="/cadastrar-questao/" style="background: #28a745; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 14px;">+ Publicar Nova Questão</a>
      </div>

      <!-- 4. Grade de Questões -->
      <div class="grid-questoes">
        <?php if ( $query->have_posts() ) : ?>
          <?php while ( $query->have_posts() ) : $query->the_post(); 
            $terms = get_the_terms( get_the_ID(), 'disciplina' );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                $terms = get_the_terms( get_the_ID(), 'categoria_disciplina' );
            }
            $materia = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Acadêmico';
            $enunciado_curto = wp_trim_words( get_field('enunciado'), 20, '...' );
          ?>
            <div class="card-preview">
              <div>
                <span class="card-tag"><?php echo esc_html( $materia ); ?></span>
                <h3 class="card-title"><?php the_title(); ?></h3>
                <p class="card-snippet"><?php echo esc_html( $enunciado_curto ); ?></p>
              </div>
              <a href="<?php the_permalink(); ?>" class="btn-resolver">Resolver Questão ➔</a>
            </div>
          <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
          <p style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">Nenhuma questão encontrada.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'feed_questoes_home', 'unilab_feed_questoes_home_shortcode' );


// ==============================================================================
// 5. SHORTCODE DA TELA DE LOGIN (RF02)
// ==============================================================================


function unilab_login_page_shortcode() {
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        return '<div style="max-width: 450px; margin: 40px auto; padding: 25px; background: #d4edda; color: #155724; border-radius: 8px; text-align: center;">
            <p>Você já está conectado como <strong>' . esc_html( $user->display_name ) . '</strong>.</p>
            <a href="' . home_url() . '" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Ir para a Página Inicial</a>
        </div>';
    }
    ob_start();
    ?>
    <div style="max-width: 420px; margin: 40px auto; padding: 30px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); font-family: 'Segoe UI', system-ui, sans-serif;">
      <h2 style="text-align: center; margin-bottom: 8px; color: #1e293b;">Acesso de Estudante</h2>
      <p style="text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px;">Entre com seu e-mail e senha acadêmicos.</p>
      
      <?php 
      wp_login_form( array(
          'redirect'       => home_url(),
          'label_username' => 'E-mail ou Usuário',
          'label_password' => 'Senha',
          'label_remember' => 'Lembrar de mim',
          'label_log_in'   => 'Acessar Conta',
          'remember'       => true
      ) ); 
      ?>
      <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #64748b;">
        Não tem uma conta? <a href="/registro/" style="color: #0073aa; font-weight: 600;">Cadastre-se aqui</a>
      </div>
    </div>
    <style>
      .login-form p { margin-bottom: 15px; }
      .login-form label { display: block; font-weight: 600; margin-bottom: 5px; color: #333; }
      .login-form input[type="text"], .login-form input[type="password"] { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
      .login-form input[type="submit"] { width: 100%; padding: 12px; background: #0073aa; color: #fff; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.2s; }
      .login-form input[type="submit"]:hover { background: #005177; }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode( 'pagina_de_login', 'unilab_login_page_shortcode' );


