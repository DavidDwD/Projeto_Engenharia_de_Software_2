<?php
/*
 * Template Name: Cadastro Aluno UNILAB
 */
get_header();

// Processamento do formulário
$mensagem_erro = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['unilab_registrar_submit'] ) ) {
    $nome = sanitize_text_field( $_POST['nome_completo'] );
    $email = sanitize_email( $_POST['email'] );
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if ( ! empty( $nome ) && is_email( $email ) && ! email_exists( $email ) && strlen( $senha ) >= 6 && $senha === $confirma_senha ) {
        $username = sanitize_user( current( explode( '@', $email ) ) );
        if ( username_exists( $username ) ) {
            $username = $username . '_' . wp_rand( 10, 99 );
        }

        $user_id = wp_create_user( $username, $senha, $email );
        if ( ! is_wp_error( $user_id ) ) {
            wp_update_user( array( 'ID' => $user_id, 'display_name' => $nome, 'first_name' => $nome, 'role' => 'subscriber' ) );
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            echo "<script>window.location.href = '" . home_url() . "';</script>";
            exit;
        }
    } else {
        $mensagem_erro = 'Por favor, confira os dados preenchidos e garanta que as senhas são iguais (mínimo 6 dígitos).';
    }
}
?>

<!-- CSS BLINDADO: FORÇA LADO A LADO -->
<style>
  /* Remove margens e travas do tema Astra nesta página */
  .ast-container, #primary, #main, article {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .unilab-figma-box {
    display: flex !important;
    flex-direction: row !important; /* FORÇA LADO A LADO */
    width: 1000px !important;
    min-height: 700px !important;
    margin: 40px auto !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important;
  }

  .unilab-figma-box * {
    box-sizing: border-box !important;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
  }

  /* 1. LADO ESQUERDO: AZUL MARINHO (A MENSAGEM) */
  .lado-esquerdo-azul {
    width: 420px !important;
    min-width: 420px !important;
    background: #0f3057 !important;
    color: #ffffff !important;
    padding: 50px 40px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
  }
  .lado-esquerdo-azul h1 { font-size: 34px !important; font-weight: 800 !important; line-height: 1.25 !important; color: #fff !important; margin: 15px 0 20px 0 !important; }
  .lado-esquerdo-azul p { font-size: 15px !important; color: #cbd5e1 !important; line-height: 1.6 !important; }
  .tag-azul { font-size: 11px !important; color: #93c5fd !important; font-weight: 700 !important; letter-spacing: 1.5px !important; text-transform: uppercase !important; }

  /* 2. LADO DIREITO: BRANCO (OS DADOS) */
  .lado-direito-branco {
    width: 580px !important;
    background: #ffffff !important;
    padding: 40px 50px !important;
  }
  .lado-direito-branco h2 { font-size: 28px !important; font-weight: 800 !important; color: #0f172a !important; margin: 0 0 6px 0 !important; }
  .tag-verde { font-size: 11px !important; color: #0284c7 !important; font-weight: 800 !important; letter-spacing: 1px !important; text-transform: uppercase !important; }
  
  .btn-google-box {
    display: flex !important; align-items: center !important; justify-content: center !important; gap: 10px !important;
    width: 100% !important; padding: 11px !important; border: 1px solid #cbd5e1 !important; border-radius: 6px !important;
    background: #fff !important; color: #334155 !important; font-weight: 700 !important; font-size: 14px !important; text-decoration: none !important;
  }
  .divisor-texto { display: flex !important; align-items: center !important; text-align: center !important; margin: 16px 0 !important; color: #94a3b8 !important; font-size: 12px !important; }
  .divisor-texto::before, .divisor-texto::after { content: '' !important; flex: 1 !important; border-bottom: 1px solid #e2e8f0 !important; }
  .divisor-texto::before { margin-right: 10px !important; }
  .divisor-texto::after { margin-left: 10px !important; }

  .campo-linha { margin-bottom: 14px !important; }
  .campo-linha label { display: block !important; font-size: 13px !important; font-weight: 700 !important; color: #1e293b !important; margin-bottom: 4px !important; }
  .campo-linha input { width: 100% !important; padding: 10px 12px !important; border: 1.5px solid #cbd5e1 !important; border-radius: 6px !important; font-size: 14px !important; }
  .campo-linha .ajuda { font-size: 11px !important; color: #64748b !important; margin-top: 3px !important; }

  .btn-submit-azul {
    width: 100% !important; padding: 13px !important; background: #0f3057 !important; color: #ffffff !important;
    font-size: 15px !important; font-weight: 700 !important; border: none !important; border-radius: 6px !important; cursor: pointer !important;
  }
  .btn-submit-azul:hover { background: #09203a !important; }
</style>

<div class="unilab-figma-box">
  
  <!-- LADO ESQUERDO: AZUL (A MENSAGEM) -->
  <div class="lado-esquerdo-azul">
    <div style="font-size:16px; font-weight:bold;">📖 Banco de Questões (UNILAB)</div>

    <div>
      <div class="tag-azul">CONHECIMENTO QUE SE MULTIPLICA</div>
      <h1>Uma boa questão abre novos caminhos.</h1>
      <p>Explore, pratique e compartilhe questões com quem também acredita no aprendizado coletivo.</p>
    </div>

    <div style="font-size:12px; color:#64748b;">Um espaço acadêmico. Muitas formas de aprender.</div>
  </div>

  <!-- LADO DIREITO: BRANCO (OS DADOS) -->
  <div class="lado-direito-branco">
    <div class="tag-verde">APRENDA • COMPARTILHE • EVOLUA</div>
    <h2>Faça parte da comunidade.</h2>
    <p style="color:#64748b; font-size:14px; margin:0 0 16px 0;">Crie seu perfil de estudante. É simples e gratuito.</p>

    <!-- Botão Google Visual -->
    <a href="#" onclick="alert('Google OAuth em breve.'); return false;" class="btn-google-box">
      <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.15z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.28v3.15C3.26 21.36 7.33 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.28C.46 8.21 0 10.05 0 12s.46 3.79 1.28 5.42l4-3.15z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.28 6.58l4 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/></svg>
      Cadastrar com Conta Google
    </a>

    <div class="divisor-texto">ou use seu e-mail</div>

    <?php if ( ! empty( $mensagem_erro ) ) : ?>
      <div style="background:#fef2f2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:12px; font-size:13px; font-weight:bold;">
        <?php echo esc_html( $mensagem_erro ); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="unilab_registrar_submit" value="1">

      <div class="campo-linha">
        <label>Nome completo *</label>
        <input type="text" name="nome_completo" placeholder="Ana Martins" required>
      </div>

      <div class="campo-linha">
        <label>E-mail *</label>
        <input type="email" name="email" placeholder="ana.martins@unilab.edu.br" required>
        <div class="ajuda">Você usará este e-mail para entrar.</div>
      </div>

      <div class="campo-linha">
        <label>Senha *</label>
        <input type="password" name="senha" placeholder="••••••••••••" required>
        <div class="ajuda">Use pelo menos 8 caracteres.</div>
      </div>

      <div class="campo-linha">
        <label>Confirmar senha *</label>
        <input type="password" name="confirma_senha" placeholder="••••••••••••" required>
        <div class="ajuda">Digite a mesma senha.</div>
      </div>

      <div style="display:flex; align-items:center; gap:8px; margin:16px 0; font-size:12px; color:#475569;">
        <input type="checkbox" name="termos" id="termos" required>
        <label for="termos">Li e aceito os Termos de uso e a Política de privacidade. *</label>
      </div>

      <button type="submit" class="btn-submit-azul">➔ Criar minha conta</button>

      <div style="text-align:center; margin-top:14px; font-size:13px; color:#64748b;">
        Já tem uma conta? <a href="/log-in/" style="color:#0f3057; font-weight:bold; text-decoration:none;">Entrar</a>
      </div>
    </form>
  </div>

</div>

<?php 
get_footer(); 
?>