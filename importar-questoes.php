<?php
/**
 * importar-questoes.php
 * Script Utilitário para Importação Automática de Dados Semente (Seeds) no WordPress
 * Projeto: Banco de Questões e Conteúdo Acadêmico - UNILAB
 * Autor: Assis Barroso (Fase 6 - Implementação)
 *
 * Como usar:
 * 1. Chame este script no WordPress via functions.php temporariamente ou execute via WP-CLI / página administrativa.
 * 2. Ele lê assets/data/questoes-seed.json e cria os posts 'questao' com todos os campos ACF preenchidos.
 */

// Evita execução direta fora do ecossistema WordPress se acessado via web
if ( ! defined( 'ABSPATH' ) ) {
    // Se quiser testar isoladamente via CLI PHP, descomente a linha abaixo:
    // echo "Para importar no WordPress, inclua este arquivo dentro do functions.php ou execute via WP-CLI.\n";
}

function unilab_importar_questoes_seed() {
    $caminho_json = get_stylesheet_directory() . '/assets/data/questoes-seed.json';

    if ( ! file_exists( $caminho_json ) ) {
        return 'Arquivo questoes-seed.json não encontrado em: ' . $caminho_json;
    }

    $conteudo = file_get_contents( $caminho_json );
    $questoes = json_decode( $conteudo, true );

    if ( empty( $questoes ) || ! is_array( $questoes ) ) {
        return 'Nenhuma questão válida encontrada no JSON.';
    }

    $criadas = 0;
    $ja_existentes = 0;

    foreach ( $questoes as $item ) {
        $assunto = sanitize_text_field( $item['assunto'] );

        // Verifica se já existe uma questão cadastrada com esse assunto para evitar duplicatas
        $post_existente = get_page_by_title( $assunto, OBJECT, 'questao' );
        if ( $post_existente ) {
            $ja_existentes++;
            continue;
        }

        // 1. Criação do Post Customizado
        $post_data = array(
            'post_title'   => $assunto,
            'post_content' => wp_kses_post( $item['enunciado'] ),
            'post_status'  => 'publish',
            'post_type'    => 'questao',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
        );

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            continue;
        }

        // 2. Gravação dos Campos Customizados (ACF / PostMeta)
        update_post_meta( $post_id, 'assunto', $assunto );
        update_post_meta( $post_id, 'enunciado', $item['enunciado'] );
        update_post_meta( $post_id, 'alternativa_a', $item['alternativas']['A'] );
        update_post_meta( $post_id, 'alternativa_b', $item['alternativas']['B'] );
        update_post_meta( $post_id, 'alternativa_c', $item['alternativas']['C'] );
        update_post_meta( $post_id, 'alternativa_d', $item['alternativas']['D'] );
        update_post_meta( $post_id, 'alternativa_e', $item['alternativas']['E'] );
        update_post_meta( $post_id, 'resposta_correta', $item['resposta_correta'] );
        update_post_meta( $post_id, 'explicacao', $item['explicacao'] );
        update_post_meta( $post_id, 'link_externo', esc_url_raw( $item['link_externo'] ) );
        update_post_meta( $post_id, 'categoria', $item['categoria'] );

        // 3. Associação com a Taxonomia Categoria/Disciplina (disciplina e categoria_disciplina)
        if ( ! empty( $item['categoria'] ) ) {
            wp_set_object_terms( $post_id, $item['categoria'], 'disciplina', false );
            if ( taxonomy_exists( 'categoria_disciplina' ) ) {
                wp_set_object_terms( $post_id, $item['categoria'], 'categoria_disciplina', false );
            }
        }

        // 4. Associação de Tags se estiver registrada
        if ( ! empty( $item['tags'] ) ) {
            wp_set_object_terms( $post_id, $item['tags'], 'tag_questao', true );
        }

        $criadas++;
    }

    return "Processo concluído: {$criadas} questão(ões) importada(s) com sucesso. {$ja_existentes} já existiam.";
}