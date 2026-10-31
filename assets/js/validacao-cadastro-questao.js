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
 * - RF20 / RNF12 / HU10: Validação de preenchimento completo com mensagens simples e sem termos técnicos
 * 
 * Autor: Assis Barroso (Fase 6 - Implementação)
 */

function inicializarValidacaoCadastro() {
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
            // Se estiver em ambiente mock de teste (sem action de backend definida), simula a confirmação
            if (!formCadastro.getAttribute('action')) {
                evento.preventDefault();
                exibirSucessoFormulario(formCadastro);
            } else {
                limparErrosFormulario(formCadastro);
            }
        }
    });

    configurarValidacaoTempoReal(formCadastro);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarValidacaoCadastro);
} else {
    inicializarValidacaoCadastro();
}

/**
 * Validação completa em conformidade com as regras de negócio
 * @param {HTMLFormElement} form
 * @returns {Array<{campo: string, mensagem: string}>}
 */
function validarFormularioQuestao(form) {
    const erros = [];

    // Assunto obrigatório
    const assunto = form.querySelector('[name="assunto"]');
    if (!assunto || !assunto.value.trim()) {
        erros.push({ campo: 'assunto', mensagem: 'Por favor, informe o assunto da questão.' });
    } else if (assunto.value.trim().length < 3) {
        erros.push({ campo: 'assunto', mensagem: 'O assunto deve ter pelo menos 3 caracteres.' });
    }

    // Categoria/Disciplina obrigatória
    const categoria = form.querySelector('[name="categoria"]');
    if (!categoria || !categoria.value.trim()) {
        erros.push({ campo: 'categoria', mensagem: 'Selecione uma disciplina ou categoria para a questão.' });
    }

    // Enunciado obrigatório (10 a 5.000 caracteres)
    const enunciado = form.querySelector('[name="enunciado"]');
    if (!enunciado || !enunciado.value.trim()) {
        erros.push({ campo: 'enunciado', mensagem: 'Por favor, digite o enunciado da questão.' });
    } else {
        const tamEnunciado = enunciado.value.trim().length;
        if (tamEnunciado < 10) {
            erros.push({ campo: 'enunciado', mensagem: 'O enunciado deve ter no mínimo 10 caracteres.' });
        } else if (tamEnunciado > 5000) {
            erros.push({ campo: 'enunciado', mensagem: 'O enunciado ultrapassou o tamanho máximo permitido de 5.000 caracteres.' });
        }
    }

    // Alternativas da questão (mínimo 2 preenchidas: A e B)
    const altA = form.querySelector('[name="alternativa_a"]');
    const altB = form.querySelector('[name="alternativa_b"]');

    if (!altA || !altA.value.trim()) {
        erros.push({ campo: 'alternativa_a', mensagem: 'A alternativa (A) é obrigatória.' });
    }
    if (!altB || !altB.value.trim()) {
        erros.push({ campo: 'alternativa_b', mensagem: 'A alternativa (B) é obrigatória (a questão precisa ter pelo menos duas alternativas).' });
    }

    // Mapeamento de alternativas preenchidas
    const letrasPreenchidas = [];
    ['a', 'b', 'c', 'd', 'e'].forEach((letra) => {
        const campoAlt = form.querySelector(`[name="alternativa_${letra}"]`);
        if (campoAlt && campoAlt.value.trim()) {
            letrasPreenchidas.push(letra.toUpperCase());
        }
    });

    // Resposta correta (gabarito)
    const respostaCorreta = form.querySelector('[name="resposta_correta"]');
    if (!respostaCorreta || !respostaCorreta.value.trim()) {
        erros.push({ campo: 'resposta_correta', mensagem: 'Selecione qual alternativa é a resposta correta.' });
    } else {
        const letraGabarito = respostaCorreta.value.trim().toUpperCase();
        if (letrasPreenchidas.length > 0 && !letrasPreenchidas.includes(letraGabarito)) {
            erros.push({
                campo: 'resposta_correta',
                mensagem: `Você selecionou a alternativa (${letraGabarito}) como correta, mas ela não foi preenchida. Escolha uma das preenchidas: ${letrasPreenchidas.join(', ')}.`
            });
        }
    }

    // Explicação obrigatória
    const explicacao = form.querySelector('[name="explicacao"]');
    if (!explicacao || !explicacao.value.trim()) {
        erros.push({ campo: 'explicacao', mensagem: 'Por favor, escreva uma breve explicação sobre a resposta correta.' });
    }

    // Anexos e arquivos (PDF até 10 MB, até 3 fotos de 5 MB, total até 20 MB)
    let tamanhoTotalAnexos = 0;
    const limiteConsolidado = 20 * 1024 * 1024; // 20 MB

    const inputPdf = form.querySelector('[name="anexo_pdf"]');
    if (inputPdf && inputPdf.files && inputPdf.files.length > 0) {
        if (inputPdf.files.length > 1) {
            erros.push({ campo: 'anexo_pdf', mensagem: 'É permitido anexar apenas 1 documento PDF.' });
        } else {
            const arquivoPdf = inputPdf.files[0];
            const limiteMaximoPdf = 10 * 1024 * 1024; // 10 MB
            tamanhoTotalAnexos += arquivoPdf.size;

            if (!arquivoPdf.name.toLowerCase().endsWith('.pdf') && arquivoPdf.type !== 'application/pdf') {
                erros.push({ campo: 'anexo_pdf', mensagem: 'O arquivo anexado precisa ser um documento em formato PDF.' });
            } else if (arquivoPdf.size > limiteMaximoPdf) {
                erros.push({ campo: 'anexo_pdf', mensagem: 'O documento PDF é muito grande. O limite máximo é de 10 MB.' });
            }
        }
    }

    const inputImagens = form.querySelector('[name="anexo_imagens"]');
    if (inputImagens && inputImagens.files && inputImagens.files.length > 0) {
        const limiteMaximoImg = 5 * 1024 * 1024; // 5 MB
        const formatosPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];

        if (inputImagens.files.length > 3) {
            erros.push({ campo: 'anexo_imagens', mensagem: 'Você pode enviar no máximo 3 imagens de apoio.' });
        }

        Array.from(inputImagens.files).forEach((img) => {
            tamanhoTotalAnexos += img.size;
            const extValida = img.name.match(/\.(jpg|jpeg|png)$/i);
            if (!formatosPermitidos.includes(img.type) && !extValida) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" deve estar no formato JPG ou PNG.` });
            } else if (img.size > limiteMaximoImg) {
                erros.push({ campo: 'anexo_imagens', mensagem: `A imagem "${img.name}" é muito pesada. O limite por imagem é de 5 MB.` });
            }
        });
    }

    if (tamanhoTotalAnexos > limiteConsolidado) {
        erros.push({
            campo: 'anexo_pdf',
            mensagem: 'O tamanho somado de todos os seus anexos passou de 20 MB. Reduza o tamanho dos arquivos para continuar.'
        });
    }

    // Link externo
    const linkExterno = form.querySelector('[name="link_externo"]');
    if (linkExterno && linkExterno.value.trim()) {
        const urlValor = linkExterno.value.trim();
        const urlRegex = /^https?:\/\/[a-zA-Z0-9\-\.]+(\.[a-zA-Z]{2,})+(:\d+)?(\/.*)?$/;
        if (!urlRegex.test(urlValor)) {
            erros.push({ campo: 'link_externo', mensagem: 'O endereço web informado é inválido. Ele deve começar com http:// ou https://' });
        }
    }

    return erros;
}

/**
 * Exibe erros contextuais ao lado dos campos (RNF12)
 */
function exibirErrosFormulario(form, erros) {
    limparErrosFormulario(form);

    const resumoSucesso = form.parentNode.querySelector('#resumo-sucesso') || document.getElementById('resumo-sucesso');
    if (resumoSucesso) {
        resumoSucesso.style.display = 'none';
    }

    const resumoErro = form.parentNode.querySelector('#resumo-erros') || document.getElementById('resumo-erros');
    if (resumoErro) {
        resumoErro.textContent = `Atenção: verifique os ${erros.length} campo(s) destacados abaixo para publicar a questão.`;
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
        primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Exibe mensagem de confirmação amigável
 */
function exibirSucessoFormulario(form) {
    limparErrosFormulario(form);

    const resumoSucesso = form.parentNode.querySelector('#resumo-sucesso') || document.getElementById('resumo-sucesso');
    if (resumoSucesso) {
        resumoSucesso.style.display = 'block';
        resumoSucesso.innerHTML = '<strong>Questão cadastrada com sucesso!</strong> A sua questão foi validada e já está pronta para o Banco de Questões da UNILAB.';
        resumoSucesso.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Limpa erros ao reenviar
 */
function limparErrosFormulario(form) {
    const resumoErro = form.parentNode.querySelector('#resumo-erros') || document.getElementById('resumo-erros');
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
 * Limpa erro do campo dinamicamente conforme o usuário digita
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
