<?php
/**
 * Template Name: Recuperar Senha UNILAB (Figma Pixel Perfect - Dynamic Fullscreen)
 * Banco de Questões — UNILAB
 */

$mensagem_sucesso = '';
$mensagem_erro    = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['unilab_recuperar_senha_submit'] ) ) {
    $user_input = sanitize_text_field( $_POST['user_login'] ?? '' );

    if ( empty( $user_input ) ) {
        $mensagem_erro = 'Por favor, digite seu e-mail institucional ou usuário.';
    } else {
        // Tenta localizar usuário por e-mail ou login
        $user_data = is_email( $user_input ) ? get_user_by( 'email', $user_input ) : get_user_by( 'login', $user_input );

        if ( ! $user_data ) {
            $mensagem_erro = 'Nenhuma conta de estudante foi encontrada com as informações fornecidas.';
        } else {
            // Utiliza o fluxo seguro de redefinição de senha do WordPress
            $result = retrieve_password( $user_data->user_login );
            if ( is_wp_error( $result ) ) {
                $mensagem_erro = 'Não foi possível enviar o e-mail: ' . $result->get_error_message();
            } else {
                $mensagem_sucesso = 'As instruções de recuperação foram enviadas para o e-mail informado. Verifique sua caixa de entrada e spam.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar Senha — Banco de Questões UNILAB</title>
  <?php wp_head(); ?>
  <style>
    /* RESET GLOBAL E TELA CHEIA DINÂMICA */
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      min-height: 100vh !important;
      background-color: #ffffff !important;
      font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important;
      color: #0f172a !important;
      -webkit-font-smoothing: antialiased;
      box-sizing: border-box !important;
    }

    *, *::before, *::after {
      box-sizing: border-box !important;
    }

    .site-header, .site-footer, .ast-footer-overlay,
    .ast-desktop-header, .ast-mobile-header,
    .entry-header, .entry-title {
      display: none !important;
    }

    .unilab-recover-wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      width: 100%;
      background-color: #ffffff;
    }

    /* BARRA SUPERIOR INSTITUCIONAL FIGMA (35px) */
    .figma-top-bar {
      width: 100%;
      height: 35px;
      background-color: #102D5C;
      display: flex;
      align-items: center;
      padding: 0 32px;
      font-size: 11px;
      font-weight: 700;
      color: #b9cdf0;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      flex-shrink: 0;
    }

    /* SPLIT SCREEN PRINCIPAL FLUIDO */
    .figma-recover-split-main {
      display: flex;
      flex-direction: row;
      flex: 1;
      width: 100%;
      min-height: calc(100vh - 100px);
    }

    /* COLUNA ESQUERDA: INSTITUCIONAL AZUL MARINHO */
    .figma-recover-left {
      flex: 0 0 44%;
      min-width: 420px;
      max-width: 650px;
      background-color: #102D5C;
      color: #ffffff;
      padding: 60px 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }

    .figma-recover-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #ffffff;
    }

    .figma-brand-icon-box {
      width: 38px;
      height: 38px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
    }

    .figma-brand-name {
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.3px;
      color: #ffffff;
    }

    .figma-recover-hero-content {
      margin: 40px 0;
    }

    .figma-recover-tag {
      display: inline-block;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: #60a5fa;
      background: rgba(96, 165, 250, 0.15);
      padding: 4px 10px;
      border-radius: 4px;
      margin-bottom: 20px;
    }

    .figma-recover-title {
      font-size: 34px;
      font-weight: 900;
      line-height: 1.2;
      color: #ffffff;
      margin: 0 0 16px 0;
      letter-spacing: -0.8px;
    }

    .figma-recover-desc {
      font-size: 15px;
      line-height: 1.6;
      color: #cbd5e1;
      margin: 0 0 32px 0;
    }

    .figma-recover-left-footer {
      font-size: 12px;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 20px;
    }

    /* COLUNA DIREITA: FORMULÁRIO BRANCO */
    .figma-recover-right {
      flex: 1;
      background-color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 50px 40px;
    }

    .figma-recover-right-inner {
      width: 100%;
      max-width: 460px;
    }

    /* ÍCONE DE CHAVE DO FIGMA */
    .figma-icon-circle-badge {
      width: 56px;
      height: 56px;
      background-color: #EAF0FC;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #102D5C;
      margin-bottom: 20px;
    }

    .figma-form-title {
      font-size: 26px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
      margin: 0 0 8px 0;
    }

    .figma-form-subtitle {
      font-size: 14px;
      color: #64748b;
      margin: 0 0 24px 0;
      line-height: 1.5;
    }

    /* ALERTAS */
    .figma-alert-box {
      padding: 14px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      line-height: 1.45;
    }

    .figma-alert-error {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
    }

    .figma-alert-success {
      background-color: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
    }

    .figma-info-card {
      background-color: #EDF8F2;
      border: 1px solid #c7eedb;
      border-radius: 8px;
      padding: 16px;
      margin: 20px 0;
      font-size: 13px;
      color: #14532d;
      line-height: 1.5;
    }

    /* CAMPOS */
    .figma-field {
      margin-bottom: 16px;
    }

    .figma-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 6px;
    }

    .figma-input {
      width: 100%;
      height: 48px;
      padding: 0 16px;
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .figma-input:focus {
      border-color: #102D5C;
      box-shadow: 0 0 0 3px rgba(16, 45, 92, 0.12);
    }

    .btn-recover-submit {
      width: 100%;
      height: 48px;
      background-color: #102D5C;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background-color 0.15s ease;
    }

    .btn-recover-submit:hover {
      background-color: #0b1f3f;
    }

    .figma-bottom-back-link {
      text-align: center;
      margin-top: 24px;
      font-size: 14px;
    }

    .figma-bottom-back-link a {
      color: #102D5C;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .figma-bottom-back-link a:hover {
      text-decoration: underline;
    }

    .figma-recover-footer {
      width: 100%;
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 20px 32px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      flex-shrink: 0;
    }

    @media (max-width: 860px) {
      .figma-top-bar { padding: 0 16px; font-size: 10px; }
      .figma-recover-split-main { flex-direction: column; }
      .figma-recover-left { flex: none; width: 100%; min-width: 0; max-width: 100%; padding: 36px 20px; }
      .figma-recover-title { font-size: 24px; }
      .figma-recover-right { padding: 32px 20px 48px 20px; }
      .figma-form-title { font-size: 22px; }
    }
  </style>
</head>
<body>

<div class="unilab-recover-wrapper">

  <!-- Faixa Superior 35px -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <div class="figma-recover-split-main">

    <!-- Painel Esquerdo -->
    <div class="figma-recover-left">
      <div>
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-recover-brand">
          <div class="figma-brand-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
          </div>
          <span class="figma-brand-name">Banco de Questões</span>
        </a>

        <div class="figma-recover-hero-content">
          <span class="figma-recover-tag">Segurança e Acesso</span>
          <h1 class="figma-recover-title">Recuperação de Acesso Acadêmico</h1>
          <p class="figma-recover-desc">
            Esqueceu sua senha? Não se preocupe. Digite o e-mail cadastrado e enviaremos um link seguro para você redefinir seu acesso em poucos passos.
          </p>
        </div>
      </div>

      <div class="figma-recover-left-footer">
        Dúvidas? Entre em contato com a Diretoria de Tecnologia da Informação (DTI).
      </div>
    </div>

    <!-- Painel Direito -->
    <div class="figma-recover-right">
      <div class="figma-recover-right-inner">

        <!-- Ícone Figma -->
        <div class="figma-icon-circle-badge">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>

        <h2 class="figma-form-title">Recuperar senha</h2>
        <p class="figma-form-subtitle">Informe seu e-mail acadêmico para receber o link seguro de redefinição.</p>

        <?php if ( ! empty( $mensagem_erro ) ) : ?>
          <div class="figma-alert-box figma-alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo esc_html( $mensagem_erro ); ?></span>
          </div>
        <?php endif; ?>

        <?php if ( ! empty( $mensagem_sucesso ) ) : ?>
          <div class="figma-alert-box figma-alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span><?php echo esc_html( $mensagem_sucesso ); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <input type="hidden" name="unilab_recuperar_senha_submit" value="1">

          <div class="figma-field">
            <label class="figma-label" for="user_login">E-mail institucional ou Usuário *</label>
            <input type="text" id="user_login" name="user_login" class="figma-input" placeholder="ana.martins@aluno.unilab.edu.br" required autocomplete="username">
          </div>

          <div class="figma-info-card">
            💡 Verifique sua caixa de entrada e a pasta de spam. O link de redefinição possui validade temporária por motivos de segurança.
          </div>

          <button type="submit" class="btn-recover-submit">
            <span>Enviar link de recuperação</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </form>

        <div class="figma-bottom-back-link">
          <a href="<?php echo esc_url( home_url('/log-in/') ); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Voltar para o Login
          </a>
        </div>

      </div>
    </div>

  </div>

  <footer class="figma-recover-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

<?php wp_footer(); ?>
</body>
</html>