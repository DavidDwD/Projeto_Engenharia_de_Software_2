<?php
/**
 * Template Name: Publicar e Editar Questão (Figma Pixel Perfect - Dynamic Fullscreen)
 * Banco de Questões — UNILAB
 */

// Se estiver em modo de teste/auditoria via preview_user, a função unilab_auto_login_preview_user já define o usuário atual.
$esta_logado = is_user_logged_in();
$mensagem_sucesso = '';
$mensagem_erro    = '';
$id_edicao = isset( $_GET['editar'] ) ? intval( $_GET['editar'] ) : 0;
$dados_preenchidos = array();
$permissao_negada = false;

// 1. Verificação de permissão para modo de edição (RF34)
if ( $id_edicao > 0 ) {
    $post_edicao = get_post( $id_edicao );
    if ( $post_edicao && $post_edicao->post_type === 'questao' ) {
        $autor_id = intval( $post_edicao->post_author );
        if ( $esta_logado && ( $autor_id === get_current_user_id() || current_user_can( 'edit_post', $id_edicao ) ) ) {
            $termos_disc = get_the_terms( $id_edicao, 'disciplina' );
            if ( empty( $termos_disc ) || is_wp_error( $termos_disc ) ) {
                $termos_disc = get_the_terms( $id_edicao, 'categoria_disciplina' );
            }
            $disc_slug = ( ! empty( $termos_disc ) && ! is_wp_error( $termos_disc ) ) ? $termos_disc[0]->slug : '';

            $dados_preenchidos = array(
                'id'               => $id_edicao,
                'assunto'          => get_the_title( $id_edicao ),
                'enunciado'        => get_post_meta( $id_edicao, 'enunciado', true ) ?: $post_edicao->post_content,
                'categoria'        => $disc_slug ?: get_post_meta( $id_edicao, 'categoria', true ),
                'alternativa_a'    => get_post_meta( $id_edicao, 'alternativa_a', true ),
                'alternativa_b'    => get_post_meta( $id_edicao, 'alternativa_b', true ),
                'alternativa_c'    => get_post_meta( $id_edicao, 'alternativa_c', true ),
                'alternativa_d'    => get_post_meta( $id_edicao, 'alternativa_d', true ),
                'alternativa_e'    => get_post_meta( $id_edicao, 'alternativa_e', true ),
                'resposta_correta' => get_post_meta( $id_edicao, 'resposta_correta', true ),
                'explicacao'       => get_post_meta( $id_edicao, 'explicacao', true ),
                'link_externo'     => get_post_meta( $id_edicao, 'link_externo', true ),
                'anexo_pdf'        => get_post_meta( $id_edicao, 'anexo_pdf', true ),
                'anexo_pdf_nome'   => get_post_meta( $id_edicao, 'anexo_pdf_nome', true ) ?: 'Documento PDF Anexado',
                'anexo_imagens'    => array_values( array_filter( (array) ( get_post_meta( $id_edicao, 'anexo_imagens', true ) ?: array() ) ) ),
            );
        } else {
            $permissao_negada = true;
        }
    }
}

// 2. Processamento do envio do formulário (Criação RF10 ou Edição RF34)
if ( $esta_logado && ! $permissao_negada && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $assunto   = sanitize_text_field( $_POST['assunto'] ?? '' );
    $enunciado = wp_kses_post( $_POST['enunciado'] ?? '' );
    $alt_a     = sanitize_text_field( $_POST['alternativa_a'] ?? '' );
    $alt_b     = sanitize_text_field( $_POST['alternativa_b'] ?? '' );
    $alt_c     = sanitize_text_field( $_POST['alternativa_c'] ?? '' );
    $alt_d     = sanitize_text_field( $_POST['alternativa_d'] ?? '' );
    $alt_e     = sanitize_text_field( $_POST['alternativa_e'] ?? '' );
    $gabarito  = strtoupper( trim( sanitize_text_field( $_POST['resposta_correta'] ?? '' ) ) );
    $explicacao = sanitize_textarea_field( $_POST['explicacao'] ?? '' );
    $categoria = sanitize_text_field( $_POST['categoria'] ?? '' );
    $id_post_salvar = isset( $_POST['id_questao_edicao'] ) ? intval( $_POST['id_questao_edicao'] ) : 0;

    // Preservar dados preenchidos no formulário
    $dados_preenchidos = array_merge( $dados_preenchidos, array(
        'assunto'          => $assunto,
        'enunciado'        => $enunciado,
        'categoria'        => $categoria,
        'alternativa_a'    => $alt_a,
        'alternativa_b'    => $alt_b,
        'alternativa_c'    => $alt_c,
        'alternativa_d'    => $alt_d,
        'alternativa_e'    => $alt_e,
        'resposta_correta' => $gabarito,
        'explicacao'       => $explicacao,
        'link_externo'     => esc_url_raw( $_POST['link_externo'] ?? '' ),
    ) );

    // Validação estrita de regras de negócio no servidor (RN08 / RF11-RF15)
    $alts_map = array('A' => $alt_a, 'B' => $alt_b, 'C' => $alt_c, 'D' => $alt_d, 'E' => $alt_e);
    if ( empty( $assunto ) ) {
        $mensagem_erro = 'Por favor, informe o assunto da questão (RF11).';
    } elseif ( empty( $enunciado ) ) {
        $mensagem_erro = 'Por favor, informe o enunciado da questão (RF12).';
    } elseif ( empty( $alt_a ) || empty( $alt_b ) ) {
        $mensagem_erro = 'A questão requer no mínimo as alternativas A e B preenchidas (RN08 / RF14).';
    } elseif ( empty( $gabarito ) ) {
        $mensagem_erro = 'Por favor, selecione qual alternativa é a resposta correta (RF14).';
    } elseif ( empty( $alts_map[$gabarito] ) ) {
        $mensagem_erro = "A alternativa ({$gabarito}) foi selecionada como resposta correta, mas não foi preenchida.";
    } elseif ( empty( $explicacao ) ) {
        $mensagem_erro = 'A explicação teórica da resposta correta é obrigatória (RN08 / RF13).';
    } elseif ( empty( $categoria ) ) {
        $mensagem_erro = 'Selecione uma disciplina para a questão (RF15).';
    } else {
        if ( $id_post_salvar > 0 ) {
            // Fluxo de Atualização (RF34)
            $post_atualizar = get_post( $id_post_salvar );
            if ( $post_atualizar && ( intval( $post_atualizar->post_author ) === get_current_user_id() || current_user_can( 'edit_post', $id_post_salvar ) ) ) {
                wp_update_post( array(
                    'ID'           => $id_post_salvar,
                    'post_title'   => $assunto,
                    'post_content' => $enunciado,
                ) );
                $post_id = $id_post_salvar;
                $mensagem_sucesso = '🎉 Questão atualizada com sucesso pelo autor!';
            } else {
                $permissao_negada = true;
            }
        } else {
            // Fluxo de Criação (RF10)
            $post_id = wp_insert_post( array(
                'post_title'   => $assunto,
                'post_content' => $enunciado,
                'post_type'    => 'questao',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ) );
            $mensagem_sucesso = '🎉 Questão publicada com sucesso!';
        }

    if ( ! empty( $post_id ) && ! is_wp_error( $post_id ) && ! $permissao_negada ) {
        update_post_meta( $post_id, 'assunto', $assunto );
        update_post_meta( $post_id, 'enunciado', $enunciado );
        update_post_meta( $post_id, 'alternativa_a', sanitize_text_field( $_POST['alternativa_a'] ?? '' ) );
        update_post_meta( $post_id, 'alternativa_b', sanitize_text_field( $_POST['alternativa_b'] ?? '' ) );
        update_post_meta( $post_id, 'alternativa_c', sanitize_text_field( $_POST['alternativa_c'] ?? '' ) );
        update_post_meta( $post_id, 'alternativa_d', sanitize_text_field( $_POST['alternativa_d'] ?? '' ) );
        update_post_meta( $post_id, 'alternativa_e', sanitize_text_field( $_POST['alternativa_e'] ?? '' ) );
        update_post_meta( $post_id, 'resposta_correta', sanitize_text_field( $_POST['resposta_correta'] ?? '' ) );
        update_post_meta( $post_id, 'explicacao', sanitize_textarea_field( $_POST['explicacao'] ?? '' ) );
        update_post_meta( $post_id, 'link_externo', esc_url_raw( $_POST['link_externo'] ?? '' ) );

        if ( ! empty( $_POST['categoria'] ) ) {
            $cat_selecionada = sanitize_text_field( $_POST['categoria'] );
            $mapa_nomes = array(
                'engenharia_software'              => 'Engenharia de Software',
                'engenharia-de-software'           => 'Engenharia de Software',
                'algoritmos'                       => 'Algoritmos e Estruturas de Dados',
                'algoritmos-e-estruturas-de-dados' => 'Algoritmos e Estruturas de Dados',
                'calculo'                          => 'Cálculo Diferencial e Integral',
                'calculo-diferencial'              => 'Cálculo Diferencial e Integral',
                'humanidades'                      => 'Humanidades e Lusofonia',
                'humanidades-e-lusofonia'          => 'Humanidades e Lusofonia',
                'redes'                            => 'Redes de Computadores',
                'redes-de-computadores'            => 'Redes de Computadores',
            );
            $nome_cat = isset( $mapa_nomes[$cat_selecionada] ) ? $mapa_nomes[$cat_selecionada] : $cat_selecionada;
            update_post_meta( $post_id, 'categoria', $nome_cat );
            wp_set_object_terms( $post_id, $nome_cat, 'disciplina', false );
            if ( taxonomy_exists( 'categoria_disciplina' ) ) {
                wp_set_object_terms( $post_id, $nome_cat, 'categoria_disciplina', false );
            }
        }

        // Upload de Arquivos e Remoção (RF17, RF18, RF35)
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // 1. Remoção Seletiva (RF35)
        if ( ! empty( $_POST['remover_pdf'] ) ) {
            delete_post_meta( $post_id, 'anexo_pdf' );
            delete_post_meta( $post_id, 'anexo_pdf_nome' );
        }

        if ( isset( $_POST['remover_imagem_idx'] ) && is_array( $_POST['remover_imagem_idx'] ) ) {
            $imgs_atuais = get_post_meta( $post_id, 'anexo_imagens', true );
            if ( ! is_array( $imgs_atuais ) ) {
                $imgs_atuais = array();
            } else {
                $imgs_atuais = array_values( array_filter( $imgs_atuais ) );
            }
            foreach ( $_POST['remover_imagem_idx'] as $idx_rem ) {
                unset( $imgs_atuais[ intval( $idx_rem ) ] );
            }
            update_post_meta( $post_id, 'anexo_imagens', array_values( array_filter( $imgs_atuais ) ) );
        }

        // 2. Upload de Documento PDF (RF17 / RN03 / RN04: máx 1 arquivo, formato PDF, máx 10 MB)
        if ( ! empty( $_FILES['anexo_pdf']['name'] ) ) {
            $pdf_file = $_FILES['anexo_pdf'];
            $pdf_ext  = strtolower( pathinfo( $pdf_file['name'], PATHINFO_EXTENSION ) );
            $limite_pdf = 10 * 1024 * 1024; // 10 MB

            if ( $pdf_ext === 'pdf' && $pdf_file['size'] <= $limite_pdf && $pdf_file['error'] === UPLOAD_ERR_OK ) {
                $upload_overrides = array( 'test_form' => false, 'mimes' => array( 'pdf' => 'application/pdf' ) );
                $upload_pdf = wp_handle_upload( $pdf_file, $upload_overrides );
                if ( isset( $upload_pdf['url'] ) && ! isset( $upload_pdf['error'] ) ) {
                    update_post_meta( $post_id, 'anexo_pdf', esc_url_raw( $upload_pdf['url'] ) );
                    update_post_meta( $post_id, 'anexo_pdf_nome', sanitize_file_name( $pdf_file['name'] ) );
                }
            }
        }

        // 3. Upload de Galeria de Imagens (RF18 / RN03 / RN05: máx 3 imagens, JPG/PNG, máx 5 MB cada)
        if ( ! empty( $_FILES['anexo_imagens']['name'] ) ) {
            $limite_img = 5 * 1024 * 1024;
            $mimes_img  = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png' );
            $imgs_banco = get_post_meta( $post_id, 'anexo_imagens', true );
            if ( ! is_array( $imgs_banco ) ) {
                $imgs_banco = array();
            } else {
                $imgs_banco = array_values( array_filter( $imgs_banco ) );
            }

            $f_img    = $_FILES['anexo_imagens'];
            $multiplo = is_array( $f_img['name'] );
            $total_f  = $multiplo ? count( $f_img['name'] ) : 1;

            for ( $k = 0; $k < $total_f; $k++ ) {
                if ( count( $imgs_banco ) >= 3 ) break;

                $nome_f = $multiplo ? $f_img['name'][$k] : $f_img['name'];
                $erro_f = $multiplo ? $f_img['error'][$k] : $f_img['error'];
                $tam_f  = $multiplo ? $f_img['size'][$k] : $f_img['size'];
                $tmp_f  = $multiplo ? $f_img['tmp_name'][$k] : $f_img['tmp_name'];
                $tipo_f = $multiplo ? $f_img['type'][$k] : $f_img['type'];

                if ( $erro_f !== UPLOAD_ERR_OK || empty( $nome_f ) ) continue;

                $ext_f = strtolower( pathinfo( $nome_f, PATHINFO_EXTENSION ) );
                if ( ! in_array( $ext_f, array( 'jpg', 'jpeg', 'png' ) ) || $tam_f > $limite_img ) {
                    continue;
                }

                $item_up = array(
                    'name'     => $nome_f,
                    'type'     => $tipo_f,
                    'tmp_name' => $tmp_f,
                    'error'    => $erro_f,
                    'size'     => $tam_f
                );

                $upload_res = wp_handle_upload( $item_up, array( 'test_form' => false, 'mimes' => $mimes_img ) );
                if ( isset( $upload_res['url'] ) && ! isset( $upload_res['error'] ) ) {
                    $imgs_banco[] = esc_url_raw( $upload_res['url'] );
                }
            }

            if ( ! empty( $imgs_banco ) ) {
                update_post_meta( $post_id, 'anexo_imagens', array_values( array_filter( array_slice( $imgs_banco, 0, 3 ) ) ) );
            }
        }

        $link_sucesso_questao = get_permalink( $post_id );
    }
    }
}

// Recarrega dados em caso de edição
if ( $id_edicao > 0 && ! $permissao_negada ) {
    $dados_preenchidos = array(
        'id'               => $id_edicao,
        'assunto'          => get_the_title( $id_edicao ),
        'enunciado'        => get_post_meta( $id_edicao, 'enunciado', true ) ?: get_post_field( 'post_content', $id_edicao ),
        'categoria'        => get_post_meta( $id_edicao, 'categoria', true ),
        'alternativa_a'    => get_post_meta( $id_edicao, 'alternativa_a', true ),
        'alternativa_b'    => get_post_meta( $id_edicao, 'alternativa_b', true ),
        'alternativa_c'    => get_post_meta( $id_edicao, 'alternativa_c', true ),
        'alternativa_d'    => get_post_meta( $id_edicao, 'alternativa_d', true ),
        'alternativa_e'    => get_post_meta( $id_edicao, 'alternativa_e', true ),
        'resposta_correta' => get_post_meta( $id_edicao, 'resposta_correta', true ),
        'explicacao'       => get_post_meta( $id_edicao, 'explicacao', true ),
        'link_externo'     => get_post_meta( $id_edicao, 'link_externo', true ),
        'anexo_pdf'        => get_post_meta( $id_edicao, 'anexo_pdf', true ),
        'anexo_pdf_nome'   => get_post_meta( $id_edicao, 'anexo_pdf_nome', true ) ?: 'Documento PDF Anexado',
        'anexo_imagens'    => array_values( array_filter( (array) ( get_post_meta( $id_edicao, 'anexo_imagens', true ) ?: array() ) ) ),
    );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $id_edicao > 0 ? 'Editar Questão Acadêmica' : 'Publicar Questão'; ?> — Banco de Questões UNILAB</title>
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

    .unilab-figma-page-layout {
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

    /* NAVBAR PRINCIPAL FIGMA */
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

    /* CONTEÚDO PRINCIPAL DINÂMICO */
    .figma-page-main {
      flex: 1;
      width: 100%;
    }

    .figma-form-wrapper {
      max-width: 1100px;
      width: 100%;
      margin: 0 auto;
      padding: 36px 32px 60px 32px;
    }

    /* BREADCRUMB */
    .figma-breadcrumb {
      font-size: 13px;
      color: #1d4ed8;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .figma-breadcrumb a { color: #1d4ed8; text-decoration: none; }
    .figma-breadcrumb a:hover { text-decoration: underline; }
    .figma-breadcrumb span { color: #94a3b8; }

    /* HERO HEADER */
    .figma-header-tag {
      font-size: 11px;
      font-weight: 800;
      color: #2563eb;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .figma-page-title {
      font-size: 32px;
      font-weight: 900;
      color: #102D5C;
      margin: 0 0 8px 0;
      letter-spacing: -0.6px;
      line-height: 1.2;
    }

    .figma-page-subtitle {
      font-size: 15px;
      color: #64748b;
      margin: 0 0 32px 0;
      line-height: 1.5;
    }

    /* SEÇÕES / CARDS DO FIGMA */
    .figma-card-section {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 36px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .figma-section-title {
      font-size: 18px;
      font-weight: 800;
      color: #102D5C;
      margin: 0 0 6px 0;
    }

    .figma-section-subtitle {
      font-size: 13px;
      color: #64748b;
      margin: 0 0 24px 0;
    }

    .figma-form-divider {
      border: none;
      border-top: 1px solid #f1f5f9;
      margin: 28px 0;
    }

    /* CAMPOS DE FORMULÁRIO */
    .figma-field-group {
      margin-bottom: 22px;
    }

    .figma-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .figma-label .obrigatorio {
      color: #ef4444;
      margin-left: 2px;
    }

    .figma-input,
    .figma-select,
    .figma-textarea {
      width: 100%;
      background: #ffffff;
      border: 1.5px solid #cbd5e1;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 14px;
      color: #0f172a;
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      font-family: inherit;
    }

    .figma-input:focus,
    .figma-select:focus,
    .figma-textarea:focus {
      border-color: #102D5C;
      box-shadow: 0 0 0 3px rgba(16, 45, 92, 0.12);
    }

    .figma-textarea {
      min-height: 120px;
      resize: vertical;
      line-height: 1.5;
    }

    .figma-input-hint {
      font-size: 12px;
      color: #64748b;
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* GRID DAS ALTERNATIVAS A-E */
    .figma-alternativas-stack {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .figma-alternativa-row {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .figma-alt-badge {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 14px;
      color: #475569;
      flex-shrink: 0;
    }

    .figma-alternativa-row input {
      flex: 1;
    }

    /* BOX DE UPLOAD DE ARQUIVOS */
    .figma-upload-box {
      border: 2px dashed #cbd5e1;
      border-radius: 10px;
      padding: 24px;
      background: #f8fafc;
      text-align: center;
      transition: border-color 0.2s ease, background 0.2s ease;
      cursor: pointer;
    }

    .figma-upload-box:hover {
      border-color: #102D5C;
      background: #f1f5f9;
    }

    .figma-upload-icon {
      color: #2563eb;
      margin-bottom: 8px;
    }

    .figma-upload-title {
      font-size: 14px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .figma-upload-rules {
      font-size: 12px;
      color: #64748b;
    }

    /* ALERTAS */
    .figma-banner-sucesso {
      background-color: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
      padding: 16px 20px;
      border-radius: 8px;
      font-weight: 700;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .figma-banner-sucesso a {
      background: #166534;
      color: #ffffff;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 13px;
    }

    .figma-banner-erro {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      padding: 16px 20px;
      border-radius: 8px;
      font-weight: 700;
      margin-bottom: 24px;
    }

    /* BARRA DE AÇÕES INFERIOR */
    .figma-actions-bar {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 16px;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid #e2e8f0;
    }

    .btn-cancelar {
      padding: 12px 24px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      background: #ffffff;
      color: #475569;
      font-weight: 700;
      font-size: 14px;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .btn-cancelar:hover {
      background: #f1f5f9;
      color: #0f172a;
    }

    .btn-publicar {
      padding: 12px 32px;
      border: none;
      border-radius: 8px;
      background: #102D5C;
      color: #ffffff;
      font-weight: 800;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s ease, transform 0.1s ease;
    }

    .btn-publicar:hover {
      background: #0b1f3f;
    }

    .btn-publicar:active {
      transform: translateY(1px);
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

    /* RESPONSIVIDADE MOBILE (Publicar questão — Mobile.svg) */
    @media (max-width: 860px) {
      .figma-top-bar { padding: 0 16px; font-size: 10px; }
      .figma-navbar { padding: 12px 16px; }
      .figma-nav-links { display: none; }
      .figma-form-wrapper { padding: 24px 16px 40px 16px; }
      .figma-card-section { padding: 24px 16px; border-radius: 10px; }
      .figma-page-title { font-size: 24px; }
      .figma-actions-bar { flex-direction: column; }
      .btn-cancelar, .btn-publicar { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="unilab-figma-page-layout">

  <!-- Faixa Institucional 35px -->
  <div class="figma-top-bar">
    Universidade da Integração Internacional da Lusofonia Afro-Brasileira · UNILAB
  </div>

  <!-- Navbar Oficial Figma -->
  <?php 
  if ( function_exists('unilab_render_figma_navbar') ) {
      unilab_render_figma_navbar('cadastrar');
  } else {
      $info = function_exists('unilab_get_current_student_info') ? unilab_get_current_student_info() : array('iniciais' => 'UN', 'nome' => 'Estudante', 'papel' => 'Acadêmico');
      ?>
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
          <div class="figma-avatar-circle"><?php echo esc_html( $info['iniciais'] ); ?></div>
          <div class="figma-user-meta">
            <div class="figma-user-name"><?php echo esc_html( $info['nome'] ); ?></div>
            <div class="figma-user-role"><?php echo esc_html( $info['papel'] ); ?></div>
          </div>
        </div>
      </header>
  <?php } ?>

  <main class="figma-page-main">
    <div class="figma-form-wrapper">

      <!-- Breadcrumb -->
      <nav class="figma-breadcrumb">
        <a href="<?php echo esc_url( home_url('/') ); ?>">Início</a>
        <span>›</span>
        <span><?php echo $id_edicao > 0 ? 'Editar Questão' : 'Publicar Questão'; ?></span>
      </nav>

      <?php if ( ! $esta_logado ) : ?>
        <!-- CARD DE ACESSO RESTRITO DESIGN FIGMA -->
        <div class="figma-card-section" style="text-align: center; padding: 48px 24px;">
          <div style="width: 56px; height: 56px; background: #fef3c7; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: #b45309; margin-bottom: 16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
          <h2 style="font-size: 24px; font-weight: 800; color: #102D5C; margin: 0 0 8px 0;">Acesso Restrito a Estudantes</h2>
          <p style="color: #64748b; font-size: 15px; max-width: 500px; margin: 0 auto 24px auto; line-height: 1.5;">
            Você precisa estar conectado à sua conta de estudante da UNILAB para publicar novas questões ou gerenciar seu conteúdo acadêmico.
          </p>
          <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="<?php echo esc_url( home_url('/log-in/') ); ?>" class="btn-publicar" style="text-decoration: none;">Acessar Minha Conta</a>
            <a href="<?php echo esc_url( home_url('/cadastro/') ); ?>" class="btn-cancelar">Criar Conta de Estudante</a>
          </div>
        </div>

      <?php elseif ( $permissao_negada ) : ?>
        <!-- BANNER DE PERMISSÃO NEGADA (RF34) -->
        <div class="figma-banner-erro" style="text-align: center; padding: 40px 20px;">
          <h3 style="margin: 0 0 8px 0; font-size: 20px;">Permissão Negada</h3>
          <p style="margin: 0; font-weight: normal; color: #7f1d1d;">Você não possui permissão para editar esta questão. Apenas o autor original pode modificá-la.</p>
          <a href="<?php echo esc_url( home_url('/') ); ?>" class="btn-cancelar" style="display: inline-block; margin-top: 20px;">Voltar para o Início</a>
        </div>

      <?php else : ?>

        <!-- CABEÇALHO DO FORMULÁRIO -->
        <div class="figma-header-tag">Contribuição Acadêmica</div>
        <h1 class="figma-page-title">
          <?php echo $id_edicao > 0 ? '✏️ Editar Questão Acadêmica — UNILAB (ID #' . $id_edicao . ')' : 'Publicar Nova Questão'; ?>
        </h1>
        <p class="figma-page-subtitle">
          Preencha os campos abaixo com atenção. Sua contribuição fortalece o banco de dados colaborativo e auxilia estudantes de toda a universidade.
        </p>

        <?php if ( ! empty( $mensagem_sucesso ) ) : ?>
          <div class="figma-banner-sucesso">
            <span><?php echo esc_html( $mensagem_sucesso ); ?></span>
            <?php if ( ! empty( $link_sucesso_questao ) ) : ?>
              <a href="<?php echo esc_url( $link_sucesso_questao ); ?>">👉 Ver Questão</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ( ! empty( $mensagem_erro ) ) : ?>
          <div class="figma-banner-erro">
            <?php echo esc_html( $mensagem_erro ); ?>
          </div>
        <?php endif; ?>

        <form id="form-cadastro-questao" method="POST" action="" enctype="multipart/form-data">
          <?php if ( $id_edicao > 0 ) : ?>
            <input type="hidden" name="id_questao_edicao" value="<?php echo esc_attr( $id_edicao ); ?>">
          <?php endif; ?>

          <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
          <div class="figma-card-section">
            <h3 class="figma-section-title">1. Informações Básicas da Questão</h3>
            <p class="figma-section-subtitle">Defina o título de referência e a disciplina correspondente.</p>

            <div class="figma-field-group">
              <label class="figma-label" for="assunto">Assunto / Título da Questão <span class="obrigatorio">*</span></label>
              <input type="text" id="assunto" name="assunto" class="figma-input" placeholder="Ex: Arquitetura MVC no Desenvolvimento Web Moderno" required value="<?php echo esc_attr( $dados_preenchidos['assunto'] ?? '' ); ?>">
              <div class="figma-input-hint">Um título claro e objetivo facilita a busca por outros estudantes.</div>
            </div>

            <div class="figma-field-group">
              <label class="figma-label" for="categoria">Disciplina / Área de Conhecimento <span class="obrigatorio">*</span></label>
              <select id="categoria" name="categoria" class="figma-select" required>
                <option value="">Selecione uma disciplina...</option>
                <option value="engenharia_software" <?php selected( ($dados_preenchidos['categoria'] ?? '') === 'engenharia_software' || ($dados_preenchidos['categoria'] ?? '') === 'engenharia-de-software' || ($dados_preenchidos['categoria'] ?? '') === 'Engenharia de Software' ); ?>>Engenharia de Software</option>
                <option value="algoritmos" <?php selected( ($dados_preenchidos['categoria'] ?? '') === 'algoritmos' || ($dados_preenchidos['categoria'] ?? '') === 'algoritmos-e-estruturas-de-dados' || ($dados_preenchidos['categoria'] ?? '') === 'Algoritmos e Estruturas de Dados' ); ?>>Algoritmos e Estruturas de Dados</option>
                <option value="calculo" <?php selected( ($dados_preenchidos['categoria'] ?? '') === 'calculo' || ($dados_preenchidos['categoria'] ?? '') === 'calculo-diferencial' || ($dados_preenchidos['categoria'] ?? '') === 'Cálculo Diferencial e Integral' ); ?>>Cálculo Diferencial e Integral</option>
                <option value="redes" <?php selected( ($dados_preenchidos['categoria'] ?? '') === 'redes' || ($dados_preenchidos['categoria'] ?? '') === 'redes-de-computadores' || ($dados_preenchidos['categoria'] ?? '') === 'Redes de Computadores' ); ?>>Redes de Computadores</option>
                <option value="humanidades" <?php selected( ($dados_preenchidos['categoria'] ?? '') === 'humanidades' || ($dados_preenchidos['categoria'] ?? '') === 'humanidades-e-lusofonia' || ($dados_preenchidos['categoria'] ?? '') === 'Humanidades e Lusofonia' ); ?>>Humanidades e Lusofonia</option>
              </select>
            </div>

            <div class="figma-field-group" style="margin-bottom: 0;">
              <label class="figma-label" for="enunciado">Enunciado Completo da Questão <span class="obrigatorio">*</span></label>
              <textarea id="enunciado" name="enunciado" class="figma-textarea" placeholder="Descreva aqui o problema, contextualização teórica e a pergunta principal..." required rows="5"><?php echo esc_textarea( $dados_preenchidos['enunciado'] ?? '' ); ?></textarea>
              <div class="figma-input-hint">Forneça todos os dados necessários para que a questão seja compreendida de forma independente.</div>
            </div>
          </div>

          <!-- SEÇÃO 2: ALTERNATIVAS E GABARITO -->
          <div class="figma-card-section">
            <h3 class="figma-section-title">2. Alternativas e Gabarito</h3>
            <p class="figma-section-subtitle">Preencha de 2 a 5 alternativas de múltipla escolha (A e B são obrigatórias; C, D e E são opcionais) e selecione o gabarito oficial.</p>

            <div class="figma-alternativas-stack">
              <div class="figma-alternativa-row">
                <span class="figma-alt-badge">A</span>
                <input type="text" name="alternativa_a" class="figma-input" placeholder="Texto da alternativa A (obrigatória)" required value="<?php echo esc_attr( $dados_preenchidos['alternativa_a'] ?? '' ); ?>">
              </div>

              <div class="figma-alternativa-row">
                <span class="figma-alt-badge">B</span>
                <input type="text" name="alternativa_b" class="figma-input" placeholder="Texto da alternativa B (obrigatória)" required value="<?php echo esc_attr( $dados_preenchidos['alternativa_b'] ?? '' ); ?>">
              </div>

              <div class="figma-alternativa-row">
                <span class="figma-alt-badge">C</span>
                <input type="text" name="alternativa_c" class="figma-input" placeholder="Texto da alternativa C (opcional)" value="<?php echo esc_attr( $dados_preenchidos['alternativa_c'] ?? '' ); ?>">
              </div>

              <div class="figma-alternativa-row">
                <span class="figma-alt-badge">D</span>
                <input type="text" name="alternativa_d" class="figma-input" placeholder="Texto da alternativa D (opcional)" value="<?php echo esc_attr( $dados_preenchidos['alternativa_d'] ?? '' ); ?>">
              </div>

              <div class="figma-alternativa-row">
                <span class="figma-alt-badge">E</span>
                <input type="text" name="alternativa_e" class="figma-input" placeholder="Texto da alternativa E (opcional)" value="<?php echo esc_attr( $dados_preenchidos['alternativa_e'] ?? '' ); ?>">
              </div>
            </div>

            <hr class="figma-form-divider">

            <div class="figma-field-group" style="margin-bottom: 0;">
              <label class="figma-label" for="resposta_correta">Alternativa Correta (Gabarito Oficial) <span class="obrigatorio">*</span></label>
              <select id="resposta_correta" name="resposta_correta" class="figma-select" required style="max-width: 320px;">
                <option value="">Selecione o gabarito...</option>
                <option value="A" <?php selected( strtoupper($dados_preenchidos['resposta_correta'] ?? '') === 'A' ); ?>>Alternativa A</option>
                <option value="B" <?php selected( strtoupper($dados_preenchidos['resposta_correta'] ?? '') === 'B' ); ?>>Alternativa B</option>
                <option value="C" <?php selected( strtoupper($dados_preenchidos['resposta_correta'] ?? '') === 'C' ); ?>>Alternativa C</option>
                <option value="D" <?php selected( strtoupper($dados_preenchidos['resposta_correta'] ?? '') === 'D' ); ?>>Alternativa D</option>
                <option value="E" <?php selected( strtoupper($dados_preenchidos['resposta_correta'] ?? '') === 'E' ); ?>>Alternativa E</option>
              </select>
              <div class="figma-input-hint">O gabarito ficará oculto até que o estudante submeta a resolução.</div>
            </div>
          </div>

          <!-- SEÇÃO 3: EXPLICAÇÃO E ANEXOS -->
          <div class="figma-card-section">
            <h3 class="figma-section-title">3. Explicação e Material de Apoio</h3>
            <p class="figma-section-subtitle">Enriqueça a questão com justificativas teóricas, documentos PDF e imagens.</p>

            <div class="figma-field-group">
              <label class="figma-label" for="explicacao">Explicação / Resolução Comentada</label>
              <textarea id="explicacao" name="explicacao" class="figma-textarea" placeholder="Explique passo a passo o porquê da alternativa correta ser a indicada e analise as pegadinhas..." rows="4"><?php echo esc_textarea( $dados_preenchidos['explicacao'] ?? '' ); ?></textarea>
            </div>

            <div class="figma-field-group">
              <label class="figma-label" for="link_externo">Link Externo Complementar</label>
              <input type="url" id="link_externo" name="link_externo" class="figma-input" placeholder="https://exemplo.org/artigo-ou-documentacao" value="<?php echo esc_attr( $dados_preenchidos['link_externo'] ?? '' ); ?>">
            </div>

            <!-- ANEXO PDF (RF17 / RF35) -->
            <div class="figma-field-group">
              <label class="figma-label">Documento PDF Complementar (Máx. 1 arquivo, até 10 MB)</label>
              
              <?php if ( ! empty( $dados_preenchidos['anexo_pdf'] ) ) : ?>
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                  <span style="font-size: 13px; font-weight: 600; color: #1e293b;">📄 <?php echo esc_html( $dados_preenchidos['anexo_pdf_nome'] ); ?></span>
                  <label style="font-size: 12px; color: #b91c1c; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" name="remover_pdf" value="1"> Remover este PDF
                  </label>
                </div>
              <?php endif; ?>

              <input type="file" id="anexo_pdf" name="anexo_pdf" class="figma-input" accept="application/pdf" style="padding-top: 10px;">
              <div class="figma-input-hint">Anexe notas de aula, artigos acadêmicos ou listas de exercícios em formato PDF.</div>
            </div>

            <!-- ANEXO IMAGENS (RF18 / RF35) -->
            <div class="figma-field-group" style="margin-bottom: 0;">
              <label class="figma-label">Galeria de Imagens Ilustrativas (Até 3 imagens JPG/PNG, máx. 5 MB cada)</label>

              <?php if ( ! empty( $dados_preenchidos['anexo_imagens'] ) ) : ?>
                <div style="display: flex; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
                  <?php foreach ( $dados_preenchidos['anexo_imagens'] as $idx => $img_url ) : ?>
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background: #ffffff; text-align: center; width: 130px;">
                      <img src="<?php echo esc_url( $img_url ); ?>" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 6px;">
                      <label style="font-size: 11px; color: #b91c1c; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <input type="checkbox" name="remover_imagem_idx[]" value="<?php echo $idx; ?>"> Remover
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <input type="file" id="anexo_imagens" name="anexo_imagens[]" class="figma-input" accept="image/png, image/jpeg, image/jpg" multiple style="padding-top: 10px;">
              <div class="figma-input-hint">Diagramas, esquemas, tabelas ou capturas conceituais.</div>
            </div>
          </div>

          <!-- BARRA DE AÇÕES -->
          <div class="figma-actions-bar">
            <a href="<?php echo esc_url( home_url('/') ); ?>" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-publicar">
              <span><?php echo $id_edicao > 0 ? 'Salvar Alterações da Questão' : 'Publicar Questão Acadêmica'; ?></span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
          </div>
        </form>

      <?php endif; ?>

    </div>
  </main>

  <footer class="figma-footer">
    Banco de Questões · Aprender em comunidade · Universidade da Integração Internacional da Lusofonia Afro-Brasileira (UNILAB).
  </footer>

</div>

<?php wp_footer(); ?>
</body>
</html>
