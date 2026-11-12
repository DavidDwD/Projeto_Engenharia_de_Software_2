<?php
/*
 * Template Name: Início e Busca Figma
 */

// 1. Dados do Usuário
$usuario_atual = wp_get_current_user();
$esta_logado   = is_user_logged_in();
$nome_aluno    = $esta_logado ? ($usuario_atual->first_name ?: $usuario_atual->display_name) : 'Estudante';
$iniciais      = $esta_logado ? strtoupper( substr( trim($nome_aluno), 0, 2 ) ) : 'UN';

// 2. Parâmetros de Filtro e Busca
$termo_busca = isset($_GET['busca_q']) ? sanitize_text_field($_GET['busca_q']) : '';
$filtro_disc = isset($_GET['filtro_disc']) ? sanitize_text_field($_GET['filtro_disc']) : '';
$paged       = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : ( ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1 );

// URL base da página atual (sem query string), para uso nos forms e links
$url_pagina_atual = strtok( $_SERVER['REQUEST_URI'], '?' );

$args = array(
    'post_type'      => 'questao',
    'posts_per_page' => 6,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
);

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

<style>
  /* 1. ELIMINA O TÍTULO 'HOME' E CABEÇALHOS DO ASTRA */
  .entry-title,
  h1.entry-title,
  .entry-header,
  header.entry-header,
  .ast-single-post-order,
  .site-header,
  .ast-desktop-header,
  .ast-mobile-header,
  .site-footer {
    display: none !important;
  }

  /* 2. FORÇA A PÁGINA A OCUPAR 100% DA TELA */
  html, body {
    background-color: #f8fafc !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .site-content,
  #content,
  .ast-container,
  #primary,
  #main,
  article,
  .entry-content {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* 3. ESCOPO GERAL */
  .figma-root-scope {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important;
    background-color: #f8fafc !important;
    color: #0f172a !important;
    width: 100% !important;
    min-height: 100vh !important;
    box-sizing: border-box !important;
  }
  .figma-root-scope *,
  .figma-root-scope *::before,
  .figma-root-scope *::after {
    box-sizing: border-box !important;
  }

  /* 4. NAVBAR */
  .figma-navbar {
    width: 100% !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 14px 48px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 999 !important;
  }
  .figma-navbar-brand {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    text-decoration: none !important;
  }
  .figma-brand-icon {
    width: 32px !important;
    height: 32px !important;
    background: #0f284e !important;
    border-radius: 8px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #ffffff !important;
  }
  .figma-brand-text {
    font-size: 17px !important;
    font-weight: 800 !important;
    color: #0f284e !important;
    letter-spacing: -0.3px !important;
  }
  .figma-nav-links {
    display: flex !important;
    gap: 28px !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .figma-nav-links a {
    color: #475569 !important;
    text-decoration: none !important;
    font-size: 14px !important;
    font-weight: 500 !important;
  }
  .figma-nav-links a:hover,
  .figma-nav-links a.active {
    color: #0f284e !important;
    font-weight: 700 !important;
  }
  .figma-nav-user {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
  }
  .figma-avatar-circle {
    width: 36px !important;
    height: 36px !important;
    background: #dbeafe !important;
    color: #1e40af !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 700 !important;
    font-size: 13px !important;
  }
  .figma-user-meta { line-height: 1.2 !important; text-align: left !important; }
  .figma-user-name { font-size: 13px !important; font-weight: 700 !important; color: #0f284e !important; }
  .figma-user-role { font-size: 11px !important; color: #64748b !important; }

  /* 5. HERO */
  .figma-hero-section {
    text-align: center !important;
    padding: 50px 20px 30px 20px !important;
    max-width: 820px !important;
    margin: 0 auto !important;
  }
  .figma-hero-tagline {
    font-size: 11px !important;
    font-weight: 800 !important;
    letter-spacing: 1.2px !important;
    color: #2563eb !important;
    text-transform: uppercase !important;
    margin-bottom: 12px !important;
  }
  .figma-hero-title {
    font-size: 38px !important;
    font-weight: 800 !important;
    color: #0f284e !important;
    letter-spacing: -0.6px !important;
    margin: 0 0 10px 0 !important;
    line-height: 1.2 !important;
  }
  .figma-hero-subtitle {
    font-size: 15px !important;
    color: #64748b !important;
    margin: 0 0 28px 0 !important;
  }

  /* BARRA PÍLULA */
  .figma-search-pill {
    display: flex !important;
    align-items: center !important;
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 50px !important;
    padding: 5px 6px 5px 20px !important;
    box-shadow: 0 4px 14px rgba(15,40,78,0.05) !important;
    max-width: 680px !important;
    margin: 0 auto 10px auto !important;
    transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
  }
  .figma-search-pill:focus-within {
    border-color: #0f284e !important;
    box-shadow: 0 4px 18px rgba(15,40,78,0.1) !important;
  }
  .figma-search-pill svg { flex-shrink: 0 !important; margin-right: 10px !important; color: #64748b !important; }
  .figma-search-input {
    flex: 1 !important;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    font-size: 14px !important;
    color: #0f172a !important;
  }
  .figma-search-btn {
    background: #0f284e !important;
    color: #ffffff !important;
    border: none !important;
    padding: 10px 22px !important;
    border-radius: 40px !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    transition: background 0.15s ease !important;
  }
  .figma-search-btn:hover { background: #091a33 !important; }
  .figma-search-hint { font-size: 12px !important; color: #94a3b8 !important; }

  /* 6. CONTAINER PRINCIPAL */
  .figma-main-container {
    max-width: 1140px !important;
    margin: 30px auto 60px auto !important;
    padding: 0 20px !important;
  }

  /* CABEÇALHO DA LISTAGEM */
  .figma-section-header {
    display: flex !important;
    align-items: flex-end !important;
    justify-content: space-between !important;
    margin-bottom: 24px !important;
  }
  .figma-section-title { font-size: 22px !important; font-weight: 800 !important; color: #0f284e !important; margin: 0 0 4px 0 !important; }
  .figma-section-subtitle { font-size: 13px !important; color: #64748b !important; }
  .figma-btn-publicar {
    background: #0f284e !important;
    color: #ffffff !important;
    text-decoration: none !important;
    padding: 10px 18px !important;
    border-radius: 6px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    transition: background 0.15s ease !important;
  }
  .figma-btn-publicar:hover { background: #091a33 !important; color: #ffffff !important; }

  /* BARRA DE TAGS — DISCRETAS */
  .figma-active-tags-row {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 20px !important;
  }
  .figma-tags-left {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex-wrap: wrap !important;
  }
  .figma-tag-pill {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 20px !important;
    padding: 3px 8px 3px 12px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    transition: all 0.15s ease !important;
    user-select: none !important;
  }
  .figma-tag-pill:hover { background: #e2e8f0 !important; color: #1e293b !important; }
  .figma-tag-pill a {
    color: #94a3b8 !important;
    text-decoration: none !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 16px !important;
    height: 16px !important;
    border-radius: 50% !important;
    outline: none !important;
    transition: all 0.15s ease !important;
  }
  .figma-tag-pill a:hover { background: #cbd5e1 !important; color: #0f172a !important; }
  .figma-tag-pill a:focus { outline: none !important; }

  /* GRID 2 COLUNAS */
  .figma-layout-grid {
    display: grid !important;
    grid-template-columns: 260px 1fr !important;
    gap: 24px !important;
    align-items: start !important;
  }

  /* SIDEBAR */
  .figma-sidebar-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 22px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
  }
  .figma-sidebar-title { font-size: 16px !important; font-weight: 800 !important; color: #0f284e !important; margin: 0 0 16px 0 !important; }
  .figma-form-group { margin-bottom: 14px !important; }
  .figma-form-group label { display: block !important; font-size: 12px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 5px !important; }
  .figma-form-group select {
    width: 100% !important;
    padding: 9px 12px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 6px !important;
    font-size: 13px !important;
    color: #0f172a !important;
    background: #ffffff !important;
    outline: none !important;
    cursor: pointer !important;
  }
  .figma-sidebar-note { font-size: 11px !important; color: #94a3b8 !important; line-height: 1.4 !important; margin-bottom: 16px !important; }
  .figma-btn-aplicar {
    width: 100% !important;
    padding: 10px !important;
    background: #0f284e !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 6px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: background 0.15s ease !important;
  }
  .figma-btn-aplicar:hover { background: #091a33 !important; }
  .figma-link-limpar {
    display: block !important;
    text-align: center !important;
    color: #64748b !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    margin-top: 10px !important;
  }
  .figma-link-limpar:hover { color: #0f284e !important; }

  /* CARDS */
  .figma-cards-stack { display: flex !important; flex-direction: column !important; gap: 16px !important; }
  .figma-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 22px 24px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
    transition: box-shadow 0.15s ease, border-color 0.15s ease !important;
  }
  .figma-card:hover { box-shadow: 0 6px 18px rgba(15,40,78,0.06) !important; border-color: #cbd5e1 !important; }
  .figma-card-badges { display: flex !important; gap: 8px !important; margin-bottom: 10px !important; }
  .badge-primary { background: #e0f2fe !important; color: #0284c7 !important; font-size: 11px !important; font-weight: 700 !important; padding: 3px 8px !important; border-radius: 4px !important; }
  .badge-secondary { background: #f1f5f9 !important; color: #475569 !important; font-size: 11px !important; font-weight: 600 !important; padding: 3px 8px !important; border-radius: 4px !important; }
  .figma-card-title { font-size: 17px !important; font-weight: 700 !important; color: #0f284e !important; margin: 0 0 8px 0 !important; line-height: 1.35 !important; }
  .figma-card-title a { color: #0f284e !important; text-decoration: none !important; }
  .figma-card-title a:hover { color: #2563eb !important; }
  .figma-card-snippet { font-size: 13px !important; color: #475569 !important; line-height: 1.5 !important; margin: 0 0 18px 0 !important; }
  .figma-card-footer {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding-top: 14px !important;
    border-top: 1px solid #f1f5f9 !important;
  }
  .figma-meta-left { line-height: 1.3 !important; }
  .figma-meta-author { font-size: 12px !important; color: #64748b !important; font-weight: 600 !important; }
  .figma-meta-sub { font-size: 11px !important; color: #94a3b8 !important; }
  .figma-meta-right { display: flex !important; align-items: center !important; gap: 16px !important; }
  .figma-like-counter { display: flex !important; align-items: center !important; gap: 5px !important; color: #64748b !important; font-size: 12px !important; font-weight: 600 !important; }
  .figma-btn-resolver {
    background: transparent !important;
    color: #0f284e !important;
    border: 1.5px solid #0f284e !important;
    border-radius: 6px !important;
    padding: 7px 16px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    transition: all 0.15s ease !important;
  }
  .figma-btn-resolver:hover { background: #0f284e !important; color: #ffffff !important; }

  /* PAGINAÇÃO */
  .figma-pagination-row { display: flex !important; align-items: center !important; justify-content: space-between !important; margin-top: 35px !important; padding-top: 20px !important; }
  .figma-pagination-info { font-size: 12px !important; color: #64748b !important; }
  .figma-pagination-nav { display: flex !important; gap: 6px !important; }
  .figma-page-btn { padding: 6px 12px !important; background: #ffffff !important; border: 1px solid #cbd5e1 !important; border-radius: 6px !important; font-size: 12px !important; font-weight: 600 !important; color: #475569 !important; text-decoration: none !important; cursor: pointer !important; }
  .figma-page-btn.active { background: #0f284e !important; color: #ffffff !important; border-color: #0f284e !important; }

  /* RODAPÉ */
  .figma-footer { text-align: center !important; padding: 30px 20px !important; border-top: 1px solid #e2e8f0 !important; color: #94a3b8 !important; font-size: 12px !important; margin-top: 60px !important; }

  /* RESPONSIVIDADE MOBILE FIGMA (Início e busca — Mobile.svg) */
  @media (max-width: 860px) {
    .figma-navbar {
      padding: 12px 16px !important;
    }
    .figma-nav-links {
      display: none !important;
    }
    .figma-hero-section {
      padding: 32px 16px 28px 16px !important;
    }
    .figma-hero-title {
      font-size: 24px !important;
      line-height: 1.25 !important;
    }
    .figma-hero-subtitle {
      font-size: 13px !important;
    }
    .figma-search-bar {
      flex-direction: column !important;
      padding: 10px !important;
      border-radius: 12px !important;
    }
    .figma-search-input-box {
      width: 100% !important;
      margin-bottom: 8px !important;
    }
    .figma-btn-search {
      width: 100% !important;
      justify-content: center !important;
    }
    .figma-main-layout {
      flex-direction: column !important;
      padding: 0 16px !important;
      gap: 20px !important;
    }
    .figma-sidebar {
      width: 100% !important;
    }
    .figma-section-header {
      flex-direction: column !important;
      align-items: flex-start !important;
      gap: 12px !important;
    }
    .figma-btn-publicar {
      width: 100% !important;
      justify-content: center !important;
    }
  }
</style>

<div class="figma-root-scope">

  <!-- 1. Header -->
  <header class="figma-navbar">
    <a href="<?php echo home_url('/'); ?>" class="figma-navbar-brand">
      <div class="figma-brand-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
      <span class="figma-brand-text">Banco de Questões</span>
    </a>
    <ul class="figma-nav-links">
      <li><a href="<?php echo home_url('/'); ?>" class="active">Início</a></li>
      <li><a href="#">Favoritos</a></li>
      <li><a href="/cadastrar-questao/">Minhas questões</a></li>
    </ul>
    <div class="figma-nav-user">
      <div class="figma-avatar-circle"><?php echo esc_html( $iniciais ); ?></div>
      <div class="figma-user-meta">
        <div class="figma-user-name"><?php echo esc_html( $nome_aluno ); ?></div>
        <div class="figma-user-role"><?php echo $esta_logado ? 'Estudante' : 'Visitante'; ?></div>
      </div>
    </div>
  </header>

  <!-- 2. Hero + Busca -->
  <section class="figma-hero-section">
    <div class="figma-hero-tagline">
      OLÁ, <?php echo esc_html( strtoupper($nome_aluno) ); ?>. O QUE VAMOS APRENDER HOJE?
    </div>
    <h1 class="figma-hero-title">Uma pergunta. Novas possibilidades.</h1>
    <p class="figma-hero-subtitle">Encontre questões, aprofunde conceitos e estude no seu ritmo.</p>

    <form method="GET" action="<?php echo esc_attr( $url_pagina_atual ); ?>">
      <?php if ( ! empty($filtro_disc) ): ?>
        <input type="hidden" name="filtro_disc" value="<?php echo esc_attr($filtro_disc); ?>">
      <?php endif; ?>
      <div class="figma-search-pill">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" name="busca_q" class="figma-search-input" placeholder="Busque por assunto, palavra-chave ou conteúdo do enunciado." value="<?php echo esc_attr( $termo_busca ); ?>">
        <button type="submit" class="figma-search-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Buscar questões
        </button>
      </div>
      <div class="figma-search-hint">Busque por assunto, palavra-chave ou conteúdo do enunciado.</div>
    </form>
  </section>

  <!-- 3. Container Principal -->
  <main class="figma-main-container">

    <?php if ( isset($_GET['msg']) && $_GET['msg'] === 'questao_excluida' ): ?>
      <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 20px; border-radius: 8px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
        <span>🗑️</span> A questão foi excluída permanentemente pelo autor com sucesso.
      </div>
    <?php elseif ( isset($_GET['msg']) && $_GET['msg'] === 'questao_atualizada' ): ?>
      <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 20px; border-radius: 8px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
        <span>🎉</span> As alterações da questão foram salvas com sucesso pelo autor!
      </div>
    <?php endif; ?>

    <!-- Cabeçalho -->
    <div class="figma-section-header">
      <div>
        <h2 class="figma-section-title">Questões para explorar</h2>
        <div class="figma-section-subtitle">
          <?php echo esc_html( $total_questoes ); ?> resultados<?php echo !empty($termo_busca) ? ' para "' . esc_html($termo_busca) . '"' : ''; ?>
        </div>
      </div>
      <a href="/cadastrar-questao/" class="figma-btn-publicar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Publicar questão
      </a>
    </div>

    <!-- Tags Ativas Discretas (RF26, RF27) -->
    <div class="figma-active-tags-row">
      <div class="figma-tags-left">
        <?php $tem_filtro = false; ?>

        <?php if ( ! empty( $termo_busca ) ) : $tem_filtro = true; ?>
          <span class="figma-tag-pill">
            Busca: "<?php echo esc_html( $termo_busca ); ?>"
            <a href="<?php echo esc_attr( $url_pagina_atual . ( !empty($filtro_disc) ? '?filtro_disc=' . urlencode($filtro_disc) : '' ) ); ?>" title="Remover busca">✕</a>
          </span>
        <?php endif; ?>

        <?php if ( ! empty( $filtro_disc ) ) : $tem_filtro = true; ?>
          <span class="figma-tag-pill">
            Disciplina: <?php echo esc_html( !empty($filtro_disc_nome) ? $filtro_disc_nome : $filtro_disc ); ?>
            <a href="<?php echo esc_attr( $url_pagina_atual . ( !empty($termo_busca) ? '?busca_q=' . urlencode($termo_busca) : '' ) ); ?>" title="Remover filtro">✕</a>
          </span>
        <?php endif; ?>

        <?php if ( ! $tem_filtro ) : ?>
          <span style="font-size: 13px; color: #64748b;">Nenhum filtro ativo (exibindo todas as questões)</span>
        <?php endif; ?>
      </div>
      <div style="font-size: 12px; color: #64748b; font-weight: 600;">Mais recentes ↓</div>
    </div>

    <!-- Grid 2 Colunas -->
    <div class="figma-layout-grid">

      <!-- Sidebar Filtros (RF27) -->
      <aside class="figma-sidebar-card">
        <h3 class="figma-sidebar-title">Refinar busca</h3>
        <form method="GET" action="<?php echo esc_attr( $url_pagina_atual ); ?>">
          <?php if ( ! empty($termo_busca) ): ?>
            <input type="hidden" name="busca_q" value="<?php echo esc_attr($termo_busca); ?>">
          <?php endif; ?>

          <div class="figma-form-group">
            <label for="select-categoria">Categoria</label>
            <select id="select-categoria" name="categoria_fake">
              <option value="">Todas as Categorias</option>
              <option value="computacao">Computação</option>
              <option value="engenharia">Engenharia</option>
              <option value="ciencias">Ciências Exatas e Humanas</option>
            </select>
          </div>

          <div class="figma-form-group">
            <label for="select-subcategoria">Subcategoria</label>
            <select id="select-subcategoria" name="subcategoria_fake">
              <option value="">Selecione uma categoria primeiro</option>
            </select>
          </div>

          <div class="figma-form-group">
            <label for="select-disciplina">Disciplina</label>
            <select name="filtro_disc" id="select-disciplina">
              <option value="">Todas as Disciplinas</option>
              <?php
              $todas_disciplinas = get_terms( array(
                  'taxonomy'   => array( 'disciplina', 'categoria_disciplina' ),
                  'hide_empty' => false,
              ) );

              $opcoes_disciplinas = array();
              if ( ! empty( $todas_disciplinas ) && ! is_wp_error( $todas_disciplinas ) ) {
                  foreach ( $todas_disciplinas as $d ) {
                      $opcoes_disciplinas[$d->slug] = $d->name;
                  }
              }

              // Fallbacks padrão UNILAB caso o banco não tenha todos os termos ainda
              $padrao_unilab = array(
                  'engenharia-de-software'           => 'Engenharia de Software',
                  'algoritmos-e-estruturas-de-dados' => 'Algoritmos e Estruturas de Dados',
                  'redes-de-computadores'            => 'Redes de Computadores',
                  'calculo-diferencial'              => 'Cálculo Diferencial e Integral',
                  'humanidades-e-lusofonia'          => 'Humanidades e Lusofonia',
              );
              foreach ( $padrao_unilab as $slug_padrao => $nome_padrao ) {
                  if ( ! isset( $opcoes_disciplinas[$slug_padrao] ) ) {
                      $opcoes_disciplinas[$slug_padrao] = $nome_padrao;
                  }
              }

              foreach ( $opcoes_disciplinas as $d_slug => $d_nome ) {
                  $is_selected = ( $filtro_disc === $d_slug || $filtro_disc === $d_nome ) ? 'selected' : '';
                  echo '<option value="' . esc_attr( $d_slug ) . '" ' . $is_selected . '>' . esc_html( $d_nome ) . '</option>';
              }
              ?>
            </select>
          </div>

          <div class="figma-sidebar-note">A subcategoria seleciona automaticamente a disciplina correspondente.</div>

          <button type="submit" class="figma-btn-aplicar">Aplicar filtros</button>

          <?php if ( !empty($filtro_disc) || !empty($termo_busca) ): ?>
            <a href="<?php echo esc_attr( $url_pagina_atual ); ?>" class="figma-link-limpar">Limpar filtros</a>
          <?php endif; ?>
        </form>
      </aside>

      <!-- Script: Subcategoria dinâmica que atualiza o select de Disciplina com slug válido -->
      <script>
      (function() {
        var mapa = {
          'computacao': [
            { label: 'Engenharia de Software',  slug: 'engenharia-de-software' },
            { label: 'Algoritmos e Estruturas', slug: 'algoritmos-e-estruturas-de-dados' },
            { label: 'Redes de Computadores',   slug: 'redes-de-computadores' }
          ],
          'engenharia': [
            { label: 'Engenharia de Software',  slug: 'engenharia-de-software' },
            { label: 'Sistemas e Redes',        slug: 'redes-de-computadores' }
          ],
          'ciencias': [
            { label: 'Cálculo Diferencial',     slug: 'calculo-diferencial' },
            { label: 'Humanidades e Lusofonia', slug: 'humanidades-e-lusofonia' }
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

          // Ao escolher subcategoria, reflete automaticamente no select de Disciplina
          selSub.addEventListener('change', function() {
            if ( this.value ) {
              selDisc.value = this.value;
            }
          });
        }
      })();
      </script>

      <!-- Cards de Questão -->
      <section class="figma-cards-stack">
        <?php if ( $query_feed->have_posts() ) : ?>
          <?php while ( $query_feed->have_posts() ) : $query_feed->the_post();
              $terms = get_the_terms( get_the_ID(), 'disciplina' );
              if ( empty($terms) || is_wp_error($terms) ) {
                  $terms = get_the_terms( get_the_ID(), 'categoria_disciplina' );
              }
              if ( ! empty($terms) && ! is_wp_error($terms) ) {
                  $materia = $terms[0]->name;
              } else {
                  // Tenta ler o campo de categoria salvo via post meta (formulário de cadastro)
                  $materia_meta = get_post_meta( get_the_ID(), 'assunto', true );
                  $materia = ! empty($materia_meta) ? $materia_meta : 'Geral';
              }
            $enunciado_curto = wp_trim_words( get_field('enunciado') ?: get_the_content(), 22, '...' );
            $autor           = get_the_author() ?: 'David';
            $likes_ficticios = rand(8, 28);
          ?>
            <article class="figma-card">
              <div class="figma-card-badges">
                <span class="badge-primary"><?php echo esc_html( $materia ); ?></span>
                <span class="badge-secondary">Múltipla escolha</span>
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
                  <div class="figma-like-counter">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span><?php echo $likes_ficticios; ?></span>
                  </div>
                  <a href="<?php the_permalink(); ?>" class="figma-btn-resolver">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                      <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
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
                'class'     => 'figma-page-btn' // Mantém o seu estilo visual
            ) );
            ?>
          </div>
</div>

        <?php else : ?>
          <div class="figma-card" style="text-align:center; padding:45px 20px; color:#64748b;">
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#0f284e;">Nenhuma questão encontrada</p>
            <p style="font-size:13px; margin:0;">Tente buscar com outros termos ou limpar os filtros da barra lateral.</p>
          </div>
        <?php endif; ?>
      </section>

    </div>

  </main>

  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade.
  </footer>

</div>