<?php
/**
 * Template Name: Cadastro Aluno UNILAB (Figma Pixel Perfect - Dynamic Fullscreen)
 * Banco de Questões — UNILAB
 */

// Se já estiver logado, redireciona para a página inicial (a não ser em modo preview)
if ( is_user_logged_in() && ! isset( $_GET['preview_user'] ) ) {
    wp_redirect( home_url('/') );
    exit;
}

$mensagem_erro = '';
$sucesso_registro = false;

// Processamento seguro do formulário de cadastro
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['unilab_registrar_submit'] ) ) {
    $nome           = sanitize_text_field( $_POST['nome_completo'] ?? '' );
    $email          = sanitize_email( $_POST['email'] ?? '' );
    $senha          = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $termos         = isset( $_POST['termos'] );

    if ( empty( $nome ) || empty( $email ) || empty( $senha ) ) {
        $mensagem_erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ( ! is_email( $email ) ) {
        $mensagem_erro = 'Informe um endereço de e-mail institucional válido.';
    } elseif ( email_exists( $email ) ) {
        $mensagem_erro = 'Este e-mail já está cadastrado no sistema. Tente fazer login ou recuperar sua senha.';
    } elseif ( strlen( $senha ) < 6 ) {
        $mensagem_erro = 'A senha de acesso deve conter no mínimo 6 caracteres.';
    } elseif ( $senha !== $confirma_senha ) {
        $mensagem_erro = 'As senhas informadas não conferem. Digite a mesma senha em ambos os campos.';
    } elseif ( ! $termos ) {
        $mensagem_erro = 'Você precisa concordar com os Termos de Uso e Política de Privacidade para continuar.';
    } else {
        // Gera nome de usuário limpo a partir do prefixo do e-mail
        $username_base = sanitize_user( current( explode( '@', $email ) ), true );
        if ( empty( $username_base ) ) {
            $username_base = 'estudante';
        }
        $username = $username_base;
        $contador = 1;
        while ( username_exists( $username ) ) {
            $username = $username_base . '_' . $contador;
            $contador++;
        }

        $user_id = wp_create_user( $username, $senha, $email );
        if ( is_wp_error( $user_id ) ) {
            $mensagem_erro = 'Erro ao criar conta: ' . $user_id->get_error_message();
        } else {
            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => $nome,
                'first_name'   => $nome,
                'role'         => 'subscriber'
            ) );

            // Autentica o aluno automaticamente
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );

            wp_safe_redirect( home_url( '/?bem-vindo=1' ) );
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
  <title>Criar Conta de Estudante — Banco de Questões UNILAB</title>
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

    /* OCULTA WRAPPERS DO TEMA BASE ASTRA CASO PRESENTES */
    .site-header, .site-footer, .ast-footer-overlay,
    .ast-desktop-header, .ast-mobile-header,
    .entry-header, .entry-title {
      display: none !important;
    }

    /* CONTAINER PRINCIPAL FLUIDO */
    .unilab-register-wrapper {
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
    .figma-register-split-main {
      display: flex;
      flex-direction: row;
      flex: 1;
      width: 100%;
      min-height: calc(100vh - 100px);
    }

    /* COLUNA ESQUERDA: INSTITUCIONAL AZUL MARINHO */
    .figma-register-left {
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

    .figma-register-brand {
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

    .figma-register-hero-content {
      margin: 40px 0;
    }

    .figma-register-tag {
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

    .figma-register-title {
      font-size: 34px;
      font-weight: 900;
      line-height: 1.2;
      color: #ffffff;
      margin: 0 0 16px 0;
      letter-spacing: -0.8px;
    }

    .figma-register-desc {
      font-size: 15px;
      line-height: 1.6;
      color: #cbd5e1;
      margin: 0 0 32px 0;
    }

    .figma-highlights-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .figma-highlight-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      color: #e2e8f0;
      font-weight: 500;
    }

    .figma-highlight-item svg {
      color: #38bdf8;
      flex-shrink: 0;
    }

    .figma-register-left-footer {
      font-size: 12px;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      padding-top: 20px;
    }

    /* COLUNA DIREITA: FORMULÁRIO BRANCO */
    .figma-register-right {
      flex: 1;
      background-color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 50px 40px;
    }

    .figma-register-right-inner {
      width: 100%;
      max-width: 460px;
    }

    .figma-form-header {
      margin-bottom: 24px;
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
      font-size: 26px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: -0.5px;
      margin: 0 0 8px 0;
    }

    .figma-form-subtitle {
      font-size: 14px;
      color: #64748b;
      margin: 0;
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

    /* DIVISOR */
    .figma-register-divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 20px 0;
      color: #94a3b8;
      font-size: 12px;
      font-weight: 500;
    }

    .figma-register-divider::before,
    .figma-register-divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid #e2e8f0;
    }

    .figma-register-divider::before { margin-right: 14px; }
    .figma-register-divider::after  { margin-left: 14px; }

    /* MENSAGEM DE ERRO */
    .figma-register-error {
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

    /* CAMPOS DO FORMULÁRIO */
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

    .figma-password-box {
      position: relative;
      display: flex;
      align-items: center;
    }

    .figma-password-box .figma-input {
      padding-right: 48px;
    }

    .btn-toggle-eye {
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

    .btn-toggle-eye:hover {
      color: #334155;
    }

    .figma-help-note {
      font-size: 11px;
      color: #64748b;
      margin-top: 4px;
    }

    /* CHECKBOX TERMOS */
    .figma-terms-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin: 18px 0 24px 0;
      font-size: 13px;
      color: #475569;
      line-height: 1.4;
    }

    .figma-terms-row input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #102D5C;
      margin-top: 2px;
      cursor: pointer;
    }

    /* BOTÃO SUBMIT */
    .btn-register-submit {
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

    .btn-register-submit:hover {
      background-color: #0b1f3f;
    }

    .btn-register-submit:active {
      transform: translateY(1px);
    }

    /* LINK PARA LOGIN */
    .figma-bottom-login-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #64748b;
    }

    .figma-bottom-login-link a {
      color: #102D5C;
      font-weight: 700;
      text-decoration: none;
      margin-left: 4px;
    }

    .figma-bottom-login-link a:hover {
      text-decoration: underline;
    }

    /* RODAPÉ ESTENDIDO */
    .figma-register-footer {
      width: 100%;
      background-color: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 20px 32px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      flex-shrink: 0;
    }

    /* RESPONSIVIDADE MOBILE (Cadastro — Mobile.svg) */
    @media (max-width: 860px) {
      .figma-top-bar {
        padding: 0 16px;
        font-size: 10px;
      }
      .figma-register-split-main {
        flex-direction: column;
      }
      .figma-register-left {
        flex: none;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        padding: 36px 20px;
      }
      .figma-register-title {
        font-size: 24px;
      }
      .figma-register-desc {
        font-size: 13px;
        margin-bottom: 20px;
      }
      .figma-register-right {
        padding: 32px 20px 48px 20px;
      }
      .figma-form-title {
        font-size: 22px;
      }
    }
  </style>
</head>
<body>

<div class="unilab-register-wrapper">

  <!-- Faixa Institucional Superior (Figma 35px) -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <!-- Split Screen Dinâmico -->
  <div class="figma-register-split-main">

    <!-- Painel Esquerdo: Identidade Institucional -->
    <div class="figma-register-left">
      <div>
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-register-brand">
          <div class="figma-brand-icon-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
          </div>
          <span class="figma-brand-name">Banco de Questões</span>
        </a>

        <div class="figma-register-hero-content">
          <span class="figma-register-tag">Comunidade Acadêmica</span>
          <h1 class="figma-register-title">Uma boa questão abre novos caminhos.</h1>
          <p class="figma-register-desc">
            Explore, pratique e compartilhe questões com quem também acredita no aprendizado coletivo e colaborativo em nível superior.
          </p>

          <ul class="figma-highlights-list">
            <li class="figma-highlight-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Acesso a centenas de questões com gabarito comentado</span>
            </li>
            <li class="figma-highlight-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Publicação de enunciados com imagens e arquivos PDF</span>
            </li>
            <li class="figma-highlight-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span>Integração entre estudantes dos campi e países parceiros</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="figma-register-left-footer">
        Ambiente acadêmico seguro para estudantes de graduação e pós-graduação.
      </div>
    </div>

    <!-- Painel Direito: Formulário de Cadastro -->
    <div class="figma-register-right">
      <div class="figma-register-right-inner">

        <div class="figma-form-header">
          <div class="figma-form-kicker">Aprenda • Compartilhe • Evolua</div>
          <h2 class="figma-form-title">Crie sua conta de estudante</h2>
          <p class="figma-form-subtitle">É simples, rápido e gratuito com seu e-mail institucional.</p>
        </div>

        <!-- Botão Google Visual do Figma -->
        <a href="#" onclick="alert('Autenticação Google Workspace UNILAB disponível em breve.'); return false;" class="btn-figma-google">
          <svg width="18" height="18" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.15z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.28v3.15C3.26 21.36 7.33 24 12 24z"/>
            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.28C.46 8.21 0 10.05 0 12s.46 3.79 1.28 5.42l4-3.15z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.28 6.58l4 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
          </svg>
          Cadastrar com Conta Google
        </a>

        <div class="figma-register-divider">ou use seus dados acadêmicos</div>

        <?php if ( ! empty( $mensagem_erro ) ) : ?>
          <div class="figma-register-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo esc_html( $mensagem_erro ); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <input type="hidden" name="unilab_registrar_submit" value="1">

          <div class="figma-field">
            <label class="figma-label" for="reg_nome">Nome completo *</label>
            <input type="text" id="reg_nome" name="nome_completo" class="figma-input" placeholder="Ana Martins" required autocomplete="name" value="<?php echo isset($_POST['nome_completo']) ? esc_attr($_POST['nome_completo']) : ''; ?>">
          </div>

          <div class="figma-field">
            <label class="figma-label" for="reg_email">E-mail institucional *</label>
            <input type="email" id="reg_email" name="email" class="figma-input" placeholder="ana.martins@aluno.unilab.edu.br" required autocomplete="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
            <div class="figma-help-note">Você usará este endereço institucional para efetuar o login.</div>
          </div>

          <div class="figma-field">
            <label class="figma-label" for="reg_senha">Senha de acesso *</label>
            <div class="figma-password-box">
              <input type="password" id="reg_senha" name="senha" class="figma-input" placeholder="••••••••••••" required autocomplete="new-password">
              <button type="button" class="btn-toggle-eye" onclick="toggleVisibilidade('reg_senha', this)" title="Mostrar/ocultar senha">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
            <div class="figma-help-note">Mínimo de 6 caracteres.</div>
          </div>

          <div class="figma-field">
            <label class="figma-label" for="reg_confirma">Confirmar senha *</label>
            <div class="figma-password-box">
              <input type="password" id="reg_confirma" name="confirma_senha" class="figma-input" placeholder="••••••••••••" required autocomplete="new-password">
              <button type="button" class="btn-toggle-eye" onclick="toggleVisibilidade('reg_confirma', this)" title="Mostrar/ocultar senha">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <div class="figma-terms-row">
            <input type="checkbox" id="reg_termos" name="termos" required <?php checked( isset($_POST['termos']) ); ?>>
            <label for="reg_termos">
              Declaro que sou estudante da UNILAB e concordo com os Termos de Uso e Política de Privacidade.
            </label>
          </div>

          <button type="submit" class="btn-register-submit">
            <span>Criar minha conta</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </button>
        </form>

        <div class="figma-bottom-login-link">
          Já tem uma conta de estudante? <a href="<?php echo esc_url( home_url('/log-in/') ); ?>">Entrar aqui</a>
        </div>

      </div>
    </div>

  </div>

  <!-- Rodapé Estendido -->
  <footer class="figma-register-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

<script>
function toggleVisibilidade(id, btn) {
  var input = document.getElementById(id);
  if ( ! input ) return;
  if ( input.type === 'password' ) {
    input.type = 'text';
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
  } else {
    input.type = 'password';
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  }
}
</script>

<?php wp_footer(); ?>
</body>
</html>
