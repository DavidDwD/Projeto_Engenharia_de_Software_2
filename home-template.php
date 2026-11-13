<?php
/**
 * Template Name: Início e Busca Figma (Pixel Perfect - Dynamic Fullscreen)
 * Banco de Questões — UNILAB
 */

// 1. Dados do Usuário
$usuario_atual = wp_get_current_user();
$esta_logado   = is_user_logged_in();
$nome_aluno    = $esta_logado ? ($usuario_atual->first_name ?: $usuario_atual->display_name) : 'Estudante';
$iniciais      = $esta_logado ? strtoupper( substr( trim($nome_aluno), 0, 2 ) ) : 'UN';

// 2. Parâmetros de Filtro e Busca (RF25, RF26, RF27, RF08, RF09, RF31)
$termo_busca      = isset($_GET['busca_q']) ? sanitize_text_field($_GET['busca_q']) : '';
$filtro_disc      = isset($_GET['filtro_disc']) ? sanitize_text_field($_GET['filtro_disc']) : '';
$filtro_minhas    = ! empty($_GET['minhas']);
$filtro_favoritos = ! empty($_GET['favoritos']);
$ordenar          = isset($_GET['ordenar']) ? sanitize_text_field($_GET['ordenar']) : '';
$paged            = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );

// URL base da página atual (sem query string)
$url_pagina_atual = strtok( $_SERVER['REQUEST_URI'], '?' );

$args = array(
    'post_type'      => 'questao',
    'posts_per_page' => 6,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
);

$titulo_filtro_especial = '';

// Ordenação pelas mais bem avaliadas / curtidas (RF31 / HU23)
if ( $ordenar === 'curtidas' ) {
    $args['meta_key'] = '_unilab_curtidas_total';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'DESC';
}

// Filtro "Minhas Questões" (RF08 / HU08)
if ( $filtro_minhas ) {
    if ( $esta_logado ) {
        $args['author'] = get_current_user_id();
        $titulo_filtro_especial = 'Minhas Questões Publicadas';
    } else {
        $args['author'] = -1;
        $titulo_filtro_especial = 'Minhas Questões (requer login)';
    }
}

// Filtro "Favoritos" (RF09 / RF41 / HU09)
if ( $filtro_favoritos ) {
    if ( $esta_logado ) {
        $fav_ids = (array) get_user_meta( get_current_user_id(), '_unilab_favoritos', true );
        $fav_ids = array_values( array_filter( array_map( 'intval', $fav_ids ) ) );
        $args['post__in'] = ! empty( $fav_ids ) ? $fav_ids : array( 0 );
        $titulo_filtro_especial = 'Minhas Questões Favoritas';
    } else {
        $args['post__in'] = array( 0 );
        $titulo_filtro_especial = 'Questões Favoritas (requer login)';
    }
}

if ( ! empty($termo_busca) ) {
    $args['s'] = $termo_busca;
}

$filtro_disc_nome = '';
if ( ! empty($filtro_disc) ) {
    $term_obj = get_term_by( 'slug', $filtro_disc, 'disciplina' );
    if ( ! $term_obj ) {
        $term_obj = get_term_by( 'name', $filtro_disc, 'disciplina' );
    }
    if ( ! $term_obj && taxonomy_exists( 'categoria_disciplina' ) ) {
        $term_obj = get_term_by( 'slug', $filtro_disc, 'categoria_disciplina' );
        if ( ! $term_obj ) {
            $term_obj = get_term_by( 'name', $filtro_disc, 'categoria_disciplina' );
        }
    }

    $term_slug = $term_obj ? $term_obj->slug : sanitize_title( $filtro_disc );
    $term_name = $term_obj ? $term_obj->name : $filtro_disc;
    $filtro_disc_nome = $term_name;

    $args['tax_query'] = array(
        'relation' => 'OR',
        array('taxonomy' => 'disciplina',           'field' => 'slug', 'terms' => array( $term_slug, $filtro_disc )),
        array('taxonomy' => 'disciplina',           'field' => 'name', 'terms' => array( $term_name, $filtro_disc )),
        array('taxonomy' => 'categoria_disciplina', 'field' => 'slug', 'terms' => array( $term_slug, $filtro_disc )),
        array('taxonomy' => 'categoria_disciplina', 'field' => 'name', 'terms' => array( $term_name, $filtro_disc )),
    );
}

$query_feed     = new WP_Query( $args );
$total_questoes = $query_feed->found_posts;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Banco de Questões — UNILAB</title>
  <?php wp_head(); ?>
  <style>
    /* RESET GLOBAL E TELA CHEIA DINÂMICA */
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      min-height: 100vh !important;
      background-color: #f8fafc !important;
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

    .figma-home-layout {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      width: 100%;
      background-color: #f8fafc;
    }

    /* BARRA SUPERIOR INSTITUCIONAL (35px) */
    .figma-top-bar {
      width: 100%;
      height: 35px;
      background-color: #102D5C;
      display: flex;
      align-items: center;
      padding: 0 48px;
      font-size: 11px;
      font-weight: 700;
      color: #b9cdf0;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      flex-shrink: 0;
    }

    /* NAVBAR FIGMA */
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
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .figma-navbar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .figma-brand-icon {
      width: 34px;
      height: 34px;
      background: #102D5C;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
    }

    .figma-brand-text {
      font-size: 18px;
      font-weight: 800;
      color: #102D5C;
      letter-spacing: -0.3px;
    }

    .figma-nav-links {
      display: flex;
      gap: 32px;
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
      color: #102D5C;
      font-weight: 700;
    }

    .figma-nav-user {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .figma-avatar-circle {
      width: 36px;
      height: 36px;
      background: #dbeafe;
      color: #1e40af;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 800;
    }

    .figma-user-meta { line-height: 1.25; text-align: left; }
    .figma-user-name { font-size: 13px; font-weight: 700; color: #102D5C; }
    .figma-user-role { font-size: 11px; color: #64748b; }

    /* HERO BANNER FLUIDO (Figma fill #EAF0FC) */
    .figma-hero-banner {
      width: 100%;
      background: #EAF0FC;
      border-bottom: 1px solid #D9E0EA;
      padding: 56px 24px 48px 24px;
      text-align: center;
    }

    .figma-hero-inner {
      max-width: 860px;
      margin: 0 auto;
    }

    .figma-hero-tagline {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1.2px;
      color: #1d4ed8;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .figma-hero-title {
      font-size: 38px;
      font-weight: 900;
      color: #102D5C;
      letter-spacing: -0.6px;
      margin: 0 0 12px 0;
      line-height: 1.2;
    }

    .figma-hero-subtitle {
      font-size: 16px;
      color: #475569;
      margin: 0 0 32px 0;
      line-height: 1.5;
    }

    /* BARRA DE PESQUISA PÍLULA FIGMA */
    .figma-search-pill {
      display: flex;
      align-items: center;
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 50px;
      padding: 6px 8px 6px 24px;
      box-shadow: 0 4px 16px rgba(16, 45, 92, 0.06);
      max-width: 740px;
      margin: 0 auto 12px auto;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .figma-search-pill:focus-within {
      border-color: #102D5C;
      box-shadow: 0 4px 20px rgba(16, 45, 92, 0.12);
    }

    .figma-search-pill svg {
      flex-shrink: 0;
      margin-right: 12px;
      color: #64748b;
    }

    .figma-search-input {
      flex: 1;
      border: none;
      outline: none;
      background: transparent;
      font-size: 15px;
      color: #0f172a;
      font-family: inherit;
    }

    .figma-search-btn {
      background: #102D5C;
      color: #ffffff;
      border: none;
      padding: 12px 26px;
      border-radius: 40px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s ease;
    }

    .figma-search-btn:hover {
      background: #091a33;
    }

    .figma-search-hint {
      font-size: 12px;
      color: #64748b;
    }

    /* CONTAINER PRINCIPAL DO FEED */
    .figma-main-container {
      max-width: 1320px;
      width: 100%;
      margin: 36px auto 60px auto;
      padding: 0 32px;
      flex: 1;
    }

    /* CABEÇALHO DA LISTAGEM */
    .figma-section-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .figma-section-title {
      font-size: 24px;
      font-weight: 900;
      color: #102D5C;
      margin: 0 0 4px 0;
      letter-spacing: -0.4px;
    }

    .figma-section-subtitle {
      font-size: 13px;
      color: #64748b;
    }

    .figma-btn-publicar {
      background: #102D5C;
      color: #ffffff;
      text-decoration: none;
      padding: 11px 20px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s ease;
    }

    .figma-btn-publicar:hover {
      background: #091a33;
      color: #ffffff;
    }

    /* BARRA DE TAGS ATIVAS */
    .figma-active-tags-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .figma-tags-left {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .figma-tag-pill {
      background: #e2e8f0;
      color: #334155;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      padding: 4px 10px 4px 14px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .figma-tag-pill a {
      color: #64748b;
      text-decoration: none;
      font-size: 12px;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 16px;
      height: 16px;
      border-radius: 50%;
    }

    .figma-tag-pill a:hover {
      background: #cbd5e1;
      color: #0f172a;
    }

    /* GRID DO LAYOUT 2 COLUNAS */
    .figma-layout-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 32px;
      align-items: start;
    }

    /* SIDEBAR */
    .figma-sidebar-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
      position: sticky;
      top: 90px;
    }

    .figma-sidebar-title {
      font-size: 16px;
      font-weight: 800;
      color: #102D5C;
      margin: 0 0 18px 0;
    }

    .figma-form-group {
      margin-bottom: 16px;
    }

    .figma-form-group label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 6px;
    }

    .figma-form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1.5px solid #cbd5e1;
      border-radius: 6px;
      font-size: 13px;
      color: #0f172a;
      background: #ffffff;
      outline: none;
      font-family: inherit;
    }

    .figma-form-group select:focus {
      border-color: #102D5C;
    }

    .figma-sidebar-actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px solid #f1f5f9;
    }

    .figma-btn-filtrar {
      width: 100%;
      background: #102D5C;
      color: #ffffff;
      border: none;
      padding: 11px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }

    .figma-btn-limpar {
      width: 100%;
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      padding: 9px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      text-decoration: none;
    }

    /* STACK DE CARDS DE QUESTÃO */
    .figma-cards-stack {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .figma-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .figma-card:hover {
      box-shadow: 0 4px 14px rgba(16, 45, 92, 0.06);
      transform: translateY(-1px);
    }

    .figma-card-badges {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
    }

    .badge-primary {
      background: #e0f2fe;
      color: #0284c7;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
    }

    .badge-secondary {
      background: #f1f5f9;
      color: #475569;
      font-size: 11px;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 4px;
    }

    .figma-card-title {
      font-size: 18px;
      font-weight: 800;
      color: #102D5C;
      margin: 0 0 10px 0;
      line-height: 1.35;
    }

    .figma-card-title a {
      color: #102D5C;
      text-decoration: none;
    }

    .figma-card-title a:hover {
      color: #2563eb;
    }

    .figma-card-snippet {
      font-size: 14px;
      color: #475569;
      line-height: 1.55;
      margin: 0 0 18px 0;
    }

    .figma-card-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 16px;
      border-top: 1px solid #f1f5f9;
    }

    .figma-meta-left { line-height: 1.3; }
    .figma-meta-author { font-size: 12px; color: #64748b; font-weight: 600; }
    .figma-meta-sub { font-size: 11px; color: #94a3b8; }
    .figma-meta-right { display: flex; align-items: center; gap: 16px; }
    .figma-like-counter { display: flex; align-items: center; gap: 5px; color: #64748b; font-size: 12px; font-weight: 600; }

    .figma-btn-resolver {
      background: transparent;
      color: #102D5C;
      border: 1.5px solid #102D5C;
      border-radius: 6px;
      padding: 8px 18px;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }

    .figma-btn-resolver:hover {
      background: #102D5C;
      color: #ffffff;
    }

    /* PAGINAÇÃO */
    .figma-pagination-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 40px;
      padding-top: 24px;
      border-top: 1px solid #e2e8f0;
    }

    .figma-pagination-info { font-size: 12px; color: #64748b; }
    .figma-pagination-nav { display: flex; gap: 6px; }

    .figma-page-btn, .page-numbers {
      padding: 7px 14px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      color: #475569;
      text-decoration: none;
      cursor: pointer;
    }

    .page-numbers.current, .figma-page-btn.active {
      background: #102D5C !important;
      color: #ffffff !important;
      border-color: #102D5C !important;
    }

    /* RODAPÉ FIGMA */
    .figma-footer {
      width: 100%;
      background: #ffffff;
      border-top: 1px solid #e2e8f0;
      padding: 24px 32px;
      text-align: center;
      font-size: 12px;
      color: #64748b;
      flex-shrink: 0;
    }

    /* RESPONSIVIDADE MOBILE FIGMA (Início e busca — Mobile.svg) */
    @media (max-width: 860px) {
      .figma-top-bar { padding: 0 16px; font-size: 10px; }
      .figma-navbar { padding: 12px 16px; }
      .figma-nav-links { display: none; }
      .figma-hero-banner { padding: 36px 16px 32px 16px; }
      .figma-hero-title { font-size: 26px; }
      .figma-hero-subtitle { font-size: 14px; margin-bottom: 24px; }
      .figma-search-pill { flex-direction: column; padding: 10px; border-radius: 12px; gap: 8px; }
      .figma-search-input { width: 100%; text-align: center; }
      .figma-search-btn { width: 100%; justify-content: center; }
      .figma-main-container { padding: 0 16px; margin: 24px auto 40px auto; }
      .figma-layout-grid { grid-template-columns: 1fr; gap: 24px; }
      .figma-sidebar-card { position: static; }
      .figma-section-header { flex-direction: column; align-items: flex-start; gap: 14px; }
      .figma-btn-publicar { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="figma-home-layout">

  <!-- Faixa Institucional 35px -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <!-- Header / Navbar Oficial Figma -->
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
      <li><a href="<?php echo esc_url( home_url('/') ); ?>" class="<?php echo (! $filtro_favoritos && ! $filtro_minhas) ? 'active' : ''; ?>">Início</a></li>
      <li><a href="<?php echo esc_url( home_url('/?favoritos=1') ); ?>" class="<?php echo $filtro_favoritos ? 'active' : ''; ?>">Favoritos</a></li>
      <li><a href="<?php echo esc_url( home_url('/?minhas=1') ); ?>" class="<?php echo $filtro_minhas ? 'active' : ''; ?>">Minhas questões</a></li>
    </ul>
    <div class="figma-nav-user">
      <div class="figma-avatar-circle"><?php echo esc_html( $iniciais ); ?></div>
      <div class="figma-user-meta">
        <div class="figma-user-name"><?php echo esc_html( $nome_aluno ); ?></div>
        <div class="figma-user-role"><?php echo $esta_logado ? 'Estudante' : 'Visitante'; ?></div>
      </div>
    </div>
  </header>

  <!-- Hero Banner Fluido -->
  <section class="figma-hero-banner">
    <div class="figma-hero-inner">
      <div class="figma-hero-tagline">BANCO DE QUESTÕES · UNILAB</div>
      <h1 class="figma-hero-title">Pratique, Compartilhe e Evolua</h1>
      <p class="figma-hero-subtitle">Explore questões colaborativas elaboradas por estudantes e professores de todos os cursos e campi da UNILAB.</p>

      <form method="GET" action="<?php echo esc_url( $url_pagina_atual ); ?>" class="figma-search-pill">
        <?php if ( ! empty($filtro_disc) ) : ?>
          <input type="hidden" name="filtro_disc" value="<?php echo esc_attr($filtro_disc); ?>">
        <?php endif; ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" name="busca_q" class="figma-search-input" placeholder="Buscar por assunto, enunciado ou palavra-chave..." value="<?php echo esc_attr($termo_busca); ?>">
        <button type="submit" class="figma-search-btn">Buscar</button>
      </form>
      <div class="figma-search-hint">Ex: Algoritmos, Arquitetura MVC, Cálculo, Redes de Computadores...</div>
    </div>
  </section>

  <!-- Container Principal -->
  <main class="figma-main-container">

    <!-- Cabeçalho da Listagem -->
    <div class="figma-section-header">
      <div>
        <h2 class="figma-section-title">Questões Acadêmicas</h2>
        <div class="figma-section-subtitle">
          <?php echo $total_questoes; ?> questão(ões) encontrada(s) no repositório colaborativo
        </div>
      </div>
      <a href="<?php echo esc_url( home_url('/cadastrar-questao/') ); ?>" class="figma-btn-publicar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Publicar questão
      </a>
    </div>

    <!-- Tags Discretas de Filtro Ativo -->
    <?php if ( ! empty($termo_busca) || ! empty($filtro_disc_nome) ) : ?>
      <div class="figma-active-tags-row">
        <div class="figma-tags-left">
          <?php if ( ! empty($termo_busca) ) : ?>
            <span class="figma-tag-pill">
              Busca: <strong>"<?php echo esc_html($termo_busca); ?>"</strong>
              <a href="<?php echo esc_url( remove_query_arg('busca_q') ); ?>" title="Remover filtro de busca">✕</a>
            </span>
          <?php endif; ?>

          <?php if ( ! empty($filtro_disc_nome) ) : ?>
            <span class="figma-tag-pill">
              Disciplina: <strong><?php echo esc_html($filtro_disc_nome); ?></strong>
              <a href="<?php echo esc_url( remove_query_arg('filtro_disc') ); ?>" title="Remover filtro de disciplina">✕</a>
            </span>
          <?php endif; ?>
        </div>

        <a href="<?php echo esc_url( $url_pagina_atual ); ?>" style="font-size: 12px; color: #64748b; text-decoration: none; font-weight: 600;">
          Limpar todos os filtros
        </a>
      </div>
    <?php endif; ?>

    <!-- Layout Grid 2 Colunas -->
    <div class="figma-layout-grid">

      <!-- Sidebar de Filtros -->
      <aside class="figma-sidebar-card">
        <h3 class="figma-sidebar-title">Filtros Rápidos</h3>
        <form method="GET" action="<?php echo esc_url( $url_pagina_atual ); ?>">
          <?php if ( ! empty($termo_busca) ) : ?>
            <input type="hidden" name="busca_q" value="<?php echo esc_attr($termo_busca); ?>">
          <?php endif; ?>

          <div class="figma-form-group">
            <label for="select-categoria">Categoria / Curso</label>
            <select id="select-categoria" name="filtro_cat">
              <option value="">Todas as categorias</option>
              <option value="engenharia_software" <?php selected(isset($_GET['filtro_cat']) && $_GET['filtro_cat'] === 'engenharia_software'); ?>>Engenharia de Software</option>
              <option value="exatas" <?php selected(isset($_GET['filtro_cat']) && $_GET['filtro_cat'] === 'exatas'); ?>>Ciências Exatas</option>
              <option value="humanidades" <?php selected(isset($_GET['filtro_cat']) && $_GET['filtro_cat'] === 'humanidades'); ?>>Humanidades e Lusofonia</option>
            </select>
          </div>

          <div class="figma-form-group">
            <label for="select-subcategoria">Subcategoria</label>
            <select id="select-subcategoria" name="filtro_sub">
              <option value="">Todas as subcategorias</option>
            </select>
          </div>

          <div class="figma-form-group">
            <label for="select-disciplina">Disciplina Específica</label>
            <select id="select-disciplina" name="filtro_disc">
              <option value="">Todas as disciplinas</option>
              <option value="engenharia_software" <?php selected($filtro_disc === 'engenharia_software'); ?>>Engenharia de Software</option>
              <option value="algoritmos" <?php selected($filtro_disc === 'algoritmos'); ?>>Algoritmos e Estruturas</option>
              <option value="calculo" <?php selected($filtro_disc === 'calculo'); ?>>Cálculo Diferencial</option>
              <option value="redes" <?php selected($filtro_disc === 'redes'); ?>>Redes de Computadores</option>
              <option value="humanidades" <?php selected($filtro_disc === 'humanidades'); ?>>Humanidades e Lusofonia</option>
            </select>
          </div>

          <div class="figma-sidebar-actions">
            <button type="submit" class="figma-btn-filtrar">Aplicar Filtros</button>
            <a href="<?php echo esc_url( $url_pagina_atual ); ?>" class="figma-btn-limpar">Limpar Filtros</a>
          </div>
        </form>
      </aside>

      <!-- Feed de Questões -->
      <section class="figma-cards-stack">

        <!-- Barra de Status do Filtro e Ordenação (RF29, RF31) -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
          <div style="font-size:14px; color:#475569; font-weight:600;">
            <?php if ( ! empty($titulo_filtro_especial) ) : ?>
              <span style="background:#dbeafe; color:#1e40af; padding:5px 12px; border-radius:14px; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                📌 <?php echo esc_html($titulo_filtro_especial); ?>
                <a href="<?php echo esc_url( home_url('/') ); ?>" style="color:#1e40af; text-decoration:none; font-weight:bold; margin-left:4px;" title="Remover filtro">✕</a>
              </span>
            <?php elseif ( ! empty($termo_busca) ) : ?>
              Resultados para: <em>"<?php echo esc_html($termo_busca); ?>"</em>
            <?php elseif ( ! empty($filtro_disc_nome) ) : ?>
              Disciplina: <strong><?php echo esc_html($filtro_disc_nome); ?></strong>
            <?php else : ?>
              Todas as questões publicadas
            <?php endif; ?>
          </div>
          <div style="font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px;">
            <span>Ordenar:</span>
            <a href="<?php echo esc_url( add_query_arg('ordenar', 'recentes') ); ?>" style="color:<?php echo $ordenar !== 'curtidas' ? '#102D5C' : '#64748b'; ?>; font-weight:<?php echo $ordenar !== 'curtidas' ? '700' : '500'; ?>; text-decoration:none;">Mais recentes</a>
            <span>·</span>
            <a href="<?php echo esc_url( add_query_arg('ordenar', 'curtidas') ); ?>" style="color:<?php echo $ordenar === 'curtidas' ? '#102D5C' : '#64748b'; ?>; font-weight:<?php echo $ordenar === 'curtidas' ? '700' : '500'; ?>; text-decoration:none;">Mais curtidas</a>
          </div>
        </div>

        <?php if ( $query_feed->have_posts() ) : ?>
          <?php while ( $query_feed->have_posts() ) : $query_feed->the_post();
              $terms = get_the_terms( get_the_ID(), 'disciplina' );
              if ( empty($terms) || is_wp_error($terms) ) {
                  $terms = get_the_terms( get_the_ID(), 'categoria_disciplina' );
              }
              $materia = ( ! empty($terms) && ! is_wp_error($terms) ) ? $terms[0]->name : ( get_post_meta( get_the_ID(), 'categoria', true ) ?: 'Engenharia de Software' );
              $enunciado_curto = wp_trim_words( get_field('enunciado') ?: get_the_content(), 22, '...' );
              $autor           = get_the_author() ?: 'Estudante UNILAB';
              $likes_total     = intval( get_post_meta( get_the_ID(), '_unilab_curtidas_total', true ) );
              $esta_favoritado = $esta_logado && in_array( get_the_ID(), (array) get_user_meta( get_current_user_id(), '_unilab_favoritos', true ) );
          ?>
            <article class="figma-card">
              <div class="figma-card-badges">
                <span class="badge-primary"><?php echo esc_html( $materia ); ?></span>
                <span class="badge-secondary">Múltipla escolha</span>
                <?php if ( $esta_favoritado ) : ?>
                  <span class="badge-secondary" style="background:#fef3c7; color:#92400e; border-color:#fde68a;">⭐ Salva</span>
                <?php endif; ?>
                <?php if ( is_user_logged_in() && ( intval(get_the_author_meta('ID')) === get_current_user_id() || current_user_can('edit_post', get_the_ID()) ) ) : ?>
                  <a href="<?php echo esc_url( home_url('/cadastrar-questao/?editar=' . get_the_ID()) ); ?>" style="margin-left:auto; font-size:12px; font-weight:600; color:#0284c7; text-decoration:none; background:#e0f2fe; padding:3px 10px; border-radius:12px; display:inline-flex; align-items:center; gap:4px;">
                    ✏️ Editar minha questão
                  </a>
                <?php endif; ?>
              </div>
              <h3 class="figma-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h3>
              <p class="figma-card-snippet"><?php echo esc_html( $enunciado_curto ); ?></p>
              <div class="figma-card-footer">
                <div class="figma-meta-left">
                  <div class="figma-meta-author"><?php echo esc_html( $autor ); ?> · <?php echo get_the_date('d M Y'); ?></div>
                  <div class="figma-meta-sub"><?php echo esc_html( $materia ); ?> · Múltipla escolha</div>
                </div>
                <div class="figma-meta-right">
                  <div class="figma-like-counter" title="Total de curtidas desta questão">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span><?php echo $likes_total; ?></span>
                  </div>
                  <a href="<?php the_permalink(); ?>" class="figma-btn-resolver">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    Resolver questão
                  </a>
                </div>
              </div>
            </article>
          <?php endwhile; wp_reset_postdata(); ?>

          <div class="figma-pagination-row">
            <div class="figma-pagination-info">
              Página <?php echo max( 1, get_query_var('paged') ); ?> de <?php echo $query_feed->max_num_pages; ?> (Total: <?php echo $total_questoes; ?> questões)
            </div>
            <div class="figma-pagination-nav">
              <?php
              echo paginate_links( array(
                  'total'     => $query_feed->max_num_pages,
                  'current'   => max( 1, get_query_var('paged') ),
                  'prev_text' => 'Anterior',
                  'next_text' => 'Próxima',
                  'class'     => 'figma-page-btn'
              ) );
              ?>
            </div>
          </div>

        <?php else : ?>
          <div class="figma-card" style="text-align:center; padding:50px 20px; color:#64748b;">
            <p style="font-size:16px; font-weight:700; margin:0 0 8px 0; color:#102D5C;">
              <?php 
              if ( $filtro_favoritos ) {
                  echo 'Nenhuma questão favoritada ainda';
              } elseif ( $filtro_minhas ) {
                  echo 'Você ainda não publicou nenhuma questão';
              } else {
                  echo 'Nenhuma questão encontrada';
              }
              ?>
            </p>
            <p style="font-size:13px; margin:0 0 16px 0;">
              <?php 
              if ( $filtro_favoritos ) {
                  echo 'Explore o banco de questões e clique em "Salvar nos favoritos" em qualquer questão para revisá-la aqui.';
              } elseif ( $filtro_minhas ) {
                  echo 'Compartilhe seus conhecimentos com a comunidade acadêmica da UNILAB publicando sua primeira questão.';
              } else {
                  echo 'Tente buscar com outros termos ou limpar os filtros da barra lateral.';
              }
              ?>
            </p>
            <?php if ( $filtro_minhas ) : ?>
              <a href="<?php echo esc_url( home_url('/cadastrar-questao/') ); ?>" class="figma-btn-filtrar" style="display:inline-block; max-width:200px; text-decoration:none;">Publicar Questão</a>
            <?php else : ?>
              <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-btn-limpar" style="display:inline-block; max-width:180px; text-decoration:none;">Ver Todas as Questões</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>

  <!-- Rodapé Figma -->
  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

<script>
(function() {
  var mapa = {
    'engenharia_software': [
      { label: 'Engenharia de Software', slug: 'engenharia_software' },
      { label: 'Algoritmos e Estruturas', slug: 'algoritmos' },
      { label: 'Redes de Computadores', slug: 'redes' }
    ],
    'exatas': [
      { label: 'Cálculo Diferencial', slug: 'calculo' }
    ],
    'humanidades': [
      { label: 'Humanidades e Lusofonia', slug: 'humanidades' }
    ]
  };

  var selCat  = document.getElementById('select-categoria');
  var selSub  = document.getElementById('select-subcategoria');
  var selDisc = document.getElementById('select-disciplina');

  if ( selCat && selSub && selDisc ) {
    selCat.addEventListener('change', function() {
      var cat = this.value;
      selSub.innerHTML = '<option value="">Todas as subcategorias</option>';

      if ( mapa[cat] ) {
        mapa[cat].forEach(function(item) {
          var opt = document.createElement('option');
          opt.value = item.slug;
          opt.textContent = item.label;
          selSub.appendChild(opt);
        });
      } else {
        selSub.innerHTML = '<option value="">Selecione uma categoria primeiro</option>';
      }
    });

    selSub.addEventListener('change', function() {
      if ( this.value ) {
        selDisc.value = this.value;
      }
    });
  }
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
