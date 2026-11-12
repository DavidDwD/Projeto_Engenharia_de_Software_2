#!/usr/bin/env python3
"""
Test Suite Completo - Banco de Questões UNILAB (Fase 6 - Assis Barroso)
Testes automatizados cobrindo:
1. Autenticação de Estudantes (estudante1 e estudante2)
2. Publicação de Questão com Anexos (PDF e Múltiplas Imagens - RF10, RF17, RF18)
3. Visualização Individual da Questão (RF21, RF22, RF23, RF24)
4. Estrutura de Resolução Interativa (RF14 / resolucao-questoes.js)
5. Painel de Gestão do Autor (RF34: Edição, RF35: Remoção de Anexos)
6. Permissões de Segurança (Outro estudante não pode editar/excluir - RF34/RF36)
7. Exclusão de Questão com Nonce (RF36)
8. Busca e Filtro de Questões (RF25, RF26, RF27)
"""

import sys
import io
import re
import requests
from bs4 import BeautifulSoup

BASE_URL = "http://localhost:8080"
CREDS_AUTOR = {"log": "estudante1", "pwd": "senha123"}
CREDS_OUTRO = {"log": "estudante2", "pwd": "senha123"}

def print_step(title):
    print("\n" + "=" * 70)
    print(f"👉 {title}")
    print("=" * 70)

def print_sub(msg, ok=True):
    status = "✅ PASS" if ok else "❌ FAIL"
    print(f"   [{status}] {msg}")
    if not ok:
        sys.exit(1)

def criar_sessao_login(creds):
    s = requests.Session()
    login_url = f"{BASE_URL}/wp-login.php"
    resp = s.post(login_url, data={
        "log": creds["log"],
        "pwd": creds["pwd"],
        "wp-submit": "Log In",
        "redirect_to": f"{BASE_URL}/wp-admin/",
        "testcookie": "1"
    }, allow_redirects=True)
    # Verifica se os cookies do WP foram definidos
    cookies_wp = [c.name for c in s.cookies if "wordpress_logged_in" in c.name]
    if not cookies_wp:
        print_sub(f"Falha ao autenticar usuário {creds['log']}", False)
    return s

def gerar_pdf_dummy():
    # Cria um PDF minimalista válido
    conteudo_pdf = (
        b"%PDF-1.4\n"
        b"1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
        b"2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n"
        b"3 0 obj<</Type/Page/MediaBox[0 0 300 144]/Parent 2 0 R/Resources<<>>>>endobj\n"
        b"xref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\n"
        b"trailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n"
    )
    return io.BytesIO(conteudo_pdf)

def gerar_png_dummy(cor_byte=b"\x80"):
    # PNG 1x1 minimalista
    png_bytes = (
        b"\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde"
        b"\x00\x00\x00\x0cIDATx\x9cc\xf8\xcf\xc0\x00\x00\x03\x01\x01\x00\x18\xdd\x8d\xb0"
        b"\x00\x00\x00\x00IEND\xaeB`\x82"
    )
    return io.BytesIO(png_bytes)

def main():
    print("🚀 INICIANDO TESTES DO BANCO DE QUESTÕES UNILAB (FASE 6)")
    
    # -------------------------------------------------------------
    # 1. Autenticação dos Usuários
    # -------------------------------------------------------------
    print_step("1. Autenticação de Alunos (RF01 / RF02)")
    sessao_autor = criar_sessao_login(CREDS_AUTOR)
    print_sub("Autor autenticado com sucesso: estudante1")
    sessao_outro = criar_sessao_login(CREDS_OUTRO)
    print_sub("Segundo usuário autenticado: estudante2")

    # -------------------------------------------------------------
    # 2. Formulário de Cadastro (RF10)
    # -------------------------------------------------------------
    print_step("2. Verificação da Página de Cadastro de Questões (RF10)")
    resp_cad = sessao_autor.get(f"{BASE_URL}/cadastrar-questao/")
    print_sub("Página /cadastrar-questao/ acessível (HTTP 200)", resp_cad.status_code == 200)
    soup_cad = BeautifulSoup(resp_cad.text, "html.parser")
    form_cad = soup_cad.find("form", id="form-cadastro-questao")
    print_sub("Formulário #form-cadastro-questao presente na página", form_cad is not None)
    print_sub("Campo anexo_pdf presente", soup_cad.find("input", id="anexo_pdf") is not None)
    print_sub("Campo anexo_imagens presente", soup_cad.find("input", id="anexo_imagens") is not None)

    # -------------------------------------------------------------
    # 3. Publicação com Uploads (RF10, RF17, RF18)
    # -------------------------------------------------------------
    print_step("3. Publicação de Questão com Anexos (PDF e Imagens - RF17, RF18)")
    
    dados_post = {
        "assunto": "Arquitetura MVC no Desenvolvimento Web Moderno",
        "categoria": "engenharia_software",
        "enunciado": "No padrão arquitetural Model-View-Controller (MVC), as responsabilidades são segregadas em três camadas fundamentais. Assinale a alternativa que descreve corretamente a atribuição da camada Controller.",
        "alternativa_a": "Armazenar dados permanentemente e executar queries SQL diretamente.",
        "alternativa_b": "Receber as requisições do usuário, acionar o Model e selecionar a View a ser renderizada.",
        "alternativa_c": "Construir a folha de estilos CSS e estrutura visual exibida na tela do cliente.",
        "alternativa_d": "Realizar a comunicação física entre switches e roteadores na rede.",
        "alternativa_e": "Executar o sistema operacional que hospeda o servidor HTTP.",
        "resposta_correta": "B",
        "explicacao": "O Controller atua como intermediário: interpreta as ações do usuário (requisições HTTP), consulta ou atualiza o Model e direciona para a View apropriada.",
        "link_externo": "https://developer.mozilla.org/pt-BR/docs/Glossary/MVC"
    }

    files = [
        ("anexo_pdf", ("guia_arquitetura_mvc.pdf", gerar_pdf_dummy(), "application/pdf")),
        ("anexo_imagens[]", ("fluxograma_mvc.png", gerar_png_dummy(), "image/png")),
        ("anexo_imagens[]", ("diagrama_camadas.png", gerar_png_dummy(), "image/png"))
    ]

    resp_pub = sessao_autor.post(
        f"{BASE_URL}/cadastrar-questao/",
        data=dados_post,
        files=files,
        allow_redirects=True
    )
    print_sub("Publicação POST respondeu com HTTP 200", resp_pub.status_code == 200)
    print_sub("Mensagem de sucesso encontrada", "Questão publicada com sucesso!" in resp_pub.text)

    # Extrai o link e ID da questão recém-criada
    soup_pub = BeautifulSoup(resp_pub.text, "html.parser")
    link_ver = soup_pub.find("a", string=re.compile("Ver Questão"))
    if not link_ver or not link_ver.get("href"):
        print_sub("Não foi possível encontrar o link da questão publicada", False)
    
    url_questao = link_ver["href"]
    print_sub(f"Link da nova questão gerado: {url_questao}")

    # -------------------------------------------------------------
    # 4. Exibição e Anexos da Questão Individual (RF21, RF22, RF23)
    # -------------------------------------------------------------
    print_step("4. Visualização Individual da Questão (RF21, RF22, RF23, RF24)")
    resp_single = sessao_autor.get(url_questao)
    print_sub("Página single-questao acessível (HTTP 200)", resp_single.status_code == 200)
    
    soup_single = BeautifulSoup(resp_single.text, "html.parser")
    card_q = soup_single.find("div", class_="card-questao")
    print_sub("Elemento .card-questao presente", card_q is not None)
    print_sub("Gabarito embutido data-gabarito='B' (RF14)", card_q.get("data-gabarito") == "B")
    
    # Disciplina / Categoria
    badge = soup_single.find("span", class_="badge-disciplina")
    print_sub("Badge da Disciplina 'Engenharia de Software' (RF15)", badge and "Engenharia de Software" in badge.text)

    # Imagens Anexadas (RF22)
    bloco_imgs = soup_single.find("div", class_="bloco-imagens-anexadas")
    imgs_encontradas = bloco_imgs.find_all("img") if bloco_imgs else []
    print_sub(f"Galeria de imagens anexadas renderizada com {len(imgs_encontradas)} imagem(ns) (RF22)", len(imgs_encontradas) == 2)

    # PDF Anexado (RF23)
    bloco_pdf = soup_single.find("div", class_="bloco-pdf-anexado")
    link_pdf = bloco_pdf.find("a") if bloco_pdf else None
    print_sub("Botão de download de PDF anexado presente (RF23)", link_pdf is not None and "guia_arquitetura_mvc.pdf" in link_pdf.text)
    
    # Teste de Download real do PDF anexado
    if link_pdf and link_pdf.get("href"):
        resp_pdf_dl = sessao_autor.get(link_pdf["href"])
        print_sub(f"Download real do PDF anexado funcionou (HTTP 200, tamanho {len(resp_pdf_dl.content)} bytes)", resp_pdf_dl.status_code == 200 and resp_pdf_dl.content.startswith(b"%PDF"))

    # Link externo complementar (RF24)
    link_ext = soup_single.find("a", class_="link-externo")
    print_sub("Link externo complementar presente (RF24)", link_ext and "mozilla.org" in link_ext.get("href", ""))

    # Toolbar do Autor (RF34, RF36)
    toolbar = soup_single.find("div", class_="toolbar-autor")
    print_sub("Toolbar de gestão do autor visível para o autor (RF34/RF36)", toolbar is not None)

    # Extrai o link de edição
    link_editar = toolbar.find("a", class_="btn-autor-editar") if toolbar else None
    url_editar = link_editar["href"] if link_editar else None
    print_sub(f"Link de edição detectado: {url_editar}", url_editar is not None)

    # -------------------------------------------------------------
    # 5. Segurança: Outro Usuário Não Pode Gerenciar (RF34 / RF36)
    # -------------------------------------------------------------
    print_step("5. Validação de Segurança e Permissões (Não-autor)")
    resp_outro = sessao_outro.get(url_questao)
    soup_outro = BeautifulSoup(resp_outro.text, "html.parser")
    toolbar_outro = soup_outro.find("div", class_="toolbar-autor")
    print_sub("Toolbar de autor NÃO é exibida para outro estudante (RF34/RF36)", toolbar_outro is None)

    # Tentativa direta de edição por outro usuário
    resp_editar_outro = sessao_outro.get(url_editar)
    print_sub("Tentativa de edição por não-autor bloqueada com Permissão Negada (RF34)", "não possui permissão" in resp_editar_outro.text or "Acesso negado" in resp_editar_outro.text or resp_editar_outro.status_code in (403, 500))

    # -------------------------------------------------------------
    # 6. Edição de Questão e Remoção de Anexos (RF34, RF35)
    # -------------------------------------------------------------
    print_step("6. Edição de Conteúdo e Remoção Seletiva de Anexos (RF34, RF35)")
    resp_tela_edicao = sessao_autor.get(url_editar)
    print_sub("Tela de edição carrega dados da questão existente (RF34)", "Arquitetura MVC no Desenvolvimento Web Moderno" in resp_tela_edicao.text)
    print_sub("Script de edição injeta campos de remoção de PDF e imagens (RF35)", "remover_pdf" in resp_tela_edicao.text and "remover_imagem_idx" in resp_tela_edicao.text)

    # Extrair ID da questão a partir da URL de edição (?editar=123)
    id_questao = re.search(r'editar=(\d+)', url_editar).group(1)

    # Submete edição: altera título, remove o PDF e remove a 1ª imagem
    dados_edicao = {
        "id_questao_edicao": id_questao,
        "assunto": "Arquitetura MVC no Desenvolvimento Web Moderno [EDITADO]",
        "categoria": "engenharia_software",
        "enunciado": "No padrão arquitetural Model-View-Controller (MVC) [versão revisada], as responsabilidades são segregadas em três camadas fundamentais. Assinale a alternativa correta:",
        "alternativa_a": "Persistir dados permanentemente.",
        "alternativa_b": "Receber requisições e coordenar Model e View.",
        "alternativa_c": "Construir folha de estilos CSS.",
        "alternativa_d": "Comunicação em nível físico de rede.",
        "alternativa_e": "Executar o sistema operacional de baixo nível.",
        "resposta_correta": "B",
        "explicacao": "Explicação revista e atualizada pelo autor.",
        "link_externo": "https://unilab.edu.br",
        "remover_pdf": "1",             # RF35: Remover PDF
        "remover_imagem_idx[]": ["0"]   # RF35: Remover 1ª imagem do array
    }

    resp_pos_edicao = sessao_autor.post(
        f"{BASE_URL}/cadastrar-questao/",
        data=dados_edicao,
        allow_redirects=True
    )
    print_sub("Edição salva com sucesso", "Questão atualizada com sucesso pelo autor!" in resp_pos_edicao.text)

    # Re-verifica a página individual da questão para confirmar as alterações
    resp_rever = sessao_autor.get(url_questao)
    soup_rever = BeautifulSoup(resp_rever.text, "html.parser")
    
    titulo_rever = soup_rever.find("h1", class_="questao-titulo")
    print_sub("Título atualizado na visualização pública", "[EDITADO]" in (titulo_rever.text if titulo_rever else ""))

    bloco_pdf_rever = soup_rever.find("div", class_="bloco-pdf-anexado")
    print_sub("PDF removido com sucesso conforme solicitação (RF35)", bloco_pdf_rever is None)

    bloco_imgs_rever = soup_rever.find("div", class_="bloco-imagens-anexadas")
    imgs_rever = bloco_imgs_rever.find_all("img") if bloco_imgs_rever else []
    print_sub(f"Galeria atualizada para 1 imagem (1 removida, 1 preservada) (RF35)", len(imgs_rever) == 1)

    # -------------------------------------------------------------
    # 7. Exclusão de Questão com Nonce pelo Autor (RF36)
    # -------------------------------------------------------------
    print_step("7. Exclusão de Questão pelo Autor com Nonce Anti-CSRF (RF36)")
    # Obtém o token nonce do formulário modal na página
    form_del = soup_rever.find("div", id="modal-excluir-questao").find("form")
    nonce_input = form_del.find("input", {"name": "unilab_nonce"})
    print_sub("Nonce de segurança anti-CSRF gerado corretamente", nonce_input is not None and len(nonce_input["value"]) > 0)
    
    nonce_val = nonce_input["value"]
    resp_del = sessao_autor.post(
        url_questao,
        data={
            "unilab_acao_excluir": "1",
            "questao_id": id_questao,
            "unilab_nonce": nonce_val
        },
        allow_redirects=False
    )
    print_sub("Ação de exclusão executada (Redirecionamento HTTP 302 pós-exclusão)", resp_del.status_code == 302)

    # Confirma que a questão não existe mais
    resp_check_del = sessao_autor.get(url_questao)
    print_sub("Questão excluída definitivamente do sistema (HTTP 404 / Não encontrada) (RF36)", resp_check_del.status_code == 404 or "não encontrada" in resp_check_del.text.lower())

    # -------------------------------------------------------------
    # 8. Filtro de Disciplina e Busca (RF25, RF26, RF27)
    # -------------------------------------------------------------
    print_step("8. Busca de Questões e Filtro de Disciplinas (RF25, RF26, RF27)")
    # Teste de busca por termo no Banco de Questões
    resp_busca = sessao_autor.get(f"{BASE_URL}/?busca_q=MVC")
    print_sub("Busca por palavra-chave 'MVC' executada (HTTP 200) (RF25, RF26)", resp_busca.status_code == 200)
    soup_b = BeautifulSoup(resp_busca.text, "html.parser")
    pills_busca = [p.text.strip() for p in soup_b.find_all("span", class_="figma-tag-pill")]
    print_sub("Tag discreta de filtro de busca ativa renderizada na UI", any("Busca" in p for p in pills_busca))

    # Teste de filtro por disciplina na Sidebar
    resp_filtro = sessao_autor.get(f"{BASE_URL}/?filtro_disc=engenharia_software")
    print_sub("Filtro por Disciplina 'Engenharia de Software' executado (HTTP 200) (RF27)", resp_filtro.status_code == 200)
    soup_f = BeautifulSoup(resp_filtro.text, "html.parser")
    pills_filtro = [p.text.strip() for p in soup_f.find_all("span", class_="figma-tag-pill")]
    print_sub("Tag discreta de disciplina ativa renderizada na UI", any("Disciplina" in p for p in pills_filtro))

    print("\n" + "=" * 70)
    print("🎉 TODOS OS TESTES PASSARAM COM 100% DE SUCESSO!")
    print("=" * 70)

if __name__ == "__main__":
    main()
