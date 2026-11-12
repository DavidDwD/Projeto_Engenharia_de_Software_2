<?php
/**
 * Template Name: Login Aluno UNILAB (Figma Pixel Perfect)
 * Banco de Questões — UNILAB
 */

// Se já estiver logado, redireciona para a home
if ( is_user_logged_in() && ! isset( $_GET['preview_user'] ) ) {
    wp_redirect( home_url('/') );
    exit;
}

$erro_login = '';

// Processamento do Login (RF01 / RF02 / RNF12)
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['unilab_login_submit'] ) ) {
    $login_input = sanitize_text_field( $_POST['log'] ?? '' );
    $senha_input = $_POST['pwd'] ?? '';
    $lembrar     = ! empty( $_POST['rememberme'] );

    if ( empty( $login_input ) || empty( $senha_input ) ) {
        $erro_login = 'Por favor, informe seu e-mail/usuário e senha para entrar.';
    } else {
        // Suporta login tanto por nome de usuário quanto por e-mail institucional
        if ( is_email( $login_input ) ) {
            $user_obj = get_user_by( 'email', $login_input );
            if ( $user_obj ) {
                $login_input = $user_obj->user_login;
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

get_header();
?>

<style>
  /* 1. OCULTA HEADER E FOOTER PADRÃO DO ASTRA */
  .entry-title,
  h1.entry-title,
  .entry-header,
  header.entry-header,
  .ast-single-post-order,
  .site-header,
  .ast-desktop-header,
  .ast-mobile-header,
  .site-footer,
  .ast-footer-overlay {
    display: none !important;
  }

  html, body {
    background-color: #f8fafc !important;
    margin: 0 !important;
    padding: 0 !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
    color: #0f172a !important;
  }
  .site-content, #content, .ast-container, #primary, #main, article, .entry-content {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .unilab-login-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: #f8fafc;
    box-sizing: border-box;
  }
  .unilab-login-wrapper *, .unilab-login-wrapper *::before, .unilab-login-wrapper *::after {
    box-sizing: border-box;
  }

  /* CONTAINER CENTRAL SPLIT-SCREEN FIGMA */
  .figma-login-card-split {
    display: flex;
    width: 1020px;
    max-width: 95%;
    min-height: 620px;
    margin: 40px auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  }

  /* COLUNA ESQUERDA: AZUL MARINHO (#102D5C) */
  .figma-login-left {
    width: 440px;
    min-width: 440px;
    background: #102d5c;
    color: #ffffff;
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .figma-login-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #ffffff;
  }
  .figma-login-brand-icon {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .figma-login-brand-text {
    font-size: 15px;
    font-weight: 700;
    letter-spacing: -0.2px;
  }
  .figma-left-tag {
    font-size: 11px;
    font-weight: 800;
    color: #93c5fd;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    margin-bottom: 12px;
  }
  .figma-left-title {
    font-size: 32px;
    font-weight: 800;
    line-height: 1.25;
    color: #ffffff;
    margin: 0 0 16px 0;
    letter-spacing: -0.5px;
  }
  .figma-left-sub {
    font-size: 14px;
    color: #cbd5e1;
    line-height: 1.6;
    margin: 0;
  }
  .figma-left-foot {
    font-size: 12px;
    color: #94a3b8;
  }

  /* COLUNA DIREITA: BRANCA (#FFFFFF) */
  .figma-login-right {
    flex: 1;
    background: #ffffff;
    padding: 48px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .figma-right-tag {
    font-size: 11px;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 6px;
  }
  .figma-right-title {
    font-size: 30px;
    font-weight: 800;
    color: #0f284e;
    margin: 0 0 6px 0;
    letter-spacing: -0.4px;
  }
  .figma-right-sub {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 24px 0;
  }

  /* BOTÃO GOOGLE */
  .btn-google-login {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 11px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #1e293b;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
  }
  .btn-google-login:hover {
    background: #f8fafc;
    border-color: #94a3b8;
  }

  /* DIVISOR */
  .figma-login-divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 20px 0;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
  }
  .figma-login-divider::before,
  .figma-login-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e2e8f0;
  }
  .figma-login-divider::before { margin-right: 12px; }
  .figma-login-divider::after { margin-left: 12px; }

  /* CAMPOS */
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
  .figma-login-label .obrigatorio {
    color: #ef4444;
  }
  .figma-login-input {
    width: 100%;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    font-family: inherit;
  }
  .figma-login-input:focus {
    border-color: #102d5c;
    box-shadow: 0 0 0 3px rgba(16, 45, 92, 0.1);
  }
  .figma-password-wrapper {
    position: relative;
  }
  .figma-password-wrapper .figma-login-input {
    padding-right: 42px;
  }
  .btn-toggle-senha {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
  }
  .btn-toggle-senha:hover {
    color: #0f284e;
  }

  /* LINHA DE CHECKBOX E ESQUECI SENHA */
  .figma-login-options-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    font-size: 13px;
  }
  .figma-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #475569;
    cursor: pointer;
    font-weight: 600;
  }
  .figma-checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #102d5c;
  }
  .link-esqueci-senha {
    color: #102d5c;
    font-weight: 700;
    text-decoration: none;
  }
  .link-esqueci-senha:hover {
    text-decoration: underline;
  }

  /* BOTÃO ENTRAR */
  .btn-login-submit {
    width: 100%;
    background: #102d5c;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 13px 20px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: background 0.15s ease;
  }
  .btn-login-submit:hover {
    background: #0a1f40;
  }

  /* RODAPÉ DO FORMULÁRIO */
  .figma-login-bottom-link {
    text-align: center;
    margin-top: 24px;
    font-size: 13px;
    color: #64748b;
  }
  .figma-login-bottom-link a {
    color: #102d5c;
    font-weight: 700;
    text-decoration: none;
    margin-left: 4px;
  }
  .figma-login-bottom-link a:hover {
    text-decoration: underline;
  }

  /* ALERTA DE ERRO */
  .figma-login-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 18px;
  }

  /* RODAPÉ GLOBAL */
  .figma-footer {
    text-align: center;
    padding: 24px 20px;
    border-top: 1px solid #e2e8f0;
    color: #94a3b8;
    font-size: 12px;
    background: #ffffff;
  }

  /* RESPONSIVIDADE MOBILE (Login — Mobile.svg) */
  @media (max-width: 860px) {
    .figma-login-card-split {
      flex-direction: column;
      width: 100%;
      margin: 16px auto;
      border-radius: 10px;
    }
    .figma-login-left {
      width: 100%;
      min-width: 100%;
      padding: 32px 24px;
    }
    .figma-left-title {
      font-size: 24px;
    }
    .figma-login-right {
      padding: 32px 24px;
    }
    .figma-right-title {
      font-size: 24px;
    }
  }
</style>

<div class="unilab-login-wrapper">

  <div class="figma-login-card-split">

    <!-- ======================================================== -->
    <!-- COLUNA ESQUERDA: AZUL MARINHO FIGMA                      -->
    <!-- ======================================================== -->
    <div class="figma-login-left">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-login-brand">
        <div class="figma-login-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
        </div>
        <span class="figma-login-brand-text">Banco de Questões</span>
      </a>

      <div style="margin: 40px 0;">
        <div class="figma-left-tag">CONHECIMENTO QUE SE MULTIPLICA</div>
        <h1 class="figma-left-title">Uma boa questão abre novos caminhos.</h1>
        <p class="figma-left-sub">Explore, pratique e compartilhe questões com quem também acredita no aprendizado coletivo.</p>
      </div>

      <div class="figma-left-foot">Um espaço acadêmico. Muitas formas de aprender.</div>
    </div>

    <!-- ======================================================== -->
    <!-- COLUNA DIREITA: FORMULÁRIO BRANCO FIGMA                  -->
    <!-- ======================================================== -->
    <div class="figma-login-right">
      <div class="figma-right-tag">SEU PRÓXIMO APRENDIZADO COMEÇA AQUI</div>
      <h2 class="figma-right-title">Bom ter você de volta.</h2>
      <p class="figma-right-sub">Entre para continuar seus estudos e compartilhar conhecimento.</p>

      <!-- Botão Google Visual Oficial do Figma -->
      <a href="#" onclick="alert('Integração com Google Workspace Institucional em breve.'); return false;" class="btn-google-login">
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
          <?php echo esc_html( $erro_login ); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="unilab_login_submit" value="1">

        <div class="figma-login-field">
          <label class="figma-login-label" for="login_user">E-mail <span class="obrigatorio">*</span></label>
          <input type="text" id="login_user" name="log" class="figma-login-input" placeholder="ana.martins@universidade.edu.br" required autocomplete="username">
        </div>

        <div class="figma-login-field">
          <label class="figma-login-label" for="login_pwd">Senha <span class="obrigatorio">*</span></label>
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
          <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="link-esqueci-senha">Esqueci minha senha</a>
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
        Ainda não tem uma conta? <a href="/cadastro/">Crie sua conta</a>
      </div>
    </div>

  </div>

  <!-- Rodapé Oficial Figma -->
  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade.
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

<?php
get_footer();
