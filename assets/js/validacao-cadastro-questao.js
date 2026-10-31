/**
 * validacao-cadastro-questao.js
 * Validação Frontend de Cadastro e Publicação de Questões (RF11 a RF20)
 * Projeto: Banco de Questões e Conteúdo Acadêmico - UNILAB
 * Autor: Assis Barroso (Fase 6 - Implementação)
 */

document.addEventListener('DOMContentLoaded', () => {
    const formCadastro = document.getElementById('form-cadastro-questao') || document.querySelector('.form-cadastro-questao');

    if (!formCadastro) {
        return;
    }

    formCadastro.addEventListener('submit', (evento) => {
        const erros = validarFormularioQuestao(formCadastro);

        if (erros.length > 0) {
            evento.preventDefault();
            exibirErrosFormulario(formCadastro, erros);
        } else {
            limparErrosFormulario(formCadastro);
        }
    });

    // Adiciona validação em tempo real para os campos de arquivo e link
    configurarValidacaoTempoReal(formCadastro);
});

/**
 * Executa a bateria de validações das regras de negócio (RF11 a RF20)
 * @param {HTMLFormElement} form
 * @returns {Array<{campo: string, mensagem: string}>} Lista de erros encontrados
 */
function validarFormularioQuestao(form) {
    const erros = [];

    // RF11: Assunto obrigatório
    const assunto = form.querySelector('[name="assunto"]');
    if (!assunto || !assunto.value.trim()) {
        erros.push({ campo: 'assunto', mensagem: 'O campo Assunto é obrigatório.' });
    } else if (assunto.value.trim().length < 3) {
        erros.push({ campo: 'assunto', mensagem: 'O assunto deve ter pelo menos 3 caracteres.' });
    }

    // RF15: Categoria/Disciplina obrigatória
    const categoria = form.querySelector('[name="categoria"]');
    if (!categoria || !categoria.value.trim()) {
        erros.push({ campo: 'categoria', mensagem: 'Selecione uma Categoria/Disciplina para a questão.' });
    }

    // RF12: Enunciado obrigatório (mínimo de 10 caracteres conforme análise de requisitos)
    const enunciado = form.querySelector('[name="enunciado"]');
    if (!enunciado || !enunciado.value.trim()) {
        erros.push({ campo: 'enunciado', mensagem: 'O campo Enunciado é obrigatório.' });
    } else if (enunciado.value.trim().length < 10) {
        erros.push({ campo: 'enunciado', mensagem: 'O enunciado deve ter pelo menos 10 caracteres.' });
    }

    // RF14: Alternativas A até E obrigatórias para o modelo de múltipla escolha
    const letras = ['a', 'b', 'c', 'd', 'e'];
    letras.forEach((letra) => {
        const alt = form.querySelector(`[name="alternativa_${letra}"]`);
        if (!alt || !alt.value.trim()) {
            erros.push({ campo: `alternativa_${letra}`, mensagem: `A alternativa (${letra.toUpperCase()}) deve ser preenchida.` });
        }
    });

    // RF14: Resposta Correta (gabarito) obrigatória
    const respostaCorreta = form.querySelector('[name="resposta_correta"]');
    if (!respostaCorreta || !respostaCorreta.value.trim()) {
        erros.push({ campo: 'resposta_correta', mensagem: 'Selecione qual alternativa é a resposta correta.' });
    }

    // RF13: Explicação do gabarito obrigatória
    const explicacao = form.querySelector('[name="explicacao"]');
    if (!explicacao || !explicacao.value.trim()) {
        erros.push({ campo: 'explicacao', mensagem: 'O campo Explicação do Gabarito é obrigatório.' });
    }

    // RF17: Validação do anexo PDF (máx. 10 MB e formato .pdf)
    const inputPdf = form.querySelector('[name="anexo_pdf"]');
    if (inputPdf && inputPdf.files && inputPdf.files.length > 0) {
        const arquivoPdf = inputPdf.files[0];
        const limiteMaximoPdf = 10 * 1024 * 1024; // 10 MB

        if (!arquivoPdf.name.toLowerCase().endsWith('.pdf') && arquivoPdf.type !== 'application/pdf') {
            erros.push({ campo: 'anexo_pdf', mensagem: 'O arquivo anexado deve ser obrigatoriamente no formato PDF.' });
        } else if (arquivoPdf.size > limiteMaximoPdf) {
            erros.push({ campo: 'anexo_pdf', mensagem: 'O arquivo PDF não pode ultrapassar o limite de 10 MB.' });
        }
    }

    // RF18: Validação de imagens anexadas (JPG/PNG, máx. 5 MB por imagem, máx. 3 imagens)
    const inputImagens = form.querySelector('[name="anexo_imagens"]');
    if (inputImagens && inputImagens.files && inputImagens.files.length > 0) {
        const limiteMaximoImg = 5 * 1024 * 1024; // 5 MB
        const formatosPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];

        if (inputImagens.files.length > 3) {
            erros.push({ campo: 'anexo_imagens', mensagem: 'É permitido anexar no máximo 3 imagens por questão.' });
        }

        Array.from(inputImagens.files).forEach((img, idx) => {
            const extValida = img.name.match(/\.(jpg|jpeg|png)$/i);
            if (!formatosPermitidos.includes(img.type) && !extValida) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" deve estar no formato JPG ou PNG.` });
            } else if (img.size > limiteMaximoImg) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" excede o tamanho máximo de 5 MB.` });
            }
        });
    }

    // RF19: Link externo validado (URL válida com http ou https)
    const linkExterno = form.querySelector('[name="link_externo"]');
    if (linkExterno && linkExterno.value.trim()) {
        const urlValor = linkExterno.value.trim();
        const urlRegex = /^https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(\/.*)?$/;
        if (!urlRegex.test(urlValor)) {
            erros.push({ campo: 'link_externo', mensagem: 'Insira uma URL válida iniciando com http:// ou https://' });
        }
    }

    return erros;
}

/**
 * Renderiza os erros na tela ao lado de cada campo (RF20)
 */
function exibirErrosFormulario(form, erros) {
    limparErrosFormulario(form);

    const resumoErro = form.querySelector('#resumo-erros') || form.querySelector('.resumo-erros');
    if (resumoErro) {
        resumoErro.textContent = `Atenção: existem ${erros.length} campo(s) com pendência de preenchimento.`;
        resumoErro.style.display = 'block';
    }

    erros.forEach((erro) => {
        const campoElemento = form.querySelector(`[name="${erro.campo}"]`);
        if (campoElemento) {
            campoElemento.classList.add('campo-invalido');
            campoElemento.style.borderColor = '#dc3545';

            // Cria mensagem de erro contextual abaixo do campo
            const msgErro = document.createElement('span');
            msgErro.className = 'mensagem-erro-campo';
            msgErro.style.color = '#dc3545';
            msgErro.style.fontSize = '12px';
            msgErro.style.display = 'block';
            msgErro.style.marginTop = '4px';
            msgErro.textContent = erro.mensagem;

            campoElemento.parentNode.insertBefore(msgErro, campoElemento.nextSibling);
        }
    });

    // Move o foco para o primeiro campo com erro
    const primeiroCampoErro = form.querySelector('.campo-invalido');
    if (primeiroCampoErro) {
        primeiroCampoErro.focus();
    }
}

/**
 * Remove classes e mensagens de erro existentes
 */
function limparErrosFormulario(form) {
    const resumoErro = form.querySelector('#resumo-erros') || form.querySelector('.resumo-erros');
    if (resumoErro) {
        resumoErro.style.display = 'none';
        resumoErro.textContent = '';
    }

    form.querySelectorAll('.campo-invalido').forEach((elem) => {
        elem.classList.remove('campo-invalido');
        elem.style.borderColor = '';
    });

    form.querySelectorAll('.mensagem-erro-campo').forEach((elem) => {
        elem.remove();
    });
}

/**
 * Limpa o erro do campo assim que o usuário digita ou altera
 */
function configurarValidacaoTempoReal(form) {
    form.querySelectorAll('input, textarea, select').forEach((campo) => {
        campo.addEventListener('input', () => {
            if (campo.classList.contains('campo-invalido')) {
                campo.classList.remove('campo-invalido');
                campo.style.borderColor = '';
                const proximo = campo.nextElementSibling;
                if (proximo && proximo.classList.contains('mensagem-erro-campo')) {
                    proximo.remove();
                }
            }
        });
    });
}
