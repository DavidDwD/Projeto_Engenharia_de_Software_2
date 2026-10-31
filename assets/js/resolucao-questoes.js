/**
 * resolucao-questoes.js
 * Módulo de Resolução Interativa de Questões
 * 
 * Regras rastreadas:
 * - RF14 / Fase 2: Resolução de questões com 2 a 5 alternativas (A até E dinamicamente)
 * - RF13 / HU15: Exibição da explicação após a resposta
 * - Fase 2 (Slide 15) / Fase 3 (Seção 5.2): Suporte opcional a novas tentativas ($allow_retry via data-permitir-tentativas)
 * - RF24 / HU18: Links externos abrem em nova aba com segurança (target="_blank" rel="noopener noreferrer")
 * - RNF12: Mensagens claras sem termos técnicos ao tentar responder sem seleção prévia
 * 
 * Projeto: Banco de Questões e Conteúdo Acadêmico - UNILAB
 * Autor: Assis Barroso (Fase 6 - Implementação)
 */

document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.card-questao');

    if (!cards || cards.length === 0) {
        return;
    }

    cards.forEach((card) => {
        inicializarCardQuestao(card);
    });

    // RF24 / HU18: Garante que qualquer link externo abra em nova aba com segurança
    configurarLinksExternos();
});

/**
 * Inicializa os ouvintes e o ciclo de vida interativo de uma questão
 * @param {HTMLElement} card - Container da questão (.card-questao)
 */
function inicializarCardQuestao(card) {
    const gabarito = (card.dataset.gabarito || '').trim().toUpperCase();
    const permitirNovasTentativas = card.dataset.permitirTentativas === 'true' || card.dataset.permitirTentativas === '1';
    const alternativas = card.querySelectorAll('.alternativa');
    const btnResponder = card.querySelector('#btn-responder') || card.querySelector('.btn-responder');
    const feedbackBox = card.querySelector('#feedback-box') || card.querySelector('.feedback-box');
    const feedbackTitulo = card.querySelector('#feedback-titulo');

    let alternativaSelecionada = null;
    let questaoRespondida = false;

    // 1. Gerencia seleção de alternativas (suporta de 2 a 5 alternativas dinâmicas)
    alternativas.forEach((alternativa) => {
        alternativa.addEventListener('click', () => {
            if (questaoRespondida) {
                return;
            }

            alternativas.forEach((alt) => alt.classList.remove('selecionada'));
            alternativa.classList.add('selecionada');
            alternativaSelecionada = alternativa;

            // Remove aviso de seleção pendente se houver
            if (feedbackBox && !questaoRespondida) {
                feedbackBox.classList.remove('visivel');
            }
        });
    });

    // 2. Ação ao clicar em "Responder"
    if (!btnResponder) {
        return;
    }

    btnResponder.addEventListener('click', () => {
        if (questaoRespondida) {
            return;
        }

        // RNF12: Alerta se não houver seleção
        if (!alternativaSelecionada) {
            exibirAvisoSemSelecao(feedbackBox, feedbackTitulo);
            return;
        }

        questaoRespondida = true;
        const letraEscolhida = (alternativaSelecionada.dataset.letra || '').trim().toUpperCase();
        const estaCorreta = (letraEscolhida === gabarito);

        btnResponder.disabled = true;
        btnResponder.style.opacity = '0.6';
        btnResponder.style.cursor = 'not-allowed';
        btnResponder.textContent = 'Respondido';

        // 3. Aplica estilos visuais nas alternativas presentes
        alternativas.forEach((alt) => {
            const letra = (alt.dataset.letra || '').trim().toUpperCase();
            alt.style.cursor = 'default';

            if (letra === gabarito) {
                alt.classList.add('correta');
            }

            if (alt === alternativaSelecionada && !estaCorreta) {
                alt.classList.add('incorreta');
            }
        });

        // 4. Exibe a caixa de feedback e explicação (RF13)
        if (feedbackBox) {
            feedbackBox.classList.add('visivel');

            if (estaCorreta) {
                feedbackBox.style.backgroundColor = '#d4edda';
                feedbackBox.style.color = '#155724';
                feedbackBox.style.border = '1px solid #c3e6cb';
                if (feedbackTitulo) {
                    feedbackTitulo.textContent = 'Parabéns, você acertou!';
                    feedbackTitulo.style.color = '#155724';
                }
            } else {
                feedbackBox.style.backgroundColor = '#f8d7da';
                feedbackBox.style.color = '#721c24';
                feedbackBox.style.border = '1px solid #f5c6cb';
                if (feedbackTitulo) {
                    feedbackTitulo.textContent = `Você errou. A alternativa correta é a (${gabarito}).`;
                    feedbackTitulo.style.color = '#721c24';
                }
            }

            // Fase 2 (Slide 15) / Fase 3 (Seção 5.2): Se novas tentativas forem permitidas
            if (permitirNovasTentativas) {
                adicionarBotaoTentarNovamente(card, () => {
                    resetarEstadoQuestao();
                });
            }
        }
    });

    /**
     * Reseta o estado para uma nova tentativa se permitido pelo autor
     */
    function resetarEstadoQuestao() {
        questaoRespondida = false;
        alternativaSelecionada = null;

        alternativas.forEach((alt) => {
            alt.classList.remove('selecionada', 'correta', 'incorreta');
            alt.style.cursor = 'pointer';
        });

        if (feedbackBox) {
            feedbackBox.classList.remove('visivel');
        }

        btnResponder.disabled = false;
        btnResponder.style.opacity = '1';
        btnResponder.style.cursor = 'pointer';
        btnResponder.textContent = 'Responder';

        const btnRetry = card.querySelector('.btn-tentar-novamente');
        if (btnRetry) {
            btnRetry.remove();
        }
    }
}

/**
 * Cria dinamicamente o botão de tentar novamente (Slide 15 Fase 2 / Seção 5.2 Fase 3)
 */
function adicionarBotaoTentarNovamente(card, onReset) {
    if (card.querySelector('.btn-tentar-novamente')) {
        return;
    }

    const btnRetry = document.createElement('button');
    btnRetry.type = 'button';
    btnRetry.className = 'btn-tentar-novamente';
    btnRetry.textContent = 'Tentar Novamente';
    btnRetry.style.marginTop = '12px';
    btnRetry.style.padding = '8px 16px';
    btnRetry.style.backgroundColor = '#6c757d';
    btnRetry.style.color = '#fff';
    btnRetry.style.border = 'none';
    btnRetry.style.borderRadius = '4px';
    btnRetry.style.cursor = 'pointer';
    btnRetry.style.fontSize = '14px';

    btnRetry.addEventListener('click', onReset);

    const feedbackBox = card.querySelector('#feedback-box') || card.querySelector('.feedback-box');
    if (feedbackBox) {
        feedbackBox.appendChild(btnRetry);
    } else {
        card.appendChild(btnRetry);
    }
}

/**
 * RNF12: Alerta claro se o aluno tentar responder sem selecionar
 */
function exibirAvisoSemSelecao(box, titulo) {
    if (!box) {
        alert('Por favor, selecione uma alternativa antes de responder.');
        return;
    }

    box.classList.add('visivel');
    box.style.backgroundColor = '#fff3cd';
    box.style.color = '#856404';
    box.style.border = '1px solid #ffeeba';

    if (titulo) {
        titulo.textContent = 'Atenção: Selecione uma alternativa antes de responder.';
        titulo.style.color = '#856404';
    }
}

/**
 * RF24 / HU18: Configura links externos para abrir com segurança em nova aba
 */
function configurarLinksExternos() {
    document.querySelectorAll('a[href^="http://"], a[href^="https://"]').forEach((link) => {
        const urlDestino = link.getAttribute('href');
        if (urlDestino && !urlDestino.includes(window.location.hostname)) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
}
