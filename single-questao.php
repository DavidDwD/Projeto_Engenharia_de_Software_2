<?php
/**
 * Template de Visualização da Questão Individual (Single Questão)
 * Banco de Questões — UNILAB (Figma Pixel Perfect)
 */
get_header(); 

$gabarito = get_field('resposta_correta') ?: get_post_meta( get_the_ID(), 'resposta_correta', true );
$enunciado = get_field('enunciado') ?: get_post_meta( get_the_ID(), 'enunciado', true ) ?: get_the_content();
$explicacao = get_field('explicacao') ?: get_post_meta( get_the_ID(), 'explicacao', true );
$link_externo = get_field('link_externo') ?: get_post_meta( get_the_ID(), 'link_externo', true );

$disciplinas = get_the_terms( get_the_ID(), 'disciplina' );
if ( empty( $disciplinas ) || is_wp_error( $disciplinas ) ) {
    $disciplinas = get_the_terms( get_the_ID(), 'categoria_disciplina' );
}
$nome_disciplina = ( ! empty( $disciplinas ) && ! is_wp_error( $disciplinas ) ) ? $disciplinas[0]->name : get_post_meta( get_the_ID(), 'categoria', true );
if ( empty( $nome_disciplina ) ) {
    $nome_disciplina = 'Engenharia de Software';
}

$usuario_atual_id = get_current_user_id();
$autor_id = intval( get_post_field( 'post_author', get_the_ID() ) );
$eh_autor = is_user_logged_in() && ( $usuario_atual_id === $autor_id || current_user_can( 'edit_post', get_the_ID() ) );

$nome_autor = get_the_author() ?: 'Estudante UNILAB';
$partes_autor = explode( ' ', trim( $nome_autor ) );
$iniciais_autor = 'UN';
if ( ! empty( $partes_autor[0] ) ) {
    $iniciais_autor = strtoupper( substr( $partes_autor[0], 0, 1 ) );
    if ( count( $partes_autor ) > 1 ) {
        $iniciais_autor .= strtoupper( substr( end( $partes_autor ), 0, 1 ) );
    }
}

// Anexos
$imagens_anexadas = get_post_meta( get_the_ID(), 'anexo_imagens', true );
if ( is_array( $imagens_anexadas ) ) {
    $imagens_anexadas = array_values( array_filter( $imagens_anexadas ) );
} else {
    $imagens_anexadas = array();
}
$pdf_url = get_post_meta( get_the_ID(), 'anexo_pdf', true );
$pdf_nome = get_post_meta( get_the_ID(), 'anexo_pdf_nome', true ) ?: 'Material de Apoio (PDF)';
$tem_materiais = ( ! empty( $imagens_anexadas ) ) || ( ! empty( $pdf_url ) ) || ( ! empty( $link_externo ) );
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

  /* 2. RESET E CONTAINER GLOBAL */
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

  .figma-single-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
    width: 100%;
    box-sizing: border-box;
  }
  .figma-single-wrapper *, .figma-single-wrapper *::before, .figma-single-wrapper *::after {
    box-sizing: border-box;
  }

  /* 3. NAVBAR FIGMA PIXEL PERFECT */
  .figma-navbar {
    width: 100%;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 48px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 999;
  }
  .figma-navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
  }
  .figma-brand-icon {
    width: 32px;
    height: 32px;
    background: #0f284e;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
  }
  .figma-brand-text {
    font-size: 17px;
    font-weight: 800;
    color: #0f284e;
    letter-spacing: -0.3px;
  }
  .figma-nav-links {
    display: flex;
    gap: 28px;
    list-style: none;
    margin: 0;
    padding: 0;
  }
  .figma-nav-links a {
    text-decoration: none;
    font-size: 14px;
    color: #64748b;
    font-weight: 600;
    transition: color 0.15s ease;
  }
  .figma-nav-links a:hover,
  .figma-nav-links a.active {
    color: #0f284e;
  }
  .figma-nav-user {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .figma-avatar-circle {
    width: 36px;
    height: 36px;
    background: #dce7ff;
    color: #173f8a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
  }
  .figma-user-meta { line-height: 1.25; }
  .figma-user-name { font-size: 13px; font-weight: 700; color: #0f284e; }
  .figma-user-role { font-size: 11px; color: #64748b; }

  /* 4. ÁREA DE CONTEÚDO */
  .figma-single-container {
    max-width: 960px;
    margin: 0 auto;
    padding: 32px 24px 60px 24px;
  }

  /* BREADCRUMB */
  .figma-breadcrumb {
    font-size: 13px;
    color: #1d4ed8;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .figma-breadcrumb a {
    color: #1d4ed8;
    text-decoration: none;
  }
  .figma-breadcrumb a:hover {
    text-decoration: underline;
  }
  .figma-breadcrumb span {
    color: #94a3b8;
  }

  /* TAGS DA QUESTÃO */
  .figma-tags-row-single {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
  }
  .badge-disciplina,
  .figma-tag-badge-single {
    display: inline-block;
    background: #dce7ff;
    color: #173f8a;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 20px;
    text-decoration: none;
    letter-spacing: 0.2px;
  }

  /* TÍTULO DA QUESTÃO */
  .questao-titulo,
  .figma-single-title {
    font-size: 32px;
    font-weight: 800;
    color: #0f284e;
    margin: 0 0 16px 0;
    letter-spacing: -0.5px;
    line-height: 1.25;
  }

  /* BARRA DE AUTOR E BOTÕES SOCIAIS */
  .figma-author-social-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 24px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
  }
  .figma-author-box {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .figma-author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dce7ff;
    color: #173f8a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
  }
  .figma-author-details {
    line-height: 1.3;
  }
  .figma-author-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f284e;
  }
  .questao-meta,
  .figma-author-date {
    font-size: 12px;
    color: #64748b;
  }

  .figma-social-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .figma-btn-social {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
  }
  .figma-btn-social:hover {
    border-color: #0f284e;
    color: #0f284e;
  }

  /* TOOLBAR DO PROPRIETÁRIO (FIGMA PIXEL PERFECT) */
  .toolbar-autor {
    background: #f0f7ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 14px 22px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }
  .toolbar-autor-info {
    font-size: 14px;
    font-weight: 700;
    color: #1e3a8a;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .toolbar-autor-acoes {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .btn-autor-editar {
    background: #0f284e;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s;
  }
  .btn-autor-editar:hover {
    background: #1e3a8a;
  }
  .btn-autor-excluir {
    background: #ef4444;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s;
  }
  .btn-autor-excluir:hover {
    background: #dc2626;
  }

  /* CARD 1: RESOLUÇÃO DA QUESTÃO */
  .card-questao {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 36px;
    margin-bottom: 24px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
  }
  .figma-tag-questao-top {
    font-size: 11px;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 14px;
  }
  .enunciado-box {
    font-size: 16px;
    line-height: 1.65;
    color: #1e293b;
    margin-bottom: 24px;
  }

  .figma-selecione-aviso {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 16px;
  }

  /* ALTERNATIVAS A-E */
  .lista-alternativas {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
  }
  .alternativa {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 14px;
    color: #1e293b;
    line-height: 1.4;
  }
  .alternativa:hover {
    border-color: #94a3b8;
    background: #f8fafc;
  }
  .alternativa.selecionada {
    border-color: #0f284e !important;
    background: #f8fafc !important;
  }
  .alternativa.correta {
    border-color: #22c55e !important;
    background: #f0fdf4 !important;
    color: #15803d !important;
  }
  .alternativa.incorreta {
    border-color: #ef4444 !important;
    background: #fef2f2 !important;
    color: #991b1b !important;
  }

  .figma-alt-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1.5px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    color: #475569;
    flex-shrink: 0;
    transition: all 0.15s ease;
  }
  .alternativa.selecionada .figma-alt-circle {
    border-color: #0f284e;
    background: #0f284e;
    color: #ffffff;
  }
  .alternativa.correta .figma-alt-circle {
    border-color: #22c55e;
    background: #22c55e;
    color: #ffffff;
  }
  .alternativa.incorreta .figma-alt-circle {
    border-color: #ef4444;
    background: #ef4444;
    color: #ffffff;
  }

  /* BOTÕES DE RESPOSTA */
  .figma-actions-row-res {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .btn-responder {
    background: #0f284e;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 12px 28px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .btn-responder:hover {
    background: #1e3a8a;
  }
  .btn-tentar-novamente {
    color: #2563eb;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    background: none;
    border: none;
  }
  .btn-tentar-novamente:hover {
    text-decoration: underline;
  }

  /* FEEDBACK BOX */
  .feedback-box {
    display: none;
    margin-top: 24px;
    border-radius: 10px;
    padding: 20px 24px;
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
  }
  .feedback-box.visivel {
    display: block;
  }
  .feedback-box.incorreto-box {
    border-color: #fecaca;
    background: #fef2f2;
  }
  #feedback-titulo {
    font-size: 15px;
    font-weight: 800;
    color: #166534;
    margin: 0 0 12px 0;
  }
  .feedback-box.incorreto-box #feedback-titulo {
    color: #991b1b;
  }
  .figma-exp-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f284e;
    margin: 16px 0 8px 0;
  }
  #feedback-explicacao {
    font-size: 14px;
    color: #1e293b;
    line-height: 1.6;
    margin: 0;
  }
  .figma-referencia-nota {
    font-size: 12px;
    color: #64748b;
    font-style: italic;
    margin-top: 10px;
  }

  /* CARD 2: MATERIAIS DE APOIO (FIGMA PIXEL PERFECT) */
  .figma-card-materiais {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
  }
  .figma-materiais-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f284e;
    margin: 0 0 4px 0;
  }
  .figma-materiais-sub {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 24px 0;
  }

  /* GRID DE IMAGENS */
  .bloco-imagens-anexadas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .figma-img-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
    display: flex;
    flex-direction: column;
  }
  .figma-img-preview-box {
    background: #f8fafc;
    height: 190px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 12px;
  }
  .figma-img-preview-box img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
  }
  .figma-img-footer {
    padding: 12px 16px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
  }
  .figma-img-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
  }
  .figma-img-meta {
    font-size: 11px;
    color: #64748b;
  }
  .figma-btn-expand {
    color: #64748b;
    font-size: 16px;
    text-decoration: none;
    padding: 4px;
  }
  .figma-btn-expand:hover {
    color: #0f284e;
  }

  /* BLOCO PDF */
  .bloco-pdf-anexado {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
  }
  .figma-pdf-left {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .figma-pdf-icon {
    width: 38px;
    height: 38px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .figma-pdf-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
  }
  .figma-pdf-sub {
    font-size: 11px;
    color: #64748b;
  }
  .btn-baixar-pdf {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 700;
    color: #0f284e;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
  }
  .btn-baixar-pdf:hover {
    border-color: #0f284e;
    background: #f8fafc;
  }

  /* LINK EXTERNO CARD */
  .figma-link-ext-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
  }
  .link-externo {
    font-size: 14px;
    font-weight: 700;
    color: #0f284e;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .link-externo:hover {
    text-decoration: underline;
  }
  .figma-link-ext-note {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
  }

  /* BOTÃO VOLTAR */
  .btn-voltar-resultados {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #0f284e;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 700;
    color: #0f284e;
    text-decoration: none;
    transition: all 0.15s ease;
    margin-top: 12px;
  }
  .btn-voltar-resultados:hover {
    background: #0f284e;
    color: #ffffff;
  }

  /* MODAL DE CONFIRMAÇÃO DE EXCLUSÃO (RF36) */
  .unilab-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  .unilab-modal-card {
    background: #ffffff;
    max-width: 480px;
    width: 90%;
    border-radius: 12px;
    padding: 28px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  }
  .unilab-modal-card h3 {
    margin: 0 0 10px 0;
    color: #991b1b;
    font-size: 18px;
  }
  .unilab-modal-card p {
    color: #475569;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 24px 0;
  }
  .unilab-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
  .btn-modal-cancelar {
    background: #e2e8f0;
    color: #334155;
    border: none;
    padding: 9px 18px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
  }
  .btn-modal-confirmar {
    background: #dc2626;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
  }

  /* FOOTER */
  .figma-footer {
    text-align: center;
    padding: 30px 20px;
    border-top: 1px solid #e2e8f0;
    color: #94a3b8;
    font-size: 12px;
    margin-top: 60px;
    background: #ffffff;
  }

  @media (max-width: 768px) {
    .figma-navbar { padding: 12px 16px; }
    .figma-nav-links { display: none; }
    .figma-single-container { padding: 20px 14px; }
    .questao-titulo, .figma-single-title { font-size: 24px; }
    .card-questao, .figma-card-materiais { padding: 20px 16px; border-radius: 10px; }
    .toolbar-autor { flex-direction: column; align-items: stretch; text-align: center; }
    .toolbar-autor-acoes { flex-direction: column; width: 100%; }
    .btn-autor-editar, .btn-autor-excluir { width: 100%; justify-content: center; }
    .figma-author-social-row { flex-direction: column; align-items: flex-start; }
    .figma-social-actions { width: 100%; justify-content: space-between; }
  }
</style>

<div class="figma-single-wrapper">

  <!-- Header Oficial Figma -->
  <?php if ( function_exists('unilab_render_figma_navbar') ) {
      unilab_render_figma_navbar('inicio');
  } else { ?>
    <header class="figma-navbar">
      <a href="/" class="figma-navbar-brand">
        <div class="figma-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
        </div>
        <span class="figma-brand-text">Banco de Questões</span>
      </a>
      <ul class="figma-nav-links">
        <li><a href="/" class="active">Início</a></li>
        <li><a href="/?favoritos=1">Favoritos</a></li>
        <li><a href="/cadastrar-questao/">Minhas questões</a></li>
      </ul>
      <div class="figma-nav-user">
        <div class="figma-avatar-circle">UN</div>
        <div class="figma-user-meta">
          <div class="figma-user-name">Estudante</div>
          <div class="figma-user-role">Estudante</div>
        </div>
      </div>
    </header>
  <?php } ?>

  <div class="figma-single-container">

    <!-- Breadcrumb Figma -->
    <div class="figma-breadcrumb">
      <a href="/">Início</a> <span>/</span> <a href="/?filtro_disc=engenharia_software">Computação</a> <span>/</span> <span><?php echo esc_html($nome_disciplina); ?></span>
    </div>

    <!-- Tags Row Figma -->
    <div class="figma-tags-row-single">
      <span class="badge-disciplina"><?php echo esc_html($nome_disciplina); ?></span>
      <span class="figma-tag-badge-single">Engenharia de Software</span>
      <span class="figma-tag-badge-single">#prototipação</span>
      <span class="figma-tag-badge-single">#validação</span>
    </div>

    <!-- Título da Questão -->
    <h1 class="questao-titulo figma-single-title"><?php the_title(); ?></h1>

    <!-- Linha de Autor e Botões Sociais -->
    <div class="figma-author-social-row">
      <div class="figma-author-box">
        <div class="figma-author-avatar"><?php echo esc_html( $iniciais_autor ); ?></div>
        <div class="figma-author-details">
          <div class="figma-author-name"><?php echo esc_html( $nome_autor ); ?></div>
          <div class="questao-meta figma-author-date">
            Publicado em <?php echo get_the_date('d \d\e M. Y'); ?> · Múltipla escolha
          </div>
        </div>
      </div>

      <div class="figma-social-actions">
        <button type="button" class="figma-btn-social" onclick="this.classList.toggle('active');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          Favoritada
        </button>
        <button type="button" class="figma-btn-social" onclick="this.classList.toggle('active');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          Curtir · 24
        </button>
        <button type="button" class="figma-btn-social" onclick="alert('Denúncia registrada para análise da moderação.');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          Denunciar
        </button>
      </div>
    </div>

    <!-- Toolbar do Proprietário / Autor (RF34, RF35, RF36) -->
    <?php if ( $eh_autor ) : ?>
      <div class="toolbar-autor">
        <div class="toolbar-autor-info">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Você é o proprietário desta questão
        </div>
        <div class="toolbar-autor-acoes">
          <a href="<?php echo esc_url( home_url( '/cadastrar-questao/?editar=' . get_the_ID() ) ); ?>" class="btn-autor-editar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar Questão
          </a>
          <button type="button" class="btn-autor-excluir" onclick="document.getElementById('modal-excluir-questao').style.display='flex';">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            Excluir Definitivamente
          </button>
        </div>
      </div>

      <!-- Modal de Confirmação (RF36) -->
      <div id="modal-excluir-questao" class="unilab-modal-overlay">
        <div class="unilab-modal-card">
          <h3>Confirmar Exclusão de Questão</h3>
          <p>Tem certeza de que deseja excluir permanentemente a questão <strong>"<?php the_title(); ?>"</strong>? Esta ação removerá o conteúdo do banco e não poderá ser desfeita.</p>
          <form method="POST" action="">
            <?php wp_nonce_field( 'unilab_excluir_questao_' . get_the_ID(), 'unilab_nonce' ); ?>
            <input type="hidden" name="unilab_acao_excluir" value="1">
            <input type="hidden" name="questao_id" value="<?php the_ID(); ?>">
            <div class="unilab-modal-footer">
              <button type="button" class="btn-modal-cancelar" onclick="document.getElementById('modal-excluir-questao').style.display='none';">Cancelar</button>
              <button type="submit" class="btn-modal-confirmar">Sim, Excluir Questão</button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- ======================================================== -->
    <!-- CARD 1: RESOLUÇÃO DA QUESTÃO (FIGMA PIXEL PERFECT)       -->
    <!-- ======================================================== -->
    <div class="card-questao" data-gabarito="<?php echo esc_attr($gabarito); ?>" data-permitir-tentativas="true">
      
      <div class="figma-tag-questao-top">
        QUESTÃO · <?php echo strtoupper(esc_html($nome_disciplina)); ?>
      </div>

      <div class="enunciado-box">
        <?php echo nl2br(esc_html($enunciado)); ?>
      </div>

      <div class="figma-selecione-aviso">Selecione uma alternativa.</div>

      <!-- Lista de Alternativas A-E com badges circulares -->
      <div class="lista-alternativas">
        <?php foreach (['a', 'b', 'c', 'd', 'e'] as $letra): 
          $texto_alt = get_field('alternativa_' . $letra) ?: get_post_meta( get_the_ID(), 'alternativa_' . $letra, true );
          if (!empty($texto_alt)): ?>
            <div class="alternativa" data-letra="<?php echo strtoupper($letra); ?>">
              <span class="figma-alt-circle"><?php echo strtoupper($letra); ?></span>
              <span><?php echo esc_html($texto_alt); ?></span>
            </div>
          <?php endif; 
        endforeach; ?>
      </div>

      <div class="figma-actions-row-res">
        <button id="btn-responder" class="btn-responder">Conferir resposta</button>
        <button type="button" class="btn-tentar-novamente" id="btn-tentar-novamente" onclick="location.reload();" style="display: none;">Tentar novamente</button>
      </div>

      <!-- Caixa de Feedback e Explicação Oficial do Figma -->
      <div id="feedback-box" class="feedback-box">
        <div id="feedback-titulo"></div>
        
        <div class="figma-exp-title">Por que essa é a resposta?</div>
        <p id="feedback-explicacao">
          <?php echo nl2br(esc_html($explicacao)); ?>
        </p>
        <div class="figma-referencia-nota">
          Referência conceitual: Banco de Questões Acadêmico — UNILAB. Síntese didática.
        </div>
      </div>

    </div>

    <!-- ======================================================== -->
    <!-- CARD 2: MATERIAIS DE APOIO (FIGMA PIXEL PERFECT)         -->
    <!-- ======================================================== -->
    <?php if ( $tem_materiais ) : ?>
      <div class="figma-card-materiais">
        <h3 class="figma-materiais-title">Materiais de apoio</h3>
        <p class="figma-materiais-sub">Imagens e leitura complementar publicadas pelo autor.</p>

        <!-- Galeria de Imagens Anexadas (RF22) -->
        <?php if ( ! empty( $imagens_anexadas ) ) : ?>
          <div class="bloco-imagens-anexadas">
            <?php foreach ( $imagens_anexadas as $idx_img => $img_url ) : 
              if ( empty($img_url) ) continue;
              $nome_arquivo_img = basename( parse_url($img_url, PHP_URL_PATH) ); ?>
              <div class="figma-img-card">
                <div class="figma-img-preview-box">
                  <a href="<?php echo esc_url($img_url); ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url($img_url); ?>" alt="Imagem complementar da questão">
                  </a>
                </div>
                <div class="figma-img-footer">
                  <div>
                    <div class="figma-img-name"><?php echo esc_html($nome_arquivo_img); ?></div>
                    <div class="figma-img-meta">Imagem complementar · Anexado ✓</div>
                  </div>
                  <a href="<?php echo esc_url($img_url); ?>" target="_blank" rel="noopener noreferrer" class="figma-btn-expand" title="Visualizar em alta resolução">
                    ⛶
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- PDF Anexado (RF23) -->
        <?php if ( ! empty( $pdf_url ) ) : ?>
          <div class="bloco-pdf-anexado">
            <div class="figma-pdf-left">
              <div class="figma-pdf-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              </div>
              <div>
                <div class="figma-pdf-name"><?php echo esc_html($pdf_nome); ?></div>
                <div class="figma-pdf-sub">Documento PDF · Material de Apoio</div>
              </div>
            </div>
            <a href="<?php echo esc_url($pdf_url); ?>" download target="_blank" rel="noopener noreferrer" class="btn-baixar-pdf">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Baixar <?php echo esc_html($pdf_nome); ?>
            </a>
          </div>
        <?php endif; ?>

        <!-- Link Externo Complementar (RF24) -->
        <?php if ( ! empty( $link_externo ) ) : ?>
          <div class="figma-link-ext-card">
            <a href="<?php echo esc_url($link_externo); ?>" target="_blank" rel="noopener noreferrer" class="link-externo">
              Material Complementar Oficial
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <div class="figma-link-ext-note">
              Link HTTPS. Abre em nova aba, sem acesso à aba de origem. Você sairá do Banco de Questões.
            </div>
          </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>

    <!-- Botão Inferior Voltar para os Resultados -->
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="btn-voltar-resultados">
      ← Voltar para os resultados
    </a>

  </div>

  <!-- Footer Oficial Figma -->
  <?php if ( function_exists('unilab_render_figma_footer') ) {
      unilab_render_figma_footer();
  } else { ?>
    <footer class="figma-footer">
      Banco de Questões · Aprender em comunidade.
    </footer>
  <?php } ?>

</div>

<!-- Script de Resolução do Assis (Exibe botão Tentar Novamente ao responder) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var btnResp = document.getElementById('btn-responder');
  var btnTentar = document.getElementById('btn-tentar-novamente');
  if (btnResp && btnTentar) {
    btnResp.addEventListener('click', function() {
      setTimeout(function() {
        var card = document.querySelector('.card-questao');
        if (card && (card.querySelector('.alternativa.correta') || card.querySelector('.alternativa.incorreta'))) {
          btnTentar.style.display = 'inline-block';
        }
      }, 100);
    });
  }
});
</script>

<?php 
get_footer(); 
?>
