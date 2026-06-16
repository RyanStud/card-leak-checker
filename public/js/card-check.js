/*
 * Verificação de cartão: restringe os campos numéricos (número, mês, ano, CVV)
 * a apenas dígitos — bloqueia digitação de letras/símbolos, limpa o que for
 * colado e respeita o maxlength de cada campo.
 *
 * A lógica pura (sanitizeDigits/shouldBlockKey/luhnIsValid) é exportada para
 * testes em Node (ver tests/card-check.test.js); no browser, o arquivo se
 * auto-conecta ao DOM.
 */
(function () {
    'use strict';

    // Mesma mensagem do servidor (CardController) para o usuário ver o mesmo
    // texto, falhando o Luhn no cliente ou no servidor.
    var LUHN_MESSAGE = 'O cartão informado falhou na validação Luhn.';

    // Pure: mantém apenas dígitos e corta no maxLength (<= 0 = sem limite).
    function sanitizeDigits(value, maxLength) {
        var digits = String(value === null || value === undefined ? '' : value).replace(/\D+/g, '');
        if (maxLength && maxLength > 0) {
            digits = digits.slice(0, maxLength);
        }
        return digits;
    }

    // Pure: algoritmo de Luhn, espelhando luhn_is_valid() do PHP. Recebe só os
    // dígitos; string vazia é tratada como inválida (Luhn de "nada" não faz
    // sentido — o caminho real só chama com 13–19 dígitos).
    function luhnIsValid(digits) {
        var str = sanitizeDigits(digits, 0);
        if (str.length === 0) {
            return false;
        }

        var sum = 0;
        var alt = false;

        for (var i = str.length - 1; i >= 0; i--) {
            var n = parseInt(str.charAt(i), 10);

            if (alt) {
                n *= 2;
                if (n > 9) {
                    n -= 9;
                }
            }

            sum += n;
            alt = !alt;
        }

        return sum % 10 === 0;
    }

    // Pure: decide se a tecla digitada deve ser bloqueada.
    // Deixa passar atalhos (Ctrl/Cmd/Alt) e teclas de controle/navegação
    // (Backspace, Tab, setas — que têm key.length !== 1).
    function shouldBlockKey(key, modifiers) {
        modifiers = modifiers || {};
        if (modifiers.ctrlKey || modifiers.metaKey || modifiers.altKey) {
            return false;
        }
        if (typeof key !== 'string' || key.length !== 1) {
            return false;
        }
        return /\D/.test(key);
    }

    function enforceDigits(el) {
        if (!el) {
            return;
        }

        var maxLength = parseInt(el.getAttribute('maxlength') || '0', 10);

        var sanitize = function () {
            var digits = sanitizeDigits(el.value, maxLength);
            if (digits !== el.value) {
                el.value = digits;
            }
        };

        el.addEventListener('keydown', function (e) {
            if (shouldBlockKey(e.key, e)) {
                e.preventDefault();
            }
        });

        // O evento input cobre colar, arrastar e autopreenchimento; o handler de
        // paste é reforço (setTimeout para ler o valor já colado).
        el.addEventListener('input', sanitize);
        el.addEventListener('paste', function () {
            setTimeout(sanitize, 0);
        });
    }

    // Bloqueia o envio quando o número falha no Luhn, sem recarregar a página
    // (o que limparia o formulário): marca o campo como inválido via
    // setCustomValidity e a validação nativa do browser segura o submit e
    // mostra a mensagem. Só avalia com 13–19 dígitos — tamanho/obrigatoriedade
    // ficam por conta do pattern/required nativos.
    function enforceLuhn(el) {
        if (!el || typeof el.setCustomValidity !== 'function') {
            return;
        }

        var validate = function () {
            var digits = sanitizeDigits(el.value, 0);
            if (digits.length >= 13 && digits.length <= 19 && !luhnIsValid(digits)) {
                el.setCustomValidity(LUHN_MESSAGE);
            } else {
                el.setCustomValidity('');
            }
        };

        el.addEventListener('input', validate);
        el.addEventListener('blur', validate);
        validate();
    }

    function attach(doc) {
        var wire = function () {
            ['card_number', 'expiry_month', 'expiry_year', 'cvv'].forEach(function (name) {
                enforceDigits(doc.querySelector('[name="' + name + '"]'));
            });
            enforceLuhn(doc.querySelector('[name="card_number"]'));
        };

        if (doc.readyState === 'loading') {
            doc.addEventListener('DOMContentLoaded', wire);
        } else {
            wire();
        }
    }

    var api = {
        sanitizeDigits: sanitizeDigits,
        shouldBlockKey: shouldBlockKey,
        luhnIsValid: luhnIsValid,
        enforceDigits: enforceDigits,
        enforceLuhn: enforceLuhn,
        attach: attach
    };

    // Browser: conecta ao DOM. (typeof é seguro mesmo com a variável indefinida.)
    if (typeof document !== 'undefined') {
        attach(document);
    }

    // Node/CommonJS: exporta os helpers para os testes.
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    }
})();
