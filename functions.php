<?php
// Carrega o CSS do Astra
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_assets' );
function astra_child_enqueue_assets() {
    wp_enqueue_style( 'astra-parent-style', get_template_directory_uri() . '/style.css' );

    // Enfileira o JavaScript do Assis
    wp_enqueue_script(
        'resolucao-questoes',
        get_stylesheet_directory_uri() . '/assets/js/resolucao-questoes.js',
        array(),
        '1.0.0',
        true
    );
}