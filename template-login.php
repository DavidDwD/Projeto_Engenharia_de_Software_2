<?php
/**
 * Template Name: Login Aluno UNILAB (Figma Pixel Perfect - Dynamic Fullscreen)
 * Banco de Questões — UNILAB
 */

// Se já estiver logado, redireciona para a home
if ( is_user_logged_in() && ! isset( $_GET['preview_user'] ) ) {
    wp_redirect( home_url('/') );
    exit;
}

$erro_login = '';

// Processamento de login nativo do WordPress
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['unilab_login_submit'] ) ) {
    $login_input = sanitize_text_field( $_POST['log'] ?? '' );
    $senha_input = $_POST['pwd'] ?? '';
    $lembrar     = isset( $_POST['rememberme'] );

    if ( empty( $login_input ) || empty( $senha_input ) ) {
        $erro_login = 'Por favor, informe seu e-mail institucional e senha.';
    } else {
        // Suporte tanto a username quanto a e-mail institucional
        if ( is_email( $login_input ) ) {
            $user = get_user_by( 'email', $login_input );
            if ( $user ) {
                $login_input = $user->user_login;
            }
        }

        $creds = array(
            'user_login'    => $login_input,
            'user_password' => $senha_input,
            'remember'      => $lembrar
        );

        $user_signon = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user_signon ) ) {
            $erro_login = 'E-mail/usuário ou senha incorretos. Por favor, tente novamente.';
        } else {
            wp_set_current_user( $user_signon->ID );
            wp_set_auth_cookie( $user_signon->ID, $lembrar );
            wp_redirect( home_url('/') );
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acesso de Estudante — Banco de Questões UNILAB</title>
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

    .unilab-login-wrapper {
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
    .figma-login-split-main {
      display: flex;
      flex-direction: row;
      flex: 1;
      width: 100%;
      min-height: calc(100vh - 100px);
    }

    /* COLUNA ESQUERDA: INSTITUCIONAL AZUL MARINHO (#102D5C) */
    .figma-login-left {
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

    .figma-login-brand {
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

    .figma-login-hero-content {
      margin: 40px 0;
    }

    .figma-hero-tag {
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

    .figma-hero-title {
      font-size: 36px;
      font-weight: 900;
      line-height: 1.2;
      color: #ffffff;
      margin: 0 0 16px 0;
      letter-spacing: -0.8px;
    }

    .figma-hero-desc {
      font-size: 15px;
      line-height: 1.6;
      color: #cbd5e1;
      margin: 0 0 32px 0;
    }

    .figma-features-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .figma-feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      color: #e2e8f0;
      font-weight: 500;
    }

    .figma-feature-item svg {
      color: #38bdf8;
      flex-shrink: 0;
    }

    .figma-login-left-footer {
      font-size: 12px;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 20px;
    }

    /* COLUNA DIREITA: FORMULÁRIO BRANCO (#FFFFFF) */
    .figma-login-right {
      flex: 1;
      background-color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 50px 40px;
    }

    .figma-login-right-inner {
      width: 100%;
      max-width: 460px;
    }

    .figma-form-kicker {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #2563eb;
      margin-bottom: 6px;
    }

    .figma-form-title {
      font-size: 28px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
      margin: 0 0 8px 0;
    }

    .figma-form-subtitle {
      font-size: 14px;
      color: #64748b;
      margin: 0 0 24px 0;
      line-height: 1.45;
    }

    /* BOTÃO GOOGLE VISUAL FIGMA */
    .btn-figma-google {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      width: 100%;
      height: 48px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      background: #ffffff;
      color: #334155;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .btn-figma-google:hover {
      background: #f8fafc;
      border-color: #94a3b8;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* DIVISOR FIGMA */
    .figma-login-divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 22px 0;
      color: #94a3b8;
      font-size: 12px;
      font-weight: 500;
    }

    .figma-login-divider::before,
    .figma-login-divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid #e2e8f0;
    }

    .figma-login-divider::before { margin-right: 14px; }
    .figma-login-divider::after  { margin-left: 14px; }

    /* MENSAGEM DE ERRO */
    .figma-login-error {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* CAMPOS DE FORMULÁRIO */
    .figma-login-field {
      margin-bottom: 18px;
    }

    .figma-login-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 6px;
    }

    .figma-login-input {
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

    .figma-login-input:focus {
      border-color: #102D5C;
      box-shadow: 0 0 0 3px rgba(16, 45, 92, 0.12);
    }

    .figma-password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .figma-password-wrapper .figma-login-input {
      padding-right: 48px;
    }

    .btn-toggle-senha {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
    }

    .btn-toggle-senha:hover {
      color: #334155;
    }

    /* LINHA DE OPÇÕES: LEMBRAR E ESQUECI SENHA */
    .figma-login-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 18px 0 24px 0;
      font-size: 13px;
    }

    .figma-checkbox-label {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #475569;
      cursor: pointer;
      user-select: none;
    }

    .figma-checkbox-label input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #102D5C;
      cursor: pointer;
    }

    .link-esqueci-senha {
      color: #102D5C;
      font-weight: 700;
      text-decoration: none;
    }

    .link-esqueci-senha:hover {
      text-decoration: underline;
    }

    /* BOTÃO ENTRAR SUBMIT */
    .btn-login-submit {
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
      transition: background-color 0.15s ease, transform 0.1s ease;
    }

    .btn-login-submit:hover {
      background-color: #0b1f3f;
    }

    .btn-login-submit:active {
      transform: translateY(1px);
    }

    /* LINK PARA REGISTRO */
    .figma-login-bottom-link {
      text-align: center;
      margin-top: 24px;
      font-size: 14px;
      color: #64748b;
    }

    .figma-login-bottom-link a {
      color: #102D5C;
      font-weight: 700;
      text-decoration: none;
      margin-left: 4px;
    }

    .figma-login-bottom-link a:hover {
      text-decoration: underline;
    }

    /* RODAPÉ ESTENDIDO */
    .figma-footer {
      width: 100%;
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 20px 32px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      flex-shrink: 0;
    }

    /* RESPONSIVIDADE MOBILE (Login — Mobile.svg) */
    @media (max-width: 860px) {
      .figma-top-bar {
        padding: 0 16px;
        font-size: 10px;
      }
      .figma-login-split-main {
        flex-direction: column;
      }
      .figma-login-left {
        flex: none;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        padding: 36px 20px;
      }
      .figma-hero-title {
        font-size: 26px;
      }
      .figma-hero-desc {
        font-size: 13px;
        margin-bottom: 20px;
      }
      .figma-login-right {
        padding: 32px 20px 48px 20px;
      }
      .figma-form-title {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>

<div class="unilab-login-wrapper">

  <!-- Faixa Institucional 35px -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <!-- Layout Split Screen Fluido -->
  <div class="figma-login-split-main">

    <!-- LADO ESQUERDO: INSTITUCIONAL AZUL MARINHO (#102D5C) -->
    <div class="figma-login-left">
      <div>
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-login-brand">
          <div class="figma-brand-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
          </div>
          <span class="figma-brand-name">Banco de Questões</span>
        </a>

        <div class="figma-login-hero-content">
          <span class="figma-hero-tag">Acesso Acadêmico</span>
          <h1 class="figma-hero-title">Aprenda, pratique e evolua junto.</h1>
          <p class="figma-hero-desc">
            Acesse o banco de questões colaborativo da UNILAB. Exercite seus conhecimentos, resolva desafios acadêmicos e acompanhe seu progresso de aprendizagem.
          </p>

          <ul class="figma-features-list">
            <li class="figma-feature-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Questões oficiais comentadas com gabarito dinâmico</span>
            </li>
            <li class="figma-feature-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Materiais de apoio com arquivos PDF e resoluções gráficas</span>
            </li>
            <li class="figma-feature-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Ambiente colaborativo para todos os cursos da universidade</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="figma-login-left-footer">
        © <?php echo date('Y'); ?> UNILAB · Pró-Reitoria de Graduação (PROGRAD).
      </div>
    </div>

    <!-- LADO DIREITO: FORMULÁRIO BRANCO -->
    <div class="figma-login-right">
      <div class="figma-login-right-inner">

        <div class="figma-form-kicker">Acesso ao Sistema</div>
        <h2 class="figma-form-title">Entrar na sua conta</h2>
        <p class="figma-form-subtitle">Digite seu e-mail institucional ou usuário acadêmico para continuar.</p>

        <!-- Botão Google OAuth Visual -->
        <a href="#" onclick="alert('Autenticação Google Workspace UNILAB disponível em breve.'); return false;" class="btn-figma-google">
          <svg width="18" height="18" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.15z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.28v3.15C3.26 21.36 7.33 24 12 24z"/>
            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.28C.46 8.21 0 10.05 0 12s.46 3.79 1.28 5.42l4-3.15z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.28 6.58l4 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
          </svg>
          Continuar com Conta Google
        </a>

        <div class="figma-login-divider">ou use seu e-mail</div>

        <?php if ( ! empty( $erro_login ) ) : ?>
          <div class="figma-login-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo esc_html( $erro_login ); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <input type="hidden" name="unilab_login_submit" value="1">

          <div class="figma-login-field">
            <label class="figma-login-label" for="login_user">E-mail ou Usuário *</label>
            <input type="text" id="login_user" name="log" class="figma-login-input" placeholder="ana.martins@aluno.unilab.edu.br" required autocomplete="username">
          </div>

          <div class="figma-login-field">
            <label class="figma-login-label" for="login_pwd">Senha *</label>
            <div class="figma-password-wrapper">
              <input type="password" id="login_pwd" name="pwd" class="figma-login-input" placeholder="••••••••••••" required autocomplete="current-password">
              <button type="button" class="btn-toggle-senha" id="btn-toggle-senha" title="Mostrar/ocultar senha">
                <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="figma-login-options-row">
            <label class="figma-checkbox-label">
              <input type="checkbox" name="rememberme" value="forever">
              Manter conectado
            </label>
            <a href="<?php echo esc_url( home_url('/password-reset/') ); ?>" class="link-esqueci-senha">Esqueci minha senha</a>
          </div>

          <button type="submit" class="btn-login-submit">
            <span>Entrar</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </form>

        <div class="figma-login-bottom-link">
          Ainda não tem uma conta? <a href="<?php echo esc_url( home_url('/cadastro/') ); ?>">Crie sua conta</a>
        </div>
      </div>
    </div>

  </div>

  <!-- Rodapé Oficial Figma -->
  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

<!-- Script de Alternância de Visibilidade de Senha -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var btnToggle = document.getElementById('btn-toggle-senha');
  var inputPwd = document.getElementById('login_pwd');
  if (btnToggle && inputPwd) {
    btnToggle.addEventListener('click', function() {
      if (inputPwd.type === 'password') {
        inputPwd.type = 'text';
      } else {
        inputPwd.type = 'password';
      }
    });
  }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
