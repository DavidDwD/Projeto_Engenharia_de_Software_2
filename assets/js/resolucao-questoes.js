/**
 * resolucao-questoes.js
 * Módulo de Resolução Interativa de Questões de Múltipla Escolha
 * Projeto: Banco de Questões e Conteúdo Acadêmico - UNILAB
 * Autor: Assis Barroso (Fase 6 - Implementação)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Busca todos os cards de questão presentes na página
    const cards = document.querySelectorAll('.card-questao');

    if (!cards || cards.length === 0) {
        return;
    }

    cards.forEach((card) => {
        inicializarCardQuestao(card);
    });
});

/**
 * Inicializa os ouvintes e o estado de uma questão
 * @param {HTMLElement} card - Elemento container da questão (.card-questao)
 */
function inicializarCardQuestao(card) {
    const gabarito = (card.dataset.gabarito || '').trim().toUpperCase();
    const alternativas = card.querySelectorAll('.alternativa');
    const btnResponder = card.querySelector('#btn-responder') || card.querySelector('.btn-responder');
    const feedbackBox = card.querySelector('#feedback-box') || card.querySelector('.feedback-box');
    const feedbackTitulo = card.querySelector('#feedback-titulo');

    let alternativaSelecionada = null;
    let questaoRespondida = false;

    // 1. Gerencia a seleção de alternativas pelo clique do aluno
    alternativas.forEach((alternativa) => {
        alternativa.addEventListener('click', () => {
            // Se já respondeu e conferiu o gabarito, bloqueia nova troca
            if (questaoRespondida) {
                return;
            }

            // Remove a seleção visual de todas as alternativas deste card
            alternativas.forEach((alt) => alt.classList.remove('selecionada'));

            // Marca a alternativa clicada
            alternativa.classList.add('selecionada');
            alternativaSelecionada = alternativa;

            // Limpa eventuais avisos de erro prévios ao selecionar
            if (feedbackBox && !questaoRespondida) {
                feedbackBox.classList.remove('visivel');
            }
        });
    });

    // 2. Ação de clique no botão "Responder"
    if (!btnResponder) {
        return;
    }

    btnResponder.addEventListener('click', () => {
        if (questaoRespondida) {
            return;
        }

        // Validação: Aluno tentou responder sem selecionar nenhuma alternativa
        if (!alternativaSelecionada) {
            exibirAvisoSemSelecao(feedbackBox, feedbackTitulo);
            return;
        }

        // Confirmação e verificação da resposta
        questaoRespondida = true;
        const letraEscolhida = (alternativaSelecionada.dataset.letra || '').trim().toUpperCase();
        const estaCorreta = (letraEscolhida === gabarito);

        // Desabilita o botão para evitar múltiplos cliques
        btnResponder.disabled = true;
        btnResponder.style.opacity = '0.6';
        btnResponder.style.cursor = 'not-allowed';
        btnResponder.textContent = 'Respondido';

        // 3. Aplica estilos visuais nas alternativas
        alternativas.forEach((alt) => {
            const letra = (alt.dataset.letra || '').trim().toUpperCase();
            alt.style.cursor = 'default';

            // Destaca a alternativa correta em verde
            if (letra === gabarito) {
                alt.classList.add('correta');
            }

            // Se o aluno marcou errado, marca a selecionada em vermelho
            if (alt === alternativaSelecionada && !estaCorreta) {
                alt.classList.add('incorreta');
            }
        });

        // 4. Exibe a caixa de feedback e explicação
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
        }
    });
}

/**
 * Exibe mensagem temporária se o aluno tentar responder sem marcar uma opção
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
