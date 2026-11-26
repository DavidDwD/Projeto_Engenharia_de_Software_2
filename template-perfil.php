<?php
/**
 * Template Name: Perfil do Estudante & Privacidade LGPD
 * Banco de Questões — UNILAB (RF04, RF06, RF07, RNF08, HU04, HU06, HU07)
 */

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/log-in/' ) );
    exit;
}

$usuario_atual = wp_get_current_user();
$user_id       = intval( $usuario_atual->ID );
$msg_sucesso   = '';
$msg_erro      = '';

if ( isset( $_GET['perfil_ok'] ) ) {
    if ( $_GET['perfil_ok'] === 'dados' ) {
        $msg_sucesso = 'Dados do perfil atualizados com sucesso!';
    } elseif ( $_GET['perfil_ok'] === 'senha' ) {
        $msg_sucesso = 'Sua senha foi alterada com segurança.';
    }
}

// ==============================================================================
// PROCESSAMENTO DE AÇÕES POST DO PERFIL (ANTES DE QUALQUER SAÍDA HTML)
// ==============================================================================
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['acao_perfil'] ) ) {
    if ( ! isset( $_POST['perfil_nonce'] ) || ! wp_verify_nonce( $_POST['perfil_nonce'], 'unilab_perfil_action' ) ) {
        $msg_erro = 'Sessão expirada ou token inválido. Recarregue a página e tente novamente.';
    } else {
        $acao = sanitize_text_field( wp_unslash( $_POST['acao_perfil'] ) );

        // 1. Exportar dados pessoais (LGPD / RNF08)
        if ( $acao === 'exportar_dados' ) {
            $questoes_ids = get_posts( array(
                'post_type'      => 'questao',
                'post_status'    => 'any',
                'author'         => $user_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ) );
            $questoes_export = array();
            foreach ( $questoes_ids as $qid ) {
                $questoes_export[] = array(
                    'id'        => $qid,
                    'titulo'    => get_the_title( $qid ),
                    'status'    => get_post_status( $qid ),
                    'data'      => get_the_date( 'Y-m-d H:i:s', $qid ),
                    'link'      => get_permalink( $qid ),
                );
            }

            $comentarios_user = get_comments( array(
                'user_id' => $user_id,
                'status'  => 'all',
            ) );
            $comentarios_export = array();
            foreach ( $comentarios_user as $c ) {
                $comentarios_export[] = array(
                    'id'         => intval( $c->comment_ID ),
                    'questao_id' => intval( $c->comment_post_ID ),
                    'conteudo'   => $c->comment_content,
                    'data'       => $c->comment_date,
                );
            }

            $favoritos = get_user_meta( $user_id, 'unilab_favoritos', true );
            $curtidas  = get_user_meta( $user_id, 'unilab_curtidas', true );

            $payload = array(
                'exportado_em' => current_time( 'c' ),
                'usuario'      => array(
                    'id'            => $user_id,
                    'login'         => $usuario_atual->user_login,
                    'nome'          => $usuario_atual->display_name,
                    'email'         => $usuario_atual->user_email,
                    'curso'         => get_user_meta( $user_id, 'unilab_curso', true ) ?: '',
                    'data_cadastro' => $usuario_atual->user_registered,
                    'papeis'        => array_values( (array) $usuario_atual->roles ),
                ),
                'questoes_publicadas' => $questoes_export,
                'comentarios'         => $comentarios_export,
                'favoritos_ids'       => is_array( $favoritos ) ? array_values( array_map( 'intval', $favoritos ) ) : array(),
                'curtidas_ids'        => is_array( $curtidas ) ? array_values( array_map( 'intval', $curtidas ) ) : array(),
            );

            nocache_headers();
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="meus-dados-unilab-' . $user_id . '.json"' );
            echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            exit;
        }

        // 2. Editar dados do perfil (RF07 / HU07)
        if ( $acao === 'editar_perfil' ) {
            $novo_nome  = trim( sanitize_text_field( wp_unslash( $_POST['nome_completo'] ?? '' ) ) );
            $novo_curso = trim( sanitize_text_field( wp_unslash( $_POST['curso'] ?? '' ) ) );
            $novo_email = trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) );

            if ( mb_strlen( $novo_nome ) < 3 ) {
                $msg_erro = 'O nome completo deve conter pelo menos 3 caracteres.';
            } elseif ( ! is_email( $novo_email ) ) {
                $msg_erro = 'Informe um endereço de e-mail válido.';
            } else {
                $dono_email = email_exists( $novo_email );
                if ( $dono_email && intval( $dono_email ) !== $user_id ) {
                    $msg_erro = 'Este e-mail já está em uso por outra conta.';
                } else {
                    $upd = wp_update_user( array(
                        'ID'           => $user_id,
                        'display_name' => $novo_nome,
                        'first_name'   => $novo_nome,
                        'user_email'   => $novo_email,
                    ) );
                    if ( is_wp_error( $upd ) ) {
                        $msg_erro = 'Não foi possível atualizar o perfil: ' . $upd->get_error_message();
                    } else {
                        update_user_meta( $user_id, 'unilab_curso', $novo_curso );
                        wp_safe_redirect( add_query_arg( 'perfil_ok', 'dados', home_url( '/perfil/' ) ) );
                        exit;
                    }
                }
            }
        }

        // 3. Alterar senha autenticado (RF04 / HU04 / RNF11)
        if ( $acao === 'alterar_senha' ) {
            $senha_atual   = wp_unslash( $_POST['senha_atual'] ?? '' );
            $nova_senha    = wp_unslash( $_POST['nova_senha'] ?? '' );
            $confirma_nova = wp_unslash( $_POST['confirma_nova_senha'] ?? '' );

            if ( empty( $senha_atual ) || empty( $nova_senha ) || empty( $confirma_nova ) ) {
                $msg_erro = 'Preencha a senha atual, a nova senha e a confirmação.';
            } elseif ( ! wp_check_password( $senha_atual, $usuario_atual->user_pass, $user_id ) ) {
                $msg_erro = 'A senha atual informada está incorreta.';
            } elseif ( strlen( $nova_senha ) < 8 ) {
                $msg_erro = 'A nova senha deve conter no mínimo 8 caracteres.';
            } elseif ( $nova_senha !== $confirma_nova ) {
                $msg_erro = 'A confirmação não coincide com a nova senha digitada.';
            } elseif ( $senha_atual === $nova_senha ) {
                $msg_erro = 'Escolha uma nova senha diferente da senha atual.';
            } else {
                wp_set_password( $nova_senha, $user_id );
                // Mantém o estudante autenticado com cookie novo antes de redirecionar (PRG mantém nonce válido)
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id );
                wp_safe_redirect( add_query_arg( 'perfil_ok', 'senha', home_url( '/perfil/' ) ) );
                exit;
            }
        }

        // 4. Excluir própria conta LGPD (RNF08 / RN06)
        if ( $acao === 'excluir_conta' ) {
            $senha_conf  = wp_unslash( $_POST['senha_confirmacao_exclusao'] ?? '' );
            $confirmacao = trim( strtoupper( sanitize_text_field( wp_unslash( $_POST['texto_confirmacao'] ?? '' ) ) ) );

            $eh_admin = user_can( $user_id, 'manage_options' );
            $total_admins = $eh_admin ? count( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) ) : 0;

            if ( $eh_admin && $total_admins <= 1 ) {
                $msg_erro = 'Não é possível excluir o único administrador ativo do sistema por aqui.';
            } elseif ( $confirmacao !== 'EXCLUIR' ) {
                $msg_erro = 'Digite EXCLUIR no campo de confirmação para apagar sua conta.';
            } elseif ( empty( $senha_conf ) || ! wp_check_password( $senha_conf, $usuario_atual->user_pass, $user_id ) ) {
                $msg_erro = 'Senha incorreta. A exclusão da conta foi cancelada por segurança.';
            } else {
                // Limpa referências e questões do usuário antes de remover a conta
                $questoes_autor = get_posts( array(
                    'post_type'      => 'questao',
                    'post_status'    => 'any',
                    'author'         => $user_id,
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                ) );
                foreach ( $questoes_autor as $qid ) {
                    if ( function_exists( 'unilab_limpar_referencias_questao' ) ) {
                        unilab_limpar_referencias_questao( $qid, 'conta_excluida' );
                    }
                    wp_delete_post( $qid, true );
                }

                // Remove comentários do usuário
                $comentarios_autor = get_comments( array(
                    'user_id' => $user_id,
                    'status'  => 'all',
                    'fields'  => 'ids',
                ) );
                foreach ( $comentarios_autor as $cid ) {
                    wp_delete_comment( $cid, true );
                }

                require_once ABSPATH . 'wp-admin/includes/user.php';
                $deletado = wp_delete_user( $user_id );
                if ( $deletado ) {
                    wp_logout();
                    wp_safe_redirect( add_query_arg( 'conta_excluida', '1', home_url( '/log-in/' ) ) );
                    exit;
                }
                $msg_erro = 'Não foi possível concluir a exclusão da conta. Tente novamente.';
            }
        }
    }
}

$curso_atual    = get_user_meta( $user_id, 'unilab_curso', true ) ?: '';
$data_cadastro  = $usuario_atual->user_registered ? wp_date( 'd/m/Y H:i', strtotime( $usuario_atual->user_registered ) ) : '-';
$favoritos_raw  = get_user_meta( $user_id, 'unilab_favoritos', true );
$total_favs     = is_array( $favoritos_raw ) ? count( $favoritos_raw ) : 0;
$total_questoes = intval( count_user_posts( $user_id, 'questao', true ) );
$total_coments  = intval( get_comments( array( 'user_id' => $user_id, 'count' => true, 'status' => 'approve' ) ) );
$info_nav       = function_exists( 'unilab_get_current_student_info' ) ? unilab_get_current_student_info() : array( 'iniciais' => 'UN', 'nome' => $usuario_atual->display_name );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meu Perfil e Privacidade — Banco de Questões UNILAB</title>
  <?php wp_head(); ?>
  <style>
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background-color: #f8fafc !important;
      font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important;
      color: #0f172a !important;
      -webkit-font-smoothing: antialiased;
      box-sizing: border-box !important;
    }
    *, *::before, *::after { box-sizing: border-box !important; }
    .site-header, .site-footer, .ast-footer-overlay,
    .ast-desktop-header, .ast-mobile-header,
    .entry-header, .entry-title { display: none !important; }

    .figma-page-shell { display: flex; flex-direction: column; min-height: 100vh; width: 100%; }
    .figma-top-bar {
      width: 100%; height: 35px; background-color: #102D5C;
      display: flex; align-items: center; padding: 0 48px;
      font-size: 11px; font-weight: 700; color: #b9cdf0;
      letter-spacing: 0.8px; text-transform: uppercase; flex-shrink: 0;
    }
    .figma-navbar {
      width: 100%; background: #ffffff; border-bottom: 1px solid #e2e8f0;
      padding: 14px 48px; display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 999; box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    body.admin-bar .figma-navbar { top: 32px; }
    @media (max-width: 782px) { body.admin-bar .figma-navbar { top: 46px; } }
    .figma-navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .figma-brand-icon {
      width: 34px; height: 34px; background: #102D5C; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; color: #ffffff;
    }
    .figma-brand-text { font-size: 16px; font-weight: 800; color: #102D5C; letter-spacing: -0.3px; }
    .figma-nav-links { display: flex; align-items: center; gap: 28px; list-style: none; margin: 0; padding: 0; }
    .figma-nav-links a { font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; padding-bottom: 4px; border-bottom: 2px solid transparent; }
    .figma-nav-links a:hover, .figma-nav-links a.active { color: #102D5C; border-bottom-color: #102D5C; font-weight: 700; }
    .figma-nav-user { display: flex; align-items: center; gap: 10px; }
    .figma-avatar-circle {
      width: 34px; height: 34px; border-radius: 50%; background: #e0f2fe; color: #102D5C;
      font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center;
    }
    .figma-user-name { font-size: 12px; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .figma-user-role { font-size: 11px; font-weight: 500; color: #64748b; }

    .perfil-main { flex: 1; padding: 36px 24px 64px; }
    .perfil-wrap { max-width: 920px; margin: 0 auto; }
    .perfil-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 20px; }
    .perfil-breadcrumb a { color: #2563eb; text-decoration: none; }
    .perfil-header { margin-bottom: 28px; }
    .perfil-kicker { font-size: 11px; font-weight: 800; color: #2563eb; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 6px; }
    .perfil-title { font-size: 28px; font-weight: 800; color: #102D5C; margin: 0 0 8px 0; letter-spacing: -0.5px; }
    .perfil-sub { font-size: 14px; color: #64748b; margin: 0; }

    .alert-box { padding: 14px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; margin-bottom: 22px; }
    .alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

    .perfil-resumo-card {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;
      padding: 26px 28px; margin-bottom: 24px;
      display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;
      box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }
    .perfil-identidade { display: flex; align-items: center; gap: 18px; }
    .perfil-avatar-lg {
      width: 64px; height: 64px; border-radius: 50%; background: #102D5C; color: #ffffff;
      font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .perfil-nome-lg { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
    .perfil-meta-txt { font-size: 13px; color: #64748b; line-height: 1.5; }
    .perfil-stats { display: flex; gap: 14px; flex-wrap: wrap; }
    .stat-pill {
      background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
      padding: 10px 16px; text-align: center; min-width: 96px;
    }
    .stat-num { font-size: 18px; font-weight: 800; color: #102D5C; line-height: 1.1; }
    .stat-lbl { font-size: 11px; font-weight: 600; color: #64748b; margin-top: 3px; }

    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
    .perfil-card {
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;
      padding: 26px 28px; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }
    .perfil-card-danger { border-color: #fecaca; background: #fffafa; }
    .perfil-card h2 { font-size: 17px; font-weight: 800; color: #102D5C; margin: 0 0 6px 0; }
    .perfil-card p.card-desc { font-size: 13px; color: #64748b; margin: 0 0 20px 0; line-height: 1.5; }
    .field-grp { margin-bottom: 16px; }
    .field-grp label { display: block; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
    .field-grp input {
      width: 100%; height: 42px; padding: 0 14px; border: 1.5px solid #cbd5e1;
      border-radius: 8px; font-size: 14px; color: #0f172a; background: #ffffff; outline: none;
    }
    .field-grp input:focus { border-color: #102D5C; box-shadow: 0 0 0 3px rgba(16,45,92,0.1); }
    .btn-primary, button.btn-primary {
      background: #102D5C !important; color: #ffffff !important; border: none !important; border-radius: 8px;
      padding: 11px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    }
    .btn-primary:hover, button.btn-primary:hover { background: #0b1f40 !important; }
    .btn-secondary, button.btn-secondary {
      background: #ffffff !important; color: #102D5C !important; border: 1.5px solid #102D5C !important; border-radius: 8px;
      padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer;
    }
    .btn-secondary:hover, button.btn-secondary:hover { background: #eff6ff !important; color: #102D5C !important; }
    .btn-danger, button.btn-danger {
      background: #dc2626 !important; color: #ffffff !important; border: none !important; border-radius: 8px;
      padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer;
    }
    .btn-danger:hover, button.btn-danger:hover { background: #b91c1c !important; }
    .lgpd-actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }

    .figma-footer {
      width: 100%; background: #ffffff; border-top: 1px solid #e2e8f0;
      padding: 20px 48px; text-align: center; font-size: 12px; color: #64748b;
    }

    @media (max-width: 860px) {
      .figma-top-bar { padding: 0 16px; font-size: 10px; }
      .figma-navbar { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
      .figma-nav-links { order: 3; width: 100%; justify-content: space-between; padding-top: 8px; border-top: 1px solid #f1f5f9; gap: 12px; }
      .perfil-main { padding: 20px 14px 40px; }
      .perfil-grid { grid-template-columns: 1fr; }
      .perfil-resumo-card, .perfil-card { padding: 20px 18px; }
      .figma-footer { padding: 16px; }
    }
  </style>
</head>
<body>
<div class="figma-page-shell">
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <?php if ( function_exists( 'unilab_render_figma_navbar' ) ) { unilab_render_figma_navbar( 'perfil' ); } ?>

  <main class="perfil-main">
    <div class="perfil-wrap">
      <nav class="perfil-breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
        <span>›</span>
        <span>Meu Perfil</span>
      </nav>

      <div class="perfil-header">
        <div class="perfil-kicker">Conta Acadêmica &amp; Privacidade (LGPD)</div>
        <h1 class="perfil-title">Meu Perfil</h1>
        <p class="perfil-sub">Gerencie seus dados cadastrais, altere sua senha ou exporte/exclua seus dados pessoais.</p>
      </div>

      <?php if ( ! empty( $msg_sucesso ) ) : ?>
        <div class="alert-box alert-ok" id="perfil-msg-sucesso"><?php echo esc_html( $msg_sucesso ); ?></div>
      <?php endif; ?>
      <?php if ( ! empty( $msg_erro ) ) : ?>
        <div class="alert-box alert-err" id="perfil-msg-erro"><?php echo esc_html( $msg_erro ); ?></div>
      <?php endif; ?>

      <!-- Resumo do Perfil (RF06 / HU06) -->
      <section class="perfil-resumo-card" aria-label="Resumo do perfil">
        <div class="perfil-identidade">
          <div class="perfil-avatar-lg"><?php echo esc_html( $info_nav['iniciais'] ); ?></div>
          <div>
            <h2 class="perfil-nome-lg" id="perfil-display-name"><?php echo esc_html( $usuario_atual->display_name ?: $usuario_atual->user_login ); ?></h2>
            <div class="perfil-meta-txt">
              <div><strong>E-mail:</strong> <span id="perfil-display-email"><?php echo esc_html( $usuario_atual->user_email ); ?></span></div>
              <div><strong>Curso:</strong> <span id="perfil-display-curso"><?php echo esc_html( $curso_atual ?: 'Não informado' ); ?></span></div>
              <div><strong>Membro desde:</strong> <?php echo esc_html( $data_cadastro ); ?> · <strong>Usuário:</strong> @<?php echo esc_html( $usuario_atual->user_login ); ?></div>
            </div>
          </div>
        </div>
        <div class="perfil-stats">
          <div class="stat-pill">
            <div class="stat-num"><?php echo $total_questoes; ?></div>
            <div class="stat-lbl">Questões</div>
          </div>
          <div class="stat-pill">
            <div class="stat-num"><?php echo $total_favs; ?></div>
            <div class="stat-lbl">Favoritos</div>
          </div>
          <div class="stat-pill">
            <div class="stat-num"><?php echo $total_coments; ?></div>
            <div class="stat-lbl">Comentários</div>
          </div>
        </div>
      </section>

      <div class="perfil-grid">
        <!-- Card Editar Dados (RF07 / HU07) -->
        <section class="perfil-card">
          <h2>Dados Cadastrais</h2>
          <p class="card-desc">Atualize seu nome de exibição, curso de graduação/pós-graduação ou e-mail institucional.</p>
          <form method="POST" action="" id="form-editar-perfil">
            <?php wp_nonce_field( 'unilab_perfil_action', 'perfil_nonce' ); ?>
            <input type="hidden" name="acao_perfil" value="editar_perfil">

            <div class="field-grp">
              <label for="perfil_nome">Nome completo *</label>
              <input type="text" id="perfil_nome" name="nome_completo" required minlength="3" value="<?php echo esc_attr( $usuario_atual->display_name ); ?>">
            </div>

            <div class="field-grp">
              <label for="perfil_curso">Curso</label>
              <input type="text" id="perfil_curso" name="curso" placeholder="Ex.: Engenharia de Computação" value="<?php echo esc_attr( $curso_atual ); ?>">
            </div>

            <div class="field-grp">
              <label for="perfil_email">E-mail institucional *</label>
              <input type="email" id="perfil_email" name="email" required value="<?php echo esc_attr( $usuario_atual->user_email ); ?>">
            </div>

            <button type="submit" class="btn-primary" id="btn-salvar-perfil">Salvar alterações</button>
          </form>
        </section>

        <!-- Card Alterar Senha (RF04 / HU04) -->
        <section class="perfil-card">
          <h2>Alterar Senha</h2>
          <p class="card-desc">Por segurança, informe sua senha atual antes de definir uma nova senha (mínimo 8 caracteres).</p>
          <form method="POST" action="" id="form-alterar-senha">
            <?php wp_nonce_field( 'unilab_perfil_action', 'perfil_nonce' ); ?>
            <input type="hidden" name="acao_perfil" value="alterar_senha">

            <div class="field-grp">
              <label for="senha_atual">Senha atual *</label>
              <input type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password">
            </div>

            <div class="field-grp">
              <label for="nova_senha">Nova senha *</label>
              <input type="password" id="nova_senha" name="nova_senha" required minlength="8" autocomplete="new-password">
            </div>

            <div class="field-grp">
              <label for="confirma_nova_senha">Confirmar nova senha *</label>
              <input type="password" id="confirma_nova_senha" name="confirma_nova_senha" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary" id="btn-alterar-senha">Atualizar senha</button>
          </form>
        </section>
      </div>

      <!-- Card LGPD: Exportar Dados e Excluir Conta (RNF08 / RN06) -->
      <section class="perfil-card perfil-card-danger">
        <h2 style="color:#991b1b;">Privacidade e Seus Dados (LGPD)</h2>
        <p class="card-desc">Você pode baixar uma cópia em JSON com todos os seus dados pessoais, questões, comentários, favoritos e curtidas ou encerrar definitivamente sua conta.</p>
        <div class="lgpd-actions">
          <form method="POST" action="" style="margin:0;">
            <?php wp_nonce_field( 'unilab_perfil_action', 'perfil_nonce' ); ?>
            <input type="hidden" name="acao_perfil" value="exportar_dados">
            <button type="submit" class="btn-secondary" id="btn-exportar-dados">Baixar meus dados (JSON)</button>
          </form>

          <button type="button" class="btn-danger" id="btn-abrir-modal-excluir-conta" onclick="document.getElementById('modalExcluirConta').style.display='flex'">
            Excluir minha conta
          </button>
        </div>
      </section>
    </div>
  </main>

  <!-- Modal Confirmação Exclusão de Conta (RNF08 / RN06) -->
  <div id="modalExcluirConta" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:14px; max-width:460px; width:100%; padding:26px; box-shadow:0 15px 35px rgba(0,0,0,0.22);">
      <h3 style="margin:0 0 8px 0; color:#991b1b; font-size:19px; font-weight:800;">Excluir Conta Permanentemente</h3>
      <p style="margin:0 0 16px 0; font-size:13px; color:#475569; line-height:1.5;">
        Esta ação é <strong>irreversível</strong>: sua conta, questões publicadas, comentários e preferências serão removidos definitivamente do Banco de Questões.
      </p>
      <form method="POST" action="" id="form-excluir-conta">
        <?php wp_nonce_field( 'unilab_perfil_action', 'perfil_nonce' ); ?>
        <input type="hidden" name="acao_perfil" value="excluir_conta">

        <div class="field-grp">
          <label for="texto_confirmacao">Digite <strong>EXCLUIR</strong> para confirmar *</label>
          <input type="text" id="texto_confirmacao" name="texto_confirmacao" required placeholder="EXCLUIR">
        </div>

        <div class="field-grp">
          <label for="senha_confirmacao_exclusao">Sua senha atual *</label>
          <input type="password" id="senha_confirmacao_exclusao" name="senha_confirmacao_exclusao" required autocomplete="current-password">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
          <button type="button" onclick="document.getElementById('modalExcluirConta').style.display='none'" style="background:#f1f5f9; border:none; padding:10px 16px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">Cancelar</button>
          <button type="submit" class="btn-danger" id="btn-confirmar-excluir-conta">Sim, excluir minha conta</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade · UNILAB.
  </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
