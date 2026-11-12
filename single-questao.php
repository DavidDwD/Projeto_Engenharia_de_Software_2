<?php
/**
 * Template de Visualização da Questão Individual (Single Questão)
 * Banco de Questões - UNILAB
 */
get_header(); 

$gabarito = get_field('resposta_correta');
$enunciado = get_field('enunciado');
$explicacao = get_field('explicacao');
$link_externo = get_field('link_externo');
$disciplinas = get_the_terms( get_the_ID(), 'disciplina' );
if ( empty( $disciplinas ) || is_wp_error( $disciplinas ) ) {
    $disciplinas = get_the_terms( get_the_ID(), 'categoria_disciplina' );
}
$nome_disciplina = ( ! empty( $disciplinas ) && ! is_wp_error( $disciplinas ) ) ? $disciplinas[0]->name : get_post_meta( get_the_ID(), 'categoria', true );
if ( empty( $nome_disciplina ) ) {
    $nome_disciplina = 'Geral';
}

$usuario_atual_id = get_current_user_id();
$autor_id = intval( get_post_field( 'post_author', get_the_ID() ) );
$eh_autor = is_user_logged_in() && ( $usuario_atual_id === $autor_id || current_user_can( 'edit_post', get_the_ID() ) );
?>

<style>
  /* Trava o container central em 800px sempre */
  .unilab-single-wrapper {
    width: 100%;
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
    box-sizing: border-box;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  }
  
  /* O Card ocupa 100% dos 800px, mesmo se o texto for curto */
  .card-questao {
    width: 100%;
    box-sizing: border-box;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 35px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  }
  
  .badge-disciplina {
    display: inline-block;
    background: #0073aa;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 20px;
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .questao-titulo {
    font-size: 24px;
    color: #1e293b;
    margin: 0 0 8px 0;
    font-weight: 700;
    line-height: 1.3;
  }
  
  .questao-meta {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 25px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 15px;
  }
  
  .enunciado-box {
    font-size: 16px;
    line-height: 1.6;
    color: #334155;
    margin-bottom: 30px;
    background: #f8fafc;
    padding: 18px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
    width: 100%;
    box-sizing: border-box;
  }

  .lista-alternativas {
    width: 100%;
    box-sizing: border-box;
  }
  
  /* Cada alternativa agora ocupa 100% da largura sempre */
  .alternativa {
    display: flex;
    align-items: center;
    width: 100%;
    box-sizing: border-box;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 15px;
    color: #1e293b;
  }
  
  .alternativa:hover {
    border-color: #94a3b8;
    background: #f8fafc;
  }
  
  .alternativa.selecionada {
    border-color: #0073aa;
    background: #f0f7fb;
    font-weight: 600;
  }
  
  .alternativa.correta {
    border-color: #28a745 !important;
    background: #d4edda !important;
    color: #155724 !important;
    font-weight: 600;
  }
  
  .alternativa.incorreta {
    border-color: #dc3545 !important;
    background: #f8d7da !important;
    color: #721c24 !important;
  }
  
  .btn-responder {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 12px 28px;
    font-size: 15px;
    font-weight: 700;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 15px;
  }
  
  .btn-responder:hover {
    background: #005177;
  }
  
  .feedback-box {
    display: none;
    margin-top: 25px;
    padding: 20px;
    border-radius: 8px;
    line-height: 1.5;
    width: 100%;
    box-sizing: border-box;
  }
  
  .feedback-box.visivel {
    display: block;
  }

  /* Barra de Gestão do Autor (RF34, RF35, RF36) */
  .toolbar-autor {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px 18px;
    margin-bottom: 20px;
    font-size: 13px;
  }
  .toolbar-autor-info {
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .toolbar-autor-acoes {
    display: flex;
    gap: 10px;
  }
  .btn-autor-editar {
    background: #0284c7;
    color: #fff !important;
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.2s;
  }
  .btn-autor-editar:hover { background: #0369a1; }
  .btn-autor-excluir {
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.2s;
  }
  .btn-autor-excluir:hover { background: #dc2626; }

  /* Modal de Confirmação de Exclusão (RF36) */
  .unilab-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    z-index: 99999;
    justify-content: center;
    align-items: center;
  }
  .unilab-modal-card {
    background: #ffffff;
    max-width: 480px;
    width: 90%;
    border-radius: 12px;
    padding: 26px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    font-family: 'Segoe UI', system-ui, sans-serif;
  }
  .unilab-modal-card h3 {
    margin: 0 0 10px 0;
    color: #991b1b;
    font-size: 19px;
  }
  .unilab-modal-card p {
    color: #475569;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 22px 0;
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
    font-weight: 600;
    cursor: pointer;
  }
  .btn-modal-confirmar {
    background: #dc2626;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
  }
</style>

<div class="unilab-single-wrapper">
  <?php if ( $eh_autor ) : ?>
    <!-- Toolbar de Ações do Autor (RF34, RF35, RF36) -->
    <div class="toolbar-autor">
      <div class="toolbar-autor-info">
        <span>⚙️</span> Painel do Autor: você publicou esta questão
      </div>
      <div class="toolbar-autor-acoes">
        <a href="<?php echo esc_url( home_url( '/cadastrar-questao/?editar=' . get_the_ID() ) ); ?>" class="btn-autor-editar">
          ✏️ Editar Questão
        </a>
        <button type="button" class="btn-autor-excluir" onclick="document.getElementById('modal-excluir-questao').style.display='flex';">
          🗑️ Excluir
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
  <!-- Container que o script resolucao-questoes.js do Assis reconhece -->
  <div class="card-questao" data-gabarito="<?php echo esc_attr($gabarito); ?>" data-permitir-tentativas="true">
    
    <span class="badge-disciplina"><?php echo esc_html($nome_disciplina); ?></span>
    <h1 class="questao-titulo"><?php the_title(); ?></h1>
    
    <div class="questao-meta">
      Postado por <strong><?php the_author(); ?></strong> em <?php echo get_the_date('d/m/Y'); ?>
    </div>

    <div class="enunciado-box">
      <?php echo nl2br(esc_html($enunciado)); ?>
    </div>

    <?php 
    // RF22 / HU16: Exibição de imagens embutidas de apoio
    $imagens_anexadas = get_post_meta( get_the_ID(), 'anexo_imagens', true );
    if ( is_array( $imagens_anexadas ) ) {
        $imagens_anexadas = array_values( array_filter( $imagens_anexadas ) );
    }
    if ( ! empty( $imagens_anexadas ) && is_array( $imagens_anexadas ) ) : ?>
      <div class="bloco-imagens-anexadas" style="margin: 20px 0; display: flex; flex-wrap: wrap; gap: 14px;">
        <?php foreach ( $imagens_anexadas as $img_url ) : 
          if ( empty( $img_url ) ) continue; ?>
          <a href="<?php echo esc_url($img_url); ?>" target="_blank" rel="noopener noreferrer" style="border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; display: block; box-shadow: 0 2px 6px rgba(0,0,0,0.06); text-decoration: none;">
            <img src="<?php echo esc_url($img_url); ?>" alt="Imagem complementar da questão" style="max-height: 220px; max-width: 100%; object-fit: contain; display: block; background: #f8fafc;">
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php 
    // RF23 / HU17: Download do documento PDF anexado
    $pdf_url = get_post_meta( get_the_ID(), 'anexo_pdf', true );
    $pdf_nome = get_post_meta( get_the_ID(), 'anexo_pdf_nome', true ) ?: 'Material de Apoio (PDF)';
    if ( ! empty( $pdf_url ) ) : ?>
      <div class="bloco-pdf-anexado" style="margin: 18px 0 24px 0;">
        <a href="<?php echo esc_url($pdf_url); ?>" download target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 10px 18px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 14px; transition: background 0.2s;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          Baixar <?php echo esc_html($pdf_nome); ?>
        </a>
      </div>
    <?php endif; ?>

    <!-- Lista de Alternativas A-E -->
    <div class="lista-alternativas">
      <?php foreach (['a', 'b', 'c', 'd', 'e'] as $letra): 
        $texto_alt = get_field('alternativa_' . $letra);
        if (!empty($texto_alt)): ?>
          <div class="alternativa" data-letra="<?php echo strtoupper($letra); ?>">
            <strong><?php echo strtoupper($letra); ?>)&nbsp;</strong> <?php echo esc_html($texto_alt); ?>
          </div>
        <?php endif; 
      endforeach; ?>
    </div>

    <button id="btn-responder" class="btn-responder">Responder</button>

    <!-- Caixa de Feedback que o script do Assis manipula -->
    <div id="feedback-box" class="feedback-box">
      <h3 id="feedback-titulo" style="margin-top:0;"></h3>
      <p id="feedback-explicacao">
        <strong>Explicação:</strong> <?php echo nl2br(esc_html($explicacao)); ?>
      </p>
      <?php if (!empty($link_externo)): ?>
        <p><a href="<?php echo esc_url($link_externo); ?>" class="link-externo">🔗 Material Complementar</a></p>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php 
get_footer(); 
?>