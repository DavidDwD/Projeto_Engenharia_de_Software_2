<?php
/*
 * Template Name: Central de Moderação Admin
 */

// 1. TRAVA DE SEGURANÇA: Só o Administrador entra!
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die(
        '<div style="text-align:center; padding:50px; font-family:sans-serif;">' .
        '<h2>⛔ Acesso Negado</h2><p>Apenas moderadores e administradores têm acesso à Central Sentinel.</p>' .
        '<p><a href="' . esc_url( home_url('/') ) . '">Voltar para a página inicial</a></p>' .
        '</div>',
        'Acesso Restrito',
        array( 'response' => 403 )
    );
}

// 2. BUSCA AS DENÚNCIAS REAIS SALVAS NO BANCO (Pendentes + Histórico)
$posts_denuncias = get_posts( array(
    'post_type'      => 'denuncia',
    'posts_per_page' => 100,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
));

$posts_historico = get_posts( array(
    'post_type'      => 'denuncia',
    'posts_per_page' => 50,
    'post_status'    => 'private',
    'orderby'        => 'modified',
    'order'          => 'DESC'
));

$items_reais = array();

foreach ( $posts_denuncias as $d ) {
    $id_questao  = intval( get_post_meta( $d->ID, 'id_questao', true ) );
    $id_com      = intval( get_post_meta( $d->ID, 'id_comentario', true ) );
    $tipo_den    = get_post_meta( $d->ID, '_tipo_denuncia', true ) ?: ( $id_com > 0 ? 'comentario' : 'questao' );
    $motivo      = get_post_meta( $d->ID, 'motivo', true ) ?: 'Outro';
    $detalhes    = get_post_meta( $d->ID, 'detalhes', true ) ?: 'Sem detalhes adicionais informados.';
    $aluno_nome  = get_post_meta( $d->ID, 'aluno_denunciante', true ) ?: 'Estudante Anônimo';
    $prioridade  = get_post_meta( $d->ID, 'prioridade', true ) ?: 'Média';

    $questao_post = $id_questao > 0 ? get_post( $id_questao ) : null;
    if ( ! $questao_post || $questao_post->post_type !== 'questao' || $questao_post->post_status !== 'publish' ) {
        // Arquiva automaticamente denúncia órfã de questão já removida (RF36 / RN07 / HU27)
        update_post_meta( $d->ID, '_status_moderacao', 'questao_excluida_admin' );
        update_post_meta( $d->ID, '_moderador_nome', 'Limpeza Automática' );
        update_post_meta( $d->ID, '_data_decisao', current_time( 'mysql' ) );
        wp_update_post( array( 'ID' => $d->ID, 'post_status' => 'private' ) );
        continue;
    }

    if ( $tipo_den === 'comentario' ) {
        $comment_obj = $id_com > 0 ? get_comment( $id_com ) : null;
        if ( ! $comment_obj || (string) $comment_obj->comment_approved !== '1' ) {
            update_post_meta( $d->ID, '_status_moderacao', 'comentario_excluido_admin' );
            update_post_meta( $d->ID, '_moderador_nome', 'Limpeza Automática' );
            update_post_meta( $d->ID, '_data_decisao', current_time( 'mysql' ) );
            wp_update_post( array( 'ID' => $d->ID, 'post_status' => 'private' ) );
            continue;
        }
        $items_reais[] = array(
            'id'            => $d->ID,
            'tipo'          => 'comentario',
            'comentario_id' => $id_com,
            'questao_id'    => $id_questao,
            'questao_url'   => get_permalink( $id_questao ) . '#comentario-' . $id_com,
            'pri'           => $prioridade,
            'ago'           => max( 0, round( ( time() - strtotime( $d->post_date_gmt ?: $d->post_date ) ) / 60 ) ),
            'type'          => 'Comentário',
            'name'          => $comment_obj->comment_author ?: 'Estudante',
            'h'             => '#C-' . $id_com,
            'text'          => wp_trim_words( $comment_obj->comment_content, 20, '...' ),
            'mot'           => $motivo,
            'why'           => $detalhes,
            'reporter'      => $aluno_nome,
            'n'             => 1,
            'sub'           => 'Comentário #' . $id_com . ' · Q #' . $id_questao,
        );
        continue;
    }

    $texto_questao = wp_trim_words( $questao_post->post_content ?: $questao_post->post_title, 20, '...' );
    $autor_questao = get_the_author_meta( 'display_name', $questao_post->post_author ) ?: 'Autor';
    $questao_url   = get_permalink( $id_questao );

    $items_reais[] = array(
        'id'            => $d->ID,
        'tipo'          => 'questao',
        'comentario_id' => 0,
        'questao_id'    => $id_questao,
        'questao_url'   => $questao_url,
        'pri'           => $prioridade,
        'ago'           => max( 0, round( ( time() - strtotime( $d->post_date_gmt ?: $d->post_date ) ) / 60 ) ),
        'type'          => 'Questão',
        'name'          => $autor_questao,
        'h'             => '#Q-' . $id_questao,
        'text'          => $texto_questao,
        'mot'           => $motivo,
        'why'           => $detalhes,
        'reporter'      => $aluno_nome,
        'n'             => 1,
        'sub'           => 'Questão #' . $id_questao
    );
}

$historico_reais = array();
foreach ( $posts_historico as $h ) {
    $id_questao   = intval( get_post_meta( $h->ID, 'id_questao', true ) );
    $id_com       = intval( get_post_meta( $h->ID, 'id_comentario', true ) );
    $tipo_den     = get_post_meta( $h->ID, '_tipo_denuncia', true ) ?: ( $id_com > 0 ? 'comentario' : 'questao' );
    $motivo       = get_post_meta( $h->ID, 'motivo', true ) ?: 'Outro';
    $status_mod   = get_post_meta( $h->ID, '_status_moderacao', true ) ?: 'arquivada';
    $mod_nome     = get_post_meta( $h->ID, '_moderador_nome', true ) ?: 'Administrador';
    $data_dec     = get_post_meta( $h->ID, '_data_decisao', true ) ?: $h->post_modified;
    $snap_tit     = get_post_meta( $h->ID, '_questao_titulo_snapshot', true );
    $snap_com     = get_post_meta( $h->ID, '_comentario_texto_snapshot', true );
    $questao_post = $id_questao > 0 ? get_post( $id_questao ) : null;
    $titulo_q     = $tipo_den === 'comentario'
        ? ( '[Comentário #' . $id_com . '] ' . ( $snap_com ?: 'Comentário removido' ) )
        : ( $questao_post ? $questao_post->post_title : ( $snap_tit ?: ('Questão #' . $id_questao) ) );

    $historico_reais[] = array(
        'id'            => $h->ID,
        'tipo'          => $tipo_den,
        'comentario_id' => $id_com,
        'questao_id'    => $id_questao,
        'titulo'        => $titulo_q,
        'mot'           => $motivo,
        'status'        => $status_mod,
        'moderador'     => $mod_nome,
        'data'          => $data_dec,
    );
}

$dados_json      = wp_json_encode( $items_reais, JSON_UNESCAPED_UNICODE );
$historico_json  = wp_json_encode( $historico_reais, JSON_UNESCAPED_UNICODE );
$categorias_init = function_exists( 'unilab_listar_categorias_payload' ) ? unilab_listar_categorias_payload() : array();
$atributos_init  = function_exists( 'unilab_get_atributos_personalizados' ) ? unilab_get_atributos_personalizados() : array();
$ajax_url        = admin_url( 'admin-ajax.php' );
$mod_nonce       = wp_create_nonce( 'unilab_moderacao_action' );
$usuario_atual   = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Sentinel Admin · Central de moderação</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#f4f5f7;--surface:#fff;--ink:#14161c;--muted:#667085;--line:#e4e7ec;--side:#15181f;--side-ink:#aeb4c0;--blue:#2563eb;--blue-bg:#e8f0fe;--red:#dc2a1e;--red-bg:#fdeceb;--amber:#c2410c;--amber-bg:#fff1e6;--green:#12a150;--shadow:0 1px 2px rgba(16,24,40,.06)}
*{box-sizing:border-box}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.45 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;display:flex;min-height:100%}
button{font:inherit;color:inherit;cursor:pointer}
svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;flex:none}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
aside{width:200px;background:var(--side);color:var(--side-ink);display:flex;flex-direction:column;padding:16px 12px;position:sticky;top:0;height:100vh;flex:none}
.brand{display:flex;gap:10px;align-items:center;color:#fff;padding:0 6px 22px}
.logo{width:30px;height:30px;border-radius:8px;background:var(--blue);display:grid;place-items:center}
.brand b{display:block;font-size:15px;line-height:1.1}.brand small{font-size:10px;letter-spacing:.08em;color:var(--side-ink)}
.grp{font-size:11px;color:#6b7280;padding:14px 8px 6px;font-weight:600}
.nav{display:flex;gap:10px;align-items:center;width:100%;border:0;background:none;color:var(--side-ink);padding:9px 8px;border-radius:8px;text-align:left}
.nav:hover{background:#20242d;color:#fff}
.nav.on{background:#262b36;color:#fff}
.nav .n{margin-left:auto;background:var(--blue);color:#fff;border-radius:99px;padding:0 7px;font-size:11px;font-weight:600}
.status{margin-top:auto;background:#1d2129;border-radius:10px;padding:10px 12px;font-size:12px}
.status b{color:#fff;display:flex;gap:6px;align-items:center}.dot{width:7px;height:7px;border-radius:50%;background:var(--green)}
.me{display:flex;gap:10px;align-items:center;padding:14px 4px 0;color:#fff}
.me small{display:block;color:var(--side-ink);font-size:11px}
.av{width:30px;height:30px;border-radius:50%;background:#2b3240;display:grid;place-items:center;font-size:11px;font-weight:600;flex:none}
main{flex:1;min-width:0}
.top{display:flex;align-items:center;gap:8px;padding:10px 24px;background:var(--surface);border-bottom:1px solid var(--line);color:var(--muted)}
.top b{color:var(--ink);font-weight:600}
.top .sp{margin-left:auto}
.search{display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:8px;padding:0 10px;height:34px;background:var(--surface);color:var(--muted)}
.search input{border:0;background:none;outline:0;color:var(--ink);font:inherit;min-width:0;width:100%}
.kbd{border:1px solid var(--line);border-radius:4px;font-size:11px;padding:0 5px}
.icb{position:relative;width:34px;height:34px;border:1px solid var(--line);border-radius:8px;background:var(--surface);display:grid;place-items:center}
.icb i{position:absolute;top:6px;right:7px;width:7px;height:7px;border-radius:50%;background:var(--red)}
.wrap{padding:24px;max-width:1180px}
h1{margin:0;font-size:26px;letter-spacing:-.02em;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.pill{font-size:11px;font-weight:600;color:var(--green);background:#e7f6ee;border:1px solid #bfe6d0;border-radius:99px;padding:1px 9px}
.sub{color:var(--muted);margin:4px 0 0}
.head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
.upd{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--muted)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid var(--line);background:var(--surface);border-radius:8px;padding:0 12px;height:34px;font-weight:500;white-space:nowrap}
.btn:hover{background:var(--bg)}
.btn.dark{background:#1b1e25;color:#fff;border-color:#1b1e25}.btn.dark:hover{background:#2b2f39}
.btn.red{background:var(--red);border-color:var(--red);color:#fff;font-weight:600}.btn.red:hover{background:#c0241a}
.filters{display:flex;gap:10px;margin:20px 0 22px;flex-wrap:wrap;align-items:center}
.sel{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:8px;background:var(--surface);padding:0 8px 0 12px;height:34px;color:var(--muted)}
.sel b{color:var(--ink);font-weight:600}
.sel select{border:0;background:none;color:var(--ink);font:inherit;font-weight:600;outline:0;cursor:pointer;padding:0 2px}
.sel option{color:#14161c}
.filters .search{width:200px}.filters .push{margin-left:auto}
h2{margin:0;font-size:19px;display:flex;gap:8px;align-items:center}
.cnt{font-size:11px;font-weight:600;background:var(--blue-bg);color:var(--blue);border-radius:99px;padding:1px 8px}
.bar{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.acts{display:flex;gap:8px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);overflow:hidden}
.scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:860px}
th{text-align:left;font-size:11px;letter-spacing:.06em;color:var(--muted);font-weight:600;padding:12px 14px;background:var(--bg);border-bottom:1px solid var(--line)}
td{padding:14px;border-bottom:1px solid var(--line);vertical-align:top}
.rm{animation:out .3s forwards}
@keyframes out{to{opacity:0;transform:translateX(24px)}}
.new td{animation:flash 1.6s}
@keyframes flash{from{background:var(--blue-bg)}}
.id{font-size:11px;color:var(--muted);font-weight:600}
.pr{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;border-radius:99px;padding:1px 8px;margin:5px 0}
.pr::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.pr.Alta{background:var(--amber-bg);color:var(--amber)}.pr.Média{background:var(--blue-bg);color:var(--blue)}.pr.Baixa{background:var(--bg);color:var(--muted);border:1px solid var(--line)}
.tm{font-weight:600}.tl{font-size:12px;color:var(--muted)}.risk{color:var(--amber)}
.post{border:1px solid var(--line);background:var(--bg);border-radius:8px;padding:10px 12px;max-width:290px}
.who{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:6px}
.tag{font-size:11px;border:1px solid var(--line);background:var(--surface);border-radius:5px;padding:0 6px;color:var(--muted);font-weight:500}
.who b{font-weight:600}.who span:last-child{color:var(--muted);font-size:12px}
.post q{display:block;quotes:"“" "”";margin-bottom:6px}
.ctx{color:var(--blue);font-size:12px;font-weight:500;display:inline-flex;gap:4px;align-items:center;text-decoration:none;cursor:pointer;background:none;border:0;padding:0}
.ctx svg{width:11px;height:11px}
.mot{display:inline-block;font-size:12px;font-weight:600;color:var(--blue);background:var(--blue-bg);border-radius:99px;padding:1px 9px;margin-bottom:6px}
.just{color:var(--muted);font-size:13px;max-width:230px}
.sig b{display:flex;gap:6px;align-items:center;font-weight:600}.sig small{color:var(--muted);font-size:12px;margin-left:22px}
.qa{display:flex;flex-direction:column;gap:8px;min-width:170px}
.qa .btn{justify-content:center;font-size:13px}
.foot{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;color:var(--muted);font-size:12px}
.pg{display:flex;gap:6px}.pg button{width:30px;height:30px;border:1px solid var(--line);border-radius:6px;background:var(--surface)}.pg .on{background:var(--blue);border-color:var(--blue);color:#fff;font-weight:600}
.empty{padding:48px 16px;text-align:center;color:var(--muted)}
.ov{position:fixed;inset:0;background:rgba(10,12,16,.6);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.ov.open{display:flex;animation:fade .15s}
@keyframes fade{from{opacity:0}}
.modal{background:var(--surface);border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.35);padding:24px;width:100%;position:relative;animation:pop .18s}
@keyframes pop{from{transform:translateY(8px) scale(.98);opacity:0}}
.m1{max-width:600px}
.mh{display:flex;gap:16px;align-items:flex-start}
.mi{width:50px;height:50px;border-radius:10px;display:grid;place-items:center;flex:none}
.mi svg{width:20px;height:20px}
.mi.r{background:var(--red-bg);color:var(--red)}
.mh h3{margin:0 0 6px;font-size:22px;letter-spacing:-.01em}
.mh p{margin:0;color:var(--muted);font-size:15px;max-width:420px}
.x{position:absolute;top:20px;right:20px;border:0;background:none;color:var(--muted);width:28px;height:28px;border-radius:6px}
.x:hover{background:var(--bg)}
.mf{display:flex;justify-content:flex-end;gap:12px;margin-top:22px}
.mf .btn{height:42px;padding:0 20px;font-size:15px;font-weight:600;border-radius:10px}
.toast{position:fixed;left:50%;bottom:calc(24px + env(safe-area-inset-bottom,0px));transform:translate(-50%,20px);background:#1b1e25;color:#fff;border-radius:10px;padding:11px 16px;opacity:0;pointer-events:none;transition:.25s;z-index:60;display:flex;gap:8px;align-items:center}
.toast.on{opacity:1;transform:translate(-50%,0)}

/* RESPONSIVIDADE MOBILE / TABLET (RNF05) */
@media (max-width: 960px) {
  body{flex-direction:column}
  aside{width:100%;height:auto;position:static;flex-direction:row;flex-wrap:wrap;align-items:center;gap:6px;padding:12px 16px}
  .brand{padding:0 12px 0 0;margin-right:auto}
  .grp,.status,.me{display:none}
  .nav{width:auto;padding:7px 10px;font-size:13px}
  .top{padding:10px 16px}
  .wrap{padding:16px}
  .filters{flex-direction:column;align-items:stretch}
  .filters .search,.filters .sel,.filters .push{width:100%;margin-left:0}
}
@media (max-width: 720px) {
  table, thead, tbody, tr, td{display:block;min-width:0;width:100%}
  thead{display:none}
  tr.row, #tbHist tr{border-bottom:2px solid var(--line);padding:12px 14px}
  td{padding:6px 0;border:0}
  .post,.just{max-width:none}
  .qa{flex-direction:row;flex-wrap:wrap}
  .qa .btn{flex:1}
  #formCategoriaAdmin,#formAtributoAdmin{grid-template-columns:1fr !important}
}
</style>
</head>
<body>
<aside>
  <div class="brand"><div class="logo"><svg style="stroke:#fff;width:17px;height:17px" viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></div><div><b>Sentinel</b><small>ADMIN</small></div></div>
  <div class="grp">OPERAÇÃO</div>
  <button type="button" class="nav on" id="tabPendentes"><svg viewBox="0 0 24 24"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg>Fila de denúncias<span class="n" id="navN">0</span></button>
  <button type="button" class="nav" id="tabHistorico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>Histórico<span class="n" id="histN" style="background:#374151;">0</span></button>
  <div class="grp">CLASSIFICAÇÃO</div>
  <button type="button" class="nav" id="tabCategorias"><svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Categorias<span class="n" id="catN" style="background:#374151;">0</span></button>
  <button type="button" class="nav" id="tabAtributos"><svg viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/></svg>Atributos<span class="n" id="attrN" style="background:#374151;">0</span></button>
  <a href="<?php echo admin_url('edit.php?post_type=questao'); ?>" class="nav" style="text-decoration:none;"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v12h14V8M10 12h4"/></svg>Gerenciar Questões</a>
  <a href="<?php echo esc_url( home_url('/') ); ?>" class="nav" style="text-decoration:none;"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Ver Página Home</a>
  <a href="<?php echo esc_url( wp_logout_url( home_url('/log-in/') ) ); ?>" class="nav" id="btnSairModeracao" style="text-decoration:none;color:#fca5a5;"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sair da Conta</a>
  <div class="status"><b><span class="dot"></span>Operação normal</b>UNILAB Banco de Questões</div>
  <div class="me">
    <div class="av"><?php echo strtoupper(substr($usuario_atual->display_name, 0, 2)); ?></div>
    <div><b style="font-weight:600"><?php echo esc_html($usuario_atual->display_name); ?></b><small>Administrador</small></div>
  </div>
</aside>

<main>
  <div class="top">
    <span>Administração</span><span>›</span><b>Moderação de Questões</b>
    <div class="sp"></div>
    <a href="<?php echo esc_url( wp_logout_url( home_url('/log-in/') ) ); ?>" class="btn" style="text-decoration:none;font-size:12px;color:#b91c1c;border-color:#fecaca;background:#fef2f2;">Sair</a>
    <div class="av" style="background:var(--blue);color:#fff"><?php echo strtoupper(substr($usuario_atual->display_name, 0, 2)); ?></div>
  </div>

  <div class="wrap">
    <div class="head">
      <div>
        <h1>Central de moderação <span class="pill">Ativa</span></h1>
        <p class="sub">Analise questões sinalizadas pelos estudantes e mantenha o banco acadêmico consistente.</p>
      </div>
      <div class="upd"><span class="dot"></span><span id="upd">Atualizado</span><button class="btn" onclick="location.reload();"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 10-2.300 5.700M20 4v7h-7"/></svg>Atualizar</button></div>
    </div>

    <div id="secPendentes">
      <div class="filters">
        <label class="search" style="margin:0;font-weight:400"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><input id="q" placeholder="Buscar denúncia" aria-label="Buscar"></label>
        <label class="sel" style="margin:0;font-weight:400">Prioridade: <select id="fp"><option value="">Todas</option><option>Alta</option><option>Média</option><option>Baixa</option></select></label>
        <label class="sel" style="margin:0;font-weight:400">Motivo: <select id="fm"><option value="">Todos</option></select></label>
        <label class="sel push" style="margin-left:auto;font-weight:400">Ordenar por <select id="so"><option value="new">Novas</option><option value="pri">Prioridade</option></select></label>
      </div>

      <div class="bar">
        <div>
          <h2>Fila de denúncias pendentes <span class="cnt" id="cnt">0</span></h2>
        </div>
      </div>

      <div class="card">
        <div class="scroll">
          <table>
            <thead><tr><th>TRIAGEM</th><th>QUESTÃO DENUNCIADA</th><th>JUSTIFICATIVA DO ALUNO</th><th>SINAIS</th><th>AÇÕES RÁPIDAS</th></tr></thead>
            <tbody id="tb"></tbody>
          </table>
        </div>
        <div class="empty" id="empty" hidden>Nenhuma denúncia pendente no banco de dados.</div>
        <div class="foot"><span id="range"></span></div>
      </div>
    </div>

    <div id="secHistorico" hidden>
      <div class="bar" style="margin-top:20px;">
        <div>
          <h2>Histórico de decisões de moderação <span class="cnt" id="cntHist">0</span></h2>
        </div>
      </div>
      <div class="card">
        <div class="scroll">
          <table>
            <thead><tr><th>PROTOCOLO</th><th>QUESTÃO</th><th>MOTIVO</th><th>DECISÃO</th><th>MODERADOR</th><th>DATA DA DECISÃO</th></tr></thead>
            <tbody id="tbHist"></tbody>
          </table>
        </div>
        <div class="empty" id="emptyHist" hidden>Nenhuma denúncia foi decidida ainda.</div>
      </div>
    </div>

    <!-- Aba Categorias & Subcategorias (HU38 / RN06) -->
    <div id="secCategorias" hidden>
      <div class="bar" style="margin-top:20px;">
        <div>
          <h2>Gerenciar Categorias e Disciplinas <span class="cnt" id="cntCat">0</span></h2>
        </div>
      </div>
      <div class="card" style="padding:18px;margin-bottom:18px;">
        <form id="formCategoriaAdmin" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end;">
          <input type="hidden" id="catEditId" value="0">
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Nome da categoria/subcategoria *</label>
            <input id="catNome" required placeholder="Ex: Inteligência Artificial" style="width:100%;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:8px;">
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Categoria Pai (opcional)</label>
            <select id="catParent" style="width:100%;height:34px;padding:0 8px;border:1px solid var(--line);border-radius:8px;background:#fff;">
              <option value="0">Nenhuma (Categoria raiz)</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Descrição</label>
            <input id="catDesc" placeholder="Breve resumo..." style="width:100%;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:8px;">
          </div>
          <div style="display:flex;gap:6px;">
            <button type="submit" class="btn dark" id="btnSalvarCategoria">Salvar Categoria</button>
            <button type="button" class="btn" id="btnCancelarEdCat" hidden>Cancelar</button>
          </div>
        </form>
      </div>
      <div class="card">
        <div class="scroll">
          <table>
            <thead><tr><th>ID</th><th>NOME / HIERARQUIA</th><th>SLUG</th><th>QUESTÕES VINCULADAS</th><th>AÇÕES</th></tr></thead>
            <tbody id="tbCat"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Aba Atributos Personalizados (HU39) -->
    <div id="secAtributos" hidden>
      <div class="bar" style="margin-top:20px;">
        <div>
          <h2>Atributos Personalizados das Questões <span class="cnt" id="cntAttr">0</span></h2>
        </div>
      </div>
      <div class="card" style="padding:18px;margin-bottom:18px;">
        <form id="formAtributoAdmin" style="display:grid;grid-template-columns:1.2fr 1fr 1.2fr auto auto;gap:10px;align-items:end;">
          <input type="hidden" id="attrEditId" value="">
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Rótulo do Campo *</label>
            <input id="attrRotulo" required placeholder="Ex: Semestre Letivo" style="width:100%;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:8px;">
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Tipo de Dado</label>
            <select id="attrTipo" style="width:100%;height:34px;padding:0 8px;border:1px solid var(--line);border-radius:8px;background:#fff;">
              <option value="texto">Texto</option>
              <option value="numero">Número</option>
              <option value="data">Data</option>
              <option value="booleano">Sim / Não</option>
              <option value="lista_opcoes">Lista de Opções</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Opções (se lista, separadas por vírgula)</label>
            <input id="attrOpcoes" placeholder="Ex: Fácil, Médio, Difícil" style="width:100%;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:8px;">
          </div>
          <div>
            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Obrigatoriedade</label>
            <select id="attrObrig" style="width:100%;height:34px;padding:0 8px;border:1px solid var(--line);border-radius:8px;background:#fff;">
              <option value="0">Opcional</option>
              <option value="1">Obrigatório</option>
            </select>
          </div>
          <div style="display:flex;gap:6px;">
            <button type="submit" class="btn dark" id="btnSalvarAtributo">Salvar Atributo</button>
            <button type="button" class="btn" id="btnCancelarEdAttr" hidden>Cancelar</button>
          </div>
        </form>
      </div>
      <div class="card">
        <div class="scroll">
          <table>
            <thead><tr><th>CHAVE</th><th>RÓTULO</th><th>TIPO</th><th>OBRIGATÓRIO</th><th>OPÇÕES</th><th>AÇÕES</th></tr></thead>
            <tbody id="tbAttr"></tbody>
          </table>
        </div>
        <div class="empty" id="emptyAttr" hidden>Nenhum atributo personalizado configurado ainda.</div>
      </div>
    </div>
  </div>
</main>

<!-- Modal: confirmação de exclusão (RN06 / RF45 / HU36) -->
<div class="ov" id="ov1" role="dialog" aria-modal="true">
  <div class="modal m1">
    <div class="mh"><div class="mi r"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></div>
      <div><h3 id="modalDelTitle">Confirmar exclusão da questão?</h3><p id="d1">Esta ação acolherá a denúncia, excluirá permanentemente a questão denunciada do banco de questões e arquivará as denúncias pendentes vinculadas a ela.</p></div></div>
    <button class="x" data-close aria-label="Fechar">✕</button>
    <div class="mf"><button class="btn" data-close>Cancelar</button><button class="btn red" id="confirmDel">Sim, Excluir</button></div>
  </div>
</div>

<div class="toast" id="toast" role="status"></div>

<script>
const AJAX_URL = <?php echo wp_json_encode( $ajax_url ); ?>;
const MOD_NONCE = <?php echo wp_json_encode( $mod_nonce ); ?>;
const REASONS = ["Gabarito errado", "Conteúdo ofensivo", "Enunciado incompleto", "Spam ou Duplicada", "Informação perigosa"];
let items = <?php echo $dados_json; ?>; // DENÚNCIAS PENDENTES REAIS DO WORDPRESS
let historico = <?php echo $historico_json; ?>; // HISTÓRICO REAL DE DECISÕES
let categoriasAdmin = <?php echo wp_json_encode( $categorias_init, JSON_UNESCAPED_UNICODE ); ?>;
let atributosAdmin = <?php echo wp_json_encode( $atributos_init, JSON_UNESCAPED_UNICODE ); ?>;

const $ = s => document.querySelector(s), P = {Alta:0, Média:1, Baixa:2};
const esc = s => String(s ?? "").replace(/[&<>"]/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const fmt = m => m < 60 ? `há ${m} min` : `há ${Math.floor(m/60)} h ${String(m%60).padStart(2,"0")} min`;
let pending = null;
let pendingType = "questao";

REASONS.forEach(r => $("#fm").insertAdjacentHTML("beforeend", `<option>${r}</option>`));

function view(){
  const q = $("#q").value.toLowerCase(), fp = $("#fp").value, fm = $("#fm").value;
  let l = items.filter(i => (!fp || i.pri === fp) && (!fm || i.mot === fm) && [i.text, i.name, i.mot, i.why, i.reporter, i.type, "dn-"+i.id].join(" ").toLowerCase().includes(q));

  const o = $("#so").value;
  l.sort(o === "pri" ? (a,b) => P[a.pri] - P[b.pri] || a.ago - b.ago : (a,b) => a.ago - b.ago);

  $("#tb").innerHTML = l.map(i => {
    const isCom = i.tipo === "comentario";
    const linkLbl = isCom ? "Ver comentário na questão ↗" : "Ver questão denunciada ↗";
    const btnLbl = isCom ? "Excluir Comentário" : "Excluir Questão";
    return `<tr class="row" data-id="${i.id}">
     <td><div class="id">#DN-${i.id}</div><span class="pr ${esc(i.pri)}">${esc(i.pri)}</span><div class="tm">${fmt(i.ago)}</div></td>
     <td><div class="post"><div class="who"><span class="tag">${esc(i.type)}</span><b>${esc(i.name)}</b><span>${esc(i.h)}</span></div><q>${esc(i.text)}</q><a class="ctx" href="${esc(i.questao_url)}" target="_blank" rel="noopener noreferrer">${linkLbl}</a></div></td>
     <td><span class="mot">${esc(i.mot)}</span><div class="just">${esc(i.why)}<div style="margin-top:4px;font-size:11px;color:#475569;">Denunciado por: <strong>${esc(i.reporter)}</strong></div></div></td>
     <td class="sig"><b>🚩 ${i.n} sinalização</b><small>${esc(i.sub)}</small></td>
     <td><div class="qa">
       <button type="button" class="btn red" data-del="${i.id}" data-del-type="${isCom ? "comentario" : "questao"}">${btnLbl}</button>
       <button type="button" class="btn" data-rej="${i.id}">Ignorar / Rejeitar</button>
     </div></td></tr>`;
  }).join("");

  $("#empty").hidden = l.length > 0;
  $("#cnt").textContent = $("#navN").textContent = items.length;
  $("#range").textContent = `Exibindo ${l.length} de ${items.length} denúncias pendentes`;
}

function viewHistorico(){
  $("#tbHist").innerHTML = historico.map(h => {
    const isCom = h.tipo === "comentario";
    const labelStatus = h.status === "acolhida" ? (isCom ? "Denúncia Acolhida (Comentário Excluído)" : "Denúncia Acolhida (Questão Excluída)")
                      : h.status === "rejeitada" ? (isCom ? "Denúncia Rejeitada (Comentário Mantido)" : "Denúncia Rejeitada (Questão Mantida)")
                      : h.status === "questao_excluida_autor" ? "Arquivada (Autor Excluiu)"
                      : esc(h.status);
    const pillStyle = h.status === "acolhida" ? "background:var(--red-bg);color:var(--red);"
                    : h.status === "rejeitada" ? "background:#e7f6ee;color:var(--green);"
                    : "background:var(--bg);color:var(--muted);";
    return `<tr>
      <td><div class="id">#DN-${h.id}</div></td>
      <td><strong>${esc(h.titulo)}</strong> <span class="id">(${isCom ? "#C-" + h.comentario_id : "#Q-" + h.questao_id})</span></td>
      <td><span class="mot">${esc(h.mot)}</span></td>
      <td><span style="display:inline-block;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600;${pillStyle}">${labelStatus}</span></td>
      <td>${esc(h.moderador)}</td>
      <td>${esc(h.data)}</td>
    </tr>`;
  }).join("");
  $("#emptyHist").hidden = historico.length > 0;
  $("#cntHist").textContent = $("#histN").textContent = historico.length;
}

function toast(m){const t=$("#toast");t.textContent=m;t.classList.add("on");clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove("on"),3000)}

async function executarModeracao(denunciaId, acao){
  const body = new URLSearchParams({
    action: "unilab_moderar_denuncia",
    nonce: MOD_NONCE,
    denuncia_id: String(denunciaId),
    acao_moderacao: acao
  });
  const resp = await fetch(AJAX_URL, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: body.toString()
  });
  const json = await resp.json();
  if(!resp.ok || !json.success){
    throw new Error((json && json.data && json.data.mensagem) || "Erro ao salvar decisão de moderação.");
  }
  return json.data;
}

function alternarAbaAdmin(aba){
  const abas = {
    pendentes: ["#tabPendentes", "#secPendentes"],
    historico: ["#tabHistorico", "#secHistorico"],
    categorias: ["#tabCategorias", "#secCategorias"],
    atributos: ["#tabAtributos", "#secAtributos"]
  };
  Object.entries(abas).forEach(([k, [btnSel, secSel]]) => {
    $(btnSel).classList.toggle("on", k === aba);
    $(secSel).hidden = (k !== aba);
  });
}

$("#tabPendentes").addEventListener("click", () => alternarAbaAdmin("pendentes"));
$("#tabHistorico").addEventListener("click", () => alternarAbaAdmin("historico"));
$("#tabCategorias").addEventListener("click", () => alternarAbaAdmin("categorias"));
$("#tabAtributos").addEventListener("click", () => alternarAbaAdmin("atributos"));

function viewCategorias(){
  const sel = $("#catParent");
  const curVal = sel.value || "0";
  sel.innerHTML = `<option value="0">Nenhuma (Categoria raiz)</option>` +
    categoriasAdmin.map(c => `<option value="${c.id}">${esc(c.nome)}</option>`).join("");
  sel.value = curVal;

  $("#tbCat").innerHTML = categoriasAdmin.map(c => {
    const labelHier = c.parent_nome ? `<span style="color:var(--muted);">${esc(c.parent_nome)} › </span><strong>${esc(c.nome)}</strong>` : `<strong>${esc(c.nome)}</strong>`;
    return `<tr>
      <td><span class="id">#${c.id}</span></td>
      <td>${labelHier}${c.descricao ? `<div class="tl">${esc(c.descricao)}</div>` : ""}</td>
      <td><code>${esc(c.slug)}</code></td>
      <td><span class="cnt">${c.count}</span></td>
      <td><div style="display:flex;gap:6px;">
        <button type="button" class="btn" data-edit-cat="${c.id}">Editar</button>
        <button type="button" class="btn red" data-del-cat="${c.id}">Excluir</button>
      </div></td>
    </tr>`;
  }).join("");
  $("#cntCat").textContent = $("#catN").textContent = categoriasAdmin.length;
}

function viewAtributos(){
  $("#tbAttr").innerHTML = atributosAdmin.map(a => `<tr>
    <td><code>${esc(a.slug)}</code></td>
    <td><strong>${esc(a.rotulo)}</strong></td>
    <td><span class="tag">${esc(a.tipo)}</span></td>
    <td>${a.obrigatorio ? '<span class="pill">Sim</span>' : '<span style="color:var(--muted);">Opcional</span>'}</td>
    <td>${(a.opcoes && a.opcoes.length) ? esc(a.opcoes.join(", ")) : "—"}</td>
    <td><div style="display:flex;gap:6px;">
      <button type="button" class="btn" data-edit-attr="${esc(a.id)}">Editar</button>
      <button type="button" class="btn red" data-del-attr="${esc(a.id)}">Excluir</button>
    </div></td>
  </tr>`).join("");
  $("#emptyAttr").hidden = atributosAdmin.length > 0;
  $("#cntAttr").textContent = $("#attrN").textContent = atributosAdmin.length;
}

async function postAdminClassificacao(actionName, payload){
  const body = new URLSearchParams(Object.assign({ action: actionName, nonce: MOD_NONCE }, payload));
  const resp = await fetch(AJAX_URL, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: body.toString()
  });
  const json = await resp.json();
  if(!resp.ok || !json.success){
    throw new Error((json && json.data && json.data.mensagem) || "Erro na operação.");
  }
  return json.data;
}

$("#formCategoriaAdmin").addEventListener("submit", async e => {
  e.preventDefault();
  const idEd = parseInt($("#catEditId").value || "0", 10);
  try {
    const res = await postAdminClassificacao("unilab_gerenciar_categoria", {
      operacao: idEd > 0 ? "editar" : "criar",
      term_id: idEd,
      nome: $("#catNome").value,
      parent: $("#catParent").value || "0",
      descricao: $("#catDesc").value
    });
    categoriasAdmin = res.categorias || [];
    $("#catEditId").value = "0";
    $("#catNome").value = "";
    $("#catDesc").value = "";
    $("#catParent").value = "0";
    $("#btnCancelarEdCat").hidden = true;
    viewCategorias();
    toast(res.mensagem);
  } catch(err) {
    toast("Erro: " + err.message);
  }
});

$("#btnCancelarEdCat").addEventListener("click", () => {
  $("#catEditId").value = "0";
  $("#catNome").value = "";
  $("#catDesc").value = "";
  $("#catParent").value = "0";
  $("#btnCancelarEdCat").hidden = true;
});

$("#formAtributoAdmin").addEventListener("submit", async e => {
  e.preventDefault();
  try {
    const res = await postAdminClassificacao("unilab_gerenciar_atributo", {
      operacao: "salvar",
      attr_id: $("#attrEditId").value,
      rotulo: $("#attrRotulo").value,
      tipo: $("#attrTipo").value,
      opcoes: $("#attrOpcoes").value,
      obrigatorio: $("#attrObrig").value === "1" ? "1" : "0",
      ativo: "1"
    });
    atributosAdmin = res.atributos || [];
    $("#attrEditId").value = "";
    $("#attrRotulo").value = "";
    $("#attrOpcoes").value = "";
    $("#attrObrig").value = "0";
    $("#btnCancelarEdAttr").hidden = true;
    viewAtributos();
    toast(res.mensagem);
  } catch(err) {
    toast("Erro: " + err.message);
  }
});

$("#btnCancelarEdAttr").addEventListener("click", () => {
  $("#attrEditId").value = "";
  $("#attrRotulo").value = "";
  $("#attrOpcoes").value = "";
  $("#attrObrig").value = "0";
  $("#btnCancelarEdAttr").hidden = true;
});

let pendingCatDel = 0;
let pendingAttrDel = "";

document.addEventListener("click", async e => {
  const t = e.target.closest("button"); if(!t) return;
  if(t.dataset.editCat){
    const c = categoriasAdmin.find(x => x.id === +t.dataset.editCat);
    if(c){
      $("#catEditId").value = String(c.id);
      $("#catNome").value = c.nome;
      $("#catParent").value = String(c.parent || 0);
      $("#catDesc").value = c.descricao || "";
      $("#btnCancelarEdCat").hidden = false;
    }
    return;
  }
  if(t.dataset.delCat){
    pending = 0;
    pendingAttrDel = "";
    pendingCatDel = +t.dataset.delCat;
    $("#modalDelTitle").textContent = "Confirmar exclusão da categoria?";
    $("#d1").textContent = "Categorias com questões vinculadas ou subcategorias serão bloqueadas automaticamente (RN06 / HU38).";
    $("#confirmDel").textContent = "Sim, Excluir Categoria";
    $("#ov1").classList.add("open");
    return;
  }
  if(t.dataset.editAttr){
    const a = atributosAdmin.find(x => x.id === t.dataset.editAttr);
    if(a){
      $("#attrEditId").value = a.id;
      $("#attrRotulo").value = a.rotulo;
      $("#attrTipo").value = a.tipo;
      $("#attrOpcoes").value = (a.opcoes || []).join(", ");
      $("#attrObrig").value = a.obrigatorio ? "1" : "0";
      $("#btnCancelarEdAttr").hidden = false;
    }
    return;
  }
  if(t.dataset.delAttr){
    pending = 0;
    pendingCatDel = 0;
    pendingAttrDel = t.dataset.delAttr;
    $("#modalDelTitle").textContent = "Confirmar exclusão do atributo?";
    $("#d1").textContent = "O atributo deixará de aparecer no formulário de publicação e edição de questões (HU39 / RN06).";
    $("#confirmDel").textContent = "Sim, Excluir Atributo";
    $("#ov1").classList.add("open");
    return;
  }
  if(t.dataset.del){
    pendingCatDel = 0;
    pendingAttrDel = "";
    pending = +t.dataset.del;
    pendingType = t.dataset.delType || "questao";
    if(pendingType === "comentario"){
      $("#modalDelTitle").textContent = "Confirmar exclusão do comentário?";
      $("#d1").textContent = "Esta ação acolherá a denúncia, excluirá permanentemente o comentário denunciado e arquivará as denúncias pendentes vinculadas a ele (a questão será preservada).";
      $("#confirmDel").textContent = "Sim, Excluir Comentário";
    } else {
      $("#modalDelTitle").textContent = "Confirmar exclusão da questão?";
      $("#d1").textContent = "Esta ação acolherá a denúncia, excluirá permanentemente a questão denunciada do banco de questões e arquivará as denúncias pendentes vinculadas a ela.";
      $("#confirmDel").textContent = "Sim, Excluir Questão";
    }
    $("#ov1").classList.add("open");
  } else if(t.dataset.rej){
    const id = +t.dataset.rej;
    t.disabled = true;
    try {
      const res = await executarModeracao(id, "rejeitar_denuncia");
      const alvo = items.find(x => x.id === id);
      items = items.filter(x => x.id !== id);
      if(alvo){
        historico.unshift({
          id: alvo.id, tipo: alvo.tipo, comentario_id: alvo.comentario_id, questao_id: alvo.questao_id, titulo: alvo.text,
          mot: alvo.mot, status: "rejeitada", moderador: res.moderador, data: res.data
        });
      }
      view();
      viewHistorico();
      toast(res.mensagem);
    } catch(err) {
      t.disabled = false;
      toast("Erro: " + err.message);
    }
  } else if(t.hasAttribute("data-close")){
    $("#ov1").classList.remove("open");
  } else if(t.id === "confirmDel"){
    if(pendingCatDel > 0){
      t.disabled = true;
      try {
        const resC = await postAdminClassificacao("unilab_gerenciar_categoria", { operacao: "excluir", term_id: pendingCatDel });
        categoriasAdmin = resC.categorias || [];
        viewCategorias();
        toast(resC.mensagem);
      } catch(errC) {
        toast("Erro: " + errC.message);
      } finally {
        $("#ov1").classList.remove("open");
        pendingCatDel = 0;
        t.disabled = false;
      }
      return;
    }
    if(pendingAttrDel){
      t.disabled = true;
      try {
        const resA = await postAdminClassificacao("unilab_gerenciar_atributo", { operacao: "excluir", attr_id: pendingAttrDel });
        atributosAdmin = resA.atributos || [];
        viewAtributos();
        toast(resA.mensagem);
      } catch(errA) {
        toast("Erro: " + errA.message);
      } finally {
        $("#ov1").classList.remove("open");
        pendingAttrDel = "";
        t.disabled = false;
      }
      return;
    }
    if(!pending) return;
    t.disabled = true;
    try {
      const acaoEnv = pendingType === "comentario" ? "excluir_comentario" : "excluir_questao";
      const res = await executarModeracao(pending, acaoEnv);
      const qid = res.questao_id;
      const cid = res.comentario_id || 0;
      const removidas = items.filter(x => {
        if (x.id === pending) return true;
        if (pendingType === "comentario") return cid > 0 && x.comentario_id === cid;
        return qid > 0 && x.questao_id === qid;
      });
      items = items.filter(x => !removidas.includes(x));
      removidas.forEach(r => {
        historico.unshift({
          id: r.id, tipo: r.tipo, comentario_id: r.comentario_id, questao_id: r.questao_id, titulo: r.text,
          mot: r.mot, status: "acolhida", moderador: res.moderador, data: res.data
        });
      });
      $("#ov1").classList.remove("open");
      pending = null;
      view();
      viewHistorico();
      toast(res.mensagem);
    } catch(err) {
      toast("Erro: " + err.message);
    } finally {
      t.disabled = false;
    }
  }
});

["#q","#fp","#fm","#so"].forEach(s => $(s).addEventListener("input", view));
view();
viewHistorico();
viewCategorias();
viewAtributos();
</script>
</body>
</html>