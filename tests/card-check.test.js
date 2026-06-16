'use strict';

/*
 * Testes da sanitização dos campos numéricos do check-card.
 * Roda sem dependências externas: `node --test tests/`
 */

const test = require('node:test');
const assert = require('node:assert/strict');

const { sanitizeDigits, shouldBlockKey, luhnIsValid, enforceDigits, enforceLuhn, attach } = require('../public/js/card-check.js');

// --- sanitizeDigits (lógica pura) -----------------------------------------
test('sanitizeDigits remove letras e símbolos', () => {
    assert.equal(sanitizeDigits('4111aaaa1111', 19), '41111111');
    assert.equal(sanitizeDigits('4111-1111 1111', 19), '411111111111');
    assert.equal(sanitizeDigits('ab12cd34!@#', 19), '1234');
});

test('sanitizeDigits trunca no maxLength', () => {
    assert.equal(sanitizeDigits('12345678', 4), '1234'); // CVV
    assert.equal(sanitizeDigits('083', 2), '08');         // mês
    assert.equal(sanitizeDigits('20299', 4), '2029');     // ano
});

test('sanitizeDigits sem limite quando maxLength <= 0', () => {
    assert.equal(sanitizeDigits('123456', 0), '123456');
    assert.equal(sanitizeDigits('123456', -1), '123456');
});

test('sanitizeDigits lida com vazio/null/undefined', () => {
    assert.equal(sanitizeDigits('', 4), '');
    assert.equal(sanitizeDigits(null, 4), '');
    assert.equal(sanitizeDigits(undefined, 4), '');
    assert.equal(sanitizeDigits('abc', 4), '');
});

test('sanitizeDigits considera apenas dígitos ASCII', () => {
    // \d em JS (sem flag u) casa só [0-9]; dígitos não-ASCII são removidos.
    assert.equal(sanitizeDigits('１２３4', 19), '4'); // fullwidth removidos
    assert.equal(sanitizeDigits('٤٥6', 19), '6');     // arábico-índicos removidos
});

// --- shouldBlockKey (lógica pura) -----------------------------------------
test('shouldBlockKey bloqueia letras e símbolos', () => {
    assert.equal(shouldBlockKey('a'), true);
    assert.equal(shouldBlockKey('Z'), true);
    assert.equal(shouldBlockKey('-'), true);
    assert.equal(shouldBlockKey(' '), true);
});

test('shouldBlockKey permite dígitos', () => {
    for (let d = 0; d <= 9; d++) {
        assert.equal(shouldBlockKey(String(d)), false);
    }
});

test('shouldBlockKey permite teclas de controle/navegação', () => {
    assert.equal(shouldBlockKey('Backspace'), false);
    assert.equal(shouldBlockKey('Tab'), false);
    assert.equal(shouldBlockKey('ArrowLeft'), false);
    assert.equal(shouldBlockKey('Delete'), false);
});

test('shouldBlockKey permite atalhos (Ctrl/Cmd/Alt)', () => {
    assert.equal(shouldBlockKey('v', { ctrlKey: true }), false); // Ctrl+V
    assert.equal(shouldBlockKey('c', { metaKey: true }), false); // Cmd+C
    assert.equal(shouldBlockKey('a', { altKey: true }), false);
});

// --- luhnIsValid (lógica pura, espelha o PHP) -----------------------------
test('luhnIsValid aceita números válidos conhecidos', () => {
    assert.equal(luhnIsValid('4111111111111111'), true); // Visa de teste
    assert.equal(luhnIsValid('5500005555555559'), true); // Mastercard de teste
    assert.equal(luhnIsValid('79927398713'), true);       // exemplo clássico
});

test('luhnIsValid rejeita números com dígito verificador errado', () => {
    assert.equal(luhnIsValid('4111111111111112'), false);
    assert.equal(luhnIsValid('1234567890123456'), false);
    assert.equal(luhnIsValid('79927398710'), false);
});

test('luhnIsValid ignora separadores e trata vazio como inválido', () => {
    assert.equal(luhnIsValid('4111 1111 1111 1111'), true);
    assert.equal(luhnIsValid(''), false);
    assert.equal(luhnIsValid('abc'), false);
});

// --- enforceLuhn (wiring com setCustomValidity) ---------------------------
test('enforceLuhn marca o campo inválido quando o Luhn falha', () => {
    const el = makeFakeInput(19);
    enforceLuhn(el);

    el.value = '4111111111111112'; // 16 dígitos, Luhn inválido
    el.dispatch('input');

    assert.notEqual(el.validationMessage, ''); // submit nativo fica bloqueado
});

test('enforceLuhn libera o campo quando o Luhn passa', () => {
    const el = makeFakeInput(19);
    enforceLuhn(el);

    el.value = '4111111111111111';
    el.dispatch('input');

    assert.equal(el.validationMessage, '');
});

test('enforceLuhn não avalia Luhn com menos de 13 dígitos', () => {
    const el = makeFakeInput(19);
    enforceLuhn(el);

    el.value = '4111'; // incompleto: deixa o pattern/required nativos cuidarem
    el.dispatch('input');

    assert.equal(el.validationMessage, '');
});

test('enforceLuhn é seguro com elemento ausente', () => {
    assert.doesNotThrow(() => enforceLuhn(null));
});

// --- enforceDigits (wiring com nó de input falso) -------------------------
function makeFakeInput(maxlength) {
    const listeners = {};
    return {
        value: '',
        validationMessage: '',
        getAttribute(name) {
            return name === 'maxlength' ? String(maxlength) : null;
        },
        setCustomValidity(msg) {
            this.validationMessage = msg;
        },
        addEventListener(type, fn) {
            (listeners[type] = listeners[type] || []).push(fn);
        },
        dispatch(type, evt) {
            (listeners[type] || []).forEach((fn) => fn(evt || {}));
        }
    };
}

test('enforceDigits limpa e trunca no evento input (cobre colar)', () => {
    const el = makeFakeInput(4);
    enforceDigits(el);

    el.value = 'ab12cd34xx';   // simula um paste de conteúdo misto
    el.dispatch('input');

    assert.equal(el.value, '1234'); // dígitos extraídos e truncados ao maxlength
});

test('enforceDigits bloqueia keydown não numérico e libera dígito', () => {
    const el = makeFakeInput(19);
    enforceDigits(el);

    let prevented = false;
    el.dispatch('keydown', { key: 'a', preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);

    prevented = false;
    el.dispatch('keydown', { key: '7', preventDefault: () => { prevented = true; } });
    assert.equal(prevented, false);
});

test('enforceDigits é seguro com elemento ausente', () => {
    assert.doesNotThrow(() => enforceDigits(null));
});

// --- attach (cobre os 4 campos + reforço de Luhn no número) ----------------
test('attach conecta os campos numéricos e o Luhn no número', () => {
    const selectors = [];
    const doc = {
        readyState: 'complete',
        addEventListener() {},
        querySelector(sel) {
            selectors.push(sel);
            return makeFakeInput(19);
        }
    };

    attach(doc);

    assert.deepEqual(selectors, [
        '[name="card_number"]',
        '[name="expiry_month"]',
        '[name="expiry_year"]',
        '[name="cvv"]',
        '[name="card_number"]' // enforceLuhn reaproveita o campo do número
    ]);
});
