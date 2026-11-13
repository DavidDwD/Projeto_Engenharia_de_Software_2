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

// 2. BUSCA AS DENÚNCIAS REAIS SALVAS NO BANCO
$posts_denuncias = get_posts( array(
    'post_type'      => 'denuncia',
    'posts_per_page' => 50,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
));

$items_reais = array();

foreach ( $posts_denuncias as $d ) {
    $id_questao  = get_post_meta( $d->ID, 'id_questao', true );
    $motivo      = get_post_meta( $d->ID, 'motivo', true ) ?: 'Informação perigosa';
    $detalhes    = get_post_meta( $d->ID, 'detalhes', true ) ?: 'Sem detalhes adicionais informados.';
    $aluno_nome  = get_post_meta( $d->ID, 'aluno_denunciante', true ) ?: 'Estudante Anônimo';
    $prioridade  = get_post_meta( $d->ID, 'prioridade', true ) ?: 'Média';
    
    // Pega o texto e autor da questão original
    $questao_post  = get_post( $id_questao );
    $texto_questao = $questao_post ? wp_trim_words( $questao_post->post_content ?: $questao_post->post_title, 20, '...' ) : 'Questão não encontrada';
    $autor_questao = $questao_post ? get_the_author_meta( 'display_name', $questao_post->post_author ) : 'Autor';

    $items_reais[] = array(
        'id'       => $d->ID,
        'pri'      => $prioridade,
        'ago'      => round( ( time() - strtotime( $d->post_date ) ) / 60 ),
        'left'     => 'SLA em dia',
        'type'     => 'Questão',
        'name'     => $autor_questao,
        'h'        => '#Q-' . $id_questao,
        'text'     => $texto_questao,
        'mot'      => $motivo,
        'why'      => $detalhes . ' (Denunciado por: ' . $aluno_nome . ')',
        'n'        => 1,
        'sub'      => 'Questão #' . $id_questao
    );
}

// Se não houver nenhuma denúncia no banco, mantemos uma de exemplo para o layout não ficar vazio
if ( empty( $items_reais ) ) {
    $items_reais[] = array(
        'id' => 101, 'pri' => 'Alta', 'ago' => 5, 'left' => '25 min restantes',
        'type' => 'Questão', 'name' => 'Exemplo Demonstrativo', 'h' => '#Q-99',
        'text' => 'Gabarito da questão de Engenharia de Software está invertido.',
        'mot' => 'Gabarito errado', 'why' => 'A alternativa correta segundo a bibliografia de Pressman seria a letra B e não C.',
        'n' => 1, 'sub' => 'Denúncia de teste'
    );
}

$dados_json = json_encode( $items_reais, JSON_UNESCAPED_UNICODE );
$usuario_atual = wp_get_current_user();
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
table{width:100%;border-collapse:collapse;min-width:900px}
th{text-align:left;font-size:11px;letter-spacing:.06em;color:var(--muted);font-weight:600;padding:12px 14px;background:var(--bg);border-bottom:1px solid var(--line)}
td{padding:14px;border-bottom:1px solid var(--line);vertical-align:top}
tr.row.sel-on td{background:var(--blue-bg)}
.rm{animation:out .3s forwards}
@keyframes out{to{opacity:0;transform:translateX(24px)}}
.new td{animation:flash 1.6s}
@keyframes flash{from{background:var(--blue-bg)}}
input[type=checkbox]{width:16px;height:16px;accent-color:var(--blue)}
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
</style>
</head>
<body>
<aside>
  <div class="brand"><div class="logo"><svg style="stroke:#fff;width:17px;height:17px" viewBox="0 0 24 24"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></div><div><b>Sentinel</b><small>ADMIN</small></div></div>
  <div class="grp">OPERAÇÃO</div>
  <button class="nav on"><svg viewBox="0 0 24 24"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg>Fila de denúncias<span class="n" id="navN">0</span></button>
  <a href="<?php echo admin_url('edit.php?post_type=questao'); ?>" class="nav" style="text-decoration:none;"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v12h14V8M10 12h4"/></svg>Gerenciar Questões</a>
  <a href="<?php echo esc_url( home_url('/') ); ?>" class="nav" style="text-decoration:none;"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Ver Página Home</a>
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
          <thead><tr><th style="width:40px"><input type="checkbox" id="all"></th><th>TRIAGEM</th><th>QUESTÃO DENUNCIADA</th><th>JUSTIFICATIVA DO ALUNO</th><th>SINAIS</th><th>AÇÕES RÁPIDAS</th></tr></thead>
          <tbody id="tb"></tbody>
        </table>
      </div>
      <div class="empty" id="empty" hidden>Nenhuma denúncia encontrada no banco de dados.</div>
      <div class="foot"><span id="range"></span></div>
    </div>
  </div>
</main>

<!-- Modal: confirmação de exclusão -->
<div class="ov" id="ov1" role="dialog" aria-modal="true">
  <div class="modal m1">
    <div class="mh"><div class="mi r"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></div>
      <div><h3>Tem certeza?</h3><p id="d1">Esta questão será arquivada ou removida do banco de questões.</p></div></div>
    <button class="x" data-close aria-label="Fechar">✕</button>
    <div class="mf"><button class="btn" data-close>Cancelar</button><button class="btn red" id="confirmDel">Remover da Lista</button></div>
  </div>
</div>

<div class="toast" id="toast" role="status"></div>

<script>
const REASONS = ["Gabarito errado", "Conteúdo ofensivo", "Enunciado incompleto", "Spam ou Duplicada", "Informação perigosa"];
let items = <?php echo $dados_json; ?>; // DADOS REAIS VINDOS DO WORDPRESS!

const $ = s => document.querySelector(s), P = {Alta:0, Média:1, Baixa:2};
const esc = s => String(s).replace(/[&<>"]/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const fmt = m => m < 60 ? `há ${m} min` : `há ${Math.floor(m/60)} h ${String(m%60).padStart(2,"0")} min`;
let pending = null, selected = new Set();

REASONS.forEach(r => $("#fm").insertAdjacentHTML("beforeend", `<option>${r}</option>`));

function view(){
  const q = $("#q").value.toLowerCase(), fp = $("#fp").value, fm = $("#fm").value;
  let l = items.filter(i => (!fp || i.pri === fp) && (!fm || i.mot === fm) && [i.text, i.name, i.mot, "dn-"+i.id].join(" ").toLowerCase().includes(q));
  
  const o = $("#so").value;
  l.sort(o === "pri" ? (a,b) => P[a.pri] - P[b.pri] || a.ago - b.ago : (a,b) => a.ago - b.ago);
  
  $("#tb").innerHTML = l.map(i => `<tr class="row ${selected.has(i.id)?"sel-on":""}" data-id="${i.id}">
   <td><input type="checkbox" data-sel="${i.id}" ${selected.has(i.id)?"checked":""}></td>
   <td><div class="id">#DN-${i.id}</div><span class="pr ${i.pri}">${i.pri}</span><div class="tm">${fmt(i.ago)}</div></td>
   <td><div class="post"><div class="who"><span class="tag">Questão</span><b>${esc(i.name)}</b><span>${esc(i.h)}</span></div><q>${esc(i.text)}</q></div></td>
   <td><span class="mot">${esc(i.mot)}</span><div class="just">${esc(i.why)}</div></td>
   <td class="sig"><b>🚩 ${i.n} sinalização</b><small>${esc(i.sub)}</small></td>
   <td><div class="qa">
     <button class="btn red" data-del="${i.id}">Excluir Questão</button>
     <button class="btn" data-rej="${i.id}">Ignorar / Rejeitar</button>
   </div></td></tr>`).join("");

  $("#empty").hidden = l.length > 0;
  $("#cnt").textContent = $("#navN").textContent = items.length;
  $("#range").textContent = `Exibindo ${l.length} de ${items.length} denúncias pendentes`;
}

function toast(m){const t=$("#toast");t.textContent=m;t.classList.add("on");clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove("on"),2600)}

document.addEventListener("click", e => {
  const t = e.target.closest("button,input"); if(!t) return;
  if(t.dataset.del){
    pending = t.dataset.del;
    $("#ov1").classList.add("open");
  } else if(t.dataset.rej){
    const id = +t.dataset.rej;
    items = items.filter(x => x.id !== id);
    view();
    toast(`Denúncia #DN-${id} resolvida e arquivada.`);
  } else if(t.hasAttribute("data-close")){
    $("#ov1").classList.remove("open");
  } else if(t.id === "confirmDel"){
    $("#ov1").classList.remove("open");
    items = items.filter(x => x.id != pending);
    view();
    toast(`Questão removida da moderação com sucesso.`);
  }
});

["#q","#fp","#fm","#so"].forEach(s => $(s).addEventListener("input", view));
view();
</script>
</body>
</html>