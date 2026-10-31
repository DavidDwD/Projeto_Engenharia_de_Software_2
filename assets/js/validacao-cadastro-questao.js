/**
 * validacao-cadastro-questao.js
 * Validação Frontend de Cadastro e Publicação de Questões
 * 
 * Regras rastreadas:
 * - RF11 / HU10: Assunto obrigatório (mínimo 3 caracteres)
 * - RF12 / HU10: Enunciado obrigatório (limites: 10 a 5.000 caracteres)
 * - RF13 / HU10: Explicação do gabarito obrigatória
 * - RF14 / Fase 2: Mínimo 2 e máximo 5 alternativas (A e B obrigatórias; C, D, E opcionais)
 * - RF14 / Consistência: Resposta correta (gabarito) deve pertencer a uma das alternativas preenchidas
 * - RF15 / RN08 / HU10: Seleção obrigatória de Categoria/Disciplina
 * - RF17 / RN03 / RN04 / HU12: Anexo PDF (máximo 1 arquivo, formato estrito PDF, tamanho máx. 10 MB)
 * - RF18 / RN03 / RN04 / HU13: Anexo Imagens (máximo 3 arquivos, formatos JPG/PNG, tamanho máx. 5 MB por imagem)
 * - RN03 / HU12 / HU13: Limite consolidado de todos os anexos somados não pode exceder 20 MB
 * - RF19 / RN05 / HU14: Link externo com formato URL válido iniciando estritamente com http:// ou https://
 * - RF20 / RNF12 / HU10: Validação de preenchimento completo com feedback claro sem termos técnicos
 * 
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

    configurarValidacaoTempoReal(formCadastro);
});

/**
 * Validação completa em conformidade com as regras de negócio e requisitos
 * @param {HTMLFormElement} form
 * @returns {Array<{campo: string, mensagem: string}>}
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

    // RF15 / RN08: Categoria/Disciplina obrigatória
    const categoria = form.querySelector('[name="categoria"]');
    if (!categoria || !categoria.value.trim()) {
        erros.push({ campo: 'categoria', mensagem: 'Selecione uma Categoria ou Disciplina para a questão.' });
    }

    // RF12: Enunciado obrigatório (limites de 10 a 5.000 caracteres conforme Fase 2)
    const enunciado = form.querySelector('[name="enunciado"]');
    if (!enunciado || !enunciado.value.trim()) {
        erros.push({ campo: 'enunciado', mensagem: 'O campo Enunciado é obrigatório.' });
    } else {
        const tamEnunciado = enunciado.value.trim().length;
        if (tamEnunciado < 10) {
            erros.push({ campo: 'enunciado', mensagem: 'O enunciado deve ter no mínimo 10 caracteres.' });
        } else if (tamEnunciado > 5000) {
            erros.push({ campo: 'enunciado', mensagem: 'O enunciado não pode ultrapassar o limite de 5.000 caracteres.' });
        }
    }

    // RF14 / Fase 2: Cadastrar de 2 a 5 alternativas
    // Alternativas A e B são obrigatórias (mínimo de 2 para múltipla escolha ou V/F)
    const altA = form.querySelector('[name="alternativa_a"]');
    const altB = form.querySelector('[name="alternativa_b"]');

    if (!altA || !altA.value.trim()) {
        erros.push({ campo: 'alternativa_a', mensagem: 'A alternativa (A) é obrigatória.' });
    }
    if (!altB || !altB.value.trim()) {
        erros.push({ campo: 'alternativa_b', mensagem: 'A alternativa (B) é obrigatória (mínimo de 2 alternativas).' });
    }

    // Coleta quais letras foram preenchidas pelo usuário
    const letrasPreenchidas = [];
    ['a', 'b', 'c', 'd', 'e'].forEach((letra) => {
        const campoAlt = form.querySelector(`[name="alternativa_${letra}"]`);
        if (campoAlt && campoAlt.value.trim()) {
            letrasPreenchidas.push(letra.toUpperCase());
        }
    });

    // RF14: Resposta Correta (gabarito) obrigatória e consistente
    const respostaCorreta = form.querySelector('[name="resposta_correta"]');
    if (!respostaCorreta || !respostaCorreta.value.trim()) {
        erros.push({ campo: 'resposta_correta', mensagem: 'Selecione qual alternativa é a resposta correta.' });
    } else {
        const letraGabarito = respostaCorreta.value.trim().toUpperCase();
        if (!letrasPreenchidas.includes(letraGabarito)) {
            erros.push({
                campo: 'resposta_correta',
                mensagem: `A resposta correta selecionada (${letraGabarito}) não foi preenchida nas alternativas. Escolha entre: ${letrasPreenchidas.join(', ')}.`
            });
        }
    }

    // RF13: Explicação do gabarito obrigatória
    const explicacao = form.querySelector('[name="explicacao"]');
    if (!explicacao || !explicacao.value.trim()) {
        erros.push({ campo: 'explicacao', mensagem: 'O campo Explicação do Gabarito é obrigatório.' });
    }

    // RN03 / RN04 / RF17 / RF18 / HU12 / HU13: Validação de arquivos e tamanho total consolidado (20 MB)
    let tamanhoTotalAnexos = 0;
    const limiteConsolidado = 20 * 1024 * 1024; // 20 MB

    // RF17: Validação do anexo PDF (máx. 1 arquivo, formato .pdf, máx. 10 MB)
    const inputPdf = form.querySelector('[name="anexo_pdf"]');
    if (inputPdf && inputPdf.files && inputPdf.files.length > 0) {
        if (inputPdf.files.length > 1) {
            erros.push({ campo: 'anexo_pdf', mensagem: 'É permitido anexar no máximo 1 arquivo PDF.' });
        } else {
            const arquivoPdf = inputPdf.files[0];
            const limiteMaximoPdf = 10 * 1024 * 1024; // 10 MB
            tamanhoTotalAnexos += arquivoPdf.size;

            if (!arquivoPdf.name.toLowerCase().endsWith('.pdf') && arquivoPdf.type !== 'application/pdf') {
                erros.push({ campo: 'anexo_pdf', mensagem: 'O arquivo anexado deve ser obrigatoriamente no formato PDF.' });
            } else if (arquivoPdf.size > limiteMaximoPdf) {
                erros.push({ campo: 'anexo_pdf', mensagem: 'O arquivo PDF não pode ultrapassar o limite de 10 MB.' });
            }
        }
    }

    // RF18: Validação de imagens anexadas (JPG/PNG, máx. 5 MB cada, máx. 3 imagens)
    const inputImagens = form.querySelector('[name="anexo_imagens"]');
    if (inputImagens && inputImagens.files && inputImagens.files.length > 0) {
        const limiteMaximoImg = 5 * 1024 * 1024; // 5 MB
        const formatosPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];

        if (inputImagens.files.length > 3) {
            erros.push({ campo: 'anexo_imagens', mensagem: 'É permitido anexar no máximo 3 imagens por questão.' });
        }

        Array.from(inputImagens.files).forEach((img) => {
            tamanhoTotalAnexos += img.size;
            const extValida = img.name.match(/\.(jpg|jpeg|png)$/i);
            if (!formatosPermitidos.includes(img.type) && !extValida) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" deve estar no formato JPG ou PNG.` });
            } else if (img.size > limiteMaximoImg) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" excede o tamanho máximo de 5 MB.` });
            }
        });
    }

    // RN03: Limite consolidado de todos os anexos não pode ultrapassar 20 MB
    if (tamanhoTotalAnexos > limiteConsolidado) {
        erros.push({
            campo: 'anexo_pdf',
            mensagem: 'O tamanho total consolidado de todos os arquivos anexados (PDF + imagens) não pode ultrapassar 20 MB.'
        });
    }

    // RF19 / RN05: Link externo validado (estritamente http:// ou https://)
    const linkExterno = form.querySelector('[name="link_externo"]');
    if (linkExterno && linkExterno.value.trim()) {
        const urlValor = linkExterno.value.trim();
        const urlRegex = /^https?:\/\/[a-zA-Z0-9\-\.]+(\.[a-zA-Z]{2,})+(:\d+)?(\/.*)?$/;
        if (!urlRegex.test(urlValor)) {
            erros.push({ campo: 'link_externo', mensagem: 'O link externo deve ser uma URL válida iniciando com http:// ou https://' });
        }
    }

    return erros;
}

/**
 * Exibe os erros de forma clara ao lado de cada campo (RF20 / RNF12)
 */
function exibirErrosFormulario(form, erros) {
    limparErrosFormulario(form);

    const resumoErro = form.querySelector('#resumo-erros') || form.querySelector('.resumo-erros');
    if (resumoErro) {
        resumoErro.textContent = `Atenção: existem ${erros.length} pendência(s) no formulário que impedem a publicação da questão.`;
        resumoErro.style.display = 'block';
    }

    erros.forEach((erro) => {
        const campoElemento = form.querySelector(`[name="${erro.campo}"]`);
        if (campoElemento) {
            campoElemento.classList.add('campo-invalido');
            campoElemento.style.borderColor = '#dc3545';

            const msgErro = document.createElement('span');
            msgErro.className = 'mensagem-erro-campo';
            msgErro.style.color = '#dc3545';
            msgErro.style.fontSize = '12px';
            msgErro.style.display = 'block';
            msgErro.style.marginTop = '4px';
            msgErro.style.fontWeight = 'normal';
            msgErro.textContent = erro.mensagem;

            campoElemento.parentNode.insertBefore(msgErro, campoElemento.nextSibling);
        }
    });

    const primeiroErro = form.querySelector('.campo-invalido');
    if (primeiroErro) {
        primeiroErro.focus();
    }
}

/**
 * Remove erros anteriores
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
 * Limpa o erro do campo dinamicamente conforme o usuário altera seu valor
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
