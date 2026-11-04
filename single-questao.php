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
$disciplinas = get_the_terms( get_the_ID(), 'categoria_disciplina' );
$nome_disciplina = ( ! empty( $disciplinas ) && ! is_wp_error( $disciplinas ) ) ? $disciplinas[0]->name : 'Geral';
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
</style>

<div class="unilab-single-wrapper">
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