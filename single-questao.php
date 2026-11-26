<?php
/**
 * Template de Visualização da Questão Individual (Single Questão)
 * Banco de Questões — UNILAB (Figma Pixel Perfect - Dynamic Fullscreen)
 */

$gabarito = get_field('resposta_correta') ?: get_post_meta( get_the_ID(), 'resposta_correta', true );
$enunciado = get_field('enunciado') ?: get_post_meta( get_the_ID(), 'enunciado', true ) ?: get_the_content();
$explicacao = get_field('explicacao') ?: get_post_meta( get_the_ID(), 'explicacao', true );
$link_externo = get_field('link_externo') ?: get_post_meta( get_the_ID(), 'link_externo', true );

$disciplinas = get_the_terms( get_the_ID(), 'disciplina' );
if ( empty( $disciplinas ) || is_wp_error( $disciplinas ) ) {
    $disciplinas = get_the_terms( get_the_ID(), 'categoria_disciplina' );
}
$nome_disciplina = ( ! empty( $disciplinas ) && ! is_wp_error( $disciplinas ) ) ? $disciplinas[0]->name : get_post_meta( get_the_ID(), 'categoria', true );
$slug_disciplina = ( ! empty( $disciplinas ) && ! is_wp_error( $disciplinas ) ) ? $disciplinas[0]->slug : sanitize_title( $nome_disciplina );
if ( empty( $nome_disciplina ) ) {
    $nome_disciplina = 'Engenharia de Software';
    $slug_disciplina = 'engenharia_software';
}
$nome_professor = get_post_meta( get_the_ID(), 'nome_professor', true );
$tags_questao   = get_the_terms( get_the_ID(), 'tag_questao' );
$ts_pub         = get_the_time( 'U' );
$ts_mod         = get_the_modified_time( 'U' );
$foi_editada    = ( $ts_mod && $ts_pub && ( $ts_mod - $ts_pub ) > 60 );

$usuario_atual_id = get_current_user_id();
$autor_id = intval( get_post_field( 'post_author', get_the_ID() ) );
$eh_autor = is_user_logged_in() && ( $usuario_atual_id === $autor_id );
$eh_admin_moderador = is_user_logged_in() && ! $eh_autor && current_user_can( 'manage_options' );

$nome_autor = get_the_author_meta( 'display_name', $autor_id ) ?: get_the_author_meta( 'user_login', $autor_id ) ?: get_the_author() ?: 'Estudante UNILAB';
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

// Interações Sociais (RF31, RF41)
$curtidas_total    = intval( get_post_meta( get_the_ID(), '_unilab_curtidas_total', true ) );
$curtidas_user     = is_user_logged_in() ? (array) get_user_meta( get_current_user_id(), '_unilab_curtidas_usuario', true ) : array();
$usuario_curtiu    = in_array( get_the_ID(), $curtidas_user );

$favoritos_user    = is_user_logged_in() ? (array) get_user_meta( get_current_user_id(), '_unilab_favoritos', true ) : array();
$usuario_favoritou = in_array( get_the_ID(), $favoritos_user );

$ja_denunciou_user = false;
if ( is_user_logged_in() ) {
    $den_existente = get_posts( array(
        'post_type'      => 'denuncia',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => 'id_questao', 'value' => get_the_ID() ),
            array( 'key' => 'id_usuario_denunciante', 'value' => get_current_user_id() ),
        ),
    ) );
    $ja_denunciou_user = ! empty( $den_existente );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php the_title(); ?> — Banco de Questões UNILAB</title>
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

    .figma-single-wrapper {
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
    body.admin-bar .figma-navbar { top: 32px; }
    @media (max-width: 782px) { body.admin-bar .figma-navbar { top: 46px; } }

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

    /* ÁREA DE CONTEÚDO PRINCIPAL DINÂMICA */
    .figma-single-main {
      flex: 1;
      width: 100%;
    }

    .figma-single-container {
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      padding: 36px 32px 60px 32px;
    }

    /* BREADCRUMB */
    .figma-breadcrumb {
      font-size: 13px;
      color: #1d4ed8;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .figma-breadcrumb a { color: #1d4ed8; text-decoration: none; }
    .figma-breadcrumb a:hover { text-decoration: underline; }
    .figma-breadcrumb span { color: #94a3b8; }

    /* BOTÃO VOLTAR */
    .btn-voltar-topo {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #102D5C;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      margin-bottom: 24px;
      padding: 6px 12px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      transition: all 0.15s ease;
    }

    .btn-voltar-topo:hover {
      background: #f1f5f9;
      color: #091a33;
    }

    /* BARRA DO AUTOR (RF34 / RF36) */
    .toolbar-autor {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 12px;
      padding: 16px 24px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .toolbar-autor-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .toolbar-autor-badge {
      background: #102D5C;
      color: #ffffff;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .toolbar-autor-text {
      font-size: 13px;
      color: #1e3a8a;
      font-weight: 600;
    }

    .toolbar-autor-acoes {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-autor-editar {
      background: #102D5C;
      color: #ffffff;
      font-size: 12px;
      font-weight: 700;
      padding: 9px 16px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background 0.15s ease;
    }

    .btn-autor-editar:hover {
      background: #091a33;
    }

    .btn-autor-excluir {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fca5a5;
      font-size: 12px;
      font-weight: 700;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }

    .btn-autor-excluir:hover {
      background: #b91c1c;
      color: #ffffff;
    }

    /* CARD 1: QUESTÃO */
    .card-questao {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 40px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .figma-tag-questao-top {
      font-size: 11px;
      font-weight: 800;
      color: #2563eb;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      margin-bottom: 14px;
    }

    .badge-disciplina {
      background: #e0f2fe;
      color: #0284c7;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 16px;
    }

    .questao-titulo {
      font-size: 24px;
      font-weight: 800;
      color: #102D5C;
      margin: 0 0 16px 0;
      line-height: 1.3;
      letter-spacing: -0.4px;
    }

    .enunciado-box {
      font-size: 16px;
      line-height: 1.7;
      color: #1e293b;
      margin-bottom: 28px;
    }

    .figma-selecione-aviso {
      font-size: 13px;
      font-weight: 700;
      color: #64748b;
      margin-bottom: 16px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* LISTA DE ALTERNATIVAS */
    .lista-alternativas {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
    }

    .alternativa {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 18px;
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      color: #1e293b;
      cursor: pointer;
      transition: all 0.15s ease;
      user-select: none;
    }

    .alternativa:hover {
      border-color: #102D5C;
      background: #f8fafc;
    }

    .alternativa.selecionada {
      border-color: #102D5C;
      background: #f0f4fa;
      font-weight: 600;
    }

    .alternativa.correta {
      border-color: #166447 !important;
      background: #EDF8F2 !important;
      color: #14532d !important;
      font-weight: 700 !important;
    }

    .alternativa.incorreta {
      border-color: #b91c1c !important;
      background: #fef2f2 !important;
      color: #991b1b !important;
    }

    .figma-alt-circle {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #f1f5f9;
      color: #475569;
      font-weight: 800;
      font-size: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: all 0.15s ease;
    }

    .alternativa.selecionada .figma-alt-circle {
      background: #102D5C;
      color: #ffffff;
    }

    .alternativa.correta .figma-alt-circle {
      background: #166447;
      color: #ffffff;
    }

    .alternativa.incorreta .figma-alt-circle {
      background: #b91c1c;
      color: #ffffff;
    }

    .figma-actions-row-res {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }

    .btn-responder {
      padding: 12px 28px;
      background: #102D5C;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s ease;
    }

    .btn-responder:hover {
      background: #091a33;
    }

    .btn-tentar-novamente {
      padding: 12px 24px;
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }

    /* FEEDBACK E EXPLICAÇÃO */
    .feedback-box {
      display: none;
      background: #EDF8F2;
      border: 1.5px solid #166447;
      border-radius: 10px;
      padding: 24px;
      margin-top: 24px;
      color: #14532d;
    }

    .feedback-box.ativo,
    .feedback-box.visivel {
      display: block !important;
    }

    #feedback-titulo {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 12px;
    }

    .figma-exp-title {
      font-size: 14px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      color: #166447;
    }

    #feedback-explicacao {
      font-size: 14px;
      line-height: 1.65;
      margin: 0 0 16px 0;
      color: #14532d;
    }

    .figma-referencia-nota {
      font-size: 11px;
      color: #15803d;
      border-top: 1px solid rgba(22, 100, 71, 0.2);
      padding-top: 10px;
    }

    /* CARD 2: MATERIAIS DE APOIO */
    .figma-card-materiais {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 36px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .figma-materiais-title {
      font-size: 18px;
      font-weight: 800;
      color: #102D5C;
      margin: 0 0 4px 0;
    }

    .figma-materiais-sub {
      font-size: 13px;
      color: #64748b;
      margin: 0 0 24px 0;
    }

    /* GALERIA DE IMAGENS */
    .bloco-imagens-anexadas {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .figma-img-card {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
      background: #ffffff;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .figma-img-preview-box {
      width: 100%;
      height: 180px;
      background: #f8fafc;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .figma-img-preview-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.2s ease;
    }

    .figma-img-preview-box img:hover {
      transform: scale(1.03);
    }

    .figma-img-footer {
      padding: 12px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1px solid #f1f5f9;
    }

    .figma-img-name { font-size: 12px; font-weight: 700; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 180px; }
    .figma-img-meta { font-size: 11px; color: #64748b; }
    .figma-btn-expand { color: #102D5C; text-decoration: none; font-size: 16px; font-weight: bold; }

    /* PDF ANEXADO */
    .bloco-pdf-anexado {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }

    .figma-pdf-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .figma-pdf-icon {
      width: 42px;
      height: 42px;
      background: #fee2e2;
      color: #dc2626;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .figma-pdf-name { font-size: 14px; font-weight: 700; color: #1e293b; }
    .figma-pdf-sub { font-size: 12px; color: #64748b; }

    .btn-baixar-pdf {
      background: #102D5C;
      color: #ffffff;
      padding: 10px 20px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s ease;
      white-space: nowrap;
    }

    .btn-baixar-pdf:hover {
      background: #091a33;
    }

    /* LINK EXTERNO */
    .figma-link-ext-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px 20px;
    }

    .link-externo {
      color: #1d4ed8;
      font-weight: 700;
      font-size: 14px;
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

    .btn-voltar-resultados {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 700;
      color: #102D5C;
      text-decoration: none;
      padding: 10px 18px;
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      margin-top: 10px;
      transition: all 0.15s ease;
    }

    .btn-voltar-resultados:hover {
      background: #f1f5f9;
    }

    /* MODAL DE CONFIRMAÇÃO DE EXCLUSÃO */
    .unilab-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .unilab-modal-card {
      background: #ffffff;
      border-radius: 12px;
      max-width: 480px;
      width: 100%;
      padding: 28px;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .unilab-modal-card h3 {
      font-size: 18px;
      font-weight: 800;
      color: #0f172a;
      margin: 0 0 10px 0;
    }

    .unilab-modal-card p {
      font-size: 14px;
      color: #64748b;
      line-height: 1.5;
      margin: 0 0 24px 0;
    }

    .unilab-modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    .btn-modal-cancelar {
      padding: 10px 18px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      color: #475569;
      cursor: pointer;
    }

    .btn-modal-confirmar {
      padding: 10px 20px;
      background: #dc2626;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      color: #ffffff;
      cursor: pointer;
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

    /* COMENTÁRIOS E DISCUSSÃO (RF37-RF40, RF43) */
    .figma-card-comentarios {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 32px 36px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .comentarios-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      gap: 12px;
      flex-wrap: wrap;
    }
    .comentarios-title {
      font-size: 20px;
      font-weight: 800;
      color: #102D5C;
      margin: 0;
    }
    .comentarios-count-pill {
      background: #e0f2fe;
      color: #0369a1;
      font-size: 12px;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 99px;
    }
    .comentario-form-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 24px;
    }
    .comentario-textarea {
      width: 100%;
      min-height: 82px;
      padding: 12px 14px;
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      color: #0f172a;
      background: #ffffff;
      resize: vertical;
      outline: none;
    }
    .comentario-textarea:focus {
      border-color: #102D5C;
      box-shadow: 0 0 0 3px rgba(16, 45, 92, 0.1);
    }
    .comentario-btn-primary {
      background: #102D5C !important;
      color: #ffffff !important;
      border: none !important;
      border-radius: 8px;
      padding: 9px 18px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
    .comentario-btn-primary:hover { background: #0b1f40 !important; }
    .comentario-item {
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px 18px;
      margin-bottom: 14px;
      background: #ffffff;
    }
    .comentario-item.comentario-resposta {
      margin-left: 34px;
      margin-top: 10px;
      margin-bottom: 0;
      background: #f8fafc;
      border-left: 3px solid #93c5fd;
    }
    .comentario-meta-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 8px;
    }
    .comentario-autor-info {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #475569;
    }
    .comentario-autor-nome { font-weight: 700; color: #0f172a; }
    .comentario-editado-badge {
      font-size: 11px;
      font-weight: 600;
      color: #64748b;
      background: #f1f5f9;
      padding: 1px 7px;
      border-radius: 99px;
    }
    .comentario-texto {
      font-size: 14px;
      line-height: 1.6;
      color: #1e293b;
      white-space: pre-line;
      margin-bottom: 10px;
    }
    .comentario-acoes {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .comentario-act-btn {
      background: #f8fafc !important;
      border: 1px solid #cbd5e1 !important;
      color: #334155 !important;
      font-size: 12px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 6px;
      cursor: pointer;
    }
    .comentario-act-btn:hover { background: #e2e8f0 !important; }
    .comentario-act-danger {
      color: #b91c1c !important;
      border-color: #fecaca !important;
      background: #fef2f2 !important;
    }
    .comentario-act-warn {
      color: #b45309 !important;
      border-color: #fde68a !important;
      background: #fffbeb !important;
    }
    .comentario-inline-box {
      margin-top: 12px;
      padding: 12px;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      display: none;
    }
    .comentario-feedback-msg {
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 14px;
      display: none;
    }

    /* RESPONSIVIDADE MOBILE FIGMA (Visualização da questão — Mobile.svg) */
    @media (max-width: 860px) {
      .figma-top-bar { padding: 0 16px; font-size: 10px; }
      .figma-navbar { padding: 12px 16px; }
      .figma-nav-links { display: none; }
      .figma-single-container { padding: 20px 16px 40px 16px; }
      .card-questao, .figma-card-materiais, .figma-card-comentarios { padding: 24px 16px; border-radius: 10px; }
      .comentario-item.comentario-resposta { margin-left: 16px; }
      .toolbar-autor { flex-direction: column; align-items: stretch; text-align: center; gap: 12px; }
      .toolbar-autor-acoes { flex-direction: column; width: 100%; }
      .btn-autor-editar, .btn-autor-excluir { width: 100%; justify-content: center; }
      .bloco-pdf-anexado { flex-direction: column; align-items: flex-start; }
      .btn-baixar-pdf { width: 100%; justify-content: center; }
      .bloco-imagens-anexadas { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="figma-single-wrapper">

  <!-- Faixa Institucional 35px -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <!-- Header / Navbar Oficial Figma -->
  <?php 
  if ( function_exists('unilab_render_figma_navbar') ) {
      unilab_render_figma_navbar('inicio');
  } else { ?>
    <header class="figma-navbar">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="figma-navbar-brand">
        <div class="figma-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <span class="figma-brand-text">Banco de Questões</span>
      </a>
      <ul class="figma-nav-links">
        <li><a href="<?php echo esc_url( home_url('/') ); ?>">Início</a></li>
        <li><a href="<?php echo esc_url( home_url('/?favoritos=1') ); ?>">Favoritos</a></li>
        <li><a href="<?php echo esc_url( home_url('/?minhas=1') ); ?>">Minhas questões</a></li>
      </ul>
      <div class="figma-nav-user">
        <div class="figma-avatar-circle"><?php echo esc_html( $iniciais_autor ); ?></div>
        <div class="figma-user-meta">
          <div class="figma-user-name"><?php echo esc_html( $nome_autor ); ?></div>
          <div class="figma-user-role">Estudante</div>
        </div>
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
  <?php } ?>

  <main class="figma-single-main">
    <div class="figma-single-container">

      <!-- Breadcrumb -->
      <nav class="figma-breadcrumb">
        <a href="<?php echo esc_url( home_url('/') ); ?>">Início</a>
        <span>›</span>
        <span>Questões</span>
        <span>›</span>
        <span><?php echo esc_html( $nome_disciplina ); ?></span>
      </nav>

      <a href="<?php echo esc_url( home_url('/') ); ?>" class="btn-voltar-topo">
        ← Voltar aos resultados
      </a>

      <!-- TOOLBAR DE GESTÃO DO AUTOR (RF34 / RF36) -->
      <?php if ( $eh_autor ) : ?>
        <div class="toolbar-autor">
          <div class="toolbar-autor-info">
            <span class="toolbar-autor-badge">Você é o autor</span>
            <span class="toolbar-autor-text">Gerencie ou atualize esta questão a qualquer momento.</span>
          </div>
          <div class="toolbar-autor-acoes">
            <a href="<?php echo esc_url( home_url('/cadastrar-questao/?editar=' . get_the_ID()) ); ?>" class="btn-autor-editar">
              ✏️ Editar Questão
            </a>
            <button type="button" class="btn-autor-excluir" onclick="document.getElementById('modal-excluir-questao').style.display='flex';">
              🗑️ Excluir
            </button>
          </div>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <div id="modal-excluir-questao" class="unilab-modal-overlay" style="display: none;">
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
      <?php elseif ( $eh_admin_moderador ) : ?>
        <div class="toolbar-autor" style="background:#f8fafc; border-color:#cbd5e1;">
          <div class="toolbar-autor-info">
            <span class="toolbar-autor-badge" style="background:#334155;">Modo Moderação</span>
            <span class="toolbar-autor-text" style="color:#334155;">Questão publicada por <strong><?php echo esc_html( $nome_autor ); ?></strong>. Use a Central de Moderação para ações administrativas.</span>
          </div>
          <div class="toolbar-autor-acoes">
            <a href="<?php echo esc_url( home_url('/central-de-moderacao/') ); ?>" class="btn-autor-editar">
              Central de Moderação
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- ======================================================== -->
      <!-- CARD 1: RESOLUÇÃO DA QUESTÃO                             -->
      <!-- ======================================================== -->
      <div class="card-questao" data-gabarito="<?php echo esc_attr($gabarito); ?>" data-permitir-tentativas="true">
        
        <div class="figma-tag-questao-top">
          QUESTÃO · <?php echo strtoupper(esc_html($nome_disciplina)); ?>
        </div>

        <span class="badge-disciplina"><a href="<?php echo esc_url( add_query_arg( 'filtro_disc', $slug_disciplina, home_url('/') ) ); ?>" style="color:inherit; text-decoration:none;"><?php echo esc_html($nome_disciplina); ?></a></span>

        <h1 class="questao-titulo"><?php the_title(); ?></h1>

        <!-- Metadados de Autor, Data Exata, Edição, Professor e Tags (RF21 / RF34 / RN08 / HU15) -->
        <div class="figma-questao-meta-bar" style="display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px; margin:-4px 0 20px 0; padding-bottom:14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#64748b;">
          <span>Por <strong style="color:#0f172a;"><?php echo esc_html( $nome_autor ); ?></strong></span>
          <span>Publicado em <strong style="color:#334155;"><?php echo esc_html( get_the_date( 'd/m/Y H:i' ) ); ?></strong></span>
          <?php if ( $foi_editada ) : ?>
            <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:12px;">Editado em <?php echo esc_html( get_the_modified_date( 'd/m/Y H:i' ) ); ?></span>
          <?php endif; ?>
          <?php if ( ! empty( $nome_professor ) ) : ?>
            <span style="background:#eff6ff; color:#1e40af; padding:2px 10px; border-radius:99px; font-size:12px; font-weight:600;">Prof.: <?php echo esc_html( $nome_professor ); ?></span>
          <?php endif; ?>
          <?php if ( ! empty( $tags_questao ) && ! is_wp_error( $tags_questao ) ) : ?>
            <span style="display:inline-flex; flex-wrap:wrap; gap:6px;">
              <?php foreach ( $tags_questao as $tg ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'busca_q', $tg->name, home_url('/') ) ); ?>" style="background:#f8fafc; border:1px solid #e2e8f0; color:#475569; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; text-decoration:none;">#<?php echo esc_html( $tg->name ); ?></a>
              <?php endforeach; ?>
            </span>
          <?php endif; ?>
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

        <!-- BARRA DE INTERAÇÕES SOCIAIS (RF31, RF41, HU32, HU37) -->
        <div class="figma-social-bar" style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Botão Curtir (RF31 / HU37) -->
            <form method="POST" action="" style="margin: 0; display: inline;">
              <?php wp_nonce_field( 'unilab_curtir_' . get_the_ID(), 'unilab_nonce' ); ?>
              <input type="hidden" name="unilab_acao_curtir" value="1">
              <input type="hidden" name="questao_id" value="<?php the_ID(); ?>">
              <button type="submit" id="btn-curtir-questao" class="btn-figma-social-like" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 20px; border: 1px solid <?php echo $usuario_curtiu ? '#ef4444' : '#cbd5e1'; ?>; background: <?php echo $usuario_curtiu ? '#fee2e2' : '#ffffff'; ?>; color: <?php echo $usuario_curtiu ? '#b91c1c' : '#475569'; ?>; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $usuario_curtiu ? '#ef4444' : 'none'; ?>" stroke="<?php echo $usuario_curtiu ? '#ef4444' : 'currentColor'; ?>" stroke-width="2">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span id="label-curtidas-total"><?php echo $curtidas_total; ?> <?php echo $curtidas_total === 1 ? 'curtida' : 'curtidas'; ?></span>
              </button>
            </form>

            <!-- Botão Favoritar / Salvar (RF41 / RF09 / HU32) -->
            <form method="POST" action="" style="margin: 0; display: inline;">
              <?php wp_nonce_field( 'unilab_favoritar_' . get_the_ID(), 'unilab_nonce' ); ?>
              <input type="hidden" name="unilab_acao_favoritar" value="1">
              <input type="hidden" name="questao_id" value="<?php the_ID(); ?>">
              <button type="submit" id="btn-favoritar-questao" class="btn-figma-social-fav" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 20px; border: 1px solid <?php echo $usuario_favoritou ? '#f59e0b' : '#cbd5e1'; ?>; background: <?php echo $usuario_favoritou ? '#fef3c7' : '#ffffff'; ?>; color: <?php echo $usuario_favoritou ? '#b45309' : '#475569'; ?>; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $usuario_favoritou ? '#f59e0b' : 'none'; ?>" stroke="<?php echo $usuario_favoritou ? '#f59e0b' : 'currentColor'; ?>" stroke-width="2">
                  <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
                <span id="label-favoritos-status"><?php echo $usuario_favoritou ? 'Salva nos favoritos' : 'Salvar nos favoritos'; ?></span>
              </button>
            </form>
          </div>

          <div style="font-size: 12px; color: #94a3b8;">
            ID da questão: #<?php the_ID(); ?>
          </div>
        </div>

        <!-- BOTÃO DE DENÚNCIA (MODERAÇÃO) -->
        <?php if ( is_user_logged_in() ) : ?>
          <?php if ( isset($_GET['denuncia']) && $_GET['denuncia'] === 'sucesso' ) : ?>
            <div style="background:#e6f4ea; color:#137333; padding:10px 16px; border-radius:8px; margin-top:12px; font-size:14px; font-weight:600;">
              ✓ Denúncia enviada para a moderação com sucesso!
            </div>
          <?php elseif ( isset($_GET['denuncia']) && $_GET['denuncia'] === 'duplicada' ) : ?>
            <div style="background:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; margin-top:12px; font-size:14px; font-weight:600;">
              ⚠️ Você já enviou uma denúncia pendente para esta questão. Ela já está na fila de análise.
            </div>
          <?php elseif ( isset($_GET['denuncia']) && $_GET['denuncia'] === 'invalida' ) : ?>
            <div style="background:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; margin-top:12px; font-size:14px; font-weight:600;">
              ❌ Por favor, informe o motivo e uma justificativa válida para enviar a denúncia.
            </div>
          <?php endif; ?>

          <div style="margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0;">
            <?php if ( $ja_denunciou_user ) : ?>
              <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#92400e; background:#fef3c7; border:1px solid #fde68a; border-radius:20px; padding:7px 14px; font-weight:600;">
                🚩 Você já denunciou esta questão (em análise)
              </span>
            <?php else : ?>
              <button type="button" id="btn-abrir-modal-denuncia" onclick="document.getElementById('modalDenuncia').style.display='flex'" style="background:none; border:1px solid #fecaca; border-radius:20px; padding:7px 14px; font-size:13px; color:#b91c1c; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:0.2s;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                Denunciar questão
              </button>
            <?php endif; ?>
          </div>
        <?php else : ?>
          <div style="margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0; font-size:13px; color:#64748b;">
            <a href="<?php echo esc_url( home_url('/log-in/') ); ?>" style="color:#102D5C; font-weight:700; text-decoration:underline;">Faça login</a> para curtir, salvar nos favoritos ou denunciar esta questão.
          </div>
        <?php endif; ?>

        <?php if ( is_user_logged_in() && ! $ja_denunciou_user ) : ?>

          <!-- Modal de Denúncia -->
          <div id="modalDenuncia" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
            <div style="background:#fff; border-radius:12px; max-width:480px; width:100%; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.2); font-family:sans-serif;">
              <h3 style="margin:0 0 8px 0; color:#102D5C;">Denunciar Questão</h3>
              <p style="margin:0 0 16px 0; font-size:13px; color:#64748b;">Ajude a manter a qualidade acadêmica do banco de questões.</p>
              
              <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="POST">
                <?php wp_nonce_field( 'enviar_denuncia_action', 'denuncia_nonce' ); ?>
                <input type="hidden" name="action" value="enviar_denuncia">
                <input type="hidden" name="id_questao" value="<?php echo get_the_ID(); ?>">

                <div style="margin-bottom:14px;">
                  <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:#334155;">Motivo da denúncia:</label>
                  <select name="motivo" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;">
                    <option value="Gabarito errado">Gabarito errado</option>
                    <option value="Enunciado incompleto">Enunciado incompleto</option>
                    <option value="Conteúdo ofensivo">Conteúdo ofensivo</option>
                    <option value="Spam ou Duplicada">Spam ou Duplicada</option>
                    <option value="Informação perigosa">Informação perigosa</option>
                  </select>
                </div>

                <div style="margin-bottom:16px;">
                  <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:#334155;">Detalhes ou justificativa:</label>
                  <textarea name="detalhes" rows="3" placeholder="Explique brevemente o problema..." required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; box-sizing:border-box; font-family:inherit;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                  <button type="button" onclick="document.getElementById('modalDenuncia').style.display='none'" style="background:#f1f5f9; border:none; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                  <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Enviar Denúncia</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>

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

      <!-- ======================================================== -->
      <!-- CARD 3: COMENTÁRIOS E DISCUSSÃO (RF37–RF40, RF43)        -->
      <!-- ======================================================== -->
      <?php
      $comentarios_todos = get_comments( array(
          'post_id' => get_the_ID(),
          'status'  => 'approve',
          'order'   => 'ASC',
      ) );
      $comentarios_raiz = array();
      $comentarios_filhos = array();
      foreach ( $comentarios_todos as $c_item ) {
          $pid_c = intval( $c_item->comment_parent );
          if ( $pid_c > 0 ) {
              $comentarios_filhos[ $pid_c ][] = $c_item;
          } else {
              $comentarios_raiz[] = $c_item;
          }
      }
      $comentario_nonce = wp_create_nonce( 'unilab_comentario_action' );
      ?>
      <section class="figma-card-comentarios" id="secao-comentarios">
        <div class="comentarios-header">
          <h3 class="comentarios-title">Comentários e Discussão</h3>
          <span class="comentarios-count-pill" id="comentarios-total-badge"><?php echo count( $comentarios_todos ); ?> comentário(s)</span>
        </div>

        <div class="comentario-feedback-msg" id="comentario-feedback-msg"></div>

        <?php if ( is_user_logged_in() ) : ?>
          <form class="comentario-form-box" id="form-novo-comentario">
            <label for="novo-comentario-texto" style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:8px;">
              Deixe um comentário ou dúvida sobre esta questão:
            </label>
            <textarea id="novo-comentario-texto" class="comentario-textarea" placeholder="Escreva seu comentário acadêmico (mínimo 2 caracteres)..." required minlength="2" maxlength="2000"></textarea>
            <div style="display:flex; justify-content:flex-end; margin-top:10px;">
              <button type="submit" class="comentario-btn-primary" id="btn-enviar-comentario">Publicar comentário</button>
            </div>
          </form>
        <?php else : ?>
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; margin-bottom:20px; font-size:13px; color:#475569;">
            <a href="<?php echo esc_url( home_url('/log-in/') ); ?>" style="color:#102D5C; font-weight:700; text-decoration:underline;">Faça login</a> para comentar, responder ou denunciar comentários nesta questão.
          </div>
        <?php endif; ?>

        <div id="lista-comentarios">
          <?php if ( empty( $comentarios_raiz ) ) : ?>
            <div id="comentarios-vazio" style="text-align:center; padding:24px; color:#64748b; font-size:14px;">
              Nenhum comentário publicado ainda. Seja o primeiro a contribuir!
            </div>
          <?php else : ?>
            <?php foreach ( $comentarios_raiz as $c ) :
                $cid       = intval( $c->comment_ID );
                $eh_c_aut  = is_user_logged_in() && ( intval( $c->user_id ) === $usuario_atual_id );
                $c_editado = get_comment_meta( $cid, '_unilab_editado_em', true );
                $respostas = $comentarios_filhos[ $cid ] ?? array();
            ?>
              <article class="comentario-item" id="comentario-<?php echo $cid; ?>" data-comment-id="<?php echo $cid; ?>">
                <div class="comentario-meta-row">
                  <div class="comentario-autor-info">
                    <span class="comentario-autor-nome"><?php echo esc_html( $c->comment_author ?: 'Estudante' ); ?></span>
                    <span>· <?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $c->comment_date ) ) ); ?></span>
                    <?php if ( ! empty( $c_editado ) ) : ?>
                      <span class="comentario-editado-badge">Editado em <?php echo esc_html( $c_editado ); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="comentario-texto" id="comentario-texto-<?php echo $cid; ?>"><?php echo esc_html( $c->comment_content ); ?></div>

                <?php if ( is_user_logged_in() ) : ?>
                  <div class="comentario-acoes">
                    <button type="button" class="comentario-act-btn btn-responder-comentario" data-cid="<?php echo $cid; ?>">Responder</button>
                    <?php if ( $eh_c_aut ) : ?>
                      <button type="button" class="comentario-act-btn btn-editar-comentario" data-cid="<?php echo $cid; ?>">Editar</button>
                      <button type="button" class="comentario-act-btn comentario-act-danger btn-excluir-comentario" data-cid="<?php echo $cid; ?>">Excluir</button>
                    <?php endif; ?>
                    <button type="button" class="comentario-act-btn comentario-act-warn btn-denunciar-comentario" data-cid="<?php echo $cid; ?>">Denunciar</button>
                  </div>

                  <!-- Form inline Responder (RF38) -->
                  <div class="comentario-inline-box" id="box-responder-<?php echo $cid; ?>">
                    <textarea class="comentario-textarea input-responder-texto" id="input-responder-<?php echo $cid; ?>" rows="2" placeholder="Escreva sua resposta..."></textarea>
                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                      <button type="button" class="comentario-act-btn btn-fechar-inline" data-target="box-responder-<?php echo $cid; ?>">Cancelar</button>
                      <button type="button" class="comentario-btn-primary btn-confirmar-resposta" data-parent="<?php echo $cid; ?>">Enviar resposta</button>
                    </div>
                  </div>

                  <?php if ( $eh_c_aut ) : ?>
                    <!-- Form inline Editar (RF39) -->
                    <div class="comentario-inline-box" id="box-editar-<?php echo $cid; ?>">
                      <textarea class="comentario-textarea input-editar-texto" id="input-editar-<?php echo $cid; ?>" rows="2"><?php echo esc_textarea( $c->comment_content ); ?></textarea>
                      <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                        <button type="button" class="comentario-act-btn btn-fechar-inline" data-target="box-editar-<?php echo $cid; ?>">Cancelar</button>
                        <button type="button" class="comentario-btn-primary btn-salvar-edicao-comentario" data-cid="<?php echo $cid; ?>">Salvar edição</button>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <div class="comentario-respostas-lista" id="respostas-de-<?php echo $cid; ?>">
                  <?php foreach ( $respostas as $r ) :
                      $rid       = intval( $r->comment_ID );
                      $eh_r_aut  = is_user_logged_in() && ( intval( $r->user_id ) === $usuario_atual_id );
                      $r_editado = get_comment_meta( $rid, '_unilab_editado_em', true );
                  ?>
                    <article class="comentario-item comentario-resposta" id="comentario-<?php echo $rid; ?>" data-comment-id="<?php echo $rid; ?>">
                      <div class="comentario-meta-row">
                        <div class="comentario-autor-info">
                          <span class="comentario-autor-nome"><?php echo esc_html( $r->comment_author ?: 'Estudante' ); ?></span>
                          <span>· <?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $r->comment_date ) ) ); ?></span>
                          <?php if ( ! empty( $r_editado ) ) : ?>
                            <span class="comentario-editado-badge">Editado em <?php echo esc_html( $r_editado ); ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="comentario-texto" id="comentario-texto-<?php echo $rid; ?>"><?php echo esc_html( $r->comment_content ); ?></div>

                      <?php if ( is_user_logged_in() ) : ?>
                        <div class="comentario-acoes">
                          <?php if ( $eh_r_aut ) : ?>
                            <button type="button" class="comentario-act-btn btn-editar-comentario" data-cid="<?php echo $rid; ?>">Editar</button>
                            <button type="button" class="comentario-act-btn comentario-act-danger btn-excluir-comentario" data-cid="<?php echo $rid; ?>">Excluir</button>
                          <?php endif; ?>
                          <button type="button" class="comentario-act-btn comentario-act-warn btn-denunciar-comentario" data-cid="<?php echo $rid; ?>">Denunciar</button>
                        </div>

                        <?php if ( $eh_r_aut ) : ?>
                          <div class="comentario-inline-box" id="box-editar-<?php echo $rid; ?>">
                            <textarea class="comentario-textarea input-editar-texto" id="input-editar-<?php echo $rid; ?>" rows="2"><?php echo esc_textarea( $r->comment_content ); ?></textarea>
                            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                              <button type="button" class="comentario-act-btn btn-fechar-inline" data-target="box-editar-<?php echo $rid; ?>">Cancelar</button>
                              <button type="button" class="comentario-btn-primary btn-salvar-edicao-comentario" data-cid="<?php echo $rid; ?>">Salvar edição</button>
                            </div>
                          </div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <?php if ( is_user_logged_in() ) : ?>
        <!-- Modal Confirmar Exclusão de Comentário (RF40 / RN06) -->
        <div id="modalExcluirComentario" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
          <div style="background:#fff; border-radius:12px; max-width:440px; width:100%; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 8px 0; color:#991b1b; font-size:18px; font-weight:800;">Excluir Comentário?</h3>
            <p style="margin:0 0 18px 0; font-size:13px; color:#475569; line-height:1.5;">Esta ação removerá permanentemente o comentário e eventuais respostas vinculadas.</p>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
              <button type="button" id="btn-cancelar-excluir-com" class="comentario-act-btn" style="padding:8px 16px;">Cancelar</button>
              <button type="button" id="btn-confirmar-excluir-com" style="background:#dc2626 !important; color:#fff !important; border:none; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;">Sim, excluir comentário</button>
            </div>
          </div>
        </div>

        <!-- Modal Denunciar Comentário (RF43 / HU34) -->
        <div id="modalDenunciaComentario" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
          <div style="background:#fff; border-radius:12px; max-width:480px; width:100%; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 8px 0; color:#102D5C; font-size:18px; font-weight:800;">Denunciar Comentário</h3>
            <p style="margin:0 0 16px 0; font-size:13px; color:#64748b;">Sinalize comentários inadequados ou ofensivos para análise da moderação.</p>
            <form id="form-denunciar-comentario">
              <input type="hidden" id="denuncia-comentario-id" value="0">
              <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:#334155;">Motivo da denúncia:</label>
                <select id="denuncia-comentario-motivo" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;">
                  <option value="Conteúdo ofensivo">Conteúdo ofensivo</option>
                  <option value="Spam ou Duplicada">Spam ou Duplicada</option>
                  <option value="Gabarito errado">Informação incorreta</option>
                  <option value="Informação perigosa">Informação perigosa</option>
                </select>
              </div>
              <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:#334155;">Justificativa:</label>
                <textarea id="denuncia-comentario-detalhes" rows="3" placeholder="Explique brevemente o motivo..." required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-family:inherit;"></textarea>
              </div>
              <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" id="btn-cancelar-denuncia-com" class="comentario-act-btn" style="padding:8px 16px;">Cancelar</button>
                <button type="submit" id="btn-enviar-denuncia-com" style="background:#dc2626 !important; color:#fff !important; border:none; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;">Enviar Denúncia</button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <a href="<?php echo esc_url( home_url('/') ); ?>" class="btn-voltar-resultados">
        ← Voltar para os resultados
      </a>

    </div>
  </main>

  <!-- Rodapé Oficial Figma -->
  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

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

  // Módulo de Comentários via AJAX (RF37, RF38, RF39, RF40, RF43)
  var COM_AJAX = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
  var COM_NONCE = <?php echo wp_json_encode( $comentario_nonce ); ?>;
  var Q_ID = <?php echo intval( get_the_ID() ); ?>;
  var msgEl = document.getElementById('comentario-feedback-msg');
  var comExcluirPendente = 0;

  function showComMsg(txt, ok) {
    if (!msgEl) return;
    msgEl.style.display = 'block';
    msgEl.style.background = ok ? '#f0fdf4' : '#fef2f2';
    msgEl.style.border = ok ? '1px solid #bbf7d0' : '1px solid #fecaca';
    msgEl.style.color = ok ? '#15803d' : '#b91c1c';
    msgEl.textContent = txt;
  }

  function escHtml(s) {
    return String(s || '').replace(/[&<>"]/g, function(c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
    });
  }

  function atualizarContagem(delta) {
    var badge = document.getElementById('comentarios-total-badge');
    if (!badge) return;
    var m = badge.textContent.match(/\d+/);
    var cur = m ? parseInt(m[0], 10) : 0;
    var nxt = Math.max(0, cur + delta);
    badge.textContent = nxt + ' comentário(s)';
  }

  function criarElementoComentario(c, isResposta) {
    var art = document.createElement('article');
    art.className = 'comentario-item' + (isResposta ? ' comentario-resposta' : '');
    art.id = 'comentario-' + c.id;
    art.dataset.commentId = String(c.id);
    art.innerHTML =
      '<div class="comentario-meta-row"><div class="comentario-autor-info">' +
      '<span class="comentario-autor-nome">' + escHtml(c.autor) + '</span>' +
      '<span>· ' + escHtml(c.data) + '</span></div></div>' +
      '<div class="comentario-texto" id="comentario-texto-' + c.id + '">' + escHtml(c.conteudo) + '</div>' +
      '<div class="comentario-acoes">' +
      (isResposta ? '' : '<button type="button" class="comentario-act-btn btn-responder-comentario" data-cid="' + c.id + '">Responder</button>') +
      '<button type="button" class="comentario-act-btn btn-editar-comentario" data-cid="' + c.id + '">Editar</button>' +
      '<button type="button" class="comentario-act-btn comentario-act-danger btn-excluir-comentario" data-cid="' + c.id + '">Excluir</button>' +
      '<button type="button" class="comentario-act-btn comentario-act-warn btn-denunciar-comentario" data-cid="' + c.id + '">Denunciar</button>' +
      '</div>' +
      (isResposta ? '' : '<div class="comentario-inline-box" id="box-responder-' + c.id + '"><textarea class="comentario-textarea input-responder-texto" id="input-responder-' + c.id + '" rows="2" placeholder="Escreva sua resposta..."></textarea><div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;"><button type="button" class="comentario-act-btn btn-fechar-inline" data-target="box-responder-' + c.id + '">Cancelar</button><button type="button" class="comentario-btn-primary btn-confirmar-resposta" data-parent="' + c.id + '">Enviar resposta</button></div></div>') +
      '<div class="comentario-inline-box" id="box-editar-' + c.id + '"><textarea class="comentario-textarea input-editar-texto" id="input-editar-' + c.id + '" rows="2">' + escHtml(c.conteudo) + '</textarea><div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;"><button type="button" class="comentario-act-btn btn-fechar-inline" data-target="box-editar-' + c.id + '">Cancelar</button><button type="button" class="comentario-btn-primary btn-salvar-edicao-comentario" data-cid="' + c.id + '">Salvar edição</button></div></div>' +
      (isResposta ? '' : '<div class="comentario-respostas-lista" id="respostas-de-' + c.id + '"></div>');
    return art;
  }

  async function postComAjax(params) {
    var body = new URLSearchParams(Object.assign({ nonce: COM_NONCE }, params));
    var resp = await fetch(COM_AJAX, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    });
    var json = await resp.json();
    if (!resp.ok || !json.success) {
      throw new Error((json && json.data && json.data.mensagem) || 'Erro na operação.');
    }
    return json.data;
  }

  var formNovo = document.getElementById('form-novo-comentario');
  if (formNovo) {
    formNovo.addEventListener('submit', async function(e) {
      e.preventDefault();
      var ta = document.getElementById('novo-comentario-texto');
      var txt = (ta.value || '').trim();
      if (txt.length < 2) { showComMsg('Digite ao menos 2 caracteres.', false); return; }
      try {
        var res = await postComAjax({ action: 'unilab_publicar_comentario', questao_id: Q_ID, parent_id: 0, conteudo: txt });
        var emptyEl = document.getElementById('comentarios-vazio');
        if (emptyEl) emptyEl.remove();
        document.getElementById('lista-comentarios').appendChild(criarElementoComentario(res.comentario, false));
        ta.value = '';
        atualizarContagem(1);
        showComMsg(res.mensagem, true);
      } catch (err) {
        showComMsg(err.message, false);
      }
    });
  }

  var secCom = document.getElementById('secao-comentarios');
  if (secCom) {
    secCom.addEventListener('click', async function(e) {
      var btn = e.target.closest('button');
      if (!btn) return;
      var cid = btn.dataset.cid || '';

      if (btn.classList.contains('btn-responder-comentario')) {
        var bResp = document.getElementById('box-responder-' + cid);
        if (bResp) bResp.style.display = bResp.style.display === 'block' ? 'none' : 'block';
      } else if (btn.classList.contains('btn-editar-comentario')) {
        var bEd = document.getElementById('box-editar-' + cid);
        if (bEd) bEd.style.display = bEd.style.display === 'block' ? 'none' : 'block';
      } else if (btn.classList.contains('btn-fechar-inline')) {
        var target = document.getElementById(btn.dataset.target);
        if (target) target.style.display = 'none';
      } else if (btn.classList.contains('btn-confirmar-resposta')) {
        var pid = btn.dataset.parent;
        var taR = document.getElementById('input-responder-' + pid);
        var txtR = (taR ? taR.value : '').trim();
        try {
          var resR = await postComAjax({ action: 'unilab_publicar_comentario', questao_id: Q_ID, parent_id: pid, conteudo: txtR });
          var boxRespostas = document.getElementById('respostas-de-' + resR.comentario.parent_id);
          if (boxRespostas) boxRespostas.appendChild(criarElementoComentario(resR.comentario, true));
          taR.value = '';
          document.getElementById('box-responder-' + pid).style.display = 'none';
          atualizarContagem(1);
          showComMsg(resR.mensagem, true);
        } catch (errR) {
          showComMsg(errR.message, false);
        }
      } else if (btn.classList.contains('btn-salvar-edicao-comentario')) {
        var taE = document.getElementById('input-editar-' + cid);
        var txtE = (taE ? taE.value : '').trim();
        try {
          var resE = await postComAjax({ action: 'unilab_editar_comentario', comentario_id: cid, conteudo: txtE });
          var txtEl = document.getElementById('comentario-texto-' + cid);
          if (txtEl) txtEl.textContent = resE.conteudo;
          document.getElementById('box-editar-' + cid).style.display = 'none';
          showComMsg(resE.mensagem, true);
        } catch (errE) {
          showComMsg(errE.message, false);
        }
      } else if (btn.classList.contains('btn-excluir-comentario')) {
        comExcluirPendente = parseInt(cid, 10);
        var mDel = document.getElementById('modalExcluirComentario');
        if (mDel) mDel.style.display = 'flex';
      } else if (btn.classList.contains('btn-denunciar-comentario')) {
        document.getElementById('denuncia-comentario-id').value = cid;
        document.getElementById('denuncia-comentario-detalhes').value = '';
        var mDen = document.getElementById('modalDenunciaComentario');
        if (mDen) mDen.style.display = 'flex';
      }
    });
  }

  var btnCancelExCom = document.getElementById('btn-cancelar-excluir-com');
  if (btnCancelExCom) {
    btnCancelExCom.addEventListener('click', function() {
      document.getElementById('modalExcluirComentario').style.display = 'none';
      comExcluirPendente = 0;
    });
  }
  var btnConfExCom = document.getElementById('btn-confirmar-excluir-com');
  if (btnConfExCom) {
    btnConfExCom.addEventListener('click', async function() {
      if (!comExcluirPendente) return;
      try {
        var resD = await postComAjax({ action: 'unilab_excluir_comentario', comentario_id: comExcluirPendente });
        var node = document.getElementById('comentario-' + comExcluirPendente);
        var removidos = 1 + (node ? node.querySelectorAll('.comentario-resposta').length : 0);
        if (node) node.remove();
        document.getElementById('modalExcluirComentario').style.display = 'none';
        comExcluirPendente = 0;
        atualizarContagem(-removidos);
        showComMsg(resD.mensagem, true);
      } catch (errD) {
        showComMsg(errD.message, false);
      }
    });
  }

  var btnCancelDenCom = document.getElementById('btn-cancelar-denuncia-com');
  if (btnCancelDenCom) {
    btnCancelDenCom.addEventListener('click', function() {
      document.getElementById('modalDenunciaComentario').style.display = 'none';
    });
  }
  var formDenCom = document.getElementById('form-denunciar-comentario');
  if (formDenCom) {
    formDenCom.addEventListener('submit', async function(e) {
      e.preventDefault();
      var cidD = document.getElementById('denuncia-comentario-id').value;
      var motD = document.getElementById('denuncia-comentario-motivo').value;
      var detD = document.getElementById('denuncia-comentario-detalhes').value;
      try {
        var resDen = await postComAjax({ action: 'unilab_denunciar_comentario', comentario_id: cidD, motivo: motD, detalhes: detD });
        document.getElementById('modalDenunciaComentario').style.display = 'none';
        showComMsg(resDen.mensagem, true);
      } catch (errDen) {
        document.getElementById('modalDenunciaComentario').style.display = 'none';
        showComMsg(errDen.message, false);
      }
    });
  }
});
</script>

<?php wp_footer(); ?>
</body>
</html>