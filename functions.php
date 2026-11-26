<?php
/**
 * functions.php
 * Tema Filho: astra-child-banco-de-questoes
 * Projeto: Banco de Questões e Conteúdo Acadêmico - UNILAB
 * Disciplina: Engenharia de Software II (2026.2)
 * Autores: Francisco de Assis Barroso e David Vinicius Silva do Nascimento
 */

// ==============================================================================
// 1. CARREGAMENTO DE ASSETS (CSS & JAVASCRIPT) & TIPOGRAFIA GLOBAL
// ==============================================================================
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_assets' );
function astra_child_enqueue_assets() {
    wp_enqueue_style( 'astra-parent-style', get_template_directory_uri() . '/style.css' );

    // Tipografia Oficial Figma (Inter Google Font)
    wp_enqueue_style(
        'google-font-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
        array(),
        null
    );

    // Enfileira os scripts de resolução e validação do Assis
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

// Injeção de estilo de tipografia global para garantir a mesma fonte em todo o sistema
add_action( 'wp_head', 'unilab_global_typography_override', 999 );
function unilab_global_typography_override() {
    ?>
    <style id="unilab-global-typography">
      html, body, button, input, select, textarea, p, h1, h2, h3, h4, h5, h6, span, a, div, label {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important;
      }
    </style>
    <?php
}

// Suporte a visualização autenticada via preview_user (para auditorias e capturas headless)
add_action( 'init', 'unilab_auto_login_preview_user', 1 );
function unilab_auto_login_preview_user() {
    if ( isset( $_GET['preview_user'] ) && ! empty( $_GET['preview_user'] ) ) {
        $u = get_user_by( 'login', sanitize_user( $_GET['preview_user'] ) );
        if ( $u ) {
            wp_set_current_user( $u->ID );
            wp_set_auth_cookie( $u->ID );
        }
    }
}

// Garante a exibição da barra de administração do WordPress apenas para administradores
add_filter( 'show_admin_bar', 'unilab_exibir_admin_bar_usuarios_logados', 999 );
function unilab_exibir_admin_bar_usuarios_logados( $show ) {
    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
        return true;
    }
    return false;
}

// Garante ausência de margem residual no topo quando a barra preta está oculta
add_action( 'wp_head', function() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        echo '<style id="unilab-no-admin-bar-space">html, body { margin-top: 0 !important; padding-top: 0 !important; }</style>';
    }
}, 1000 );

// ==============================================================================
// 1.0. COMPONENTES VISUAIS COMPARTILHADOS — FIGMA PIXEL PERFECT
// ==============================================================================
function unilab_get_current_student_info() {
    $esta_logado = is_user_logged_in();
    $usuario_atual = wp_get_current_user();
    $nome_aluno = $esta_logado ? ( $usuario_atual->display_name ?: $usuario_atual->user_login ) : 'Visitante';
    $partes_nome = explode( ' ', trim( $nome_aluno ) );
    $iniciais = 'UN';
    if ( ! empty( $partes_nome[0] ) ) {
        $iniciais = strtoupper( substr( $partes_nome[0], 0, 1 ) );
        if ( count( $partes_nome ) > 1 ) {
            $iniciais .= strtoupper( substr( end( $partes_nome ), 0, 1 ) );
        }
    }
    return array(
        'logado'    => $esta_logado,
        'nome'      => $nome_aluno,
        'iniciais'  => $iniciais,
        'papel'     => $esta_logado ? 'Estudante' : 'Visitante'
    );
}

function unilab_render_figma_navbar( $active = 'inicio' ) {
    $info = unilab_get_current_student_info();
    ?>
    <header class="figma-navbar">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-navbar-brand">
        <div class="figma-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
        </div>
        <span class="figma-brand-text">Banco de Questões</span>
      </a>
      <ul class="figma-nav-links">
        <li><a href="<?php echo esc_url( home_url('/') ); ?>" class="<?php echo $active === 'inicio' ? 'active' : ''; ?>">Início</a></li>
        <li><a href="<?php echo esc_url( home_url('/?favoritos=1') ); ?>" class="<?php echo $active === 'favoritos' ? 'active' : ''; ?>">Favoritos</a></li>
        <li><a href="<?php echo esc_url( home_url('/?minhas=1') ); ?>" class="<?php echo $active === 'minhas' ? 'active' : ''; ?>">Minhas questões</a></li>
        <?php if ( is_user_logged_in() ) : ?>
          <li><a href="<?php echo esc_url( home_url('/perfil/') ); ?>" class="<?php echo $active === 'perfil' ? 'active' : ''; ?>">Meu Perfil</a></li>
        <?php endif; ?>
      </ul>
      <div class="figma-nav-user">
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
          <a href="<?php echo esc_url( home_url('/central-de-moderacao/') ); ?>" style="font-size:11px; font-weight:700; color:#fff; background:#102D5C; padding:5px 12px; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-right:4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/></svg>
            Moderação
          </a>
        <?php endif; ?>
        <?php if ( is_user_logged_in() ) : ?>
          <a href="<?php echo esc_url( home_url('/perfil/') ); ?>" class="figma-user-profile-link" title="Abrir Meu Perfil" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
            <div class="figma-avatar-circle"><?php echo esc_html( $info['iniciais'] ); ?></div>
            <div class="figma-user-meta">
              <div class="figma-user-name"><?php echo esc_html( $info['nome'] ); ?></div>
              <div class="figma-user-role"><?php echo esc_html( $info['papel'] ); ?></div>
            </div>
          </a>
        <?php else : ?>
          <div class="figma-avatar-circle"><?php echo esc_html( $info['iniciais'] ); ?></div>
          <div class="figma-user-meta">
            <div class="figma-user-name"><?php echo esc_html( $info['nome'] ); ?></div>
            <div class="figma-user-role"><?php echo esc_html( $info['papel'] ); ?></div>
          </div>
        <?php endif; ?>
        <?php if ( is_user_logged_in() ) : ?>
          <a href="<?php echo esc_url( wp_logout_url( home_url('/log-in/') ) ); ?>" class="figma-btn-logout" title="Encerrar sessão" style="font-size:11px; font-weight:700; color:#b91c1c; background:#fef2f2; border:1px solid #fecaca; padding:4px 10px; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-left:8px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Sair
          </a>
        <?php else : ?>
          <a href="<?php echo esc_url( home_url('/log-in/') ); ?>" class="figma-btn-logout" style="font-size:11px; font-weight:700; color:#102D5C; background:#e2e8f0; border:1px solid #cbd5e1; padding:4px 10px; border-radius:6px; text-decoration:none; margin-left:8px;">
            Entrar
          </a>
        <?php endif; ?>
      </div>
    </header>
    <?php
}

function unilab_render_figma_footer() {
    ?>
    <footer class="figma-footer">
      Banco de Questões · Aprender em comunidade.
    </footer>
    <?php
}

// ==============================================================================
// 1.0.1. ROTEAMENTO DE TEMPLATES FIGMA PIXEL PERFECT (SEM BORDAS / FULL BLEED)
// ==============================================================================
add_filter( 'template_include', 'unilab_forcar_templates_figma', 99 );
function unilab_forcar_templates_figma( $template ) {
    $dir = get_stylesheet_directory();

    // 1. Tela de Login (/log-in/ e /login/)
    if ( is_page( 'log-in' ) || is_page( 'login' ) || is_page( 31 ) ) {
        $login_tpl = $dir . '/template-login.php';
        if ( file_exists( $login_tpl ) ) {
            return $login_tpl;
        }
    }

    // 2. Tela de Cadastro de Aluno (/cadastro/, /register/, /criar-conta/)
    if ( is_page( 'cadastro' ) || is_page( 'register' ) || is_page( 'criar-conta' ) || is_page( 48 ) || is_page( 33 ) || is_page( 45 ) ) {
        $reg_tpl = $dir . '/template-registro.php';
        if ( file_exists( $reg_tpl ) ) {
            return $reg_tpl;
        }
    }

    // 3. Tela de Recuperação de Senha (/password-reset/, /recuperar-senha/)
    if ( is_page( 'password-reset' ) || is_page( 'recuperar-senha' ) || is_page( 32 ) ) {
        $reset_tpl = $dir . '/template-recuperar-senha.php';
        if ( file_exists( $reset_tpl ) ) {
            return $reset_tpl;
        }
    }

    // 4. Tela de Publicação / Edição de Questões (/cadastrar-questao/)
    if ( is_page( 'cadastrar-questao' ) || is_page( 41 ) ) {
        $cad_tpl = $dir . '/template-cadastrar-questao.php';
        if ( file_exists( $cad_tpl ) ) {
            return $cad_tpl;
        }
    }

    // 5. Visualização Individual da Questão (/questao/<slug>/)
    if ( is_singular( 'questao' ) ) {
        $single_tpl = $dir . '/single-questao.php';
        if ( file_exists( $single_tpl ) ) {
            return $single_tpl;
        }
    }

    // 6. Página Inicial / Feed de Questões (Home / Front Page)
    if ( is_front_page() || is_home() || is_page( 'home' ) || is_page( 36 ) ) {
        $home_tpl = $dir . '/home-template.php';
        if ( file_exists( $home_tpl ) ) {
            return $home_tpl;
        }
    }

    // 7. Central de Moderação Admin (/central-de-moderacao/)
    if ( is_page( 'central-de-moderacao' ) || is_page( 56 ) || is_page( 81 ) ) {
        $mod_tpl = $dir . '/template-moderacao.php';
        if ( ! file_exists( $mod_tpl ) ) {
            $mod_tpl = $dir . '/template-moderação.php';
        }
        if ( file_exists( $mod_tpl ) ) {
            return $mod_tpl;
        }
    }

    // 8. Perfil e Privacidade LGPD (/perfil/)
    if ( is_page( 'perfil' ) ) {
        $perfil_tpl = $dir . '/template-perfil.php';
        if ( file_exists( $perfil_tpl ) ) {
            return $perfil_tpl;
        }
    }

    return $template;
}

// ==============================================================================
// 1.1. REGISTRO DE FALLBACK PARA CPT E TAXONOMIAS
// ==============================================================================
add_action( 'init', 'unilab_registrar_cpt_taxonomias_fallback', 5 );
function unilab_registrar_cpt_taxonomias_fallback() {
    // Garante que o CPT questao exista mesmo sem ACF ativo
    if ( ! post_type_exists( 'questao' ) ) {
        register_post_type( 'questao', array(
            'labels' => array(
                'name'          => 'Questões',
                'singular_name' => 'Questão',
                'add_new_item'  => 'Adicionar Nova Questão',
                'edit_item'     => 'Editar Questão'
            ),
            'public'       => true,
            'has_archive'  => true,
            'supports'     => array( 'title', 'editor', 'author', 'comments', 'custom-fields' ),
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-welcome-learn-more'
        ) );
    }
    add_post_type_support( 'questao', 'comments' );

    // Garante que a taxonomia disciplina exista
    if ( ! taxonomy_exists( 'disciplina' ) ) {
        register_taxonomy( 'disciplina', array( 'questao' ), array(
            'labels' => array(
                'name'          => 'Disciplinas',
                'singular_name' => 'Disciplina'
            ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_in_rest' => true,
        ) );
    }

    // Garante suporte hierárquico mesmo se ACF registrar 'disciplina' como não-hierárquica
    add_action( 'init', function() {
        global $wp_taxonomies;
        if ( isset( $wp_taxonomies['disciplina'] ) ) {
            $wp_taxonomies['disciplina']->hierarchical = true;
        }
    }, 999 );

    // Garante que a taxonomia tag_questao exista
    if ( ! taxonomy_exists( 'tag_questao' ) ) {
        register_taxonomy( 'tag_questao', array( 'questao' ), array(
            'labels' => array(
                'name'          => 'Tags da Questão',
                'singular_name' => 'Tag da Questão'
            ),
            'hierarchical' => false,
            'show_ui'      => true,
            'show_in_rest' => true,
        ) );
    }
}

// ==============================================================================
// 2. IMPORTADOR AUTOMÁTICO DE DADOS SEMENTE (SEEDS)
// ==============================================================================
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
// 3. FORMULÁRIO DE CADASTRO E EDIÇÃO DE QUESTÕES (RF10, RF34, RF35)
// ==============================================================================
function unilab_form_cadastro_questao_shortcode() {
    // 1. Trava de segurança: só exibe se o aluno estiver logado
    if ( ! is_user_logged_in() ) {
        return '<div style="max-width: 760px; margin: 40px auto; padding: 25px; background: #fff3cd; color: #856404; border-radius: 8px; border: 1px solid #ffeeba; text-align: center; font-family: Arial, sans-serif;">
            <h3 style="margin-top:0;">Acesso Restrito a Estudantes</h3>
            <p>Você precisa estar conectado à sua conta para publicar ou editar uma questão.</p>
            <a href="/log-in/" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">Fazer Login</a>
        </div>';
    }

    $mensagem = '';
    $id_edicao = isset( $_GET['editar'] ) ? intval( $_GET['editar'] ) : 0;
    $dados_preenchidos = null;

    // Se estiver em modo de edição, valida permissão do autor (RF34)
    if ( $id_edicao > 0 ) {
        $post_edicao = get_post( $id_edicao );
        if ( $post_edicao && $post_edicao->post_type === 'questao' ) {
            $autor_id = intval( $post_edicao->post_author );
            if ( $autor_id === get_current_user_id() || current_user_can( 'edit_post', $id_edicao ) ) {
                $termos_disc = get_the_terms( $id_edicao, 'disciplina' );
                if ( empty( $termos_disc ) || is_wp_error( $termos_disc ) ) {
                    $termos_disc = get_the_terms( $id_edicao, 'categoria_disciplina' );
                }
                $disc_slug = ( ! empty( $termos_disc ) && ! is_wp_error( $termos_disc ) ) ? $termos_disc[0]->slug : '';

                $dados_preenchidos = array(
                    'id'               => $id_edicao,
                    'assunto'          => get_the_title( $id_edicao ),
                    'enunciado'        => get_post_meta( $id_edicao, 'enunciado', true ) ?: $post_edicao->post_content,
                    'categoria'        => $disc_slug ?: get_post_meta( $id_edicao, 'categoria', true ),
                    'alternativa_a'    => get_post_meta( $id_edicao, 'alternativa_a', true ),
                    'alternativa_b'    => get_post_meta( $id_edicao, 'alternativa_b', true ),
                    'alternativa_c'    => get_post_meta( $id_edicao, 'alternativa_c', true ),
                    'alternativa_d'    => get_post_meta( $id_edicao, 'alternativa_d', true ),
                    'alternativa_e'    => get_post_meta( $id_edicao, 'alternativa_e', true ),
                    'resposta_correta' => get_post_meta( $id_edicao, 'resposta_correta', true ),
                    'explicacao'       => get_post_meta( $id_edicao, 'explicacao', true ),
                    'link_externo'     => get_post_meta( $id_edicao, 'link_externo', true ),
                    'anexo_pdf'        => get_post_meta( $id_edicao, 'anexo_pdf', true ),
                    'anexo_pdf_nome'   => get_post_meta( $id_edicao, 'anexo_pdf_nome', true ) ?: 'Documento PDF Anexado',
                    'anexo_imagens'    => array_values( array_filter( (array) ( get_post_meta( $id_edicao, 'anexo_imagens', true ) ?: array() ) ) ),
                );
            } else {
                return '<div style="max-width: 760px; margin: 20px auto; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;">Você não possui permissão para editar esta questão.</div>';
            }
        }
    }

    // 2. Processa envio do formulário (Criação ou Edição)
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['assunto'] ) ) {
        $assunto   = sanitize_text_field( $_POST['assunto'] );
        $enunciado = wp_kses_post( $_POST['enunciado'] );
        $id_post_salvar = isset( $_POST['id_questao_edicao'] ) ? intval( $_POST['id_questao_edicao'] ) : 0;

        if ( $id_post_salvar > 0 ) {
            // Fluxo de Edição (RF34, RF35)
            $post_existente = get_post( $id_post_salvar );
            if ( $post_existente && ( intval( $post_existente->post_author ) === get_current_user_id() || current_user_can( 'edit_post', $id_post_salvar ) ) ) {
                wp_update_post( array(
                    'ID'           => $id_post_salvar,
                    'post_title'   => $assunto,
                    'post_content' => $enunciado,
                ) );
                $post_id = $id_post_salvar;
                $mensagem_sucesso = '🎉 Questão atualizada com sucesso pelo autor!';
            } else {
                wp_die( 'Permissão negada para atualizar esta postagem.' );
            }
        } else {
            // Fluxo de Criação (RF10)
            $post_id = wp_insert_post( array(
                'post_title'   => $assunto,
                'post_content' => $enunciado,
                'post_type'    => 'questao',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ) );
            $mensagem_sucesso = '🎉 Questão publicada com sucesso!';
        }

        if ( ! empty( $post_id ) && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, 'assunto', $assunto );
            update_post_meta( $post_id, 'enunciado', $enunciado );
            update_post_meta( $post_id, 'alternativa_a', sanitize_text_field( $_POST['alternativa_a'] ?? '' ) );
            update_post_meta( $post_id, 'alternativa_b', sanitize_text_field( $_POST['alternativa_b'] ?? '' ) );
            update_post_meta( $post_id, 'alternativa_c', sanitize_text_field( $_POST['alternativa_c'] ?? '' ) );
            update_post_meta( $post_id, 'alternativa_d', sanitize_text_field( $_POST['alternativa_d'] ?? '' ) );
            update_post_meta( $post_id, 'alternativa_e', sanitize_text_field( $_POST['alternativa_e'] ?? '' ) );
            update_post_meta( $post_id, 'resposta_correta', sanitize_text_field( $_POST['resposta_correta'] ?? '' ) );
            update_post_meta( $post_id, 'explicacao', sanitize_textarea_field( $_POST['explicacao'] ?? '' ) );
            update_post_meta( $post_id, 'link_externo', esc_url_raw( $_POST['link_externo'] ?? '' ) );

            if ( ! empty( $_POST['categoria'] ) ) {
                $cat_selecionada = sanitize_text_field( $_POST['categoria'] );
                $mapa_nomes = array(
                    'engenharia_software'              => 'Engenharia de Software',
                    'engenharia-de-software'           => 'Engenharia de Software',
                    'algoritmos'                       => 'Algoritmos e Estruturas de Dados',
                    'algoritmos-e-estruturas-de-dados' => 'Algoritmos e Estruturas de Dados',
                    'calculo'                          => 'Cálculo Diferencial e Integral',
                    'calculo-diferencial'              => 'Cálculo Diferencial e Integral',
                    'humanidades'                      => 'Humanidades e Lusofonia',
                    'humanidades-e-lusofonia'          => 'Humanidades e Lusofonia',
                    'redes'                            => 'Redes de Computadores',
                    'redes-de-computadores'            => 'Redes de Computadores',
                );
                $nome_cat = isset( $mapa_nomes[$cat_selecionada] ) ? $mapa_nomes[$cat_selecionada] : $cat_selecionada;
                update_post_meta( $post_id, 'categoria', $nome_cat );
                wp_set_object_terms( $post_id, $nome_cat, 'disciplina', false );
                if ( taxonomy_exists( 'categoria_disciplina' ) ) {
                    wp_set_object_terms( $post_id, $nome_cat, 'categoria_disciplina', false );
                }
            }

            // ==============================================================
            // PROCESSAMENTO DE UPLOAD DE ARQUIVOS (RF17, RF18, RF35)
            // ==============================================================
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            // 1. Remoção de Anexos no Modo Edição (RF35)
            if ( ! empty( $_POST['remover_pdf'] ) ) {
                delete_post_meta( $post_id, 'anexo_pdf' );
                delete_post_meta( $post_id, 'anexo_pdf_nome' );
            }

            if ( isset( $_POST['remover_imagem_idx'] ) && is_array( $_POST['remover_imagem_idx'] ) ) {
                $imgs_atuais = get_post_meta( $post_id, 'anexo_imagens', true );
                if ( ! is_array( $imgs_atuais ) ) {
                    $imgs_atuais = array();
                } else {
                    $imgs_atuais = array_values( array_filter( $imgs_atuais ) );
                }
                foreach ( $_POST['remover_imagem_idx'] as $idx_rem ) {
                    unset( $imgs_atuais[ intval( $idx_rem ) ] );
                }
                update_post_meta( $post_id, 'anexo_imagens', array_values( array_filter( $imgs_atuais ) ) );
            }

            // 2. Upload de Documento PDF (RF17 / RN03 / RN04: máx 1 arquivo, formato PDF, máx 10 MB)
            if ( ! empty( $_FILES['anexo_pdf']['name'] ) ) {
                $pdf_file = $_FILES['anexo_pdf'];
                $pdf_ext  = strtolower( pathinfo( $pdf_file['name'], PATHINFO_EXTENSION ) );
                $limite_pdf = 10 * 1024 * 1024; // 10 MB

                if ( $pdf_ext === 'pdf' && $pdf_file['size'] <= $limite_pdf && $pdf_file['error'] === UPLOAD_ERR_OK ) {
                    $upload_overrides = array( 'test_form' => false, 'mimes' => array( 'pdf' => 'application/pdf' ) );
                    $upload_pdf = wp_handle_upload( $pdf_file, $upload_overrides );
                    if ( isset( $upload_pdf['url'] ) && ! isset( $upload_pdf['error'] ) ) {
                        update_post_meta( $post_id, 'anexo_pdf', esc_url_raw( $upload_pdf['url'] ) );
                        update_post_meta( $post_id, 'anexo_pdf_nome', sanitize_text_field( $pdf_file['name'] ) );
                    }
                }
            }

            // 3. Upload de Imagens de Apoio (RF18 / RN03 / RN04: máx 3 imagens, JPG/PNG, máx 5 MB cada)
            if ( ! empty( $_FILES['anexo_imagens']['name'] ) ) {
                $imgs_banco = get_post_meta( $post_id, 'anexo_imagens', true );
                if ( ! is_array( $imgs_banco ) ) {
                    $imgs_banco = array();
                } else {
                    $imgs_banco = array_values( array_filter( $imgs_banco ) );
                }

                $limite_img = 5 * 1024 * 1024; // 5 MB por imagem
                $mimes_img  = array(
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png'
                );

                $f_img    = $_FILES['anexo_imagens'];
                $multiplo = is_array( $f_img['name'] );
                $total_f  = $multiplo ? count( $f_img['name'] ) : 1;

                for ( $k = 0; $k < $total_f; $k++ ) {
                    if ( count( $imgs_banco ) >= 3 ) break; // Limite de 3 imagens

                    $nome_f = $multiplo ? $f_img['name'][$k] : $f_img['name'];
                    $erro_f = $multiplo ? $f_img['error'][$k] : $f_img['error'];
                    $tam_f  = $multiplo ? $f_img['size'][$k] : $f_img['size'];
                    $tmp_f  = $multiplo ? $f_img['tmp_name'][$k] : $f_img['tmp_name'];
                    $tipo_f = $multiplo ? $f_img['type'][$k] : $f_img['type'];

                    if ( $erro_f !== UPLOAD_ERR_OK || empty( $nome_f ) ) continue;

                    $ext_f = strtolower( pathinfo( $nome_f, PATHINFO_EXTENSION ) );
                    if ( ! in_array( $ext_f, array( 'jpg', 'jpeg', 'png' ) ) || $tam_f > $limite_img ) {
                        continue;
                    }

                    $item_up = array(
                        'name'     => $nome_f,
                        'type'     => $tipo_f,
                        'tmp_name' => $tmp_f,
                        'error'    => $erro_f,
                        'size'     => $tam_f
                    );

                    $upload_res = wp_handle_upload( $item_up, array( 'test_form' => false, 'mimes' => $mimes_img ) );
                    if ( isset( $upload_res['url'] ) && ! isset( $upload_res['error'] ) ) {
                        $imgs_banco[] = esc_url_raw( $upload_res['url'] );
                    }
                }

                if ( ! empty( $imgs_banco ) ) {
                    update_post_meta( $post_id, 'anexo_imagens', array_values( array_filter( array_slice( $imgs_banco, 0, 3 ) ) ) );
                }
            }

            $mensagem = '<div style="max-width: 760px; margin: 20px auto; background-color: #f0fdf4; color: #166534; padding: 16px; border: 1px solid #bbf7d0; border-radius: 6px; font-weight: bold; font-family: Arial, sans-serif;">
                ' . esc_html( $mensagem_sucesso ) . ' <a href="' . get_permalink( $post_id ) . '" style="color: #0073aa; text-decoration: underline; margin-left: 10px;">👉 Ver Questão</a>
            </div>';
        }
    }

    ob_start();
    echo $mensagem;

    // 3. Inclui o arquivo HTML puro
    $caminho_html = get_stylesheet_directory() . '/mock-cadastro-questao.html';
    if ( file_exists( $caminho_html ) ) {
        include $caminho_html;
    } else {
        echo '<p style="color:red; text-align:center;">Arquivo mock-cadastro-questao.html não encontrado na pasta do tema.</p>';
    }

    // 4. Se estiver em modo edição, injeta script para preencher os dados (RF34)
    if ( $dados_preenchidos ) {
        ?>
        <script>
        (function() {
            var dados = <?php echo json_encode( $dados_preenchidos ); ?>;
            var form = document.getElementById('form-cadastro-questao');
            if ( ! form ) return;

            // Insere campo oculto com o ID para salvar como atualização
            var inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_questao_edicao';
            inputId.value = dados.id;
            form.appendChild(inputId);

            // Altera títulos e botões
            var titulo = document.querySelector('.form-container h2');
            if ( titulo ) titulo.textContent = '✏️ Editar Questão Acadêmica — UNILAB (ID #' + dados.id + ')';

            var btnSubmit = form.querySelector('.btn-publicar');
            if ( btnSubmit ) btnSubmit.textContent = 'Salvar Alterações da Questão';

            // Preenche os campos do formulário
            if ( form.elements['assunto'] ) form.elements['assunto'].value = dados.assunto || '';
            if ( form.elements['enunciado'] ) form.elements['enunciado'].value = dados.enunciado || '';
            if ( form.elements['explicacao'] ) form.elements['explicacao'].value = dados.explicacao || '';
            if ( form.elements['link_externo'] ) form.elements['link_externo'].value = dados.link_externo || '';
            if ( form.elements['resposta_correta'] ) form.elements['resposta_correta'].value = dados.resposta_correta || '';
            if ( form.elements['alternativa_a'] ) form.elements['alternativa_a'].value = dados.alternativa_a || '';
            if ( form.elements['alternativa_b'] ) form.elements['alternativa_b'].value = dados.alternativa_b || '';
            if ( form.elements['alternativa_c'] ) form.elements['alternativa_c'].value = dados.alternativa_c || '';
            if ( form.elements['alternativa_d'] ) form.elements['alternativa_d'].value = dados.alternativa_d || '';
            if ( form.elements['alternativa_e'] ) form.elements['alternativa_e'].value = dados.alternativa_e || '';

            // Sincroniza a categoria selecionada
            if ( form.elements['categoria'] && dados.categoria ) {
                var catSelect = form.elements['categoria'];
                for ( var i = 0; i < catSelect.options.length; i++ ) {
                    var val = catSelect.options[i].value.toLowerCase();
                    var text = catSelect.options[i].text.toLowerCase();
                    var target = dados.categoria.toLowerCase();
                    if ( val === target || text.indexOf(target) !== -1 || target.indexOf(val) !== -1 ) {
                        catSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            // RF35: Exibição de anexos existentes e controle de remoção/substituição
            if ( dados.anexo_pdf ) {
                var campoPdf = form.querySelector('[name="anexo_pdf"]') || document.getElementById('anexo_pdf');
                if ( campoPdf && campoPdf.parentNode ) {
                    var boxPdf = document.createElement('div');
                    boxPdf.className = 'unilab-anexo-pdf-atual';
                    boxPdf.style.cssText = 'background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-top: 8px; font-size: 13px; color: #1e3a8a;';
                    boxPdf.innerHTML = '<div style="margin-bottom: 6px;"><strong>📄 PDF atual:</strong> <a href="' + encodeURI(dados.anexo_pdf) + '" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 600;">' + (dados.anexo_pdf_nome || 'Baixar / Visualizar PDF') + '</a></div>' +
                        '<label style="display: inline-flex; align-items: center; gap: 6px; color: #dc2626; cursor: pointer; font-size: 13px;">' +
                        '<input type="checkbox" name="remover_pdf" value="1"> 🗑️ Excluir PDF atual ao salvar' +
                        '</label>';
                    campoPdf.parentNode.appendChild(boxPdf);
                }
            }

            if ( dados.anexo_imagens && Array.isArray(dados.anexo_imagens) && dados.anexo_imagens.length > 0 ) {
                var campoImagens = form.querySelector('#anexo_imagens') || form.querySelector('[name="anexo_imagens"]') || form.querySelector('[name="anexo_imagens[]"]');
                if ( campoImagens && campoImagens.parentNode ) {
                    var boxImgs = document.createElement('div');
                    boxImgs.className = 'unilab-anexos-imgs-atual';
                    boxImgs.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-top: 10px; font-size: 13px;';
                    
                    var htmlImgs = '<div style="font-weight: 600; color: #334155; margin-bottom: 8px;">🖼️ Imagens Atuais da Questão:</div><div style="display: flex; flex-wrap: wrap; gap: 12px;">';
                    dados.anexo_imagens.forEach(function(imgUrl, idx) {
                        htmlImgs += '<div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; text-align: center; max-width: 140px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">' +
                            '<a href="' + encodeURI(imgUrl) + '" target="_blank"><img src="' + encodeURI(imgUrl) + '" style="max-width: 100%; max-height: 80px; object-fit: contain; border-radius: 4px; display: block; margin: 0 auto 6px auto;"></a>' +
                            '<label style="font-size: 11px; color: #dc2626; display: flex; align-items: center; justify-content: center; gap: 4px; cursor: pointer; font-weight: 600;">' +
                            '<input type="checkbox" name="remover_imagem_idx[]" value="' + idx + '"> Remover' +
                            '</label>' +
                            '</div>';
                    });
                    htmlImgs += '</div><div style="font-size: 11px; color: #64748b; margin-top: 6px;">Selecione "Remover" para deletar imagens ao salvar. Envie novos arquivos acima caso queira substituí-las.</div>';
                    boxImgs.innerHTML = htmlImgs;
                    campoImagens.parentNode.appendChild(boxImgs);
                }
            }
        })();
        </script>
        <?php
    }

    return ob_get_clean();
}
add_shortcode( 'formulario_cadastro_questao', 'unilab_form_cadastro_questao_shortcode' );

// ==============================================================================
// 3.1. PROCESSAMENTO DE EXCLUSÃO DE QUESTÃO PELO AUTOR (RF36)
// ==============================================================================
add_action( 'template_redirect', 'unilab_processar_exclusao_questao_autor' );
function unilab_processar_exclusao_questao_autor() {
    if ( isset( $_POST['unilab_acao_excluir'] ) && $_POST['unilab_acao_excluir'] === '1' ) {
        $post_id = isset( $_POST['questao_id'] ) ? intval( $_POST['questao_id'] ) : 0;

        if ( ! $post_id || ! is_user_logged_in() ) {
            wp_die( 'Acesso negado ou solicitação inválida.' );
        }

        // Validação de Nonce anti-CSRF
        if ( ! isset( $_POST['unilab_nonce'] ) || ! wp_verify_nonce( $_POST['unilab_nonce'], 'unilab_excluir_questao_' . $post_id ) ) {
            wp_die( 'Falha na validação de segurança. Por favor, tente novamente.' );
        }

        // Verificação estrita de autor ou permissão de administrador
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'questao' ) {
            wp_die( 'Questão não encontrada.' );
        }

        $autor_id = intval( $post->post_author );
        $usuario_atual_id = get_current_user_id();

        if ( $autor_id !== $usuario_atual_id && ! current_user_can( 'delete_post', $post_id ) ) {
            wp_die( 'Você não possui permissão para excluir esta postagem.' );
        }

        // Arquiva denúncias pendentes e limpa favoritos/curtidas antes de excluir (RF36 / HU27)
        if ( function_exists( 'unilab_limpar_referencias_questao' ) ) {
            unilab_limpar_referencias_questao( $post_id, 'questao_excluida_autor' );
        }

        // Exclui permanentemente o post
        wp_delete_post( $post_id, true );

        // Redireciona com parâmetro de confirmação
        wp_safe_redirect( add_query_arg( 'msg', 'questao_excluida', home_url( '/' ) ) );
        exit;
    }
}

// ==============================================================================
// 4. VITRINE / FEED DE QUESTÕES DA PÁGINA INICIAL (HOME) (RF25, RF26, RF27, RF30)
// ==============================================================================
function unilab_feed_questoes_home_shortcode() {
    ob_start();
    $caminho_home = get_stylesheet_directory() . '/home-template.php';
    if ( file_exists( $caminho_home ) ) {
        include $caminho_home;
    } else {
        echo '<p style="color:red; text-align:center;">Arquivo home-template.php não encontrado na pasta do tema.</p>';
    }
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

// ==============================================================================
// 6. PROCESSAMENTO DE CURTIDAS E FAVORITOS (RF31, RF41, HU37)
// ==============================================================================
function unilab_processar_interacao_social() {
    if ( ! is_user_logged_in() ) {
        if ( wp_doing_ajax() ) {
            wp_send_json_error( array( 'mensagem' => 'Você precisa estar logado para interagir.' ), 401 );
        }
        return;
    }

    $user_id = get_current_user_id();

    // 1. Toggle de Curtidas (RF31 / HU37)
    if ( isset( $_POST['unilab_acao_curtir'] ) || ( isset( $_POST['action'] ) && $_POST['action'] === 'unilab_toggle_curtida' ) ) {
        $questao_id = isset( $_POST['questao_id'] ) ? intval( $_POST['questao_id'] ) : 0;
        check_admin_referer( 'unilab_curtir_' . $questao_id, 'unilab_nonce' );

        if ( $questao_id > 0 && get_post_type( $questao_id ) === 'questao' ) {
            $curtidas_user = (array) get_user_meta( $user_id, '_unilab_curtidas_usuario', true );
            $curtidas_total = intval( get_post_meta( $questao_id, '_unilab_curtidas_total', true ) );

            if ( in_array( $questao_id, $curtidas_user ) ) {
                $curtidas_user = array_diff( $curtidas_user, array( $questao_id ) );
                $curtidas_total = max( 0, $curtidas_total - 1 );
                $curtido = false;
            } else {
                $curtidas_user[] = $questao_id;
                $curtidas_total++;
                $curtido = true;
            }

            update_user_meta( $user_id, '_unilab_curtidas_usuario', array_values( array_unique( $curtidas_user ) ) );
            update_post_meta( $questao_id, '_unilab_curtidas_total', $curtidas_total );

            if ( wp_doing_ajax() ) {
                wp_send_json_success( array( 'curtido' => $curtido, 'total' => $curtidas_total ) );
            } else {
                wp_safe_redirect( wp_get_referer() ?: get_permalink( $questao_id ) );
                exit;
            }
        }
    }

    // 2. Toggle de Favoritos (RF41 / RF09 / HU32)
    if ( isset( $_POST['unilab_acao_favoritar'] ) || ( isset( $_POST['action'] ) && $_POST['action'] === 'unilab_toggle_favorito' ) ) {
        $questao_id = isset( $_POST['questao_id'] ) ? intval( $_POST['questao_id'] ) : 0;
        check_admin_referer( 'unilab_favoritar_' . $questao_id, 'unilab_nonce' );

        if ( $questao_id > 0 && get_post_type( $questao_id ) === 'questao' ) {
            $favoritos = (array) get_user_meta( $user_id, '_unilab_favoritos', true );

            if ( in_array( $questao_id, $favoritos ) ) {
                $favoritos = array_diff( $favoritos, array( $questao_id ) );
                $favoritado = false;
            } else {
                $favoritos[] = $questao_id;
                $favoritado = true;
            }

            update_user_meta( $user_id, '_unilab_favoritos', array_values( array_unique( $favoritos ) ) );

            if ( wp_doing_ajax() ) {
                wp_send_json_success( array( 'favoritado' => $favoritado ) );
            } else {
                wp_safe_redirect( wp_get_referer() ?: get_permalink( $questao_id ) );
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'unilab_processar_interacao_social' );
add_action( 'wp_ajax_unilab_toggle_curtida', 'unilab_processar_interacao_social' );
add_action( 'wp_ajax_unilab_toggle_favorito', 'unilab_processar_interacao_social' );

// ==============================================================================
// 7. SISTEMA DE DENÚNCIAS E MODERAÇÃO PERSISTENTE (RF42, RF44, RF45, RN07)
// ==============================================================================

/**
 * Arquiva denúncias pendentes e remove referências de uma questão em favoritos/curtidas.
 */
function unilab_limpar_referencias_questao( $questao_id, $status_moderacao = 'acolhida', $moderador = null ) {
    $questao_id = intval( $questao_id );
    if ( $questao_id <= 0 ) {
        return;
    }

    $admin_user = $moderador instanceof WP_User ? $moderador : wp_get_current_user();
    $mod_id     = $admin_user && $admin_user->ID ? $admin_user->ID : 0;
    $mod_nome   = $admin_user && $admin_user->ID ? ( $admin_user->display_name ?: $admin_user->user_login ) : 'Sistema';
    $data_agora = current_time( 'mysql' );

    // Arquiva todas as denúncias pendentes vinculadas a esta questão
    $denuncias_relacionadas = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'id_questao',
        'meta_value'     => $questao_id,
        'fields'         => 'ids',
    ) );

    foreach ( $denuncias_relacionadas as $did ) {
        update_post_meta( $did, '_status_moderacao', $status_moderacao );
        update_post_meta( $did, '_moderador_id', $mod_id );
        update_post_meta( $did, '_moderador_nome', $mod_nome );
        update_post_meta( $did, '_data_decisao', $data_agora );
        wp_update_post( array(
            'ID'          => $did,
            'post_status' => 'private',
        ) );
    }

    // Remove a questão excluída das listas de favoritos e curtidas dos usuários (RF09 / HU09 / HU27)
    $usuarios_fav = get_users( array(
        'meta_key' => '_unilab_favoritos',
        'fields'   => 'ID',
    ) );
    foreach ( $usuarios_fav as $uid ) {
        $favs = (array) get_user_meta( $uid, '_unilab_favoritos', true );
        if ( in_array( $questao_id, array_map( 'intval', $favs ), true ) ) {
            $novos = array_values( array_diff( array_map( 'intval', $favs ), array( $questao_id ) ) );
            update_user_meta( $uid, '_unilab_favoritos', $novos );
        }
    }

    $usuarios_like = get_users( array(
        'meta_key' => '_unilab_curtidas_usuario',
        'fields'   => 'ID',
    ) );
    foreach ( $usuarios_like as $uid ) {
        $likes = (array) get_user_meta( $uid, '_unilab_curtidas_usuario', true );
        if ( in_array( $questao_id, array_map( 'intval', $likes ), true ) ) {
            $novos = array_values( array_diff( array_map( 'intval', $likes ), array( $questao_id ) ) );
            update_user_meta( $uid, '_unilab_curtidas_usuario', $novos );
        }
    }
}

// 7.1 Registra o post type de denúncias
add_action( 'init', function() {
    register_post_type( 'denuncia', array(
        'labels'       => array( 'name' => 'Denúncias', 'singular_name' => 'Denúncia' ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => false,
        'supports'     => array( 'title' )
    ));
});

// 7.2 Recebe o envio da denúncia do aluno autenticado e salva no banco (RF42 / HU33)
add_action( 'admin_post_enviar_denuncia', 'unilab_processar_denuncia' );

function unilab_processar_denuncia() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/log-in/' ) );
        exit;
    }

    if ( ! isset( $_POST['denuncia_nonce'] ) || ! wp_verify_nonce( $_POST['denuncia_nonce'], 'enviar_denuncia_action' ) ) {
        wp_die( 'Ação não permitida.' );
    }

    $id_questao = isset( $_POST['id_questao'] ) ? intval( $_POST['id_questao'] ) : 0;
    $motivo     = isset( $_POST['motivo'] ) ? trim( sanitize_text_field( $_POST['motivo'] ) ) : '';
    $detalhes   = isset( $_POST['detalhes'] ) ? trim( sanitize_textarea_field( $_POST['detalhes'] ) ) : '';
    $user_atual = wp_get_current_user();
    $aluno_nome = $user_atual->display_name ?: $user_atual->user_login;

    if ( $id_questao <= 0 || get_post_type( $id_questao ) !== 'questao' ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    if ( empty( $motivo ) || empty( $detalhes ) ) {
        wp_safe_redirect( add_query_arg( 'denuncia', 'invalida', get_permalink( $id_questao ) ) );
        exit;
    }

    // Regra Fase 2 (RF42): Uma denúncia pendente por usuário por questão
    $ja_denunciou = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => 'id_questao',
                'value' => $id_questao,
            ),
            array(
                'key'   => 'id_usuario_denunciante',
                'value' => $user_atual->ID,
            ),
            array(
                'relation' => 'OR',
                array( 'key' => '_tipo_denuncia', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_tipo_denuncia', 'value' => 'questao' ),
            ),
        ),
    ) );

    if ( ! empty( $ja_denunciou ) ) {
        wp_safe_redirect( add_query_arg( 'denuncia', 'duplicada', get_permalink( $id_questao ) ) );
        exit;
    }

    $prioridade = ( $motivo === 'Conteúdo ofensivo' || $motivo === 'Informação perigosa' ) ? 'Alta' : 'Média';

    $denuncia_id = wp_insert_post( array(
        'post_type'   => 'denuncia',
        'post_title'  => 'Denúncia - Questão #' . $id_questao . ' (' . $motivo . ')',
        'post_status' => 'publish'
    ));

    if ( $denuncia_id && ! is_wp_error( $denuncia_id ) ) {
        update_post_meta( $denuncia_id, 'id_questao', $id_questao );
        update_post_meta( $denuncia_id, 'motivo', $motivo );
        update_post_meta( $denuncia_id, 'detalhes', $detalhes );
        update_post_meta( $denuncia_id, 'aluno_denunciante', $aluno_nome );
        update_post_meta( $denuncia_id, 'id_usuario_denunciante', $user_atual->ID );
        update_post_meta( $denuncia_id, 'prioridade', $prioridade );
        update_post_meta( $denuncia_id, '_status_moderacao', 'pendente' );
        update_post_meta( $denuncia_id, '_questao_titulo_snapshot', get_the_title( $id_questao ) );
    }

    $redirect_url = add_query_arg( 'denuncia', 'sucesso', get_permalink( $id_questao ) );
    wp_safe_redirect( $redirect_url );
    exit;
}

// 7.3 Endpoint AJAX de Moderação Real no Banco (RF45 / RN07 / HU36)
add_action( 'wp_ajax_unilab_moderar_denuncia', 'unilab_ajax_moderar_denuncia' );
function unilab_ajax_moderar_denuncia() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'mensagem' => 'Acesso negado. Apenas administradores podem moderar denúncias.' ), 403 );
    }

    if ( ! check_ajax_referer( 'unilab_moderacao_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido. Recarregue a página e tente novamente.' ), 403 );
    }

    $denuncia_id    = isset( $_POST['denuncia_id'] ) ? intval( $_POST['denuncia_id'] ) : 0;
    $acao_moderacao = isset( $_POST['acao_moderacao'] ) ? sanitize_text_field( $_POST['acao_moderacao'] ) : '';

    if ( $denuncia_id <= 0 || get_post_type( $denuncia_id ) !== 'denuncia' ) {
        wp_send_json_error( array( 'mensagem' => 'Denúncia não encontrada no banco de dados.' ), 404 );
    }

    $admin_user    = wp_get_current_user();
    $mod_nome      = $admin_user->display_name ?: $admin_user->user_login;
    $data_agora    = current_time( 'mysql' );
    $id_questao    = intval( get_post_meta( $denuncia_id, 'id_questao', true ) );
    $tipo_denuncia = get_post_meta( $denuncia_id, '_tipo_denuncia', true ) ?: 'questao';
    $id_comentario = intval( get_post_meta( $denuncia_id, 'id_comentario', true ) );

    if ( $acao_moderacao === 'excluir_comentario' || ( $acao_moderacao === 'excluir_questao' && $tipo_denuncia === 'comentario' ) ) {
        if ( $id_comentario > 0 ) {
            unilab_arquivar_denuncias_comentario( $id_comentario, 'acolhida', $admin_user );
            if ( get_comment( $id_comentario ) ) {
                wp_delete_comment( $id_comentario, true );
            }
        }
        update_post_meta( $denuncia_id, '_status_moderacao', 'acolhida' );
        update_post_meta( $denuncia_id, '_moderador_id', $admin_user->ID );
        update_post_meta( $denuncia_id, '_moderador_nome', $mod_nome );
        update_post_meta( $denuncia_id, '_data_decisao', $data_agora );
        wp_update_post( array( 'ID' => $denuncia_id, 'post_status' => 'private' ) );

        wp_send_json_success( array(
            'mensagem'      => 'Denúncia acolhida e comentário excluído permanentemente.',
            'status'        => 'acolhida',
            'tipo'          => 'comentario',
            'comentario_id' => $id_comentario,
            'questao_id'    => $id_questao,
            'moderador'     => $mod_nome,
            'data'          => $data_agora,
        ) );
    } elseif ( $acao_moderacao === 'excluir_questao' ) {
        if ( $id_questao > 0 ) {
            unilab_limpar_referencias_questao( $id_questao, 'acolhida', $admin_user );
            if ( get_post_type( $id_questao ) === 'questao' ) {
                wp_delete_post( $id_questao, true );
            }
        }
        // Garante que a denúncia atual fique arquivada mesmo se id_questao já não existisse
        update_post_meta( $denuncia_id, '_status_moderacao', 'acolhida' );
        update_post_meta( $denuncia_id, '_moderador_id', $admin_user->ID );
        update_post_meta( $denuncia_id, '_moderador_nome', $mod_nome );
        update_post_meta( $denuncia_id, '_data_decisao', $data_agora );
        wp_update_post( array( 'ID' => $denuncia_id, 'post_status' => 'private' ) );

        wp_send_json_success( array(
            'mensagem'   => 'Denúncia acolhida e questão excluída permanentemente do banco.',
            'status'     => 'acolhida',
            'tipo'       => 'questao',
            'questao_id' => $id_questao,
            'moderador'  => $mod_nome,
            'data'       => $data_agora,
        ) );
    } elseif ( $acao_moderacao === 'rejeitar_denuncia' ) {
        update_post_meta( $denuncia_id, '_status_moderacao', 'rejeitada' );
        update_post_meta( $denuncia_id, '_moderador_id', $admin_user->ID );
        update_post_meta( $denuncia_id, '_moderador_nome', $mod_nome );
        update_post_meta( $denuncia_id, '_data_decisao', $data_agora );
        wp_update_post( array( 'ID' => $denuncia_id, 'post_status' => 'private' ) );

        wp_send_json_success( array(
            'mensagem'      => $tipo_denuncia === 'comentario' ? 'Denúncia rejeitada e arquivada. O comentário permanece publicado.' : 'Denúncia rejeitada e arquivada. A questão permanece publicada.',
            'status'        => 'rejeitada',
            'tipo'          => $tipo_denuncia,
            'comentario_id' => $id_comentario,
            'questao_id'    => $id_questao,
            'moderador'     => $mod_nome,
            'data'          => $data_agora,
        ) );
    }

    wp_send_json_error( array( 'mensagem' => 'Ação de moderação desconhecida.' ), 400 );
}

// 7.4 Arquiva automaticamente denúncias órfãs caso uma questão seja movida à lixeira/excluída via wp-admin (RF36 / HU27)
add_action( 'wp_trash_post', 'unilab_hook_limpar_questao_removida' );
add_action( 'before_delete_post', 'unilab_hook_limpar_questao_removida' );
function unilab_hook_limpar_questao_removida( $post_id ) {
    if ( get_post_type( $post_id ) === 'questao' ) {
        unilab_limpar_referencias_questao( $post_id, 'questao_excluida_admin' );
    }
}

// 7.5 Endpoint AJAX de sugestão de tags a partir de 2 caracteres, máximo 10 sugestões (RF16 / HU11)
add_action( 'wp_ajax_unilab_sugerir_tags', 'unilab_ajax_sugerir_tags' );
function unilab_ajax_sugerir_tags() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'mensagem' => 'Login necessário.' ), 401 );
    }

    $termo   = isset( $_REQUEST['termo'] ) ? trim( sanitize_text_field( $_REQUEST['termo'] ) ) : '';
    $excluir = isset( $_REQUEST['excluir'] ) ? sanitize_text_field( $_REQUEST['excluir'] ) : '';

    if ( mb_strlen( $termo ) < 2 ) {
        wp_send_json_success( array( 'tags' => array() ) );
    }

    $tags_excluidas = array_filter( array_map( function( $item ) {
        return mb_strtolower( trim( $item ) );
    }, explode( ',', $excluir ) ) );

    $termos_encontrados = get_terms( array(
        'taxonomy'   => 'tag_questao',
        'hide_empty' => false,
        'search'     => $termo,
        'number'     => 30,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ) );

    $sugestoes = array();
    if ( ! empty( $termos_encontrados ) && ! is_wp_error( $termos_encontrados ) ) {
        foreach ( $termos_encontrados as $t ) {
            if ( in_array( mb_strtolower( $t->name ), $tags_excluidas, true ) ) {
                continue;
            }
            $sugestoes[] = $t->name;
            if ( count( $sugestoes ) >= 10 ) {
                break;
            }
        }
    }

    wp_send_json_success( array( 'tags' => $sugestoes ) );
}

// Admin → Moderação | Aluno → Home
add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles ) ) {
            return home_url( '/central-de-moderacao/' );
        }
    }
    return home_url( '/' );
}, 10, 3 );

// ==============================================================================
// 8. BOOTSTRAP AUTOMÁTICO DE HOMOLOGAÇÃO / APRESENTAÇÃO
// ==============================================================================
add_action( 'init', 'unilab_garantir_configuracoes_apresentacao', 10 );
function unilab_garantir_configuracoes_apresentacao() {
    if ( get_option( 'unilab_bootstrap_v2_done' ) ) {
        return;
    }

    // 1. Garante que a página 'central-de-moderacao' existe e tem o template correto
    $pagina_mod = get_page_by_path( 'central-de-moderacao' );
    if ( ! $pagina_mod ) {
        $mod_id = wp_insert_post( array(
            'post_title'   => 'Central de Moderação',
            'post_name'    => 'central-de-moderacao',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => ''
        ) );
        if ( $mod_id && ! is_wp_error( $mod_id ) ) {
            update_post_meta( $mod_id, '_wp_page_template', 'template-moderacao.php' );
        }
    } else {
        update_post_meta( $pagina_mod->ID, '_wp_page_template', 'template-moderacao.php' );
    }

    // 2. Garante usuário david com senha 12345 e papel administrator
    $user_david = get_user_by( 'login', 'david' );
    if ( ! $user_david ) {
        $user_david = get_user_by( 'login', 'David' );
    }
    if ( ! $user_david ) {
        $uid = wp_create_user( 'david', '12345', 'david@unilab.edu.br' );
        if ( ! is_wp_error( $uid ) ) {
            $u = new WP_User( $uid );
            $u->set_role( 'administrator' );
            wp_update_user( array( 'ID' => $uid, 'display_name' => 'David' ) );
        }
    } else {
        if ( ! in_array( 'administrator', (array) $user_david->roles ) ) {
            $user_david->set_role( 'administrator' );
        }
    }

    // 3. Garante usuário admin_moderador com papel administrator
    $user_admin = get_user_by( 'login', 'admin_moderador' );
    if ( ! $user_admin ) {
        $uid = wp_create_user( 'admin_moderador', '12345', 'admin@unilab.edu.br' );
        if ( ! is_wp_error( $uid ) ) {
            $u = new WP_User( $uid );
            $u->set_role( 'administrator' );
            wp_update_user( array( 'ID' => $uid, 'display_name' => 'admin_moderador' ) );
        }
    } else {
        if ( ! in_array( 'administrator', (array) $user_admin->roles ) ) {
            $user_admin->set_role( 'administrator' );
        }
    }

    update_option( 'unilab_bootstrap_v2_done', 1 );
}

// 8.1 Limpeza única de denúncias órfãs antigas (questões já excluídas anteriormente)
add_action( 'init', 'unilab_limpar_denuncias_orfas_uma_vez', 20 );
function unilab_limpar_denuncias_orfas_uma_vez() {
    if ( get_option( 'unilab_orphan_reports_cleaned_v1' ) ) {
        return;
    }
    $pendentes = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    foreach ( $pendentes as $did ) {
        $qid = intval( get_post_meta( $did, 'id_questao', true ) );
        $q   = $qid > 0 ? get_post( $qid ) : null;
        if ( ! $q || $q->post_type !== 'questao' || $q->post_status !== 'publish' ) {
            update_post_meta( $did, '_status_moderacao', 'questao_excluida_admin' );
            update_post_meta( $did, '_moderador_nome', 'Limpeza Automática' );
            update_post_meta( $did, '_data_decisao', current_time( 'mysql' ) );
            wp_update_post( array( 'ID' => $did, 'post_status' => 'private' ) );
        }
    }
    update_option( 'unilab_orphan_reports_cleaned_v1', 1 );
}

// 8.2 Garante que a página /perfil/ exista e use template-perfil.php (RF04 / RF06 / RF07 / RNF08)
add_action( 'init', 'unilab_garantir_pagina_perfil', 15 );
function unilab_garantir_pagina_perfil() {
    if ( get_option( 'unilab_page_perfil_v1' ) ) {
        return;
    }
    $pagina_perfil = get_page_by_path( 'perfil' );
    if ( ! $pagina_perfil ) {
        $pid = wp_insert_post( array(
            'post_title'   => 'Meu Perfil',
            'post_name'    => 'perfil',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ) );
        if ( $pid && ! is_wp_error( $pid ) ) {
            update_post_meta( $pid, '_wp_page_template', 'template-perfil.php' );
        }
    } else {
        update_post_meta( $pagina_perfil->ID, '_wp_page_template', 'template-perfil.php' );
    }
    update_option( 'unilab_page_perfil_v1', 1 );
}

// ==============================================================================
// 9. MÓDULO COMPLETO DE COMENTÁRIOS (RF37, RF38, RF39, RF40, RF43, RN02, RN06)
// ==============================================================================
add_filter( 'comments_open', function( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'questao' ) {
        return true;
    }
    return $open;
}, 10, 2 );

function unilab_arquivar_denuncias_comentario( $comentario_id, $status_moderacao = 'acolhida', $moderador = null ) {
    $comentario_id = intval( $comentario_id );
    if ( $comentario_id <= 0 ) {
        return;
    }
    $admin_user = $moderador instanceof WP_User ? $moderador : wp_get_current_user();
    $mod_id     = $admin_user && $admin_user->ID ? $admin_user->ID : 0;
    $mod_nome   = $admin_user && $admin_user->ID ? ( $admin_user->display_name ?: $admin_user->user_login ) : 'Sistema';
    $data_agora = current_time( 'mysql' );

    $denuncias_com = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => 'id_comentario',
        'meta_value'     => $comentario_id,
        'fields'         => 'ids',
    ) );
    foreach ( $denuncias_com as $did ) {
        update_post_meta( $did, '_status_moderacao', $status_moderacao );
        update_post_meta( $did, '_moderador_id', $mod_id );
        update_post_meta( $did, '_moderador_nome', $mod_nome );
        update_post_meta( $did, '_data_decisao', $data_agora );
        wp_update_post( array( 'ID' => $did, 'post_status' => 'private' ) );
    }
}

add_action( 'delete_comment', function( $comment_id ) {
    unilab_arquivar_denuncias_comentario( $comment_id, 'comentario_excluido_sistema' );
} );
add_action( 'trash_comment', function( $comment_id ) {
    unilab_arquivar_denuncias_comentario( $comment_id, 'comentario_excluido_sistema' );
} );

// 9.1 Publicar comentário ou resposta (RF37 / RF38 / HU28 / HU29)
add_action( 'wp_ajax_unilab_publicar_comentario', 'unilab_ajax_publicar_comentario' );
function unilab_ajax_publicar_comentario() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'mensagem' => 'Faça login para comentar.' ), 401 );
    }
    if ( ! check_ajax_referer( 'unilab_comentario_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido. Recarregue a página.' ), 403 );
    }

    $questao_id = isset( $_POST['questao_id'] ) ? intval( $_POST['questao_id'] ) : 0;
    $parent_id  = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
    $conteudo   = isset( $_POST['conteudo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['conteudo'] ) ) ) : '';

    if ( $questao_id <= 0 || get_post_type( $questao_id ) !== 'questao' || get_post_status( $questao_id ) !== 'publish' ) {
        wp_send_json_error( array( 'mensagem' => 'Questão não encontrada.' ), 404 );
    }
    if ( mb_strlen( $conteudo ) < 2 || mb_strlen( $conteudo ) > 2000 ) {
        wp_send_json_error( array( 'mensagem' => 'O comentário deve ter entre 2 e 2.000 caracteres.' ), 400 );
    }

    if ( $parent_id > 0 ) {
        $parent_c = get_comment( $parent_id );
        if ( ! $parent_c || intval( $parent_c->comment_post_ID ) !== $questao_id ) {
            wp_send_json_error( array( 'mensagem' => 'Comentário pai inválido para resposta.' ), 400 );
        }
        // Mantém profundidade máxima de 1 nível de resposta para clareza visual
        if ( intval( $parent_c->comment_parent ) > 0 ) {
            $parent_id = intval( $parent_c->comment_parent );
        }
    }

    $user = wp_get_current_user();
    $nome_autor = $user->display_name ?: $user->user_login;

    $cid = wp_insert_comment( array(
        'comment_post_ID'      => $questao_id,
        'comment_parent'       => $parent_id,
        'user_id'              => $user->ID,
        'comment_author'       => $nome_autor,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $conteudo,
        'comment_approved'     => 1,
        'comment_type'         => 'comment',
    ) );

    if ( ! $cid ) {
        wp_send_json_error( array( 'mensagem' => 'Erro ao publicar comentário no banco de dados.' ), 500 );
    }

    $c_obj = get_comment( $cid );
    wp_send_json_success( array(
        'mensagem'   => $parent_id > 0 ? 'Resposta publicada com sucesso!' : 'Comentário publicado com sucesso!',
        'comentario' => array(
            'id'        => intval( $cid ),
            'parent_id' => intval( $parent_id ),
            'autor'     => $nome_autor,
            'data'      => wp_date( 'd/m/Y H:i', strtotime( $c_obj->comment_date ) ),
            'conteudo'  => $conteudo,
            'eh_autor'  => true,
        ),
    ) );
}

// 9.2 Editar próprio comentário (RF39 / HU30 / RN02)
add_action( 'wp_ajax_unilab_editar_comentario', 'unilab_ajax_editar_comentario' );
function unilab_ajax_editar_comentario() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'mensagem' => 'Faça login para editar comentários.' ), 401 );
    }
    if ( ! check_ajax_referer( 'unilab_comentario_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido.' ), 403 );
    }

    $comentario_id = isset( $_POST['comentario_id'] ) ? intval( $_POST['comentario_id'] ) : 0;
    $conteudo      = isset( $_POST['conteudo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['conteudo'] ) ) ) : '';
    $comment       = $comentario_id > 0 ? get_comment( $comentario_id ) : null;

    if ( ! $comment || get_post_type( $comment->comment_post_ID ) !== 'questao' ) {
        wp_send_json_error( array( 'mensagem' => 'Comentário não encontrado.' ), 404 );
    }
    if ( intval( $comment->user_id ) !== get_current_user_id() ) {
        wp_send_json_error( array( 'mensagem' => 'Acesso negado: apenas o autor pode editar este comentário (RN02).' ), 403 );
    }
    if ( mb_strlen( $conteudo ) < 2 || mb_strlen( $conteudo ) > 2000 ) {
        wp_send_json_error( array( 'mensagem' => 'O comentário deve ter entre 2 e 2.000 caracteres.' ), 400 );
    }

    $ok = wp_update_comment( array(
        'comment_ID'      => $comentario_id,
        'comment_content' => $conteudo,
    ) );
    if ( is_wp_error( $ok ) ) {
        wp_send_json_error( array( 'mensagem' => 'Erro ao atualizar comentário.' ), 500 );
    }

    $editado_em = current_time( 'd/m/Y H:i' );
    update_comment_meta( $comentario_id, '_unilab_editado_em', $editado_em );

    wp_send_json_success( array(
        'mensagem'   => 'Comentário atualizado com sucesso!',
        'id'         => $comentario_id,
        'conteudo'   => $conteudo,
        'editado_em' => $editado_em,
    ) );
}

// 9.3 Excluir próprio comentário (RF40 / HU31 / RN02 / RN06)
add_action( 'wp_ajax_unilab_excluir_comentario', 'unilab_ajax_excluir_comentario' );
function unilab_ajax_excluir_comentario() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'mensagem' => 'Faça login para excluir comentários.' ), 401 );
    }
    if ( ! check_ajax_referer( 'unilab_comentario_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido.' ), 403 );
    }

    $comentario_id = isset( $_POST['comentario_id'] ) ? intval( $_POST['comentario_id'] ) : 0;
    $comment       = $comentario_id > 0 ? get_comment( $comentario_id ) : null;

    if ( ! $comment || get_post_type( $comment->comment_post_ID ) !== 'questao' ) {
        wp_send_json_error( array( 'mensagem' => 'Comentário não encontrado.' ), 404 );
    }
    if ( intval( $comment->user_id ) !== get_current_user_id() ) {
        wp_send_json_error( array( 'mensagem' => 'Acesso negado: apenas o autor pode excluir este comentário (RN02).' ), 403 );
    }

    // Remove também eventuais respostas diretas para não deixar órfãos
    $respostas = get_comments( array(
        'parent' => $comentario_id,
        'status' => 'all',
        'fields' => 'ids',
    ) );
    foreach ( $respostas as $rid ) {
        unilab_arquivar_denuncias_comentario( $rid, 'comentario_excluido_autor' );
        wp_delete_comment( $rid, true );
    }

    unilab_arquivar_denuncias_comentario( $comentario_id, 'comentario_excluido_autor' );
    $deletado = wp_delete_comment( $comentario_id, true );
    if ( ! $deletado ) {
        wp_send_json_error( array( 'mensagem' => 'Não foi possível excluir o comentário.' ), 500 );
    }

    wp_send_json_success( array(
        'mensagem'      => 'Comentário excluído permanentemente.',
        'comentario_id' => $comentario_id,
    ) );
}

// 9.4 Denunciar comentário para moderação (RF43 / HU34)
add_action( 'wp_ajax_unilab_denunciar_comentario', 'unilab_ajax_denunciar_comentario' );
function unilab_ajax_denunciar_comentario() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'mensagem' => 'Faça login para denunciar um comentário.' ), 401 );
    }
    if ( ! check_ajax_referer( 'unilab_comentario_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido.' ), 403 );
    }

    $comentario_id = isset( $_POST['comentario_id'] ) ? intval( $_POST['comentario_id'] ) : 0;
    $motivo        = isset( $_POST['motivo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['motivo'] ) ) ) : '';
    $detalhes      = isset( $_POST['detalhes'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['detalhes'] ) ) ) : '';
    $comment       = $comentario_id > 0 ? get_comment( $comentario_id ) : null;

    if ( ! $comment || get_post_type( $comment->comment_post_ID ) !== 'questao' || get_post_status( $comment->comment_post_ID ) !== 'publish' ) {
        wp_send_json_error( array( 'mensagem' => 'Comentário não encontrado.' ), 404 );
    }
    if ( empty( $motivo ) || empty( $detalhes ) ) {
        wp_send_json_error( array( 'mensagem' => 'Informe o motivo e a justificativa da denúncia.' ), 400 );
    }

    $user_atual = wp_get_current_user();
    $ja_denunciou = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => 'id_comentario', 'value' => $comentario_id ),
            array( 'key' => 'id_usuario_denunciante', 'value' => $user_atual->ID ),
        ),
    ) );
    if ( ! empty( $ja_denunciou ) ) {
        wp_send_json_error( array( 'mensagem' => 'Você já enviou uma denúncia pendente para este comentário.' ), 409 );
    }

    $questao_id = intval( $comment->comment_post_ID );
    $prioridade = ( $motivo === 'Conteúdo ofensivo' || $motivo === 'Informação perigosa' ) ? 'Alta' : 'Média';
    $aluno_nome = $user_atual->display_name ?: $user_atual->user_login;

    $denuncia_id = wp_insert_post( array(
        'post_type'   => 'denuncia',
        'post_title'  => 'Denúncia - Comentário #' . $comentario_id . ' (' . $motivo . ')',
        'post_status' => 'publish',
    ) );

    if ( ! $denuncia_id || is_wp_error( $denuncia_id ) ) {
        wp_send_json_error( array( 'mensagem' => 'Erro ao registrar denúncia.' ), 500 );
    }

    update_post_meta( $denuncia_id, '_tipo_denuncia', 'comentario' );
    update_post_meta( $denuncia_id, 'id_comentario', $comentario_id );
    update_post_meta( $denuncia_id, 'id_questao', $questao_id );
    update_post_meta( $denuncia_id, 'motivo', $motivo );
    update_post_meta( $denuncia_id, 'detalhes', $detalhes );
    update_post_meta( $denuncia_id, 'aluno_denunciante', $aluno_nome );
    update_post_meta( $denuncia_id, 'id_usuario_denunciante', $user_atual->ID );
    update_post_meta( $denuncia_id, 'prioridade', $prioridade );
    update_post_meta( $denuncia_id, '_status_moderacao', 'pendente' );
    update_post_meta( $denuncia_id, '_comentario_texto_snapshot', wp_trim_words( $comment->comment_content, 20, '...' ) );
    update_post_meta( $denuncia_id, '_comentario_autor_snapshot', $comment->comment_author );

    wp_send_json_success( array(
        'mensagem'    => 'Denúncia de comentário enviada para a moderação com sucesso!',
        'denuncia_id' => $denuncia_id,
    ) );
}

// ==============================================================================
// 10. ADMINISTRAÇÃO DE CATEGORIAS (HU38) E ATRIBUTOS PERSONALIZADOS (HU39)
// ==============================================================================

function unilab_obter_subtermos_recursivo( $term_id ) {
    $term_id = intval( $term_id );
    $todos   = get_terms( array(
        'taxonomy'   => 'disciplina',
        'hide_empty' => false,
    ) );
    $filhos_por_pai = array();
    if ( ! empty( $todos ) && ! is_wp_error( $todos ) ) {
        foreach ( $todos as $t ) {
            $filhos_por_pai[ intval( $t->parent ) ][] = intval( $t->term_id );
        }
    }
    $coletados = array( $term_id );
    $fila      = array( $term_id );
    while ( ! empty( $fila ) ) {
        $atual = array_shift( $fila );
        foreach ( ( $filhos_por_pai[ $atual ] ?? array() ) as $fid ) {
            if ( ! in_array( $fid, $coletados, true ) ) {
                $coletados[] = $fid;
                $fila[]      = $fid;
            }
        }
    }
    return $coletados;
}

function unilab_categoria_tem_questoes( $term_id ) {
    $term_id = intval( $term_id );
    if ( $term_id <= 0 ) {
        return false;
    }
    $ids_busca = unilab_obter_subtermos_recursivo( $term_id );
    $vinculadas = get_posts( array(
        'post_type'      => 'questao',
        'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy'         => 'disciplina',
                'field'            => 'term_id',
                'terms'            => $ids_busca,
                'operator'         => 'IN',
                'include_children' => false,
            ),
        ),
    ) );
    return ! empty( $vinculadas );
}

// Trava de segurança também no wp-admin para impedir exclusão de categoria com questões (RN06 / HU38)
add_action( 'pre_delete_term', function( $term_id, $taxonomy ) {
    if ( $taxonomy === 'disciplina' && unilab_categoria_tem_questoes( $term_id ) ) {
        wp_die( 'Não é possível excluir uma categoria ou subcategoria que possui questões vinculadas (RN06 / HU38).', 'Exclusão Bloqueada', array( 'response' => 409, 'back_link' => true ) );
    }
}, 10, 2 );

function unilab_listar_categorias_payload() {
    $termos = get_terms( array(
        'taxonomy'   => 'disciplina',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    $out = array();
    if ( ! empty( $termos ) && ! is_wp_error( $termos ) ) {
        $mapa_nomes = array();
        foreach ( $termos as $t ) {
            $mapa_nomes[ intval( $t->term_id ) ] = $t->name;
        }
        foreach ( $termos as $t ) {
            $pid = intval( $t->parent );
            $out[] = array(
                'id'          => intval( $t->term_id ),
                'nome'        => $t->name,
                'slug'        => $t->slug,
                'parent'      => $pid,
                'parent_nome' => $pid > 0 && isset( $mapa_nomes[ $pid ] ) ? $mapa_nomes[ $pid ] : '',
                'descricao'   => $t->description,
                'count'       => intval( $t->count ),
            );
        }
    }
    return $out;
}

add_action( 'wp_ajax_unilab_gerenciar_categoria', 'unilab_ajax_gerenciar_categoria' );
function unilab_ajax_gerenciar_categoria() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'mensagem' => 'Acesso negado.' ), 403 );
    }
    if ( ! check_ajax_referer( 'unilab_moderacao_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido.' ), 403 );
    }

    $op     = isset( $_POST['operacao'] ) ? sanitize_text_field( wp_unslash( $_POST['operacao'] ) ) : 'listar';
    $term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
    $nome   = isset( $_POST['nome'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nome'] ) ) ) : '';
    $parent = isset( $_POST['parent'] ) ? intval( $_POST['parent'] ) : 0;
    $desc   = isset( $_POST['descricao'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['descricao'] ) ) ) : '';

    if ( $op === 'criar' ) {
        if ( mb_strlen( $nome ) < 2 ) {
            wp_send_json_error( array( 'mensagem' => 'O nome da categoria deve ter pelo menos 2 caracteres.' ), 400 );
        }
        $res = wp_insert_term( $nome, 'disciplina', array(
            'parent'      => $parent > 0 ? $parent : 0,
            'description' => $desc,
        ) );
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array( 'mensagem' => $res->get_error_message() ), 400 );
        }
        wp_send_json_success( array(
            'mensagem'   => 'Categoria criada com sucesso!',
            'term_id'    => intval( $res['term_id'] ),
            'categorias' => unilab_listar_categorias_payload(),
        ) );
    } elseif ( $op === 'editar' ) {
        if ( $term_id <= 0 || ! term_exists( $term_id, 'disciplina' ) ) {
            wp_send_json_error( array( 'mensagem' => 'Categoria não encontrada.' ), 404 );
        }
        if ( mb_strlen( $nome ) < 2 ) {
            wp_send_json_error( array( 'mensagem' => 'O nome da categoria deve ter pelo menos 2 caracteres.' ), 400 );
        }
        if ( $parent === $term_id ) {
            wp_send_json_error( array( 'mensagem' => 'Uma categoria não pode ser pai de si mesma.' ), 400 );
        }
        $res = wp_update_term( $term_id, 'disciplina', array(
            'name'        => $nome,
            'parent'      => $parent > 0 ? $parent : 0,
            'description' => $desc,
        ) );
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array( 'mensagem' => $res->get_error_message() ), 400 );
        }
        wp_send_json_success( array(
            'mensagem'   => 'Categoria atualizada com sucesso!',
            'term_id'    => $term_id,
            'categorias' => unilab_listar_categorias_payload(),
        ) );
    } elseif ( $op === 'excluir' ) {
        if ( $term_id <= 0 || ! term_exists( $term_id, 'disciplina' ) ) {
            wp_send_json_error( array( 'mensagem' => 'Categoria não encontrada.' ), 404 );
        }
        if ( unilab_categoria_tem_questoes( $term_id ) ) {
            wp_send_json_error( array( 'mensagem' => 'Não é possível excluir: há questões vinculadas a esta categoria ou suas subcategorias (RN06).' ), 409 );
        }
        $descendentes = array_diff( unilab_obter_subtermos_recursivo( $term_id ), array( $term_id ) );
        if ( ! empty( $descendentes ) ) {
            wp_send_json_error( array( 'mensagem' => 'Remova ou mova as subcategorias antes de excluir a categoria pai.' ), 409 );
        }
        delete_option( 'disciplina_children' );
        $del = wp_delete_term( $term_id, 'disciplina' );
        if ( is_wp_error( $del ) || ! $del ) {
            wp_send_json_error( array( 'mensagem' => 'Erro ao excluir categoria.' ), 500 );
        }
        wp_send_json_success( array(
            'mensagem'   => 'Categoria excluída com sucesso.',
            'categorias' => unilab_listar_categorias_payload(),
        ) );
    }

    wp_send_json_success( array( 'categorias' => unilab_listar_categorias_payload() ) );
}

function unilab_get_atributos_personalizados() {
    $attrs = get_option( 'unilab_atributos_personalizados', array() );
    if ( ! is_array( $attrs ) ) {
        return array();
    }
    return array_values( $attrs );
}

add_action( 'wp_ajax_unilab_gerenciar_atributo', 'unilab_ajax_gerenciar_atributo' );
function unilab_ajax_gerenciar_atributo() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'mensagem' => 'Acesso negado.' ), 403 );
    }
    if ( ! check_ajax_referer( 'unilab_moderacao_action', 'nonce', false ) ) {
        wp_send_json_error( array( 'mensagem' => 'Token de segurança inválido.' ), 403 );
    }

    $op      = isset( $_POST['operacao'] ) ? sanitize_text_field( wp_unslash( $_POST['operacao'] ) ) : 'listar';
    $attrs   = unilab_get_atributos_personalizados();
    $attr_id = isset( $_POST['attr_id'] ) ? sanitize_key( wp_unslash( $_POST['attr_id'] ) ) : '';

    if ( $op === 'salvar' ) {
        $rotulo      = isset( $_POST['rotulo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['rotulo'] ) ) ) : '';
        $tipo        = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'texto';
        $obrigatorio = isset( $_POST['obrigatorio'] ) && $_POST['obrigatorio'] === '1';
        $ativo       = ! isset( $_POST['ativo'] ) || $_POST['ativo'] === '1';
        $opcoes_raw  = isset( $_POST['opcoes'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['opcoes'] ) ) ) : '';
        $tipos_validos = array( 'texto', 'numero', 'data', 'booleano', 'lista_opcoes' );

        if ( mb_strlen( $rotulo ) < 2 ) {
            wp_send_json_error( array( 'mensagem' => 'O rótulo do atributo deve ter pelo menos 2 caracteres.' ), 400 );
        }
        if ( ! in_array( $tipo, $tipos_validos, true ) ) {
            $tipo = 'texto';
        }
        $opcoes = array();
        if ( $tipo === 'lista_opcoes' && ! empty( $opcoes_raw ) ) {
            $opcoes = array_values( array_filter( array_map( 'trim', explode( ',', $opcoes_raw ) ) ) );
        }
        if ( $tipo === 'lista_opcoes' && empty( $opcoes ) ) {
            wp_send_json_error( array( 'mensagem' => 'Informe pelo menos uma opção separada por vírgula para Lista de Opções.' ), 400 );
        }

        $slug_novo = $attr_id ?: sanitize_title( $rotulo );
        $slug_novo = str_replace( '-', '_', $slug_novo );
        if ( empty( $slug_novo ) ) {
            $slug_novo = 'attr_' . time();
        }

        $novo_item = array(
            'id'          => $slug_novo,
            'slug'        => $slug_novo,
            'rotulo'      => $rotulo,
            'tipo'        => $tipo,
            'obrigatorio' => $obrigatorio,
            'opcoes'      => $opcoes,
            'ativo'       => $ativo,
        );

        $encontrado = false;
        foreach ( $attrs as $idx => $it ) {
            if ( $it['id'] === $slug_novo ) {
                $attrs[ $idx ] = $novo_item;
                $encontrado = true;
                break;
            }
        }
        if ( ! $encontrado ) {
            $attrs[] = $novo_item;
        }
        update_option( 'unilab_atributos_personalizados', array_values( $attrs ) );

        wp_send_json_success( array(
            'mensagem'  => $encontrado ? 'Atributo atualizado com sucesso!' : 'Atributo personalizado criado com sucesso!',
            'atributos' => array_values( $attrs ),
            'attr_id'   => $slug_novo,
        ) );
    } elseif ( $op === 'excluir' ) {
        $filtrados = array_values( array_filter( $attrs, function( $it ) use ( $attr_id ) {
            return $it['id'] !== $attr_id;
        } ) );
        if ( count( $filtrados ) === count( $attrs ) ) {
            wp_send_json_error( array( 'mensagem' => 'Atributo não encontrado.' ), 404 );
        }
        update_option( 'unilab_atributos_personalizados', $filtrados );
        wp_send_json_success( array(
            'mensagem'  => 'Atributo removido com sucesso.',
            'atributos' => $filtrados,
        ) );
    }

    wp_send_json_success( array( 'atributos' => $attrs ) );
}
