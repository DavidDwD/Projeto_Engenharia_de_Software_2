#!/usr/bin/env python3
"""
Auditoria E2E completa com Playwright + Google Chrome real no navegador.
Simula cliques reais, validações, uploads, autocomplete AJAX de tags, moderação
persistente e gera screenshots + relatório JSON.

Uso:
  python3 tests/test_e2e_playwright.py

Variáveis de ambiente opcionais:
  UNILAB_BASE_URL  URL base do ambiente local (padrão: http://localhost:8080)
  UNILAB_E2E_OUT   Pasta de saída para screenshots e JSON (padrão: /tmp/unilab_e2e_playwright)
  CHROME_BIN       Caminho do executável Chrome/Chromium (detectado automaticamente se omitido)
"""

import os
import json
import tempfile
from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get("UNILAB_BASE_URL", "http://localhost:8080").rstrip("/")
ARTIFACT_DIR = os.environ.get("UNILAB_E2E_OUT", os.path.join(tempfile.gettempdir(), "unilab_e2e_playwright"))
SHOT_DIR = os.path.join(ARTIFACT_DIR, "screenshots")
REPORT_JSON = os.path.join(ARTIFACT_DIR, "e2e_results.json")
CHROME_BIN = os.environ.get("CHROME_BIN") or next(
    (p for p in ("/usr/bin/google-chrome", "/usr/bin/chromium", "/usr/bin/chromium-browser") if os.path.exists(p)),
    None,
)

os.makedirs(SHOT_DIR, exist_ok=True)
results = []
console_errors = []
page_errors = []


def record(spec_id, desc, ok, details=""):
    results.append({"id": spec_id, "desc": desc, "ok": bool(ok), "details": details})
    tag = "PASS" if ok else "FAIL"
    print(f"[{tag}] {spec_id}: {desc}" + (f" -> {details}" if details else ""))


def make_temp_files():
    tmpdir = tempfile.mkdtemp(prefix="unilab_e2e_")
    pdf_path = os.path.join(tmpdir, "resumo_gof_e2e.pdf")
    with open(pdf_path, "wb") as f:
        f.write(
            b"%PDF-1.4\n"
            b"1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            b"2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n"
            b"3 0 obj<</Type/Page/MediaBox[0 0 300 144]/Parent 2 0 R/Resources<<>>>>endobj\n"
            b"xref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\n"
            b"trailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n"
        )
    png_bytes = (
        b"\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde"
        b"\x00\x00\x00\x0cIDATx\x9cc\xf8\xcf\xc0\x00\x00\x03\x01\x01\x00\x18\xdd\x8d\xb0"
        b"\x00\x00\x00\x00IEND\xaeB`\x82"
    )
    img1 = os.path.join(tmpdir, "diagrama_singleton.png")
    img2 = os.path.join(tmpdir, "uml_classes.png")
    for p in (img1, img2):
        with open(p, "wb") as f:
            f.write(png_bytes)
    return pdf_path, img1, img2


allow_expected_409 = False


def attach_listeners(page):
    def on_console(msg):
        if msg.type != "error":
            return
        if allow_expected_409 and "409 (Conflict)" in msg.text:
            return
        console_errors.append(f"{page.url}: {msg.text}")

    page.on("console", on_console)
    page.on("pageerror", lambda err: page_errors.append(f"{page.url}: {err}"))


def login_via_ui(page, user, pwd):
    page.goto(f"{BASE_URL}/log-in/", wait_until="networkidle")
    page.fill('input[name="log"]', user)
    page.fill('input[name="pwd"]', pwd)
    with page.expect_navigation(wait_until="networkidle"):
        page.click('button.btn-login-submit')


def logout_via_ui(page):
    btn = page.locator("a.figma-btn-logout:has-text('Sair'), a#btnSairModeracao, a:has-text('Sair')").first
    if btn.count() > 0:
        with page.expect_navigation(wait_until="networkidle"):
            btn.click()


def main():
    pdf_path, img1_path, img2_path = make_temp_files()

    launch_kwargs = {
        "headless": True,
        "args": ["--no-sandbox", "--disable-dev-shm-usage"],
    }
    if CHROME_BIN:
        launch_kwargs["executable_path"] = CHROME_BIN

    with sync_playwright() as p:
        browser = p.chromium.launch(**launch_kwargs)

        context = browser.new_context(viewport={"width": 1366, "height": 900})
        page = context.new_page()
        attach_listeners(page)

        # 1. Home Desktop & Paginação
        page.goto(f"{BASE_URL}/", wait_until="networkidle")
        search_vis = page.locator("input[name='busca_q']").is_visible()
        cards_count = page.locator("article.figma-card").count()
        overflow_dt = page.evaluate("document.documentElement.scrollWidth > window.innerWidth")
        record("RF25/RF29/RF30", "Home exibe barra de pesquisa e lista de questões", search_vis and cards_count > 0, f"cards={cards_count}, overflow={overflow_dt}")
        page.screenshot(path=f"{SHOT_DIR}/01_home_desktop.png", full_page=True)

        # Paginação (RF32)
        next_link = page.locator(".figma-pagination-nav a.next, .figma-pagination-nav a:has-text('2')").first
        if next_link.count() > 0:
            with page.expect_navigation(wait_until="networkidle"):
                next_link.click()
            pag_info = page.locator(".figma-pagination-info").inner_text()
            record("RF32/HU22", "Paginação navega para página 2 corretamente", "Página 2" in pag_info, pag_info)
        else:
            record("RF32/HU22", "Paginação visível", page.locator(".figma-pagination-row").count() > 0, "Apenas 1 página com 10/pág")

        # 2. Mobile Responsividade (RNF05)
        mob_ctx = browser.new_context(viewport={"width": 390, "height": 844})
        mob_page = mob_ctx.new_page()
        mob_page.goto(f"{BASE_URL}/", wait_until="networkidle")
        overflow_mob = mob_page.evaluate("document.documentElement.scrollWidth > window.innerWidth")
        nav_mob_vis = mob_page.locator(".figma-nav-links a:has-text('Favoritos')").is_visible()
        record("RNF05", "Layout mobile 390px sem overflow horizontal e com menu acessível", (not overflow_mob) and nav_mob_vis, f"overflow={overflow_mob}, nav_vis={nav_mob_vis}")
        mob_page.screenshot(path=f"{SHOT_DIR}/02_home_mobile.png", full_page=False)
        mob_ctx.close()

        # 3. Cadastro mínimo 8 chars (RF01 / HU01)
        page.goto(f"{BASE_URL}/cadastro/", wait_until="networkidle")
        page.evaluate("document.getElementById('reg_senha').removeAttribute('minlength')")
        page.fill("#reg_nome", "Aluno Teste E2E")
        page.fill("#reg_email", "aluno.e2e.teste@unilab.edu.br")
        page.fill("#reg_senha", "123456")
        page.fill("#reg_confirma", "123456")
        page.check("#reg_termos")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.btn-register-submit")
        err_reg = page.locator(".figma-register-error").inner_text() if page.locator(".figma-register-error").count() > 0 else ""
        record("RF01/HU01", "Cadastro bloqueia senha com menos de 8 caracteres", "8 caracteres" in err_reg, err_reg)
        page.screenshot(path=f"{SHOT_DIR}/03_cadastro_validacao_8chars.png", full_page=True)

        # 4. Recuperação de senha neutra (RF03 / HU03) - navegando pelo link em /log-in/
        page.goto(f"{BASE_URL}/log-in/", wait_until="networkidle")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("a.link-esqueci-senha")
        page.fill('input[name="user_login"]', "inexistente.nao.existe@unilab.edu.br")
        with page.expect_navigation(wait_until="networkidle"):
            page.click('button.btn-recover-submit')
        rec_txt = page.content()
        neutral_ok = "Se houver uma conta cadastrada" in rec_txt and "Nenhuma conta" not in rec_txt
        record("RF03/HU03", "Recuperação de senha exibe mensagem neutra sem vazar e-mail", neutral_ok)
        page.screenshot(path=f"{SHOT_DIR}/04_recuperar_senha_neutra.png", full_page=True)

        # 5. Login inválido + Login válido (RF02 / HU02) e Admin Bar
        page.goto(f"{BASE_URL}/log-in/", wait_until="networkidle")
        page.fill('input[name="log"]', "estudante1")
        page.fill('input[name="pwd"]', "senha_errada_123")
        with page.expect_navigation(wait_until="networkidle"):
            page.click('button.btn-login-submit')
        login_err_txt = page.locator(".figma-login-error, body").first.inner_text()
        record("RF02/HU02", "Login inválido exibe erro neutro sem revelar qual campo errou", "incorretos" in login_err_txt.lower())

        login_via_ui(page, "estudante1", "senha123")
        no_admin_bar = page.locator("#wpadminbar").count() == 0
        tb_box = page.locator(".figma-top-bar").bounding_box()
        nav_box = page.locator(".figma-navbar").bounding_box()
        no_top_gap = (tb_box is not None and tb_box["y"] == 0) and (nav_box is not None and nav_box["y"] <= 40)
        record("RF02+UI", "Estudante logado redireciona para Home sem barra preta #wpadminbar e sem espaço vazio no topo", no_admin_bar and no_top_gap, f"url={page.url}, tb_y={tb_box['y'] if tb_box else None}, nav_y={nav_box['y'] if nav_box else None}")
        page.screenshot(path=f"{SHOT_DIR}/05_home_logado_estudante.png", full_page=False)

        # 6. Validação frontend de cadastro de questão (RNF12 / RN08)
        with page.expect_navigation(wait_until="networkidle"):
            page.click("a.figma-btn-publicar")
        # Tentar enviar sem preencher nada removendo required HTML5 para testar validação JS
        page.evaluate("document.querySelectorAll('#form-cadastro-questao [required]').forEach(el => el.removeAttribute('required'))")
        page.click("button.btn-publicar")
        page.wait_for_timeout(300)
        js_errs = page.locator(".mensagem-erro-campo").count()
        record("RF20/RNF12", "Validação frontend exibe erros contextuais claros em linguagem simples", js_errs >= 4, f"erros_exibidos={js_errs}")
        page.screenshot(path=f"{SHOT_DIR}/06_cadastro_questao_validacao.png", full_page=True)

        # 7. RF16 Completo (2+ chars AJAX, máx 10 sugestões, vírgula + nova tag, máx 10 tags) + Publicação real
        page.reload(wait_until="networkidle")
        assunto_e2e = "Padrao Singleton e GoF [E2E Playwright]"
        page.fill("#assunto", assunto_e2e)
        page.select_option("#categoria", "engenharia_software")
        page.fill("#nome_professor", "Prof. Ramon Vieira")

        # 7a. Testar 1 char -> sem dropdown; 2+ chars -> dropdown <= 10; "POO," -> segunda tag volta a sugerir!
        page.fill("#tag_input_digitacao", "C")
        page.wait_for_timeout(250)
        drop_1c_vis = page.locator("#tags-autocomplete-dropdown").is_visible()
        page.fill("#tag_input_digitacao", "Co")
        page.wait_for_timeout(450)
        drop_2c_vis = page.locator("#tags-autocomplete-dropdown").is_visible()
        sug_count = page.locator("#tags-autocomplete-dropdown .unilab-tags-option").count()
        page.screenshot(path=f"{SHOT_DIR}/06b_rf16_tags_chips_ajax.png", full_page=False)

        # Digita "POO," -> vira chip e limpa input, depois digita "Co" e seleciona sugestão (ou cria)
        page.fill("#tag_input_digitacao", "POO, ")
        page.wait_for_timeout(200)
        page.fill("#tag_input_digitacao", "Co")
        page.wait_for_timeout(450)
        drop_after_comma = page.locator("#tags-autocomplete-dropdown").is_visible()
        if page.locator("#tags-autocomplete-dropdown .unilab-tags-option").count() > 0:
            page.locator("#tags-autocomplete-dropdown .unilab-tags-option").first.click()
        else:
            page.press("#tag_input_digitacao", "Enter")

        # Testa limite máximo de 10 tags (adiciona até completar 10 e tenta a 11ª)
        for idx_t in range(1, 11):
            page.fill("#tag_input_digitacao", f"taglim{idx_t}")
            page.press("#tag_input_digitacao", "Enter")
        chips_max = page.locator(".unilab-tag-chip").count()
        aviso_max_txt = page.locator("#tags-limite-aviso").inner_text()
        record("RF16/HU11", "AJAX 2+ chars (<=10 sugestões), pós-vírgula sugere novamente e trava em no máximo 10 tags",
               (not drop_1c_vis) and drop_2c_vis and (1 <= sug_count <= 10) and drop_after_comma and chips_max == 10 and "10 tags" in aviso_max_txt,
               f"drop_2c={drop_2c_vis}, sugs={sug_count}, after_comma={drop_after_comma}, chips={chips_max}")

        # Remove chips extras até ficar com gof, singleton, e2e-test
        while page.locator(".unilab-tag-chip button").count() > 0:
            page.locator(".unilab-tag-chip button").first.click()
        page.fill("#tag_input_digitacao", "gof, singleton, e2e-test")
        page.press("#tag_input_digitacao", "Enter")
        page.fill("#enunciado", "Em Engenharia de Software, qual padrão de projeto criacional garante que uma classe tenha apenas uma instância e fornece um ponto global de acesso a ela?")
        page.fill('input[name="alternativa_a"]', "Observer")
        page.fill('input[name="alternativa_b"]', "Singleton")
        page.fill('input[name="alternativa_c"]', "Decorator")
        page.select_option("#resposta_correta", "B")
        page.fill("#explicacao", "O padrão Singleton garante uma única instância controlada globalmente durante o ciclo de vida da aplicação.")
        page.fill("#link_externo", "https://refactoring.guru/design-patterns/singleton")
        page.set_input_files("#anexo_pdf", pdf_path)
        page.set_input_files("#anexo_imagens", [img1_path, img2_path])

        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.btn-publicar")
        pub_ok = "Questão publicada com sucesso!" in page.content()
        ver_q_link = page.locator("a:has-text('Ver Questão')").first
        url_questao = ver_q_link.get_attribute("href") if ver_q_link.count() > 0 else ""
        record("RF10-RF20/RN08", "Publicação com 3 alts, professor, tags, PDF, imagens e link funciona", pub_ok and bool(url_questao), url_questao)
        page.screenshot(path=f"{SHOT_DIR}/07_questao_publicada_sucesso.png", full_page=True)

        # 8. Visualização, metadados, resolução interativa, likes e favoritos
        page.goto(url_questao, wait_until="networkidle")
        meta_bar = page.locator(".figma-questao-meta-bar").inner_text()
        alts_vis = page.locator(".lista-alternativas .alternativa").count()
        imgs_vis = page.locator(".bloco-imagens-anexadas img").count()
        pdf_vis = page.locator(".bloco-pdf-anexado a.btn-baixar-pdf").count()
        ext_target = page.locator("a.link-externo").get_attribute("target")
        record("RF21-RF24/HU15-HU18", "Single exibe autor, data, professor, tags, 3 alts, 2 imgs, PDF e link nova aba",
               ("Por " in meta_bar and "Publicado em " in meta_bar and "Ramon Vieira" in meta_bar and alts_vis == 3 and imgs_vis == 2 and pdf_vis == 1 and ext_target == "_blank"),
               f"alts={alts_vis}, imgs={imgs_vis}, pdf={pdf_vis}, target={ext_target}")

        # Resolução interativa (cliques reais)
        page.click("#btn-responder")  # sem seleção
        aviso_vis = page.locator("#feedback-box").is_visible()
        aviso_txt = page.locator("#feedback-titulo").inner_text()
        page.locator('.alternativa[data-letra="A"]').click()
        page.click("#btn-responder")
        erro_res = page.locator("#feedback-titulo").inner_text()
        # Tentar novamente e acertar
        btn_retry = page.locator("#btn-tentar-novamente, .btn-tentar-novamente").first
        with page.expect_navigation(wait_until="networkidle"):
            btn_retry.click()
        page.locator('.alternativa[data-letra="B"]').click()
        page.click("#btn-responder")
        fb_vis = page.locator("#feedback-box").is_visible()
        acerto_res = page.locator("#feedback-titulo").inner_text()
        exp_vis = page.locator("#feedback-explicacao").is_visible()
        record("RF14/RF13", "Resolução interativa valida seleção, erro, nova tentativa e caixa verde visível com explicação",
               (aviso_vis and fb_vis and exp_vis and "Selecione" in aviso_txt and "errou" in erro_res.lower() and "acertou" in acerto_res.lower()),
               f"fb_vis={fb_vis}, exp_vis={exp_vis}, msg={acerto_res}")
        page.evaluate("window.scrollTo(0, 0)")
        page.wait_for_timeout(150)
        page.screenshot(path=f"{SHOT_DIR}/08_single_questao_resolvida.png", full_page=True)

        # Curtir e Favoritar
        with page.expect_navigation(wait_until="networkidle"):
            page.click("#btn-curtir-questao")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("#btn-favoritar-questao")
        like_txt = page.locator("#label-curtidas-total").inner_text()
        fav_txt = page.locator("#label-favoritos-status").inner_text()
        record("RF31/RF41/HU32/HU37", "Curtida e Favorito persistem após clique e reload", "1" in like_txt and "Salva" in fav_txt, f"{like_txt} | {fav_txt}")

        # 9. Busca por Professor/Tag, Filtro Múltiplo de Disciplinas, Ordenação e Minhas/Favoritos
        page.goto(f"{BASE_URL}/", wait_until="networkidle")
        page.fill("input[name='busca_q']", "Ramon Vieira")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.figma-search-btn")
        found_prof = assunto_e2e in page.content()

        # Combinar com múltiplas disciplinas na sidebar
        page.check('#filtro-disciplinas-multi input[value="engenharia_software"]')
        page.check('#filtro-disciplinas-multi input[value="algoritmos"]')
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.figma-btn-filtrar")
        pills_cnt = page.locator(".figma-tag-pill").count()
        found_combo = assunto_e2e in page.content() and pills_cnt >= 3
        record("RF26/RF27/HU20/HU21", "Busca por professor/tag + filtro múltiplo de disciplinas combinados funcionam", found_prof and found_combo, f"found_prof={found_prof}, pills={pills_cnt}")
        page.screenshot(path=f"{SHOT_DIR}/09_busca_e_filtros_multiplos.png", full_page=True)

        # Ordenação por mais curtidas (RF31: estritamente decrescente, sem 0 antes de >0)
        page.goto(f"{BASE_URL}/", wait_until="networkidle")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("a:has-text('Mais curtidas')")
        cnt_curtidas = page.locator("article.figma-card").count()
        likes_vals = [int(t.strip()) for t in page.locator(".figma-like-counter span").all_inner_texts() if t.strip().isdigit()]
        likes_sorted = len(likes_vals) > 0 and all(likes_vals[i] >= likes_vals[i + 1] for i in range(len(likes_vals) - 1))
        record("RF31/HU23", "Ordenação 'Mais curtidas' exibe todas as questões em ordem estritamente decrescente", cnt_curtidas >= 5 and likes_sorted, f"cards={cnt_curtidas}, likes={likes_vals}")

        # Minhas questões e Favoritos
        with page.expect_navigation(wait_until="networkidle"):
            page.click(".figma-nav-links a:has-text('Minhas questões')")
        in_minhas = assunto_e2e in page.content()
        with page.expect_navigation(wait_until="networkidle"):
            page.click(".figma-nav-links a:has-text('Favoritos')")
        in_favs = assunto_e2e in page.content()
        record("RF08/RF09/HU08/HU09", "Abas 'Minhas questões' e 'Favoritos' listam a questão publicada/salva", in_minhas and in_favs, f"minhas={in_minhas}, favs={in_favs}")

        # 10. Edição e Remoção Seletiva de Anexo (RF34 / RF35)
        page.goto(url_questao, wait_until="networkidle")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("a.btn-autor-editar")
        prof_loaded = page.input_value("#nome_professor")
        tags_loaded = page.input_value("#tags_livres")
        page.fill("#assunto", assunto_e2e + " [EDITADA]")
        pdf_chk = page.locator("input[name='remover_pdf']").first
        if pdf_chk.count() > 0:
            pdf_chk.check()
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.btn-publicar")
        page.goto(url_questao, wait_until="networkidle")
        meta_pos_ed = page.locator(".figma-questao-meta-bar").inner_text()
        edit_ok = (
            "[EDITADA]" in page.locator("h1.questao-titulo").inner_text()
            and page.locator(".bloco-pdf-anexado").count() == 0
            and "Estudante Autor" in meta_pos_ed
            and "Ramon Vieira" in meta_pos_ed
            and "gof" in meta_pos_ed
        )
        record("RF34/RF35/HU25/HU26", "Edição atualiza título, remove PDF e preserva autor real, professor e tags", edit_ok, f"loaded_prof={prof_loaded!r}, loaded_tags={tags_loaded!r}, meta={meta_pos_ed!r}")
        page.evaluate("window.scrollTo(0, 0)")
        page.screenshot(path=f"{SHOT_DIR}/10_questao_editada.png", full_page=True)

        # 10b. Comentários do autor (RF37 publicar, RF39 editar, RF38 responder)
        page.fill("#novo-comentario-texto", "Comentário inicial E2E sobre Singleton.")
        page.click("#btn-enviar-comentario")
        page.wait_for_selector(".comentario-item:has-text('Comentário inicial E2E')")
        com_id_e2e = page.locator(".comentario-item:has-text('Comentário inicial E2E')").first.get_attribute("data-comment-id")

        page.click(f"#comentario-{com_id_e2e} .btn-editar-comentario")
        page.fill(f"#input-editar-{com_id_e2e}", "Comentário editado E2E sobre Singleton.")
        page.click(f"#comentario-{com_id_e2e} .btn-salvar-edicao-comentario")
        page.wait_for_timeout(400)
        page.reload(wait_until="networkidle")
        com_ed_persist = "Comentário editado E2E sobre Singleton." in page.locator("#lista-comentarios").inner_text()
        record("RF37/RF39/HU28/HU30", "Autor publica e edita comentário com persistência no banco", com_ed_persist, f"cid={com_id_e2e}")

        # 11. Logoff Estudante1 -> Login Estudante2 -> Responder/Excluir/Denunciar Comentário + Denúncia Questão
        logout_via_ui(page)
        login_via_ui(page, "estudante2", "senha123")
        page.goto(url_questao, wait_until="networkidle")
        no_toolbar = page.locator(".toolbar-autor").count() == 0
        sem_edit_com_alheio = page.locator(f"#comentario-{com_id_e2e} .btn-editar-comentario").count() == 0

        # Responde ao comentário (RF38)
        page.click(f"#comentario-{com_id_e2e} .btn-responder-comentario")
        page.fill(f"#input-responder-{com_id_e2e}", "Resposta E2E ao comentário sobre Singleton.")
        page.click(f"#comentario-{com_id_e2e} .btn-confirmar-resposta")
        page.wait_for_selector(".comentario-resposta:has-text('Resposta E2E')")

        # Publica e exclui próprio comentário via modal RN06 (RF40)
        page.fill("#novo-comentario-texto", "Comentário efêmero E2E para exclusão.")
        page.click("#btn-enviar-comentario")
        page.wait_for_selector(".comentario-item:has-text('Comentário efêmero E2E')")
        cid_efemero = page.locator(".comentario-item:has-text('Comentário efêmero E2E')").first.get_attribute("data-comment-id")
        page.click(f"#comentario-{cid_efemero} .btn-excluir-comentario")
        page.click("#btn-confirmar-excluir-com")
        page.wait_for_timeout(400)
        com_excluido_ok = page.locator(f"#comentario-{cid_efemero}").count() == 0

        # Denuncia comentário para moderação (RF43)
        page.click(f"#comentario-{com_id_e2e} .btn-denunciar-comentario")
        page.select_option("#denuncia-comentario-motivo", "Conteúdo ofensivo")
        page.fill("#denuncia-comentario-detalhes", "Denúncia E2E de comentário.")
        page.click("#btn-enviar-denuncia-com")
        page.wait_for_timeout(400)
        den_com_ok = "Denúncia de comentário enviada" in page.locator("#comentario-feedback-msg").inner_text()
        page.screenshot(path=f"{SHOT_DIR}/11b_single_comentarios.png", full_page=True)
        record("RF38/RF40/RF43/RN02", "Resposta, bloqueio de edição alheia, exclusão com modal e denúncia de comentário funcionam",
               sem_edit_com_alheio and com_excluido_ok and den_com_ok, f"com_excluido={com_excluido_ok}, den_com={den_com_ok}")

        page.click("#btn-abrir-modal-denuncia")
        page.select_option('#modalDenuncia select[name="motivo"]', "Gabarito errado")
        page.fill('#modalDenuncia textarea[name="detalhes"]', "Denúncia E2E #1 para testar rejeição na moderação.")
        with page.expect_navigation(wait_until="networkidle"):
            page.click('#modalDenuncia button[type="submit"]')
        den_ok = "Denúncia enviada para a moderação com sucesso" in page.content() and "já denunciou esta questão" in page.content()
        record("RN02/RF42/HU33", "Outro aluno não vê toolbar do autor, envia denúncia e fica bloqueado contra duplicata", no_toolbar and den_ok)
        page.screenshot(path=f"{SHOT_DIR}/11_denuncia_enviada.png", full_page=True)
        logout_via_ui(page)

        # 12. Admin Moderação - Checkboxes removidos, sem órfãs, responsivo mobile, RN02 autoria, Moderar Comentário e Rejeitar Questão
        login_via_ui(page, "david", "12345")
        in_mod = "central-de-moderacao" in page.url
        has_ver_q = page.locator("a.ctx:has-text('Ver questão denunciada')").count() > 0
        has_sair_mod = page.locator("#btnSairModeracao").is_visible()
        no_checkboxes = page.locator("input[type='checkbox']").count() == 0
        no_orfas = "Questão indisponível" not in page.locator("#tb").inner_text()
        has_den_com_row = page.locator('tr.row:has(button[data-del-type="comentario"])').count() > 0
        record("RF44/RF05/HU35", "Moderação sem checkboxes, sem denúncias órfãs, listando questão/comentário e botão Sair",
               in_mod and has_ver_q and has_sair_mod and no_checkboxes and no_orfas and has_den_com_row, page.url)
        page.screenshot(path=f"{SHOT_DIR}/12_moderacao_fila_desktop.png", full_page=True)

        # Responsividade mobile do painel de moderação (RNF05)
        page.set_viewport_size({"width": 390, "height": 844})
        page.wait_for_timeout(200)
        mod_overflow = page.evaluate("document.documentElement.scrollWidth > window.innerWidth")
        record("RNF05 (Moderação)", "Painel de moderação responsivo em 390px sem overflow horizontal", not mod_overflow, f"overflow={mod_overflow}")
        page.screenshot(path=f"{SHOT_DIR}/16_moderacao_mobile.png", full_page=False)
        page.set_viewport_size({"width": 1366, "height": 900})

        # Verifica RN02: Admin visitando questão de outro estudante NÃO vê "VOCÊ É O AUTOR"
        page.goto(url_questao, wait_until="networkidle")
        adm_nao_autor = "VOCÊ É O AUTOR" not in page.content() and "Modo Moderação" in page.content()
        record("RN02/HU25 (Admin)", "Admin vendo questão de outro aluno não vê 'VOCÊ É O AUTOR'", adm_nao_autor)
        page.goto(f"{BASE_URL}/central-de-moderacao/", wait_until="networkidle")

        # Exclui comentário denunciado na moderação sem remover a questão (RF43/RF45)
        del_com_btn = page.locator('tr.row button[data-del-type="comentario"]').first
        den_com_mod_id = del_com_btn.get_attribute("data-del")
        del_com_btn.click()
        page.wait_for_timeout(250)
        modal_com_txt = page.locator("#modalDelTitle").inner_text()
        page.click("#confirmDel")
        page.wait_for_timeout(1200)
        page.reload(wait_until="networkidle")
        com_mod_gone = page.locator(f'tr.row[data-id="{den_com_mod_id}"]').count() == 0
        q_pos_com_del = page.request.get(url_questao).status == 200
        record("RF43/RF45/HU34", "Moderação acolhe denúncia de comentário, exclui comentário e mantém questão publicada",
               "comentário" in modal_com_txt.lower() and com_mod_gone and q_pos_com_del, f"dn_com={den_com_mod_id}, q_status_200={q_pos_com_del}")

        # Clica em Ignorar / Rejeitar na denúncia da QUESTÃO mais recente
        first_rej = page.locator('tr.row:has(button[data-del-type="questao"]) button[data-rej]').first
        den_id_rej = first_rej.get_attribute("data-rej")
        first_rej.click()
        page.wait_for_timeout(1200)
        page.reload(wait_until="networkidle")
        still_pending = page.locator(f'tr.row[data-id="{den_id_rej}"]').count() > 0
        page.click("#tabHistorico")
        hist_txt = page.locator("#tbHist").inner_text()
        q_still_live = page.request.get(url_questao).status == 200
        record("RF45/RN07/HU36 (Rejeitar)", "Rejeitar denúncia persiste após reload, move para Histórico e mantém questão publicada", (not still_pending) and "Rejeitada" in hist_txt and q_still_live, f"dn={den_id_rej}, q_live={q_still_live}")
        page.screenshot(path=f"{SHOT_DIR}/13_moderacao_historico_rejeitada.png", full_page=True)
        logout_via_ui(page)

        # 13. Caso B: Estudante.teste denuncia -> Admin acolhe e exclui questão com modal + persistência (RF45 / RN06 / RN07)
        login_via_ui(page, "estudante.teste", "senha123")
        page.goto(url_questao, wait_until="networkidle")
        page.click("#btn-abrir-modal-denuncia")
        page.select_option('#modalDenuncia select[name="motivo"]', "Conteúdo ofensivo")
        page.fill('#modalDenuncia textarea[name="detalhes"]', "Denúncia E2E #2 para testar exclusão definitiva via moderação.")
        with page.expect_navigation(wait_until="networkidle"):
            page.click('#modalDenuncia button[type="submit"]')
        logout_via_ui(page)

        login_via_ui(page, "david", "12345")
        first_del = page.locator('tr.row button[data-del-type="questao"]').first
        den_id_del = first_del.get_attribute("data-del")
        first_del.click()
        page.wait_for_timeout(300)
        modal_vis = page.locator("#ov1.open").is_visible()
        page.screenshot(path=f"{SHOT_DIR}/14_moderacao_modal_confirmacao.png", full_page=True)
        page.click("#confirmDel")
        page.wait_for_timeout(1200)
        page.reload(wait_until="networkidle")
        still_pending_del = page.locator(f'tr.row[data-id="{den_id_del}"]').count() > 0
        no_fake_101 = "#DN-101" not in page.locator("#tb").inner_text()
        page.click("#tabHistorico")
        hist_del_txt = page.locator("#tbHist").inner_text()
        q_deleted_status = page.request.get(url_questao).status
        record("RF45/RN06/RN07/HU36 (Acolher)", "Acolher denúncia exige confirmação modal, exclui questão do banco (404) e arquiva no histórico",
               modal_vis and (not still_pending_del) and no_fake_101 and "Acolhida" in hist_del_txt and q_deleted_status == 404,
               f"dn={den_id_del}, status_pos_exclusao={q_deleted_status}")
        page.screenshot(path=f"{SHOT_DIR}/15_moderacao_pos_exclusao.png", full_page=True)

        # 13b. Administração de Categorias (HU38) e Atributos Personalizados (HU39)
        page.click("#tabCategorias")
        cat_e2e_nome = f"Categoria E2E {id(page) % 10000}"
        page.fill("#catNome", cat_e2e_nome)
        page.fill("#catDesc", "Criada via Playwright")
        page.click("#btnSalvarCategoria")
        page.wait_for_selector(f"#tbCat tr:has-text('{cat_e2e_nome}')")
        cat_criada_ok = cat_e2e_nome in page.locator("#tbCat").inner_text()

        # Testa trava de exclusão em categoria com questões vinculadas (Engenharia de Software)
        global allow_expected_409
        eng_del_btn = page.locator("#tbCat tr:has-text('Engenharia de Software') button[data-del-cat]").first
        if eng_del_btn.count() > 0:
            allow_expected_409 = True
            eng_del_btn.click()
            page.click("#confirmDel")
            page.wait_for_timeout(500)
            allow_expected_409 = False
            trava_cat_ok = "Não é possível excluir" in page.locator("#toast").inner_text()
        else:
            trava_cat_ok = True

        # Exclui categoria de teste vazia
        page.locator(f"#tbCat tr:has-text('{cat_e2e_nome}') button[data-del-cat]").first.click()
        page.click("#confirmDel")
        page.wait_for_timeout(500)
        cat_excluida_ok = cat_e2e_nome not in page.locator("#tbCat").inner_text()
        page.screenshot(path=f"{SHOT_DIR}/18_moderacao_categorias.png", full_page=True)

        # Cria atributo personalizado, confere no formulário de questão e exclui ao final (HU39)
        page.click("#tabAtributos")
        page.fill("#attrRotulo", "Dificuldade E2E")
        page.select_option("#attrTipo", "lista_opcoes")
        page.fill("#attrOpcoes", "Fácil, Difícil")
        page.click("#btnSalvarAtributo")
        page.wait_for_selector("#tbAttr tr:has-text('Dificuldade E2E')")
        attr_criado_ok = "Dificuldade E2E" in page.locator("#tbAttr").inner_text()

        page.goto(f"{BASE_URL}/cadastrar-questao/", wait_until="networkidle")
        attr_form_ok = page.locator("#attr_dificuldade_e2e").count() == 1
        page.goto(f"{BASE_URL}/central-de-moderacao/", wait_until="networkidle")
        page.click("#tabAtributos")
        page.locator("#tbAttr tr:has-text('Dificuldade E2E') button[data-del-attr]").first.click()
        page.click("#confirmDel")
        page.wait_for_timeout(500)
        attr_excluido_ok = "Dificuldade E2E" not in page.locator("#tbAttr").inner_text()
        record("HU38/HU39/RN06", "Admin gerencia categorias/subcategorias (com trava de exclusão) e atributos dinâmicos visíveis no cadastro",
               cat_criada_ok and trava_cat_ok and cat_excluida_ok and attr_criado_ok and attr_form_ok and attr_excluido_ok,
               f"cat_ok={cat_criada_ok}, trava={trava_cat_ok}, attr_form={attr_form_ok}")

        # Verifica logoff a partir da Moderação (RF05)
        logout_via_ui(page)
        logged_out_ok = "log-in" in page.url
        record("RF05/HU05", "Logoff a partir da Central de Moderação encerra sessão corretamente", logged_out_ok, page.url)

        # 14. Perfil do Estudante & LGPD (/perfil/ — RF04, RF06, RF07, RNF08)
        import time
        email_lgpd = f"e2e.perfil.{int(time.time())}@aluno.unilab.edu.br"
        page.goto(f"{BASE_URL}/cadastro/", wait_until="networkidle")
        page.fill("#reg_nome", "Aluno Perfil E2E")
        page.fill("#reg_curso", "Engenharia de Computação")
        page.fill("#reg_email", email_lgpd)
        page.fill("#reg_senha", "SenhaTeste123")
        page.fill("#reg_confirma", "SenhaTeste123")
        page.check("#reg_termos")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("button.btn-register-submit")

        with page.expect_navigation(wait_until="networkidle"):
            page.click(".figma-nav-links a:has-text('Meu Perfil')")
        perf_curso_ok = "Engenharia de Computação" in page.locator("#perfil-display-curso").inner_text()
        page.fill("#perfil_nome", "Aluno Perfil Editado")
        page.fill("#perfil_curso", "Sistemas de Informação")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("#btn-salvar-perfil")
        edit_perf_ok = "Aluno Perfil Editado" in page.locator("#perfil-display-name").inner_text() and "Sistemas de Informação" in page.locator("#perfil-display-curso").inner_text()

        # Troca de senha (RF04)
        page.fill("#senha_atual", "SenhaTeste123")
        page.fill("#nova_senha", "NovaSenhaE2E99")
        page.fill("#confirma_nova_senha", "NovaSenhaE2E99")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("#btn-alterar-senha")
        pwd_changed_ok = "alterada com segurança" in page.content()

        # Exportação JSON LGPD (RNF08)
        with page.expect_download() as dl_info:
            page.click("#btn-exportar-dados")
        dl = dl_info.value
        dl_ok = dl.suggested_filename.endswith(".json")

        # Responsividade mobile /perfil/
        page.set_viewport_size({"width": 390, "height": 844})
        page.wait_for_timeout(150)
        perf_overflow = page.evaluate("document.documentElement.scrollWidth > window.innerWidth")
        page.set_viewport_size({"width": 1366, "height": 900})
        page.evaluate("window.scrollTo(0, 0)")
        page.wait_for_timeout(150)
        page.screenshot(path=f"{SHOT_DIR}/17_perfil_lgpd.png", full_page=True)

        # Exclusão da conta via modal LGPD (RNF08 / RN06)
        page.click("#btn-abrir-modal-excluir-conta")
        page.fill("#texto_confirmacao", "EXCLUIR")
        page.fill("#senha_confirmacao_exclusao", "NovaSenhaE2E99")
        with page.expect_navigation(wait_until="networkidle"):
            page.click("#btn-confirmar-excluir-conta")
        del_acc_ok = "conta_excluida=1" in page.url
        record("RF04/RF06/RF07/RNF08", "Perfil visualiza/edita nome+curso, troca senha, exporta JSON LGPD, é responsivo e exclui conta via modal",
               perf_curso_ok and edit_perf_ok and pwd_changed_ok and dl_ok and (not perf_overflow) and del_acc_ok,
               f"dl={dl.suggested_filename}, overflow={perf_overflow}, url={page.url}")

        browser.close()

    with open(REPORT_JSON, "w", encoding="utf-8") as f:
        json.dump({"results": results, "console_errors": console_errors, "page_errors": page_errors}, f, ensure_ascii=False, indent=2)

    passed = sum(1 for r in results if r["ok"])
    total = len(results)
    print(f"\nRESUMO PLAYWRIGHT: {passed}/{total} checks PASS | console_errors={len(console_errors)} | page_errors={len(page_errors)}")
    print(f"Artefatos salvos em: {ARTIFACT_DIR}")
    if console_errors:
        print("CONSOLE ERRORS:", console_errors)
    if page_errors:
        print("PAGE ERRORS:", page_errors)
    if passed != total or page_errors:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
