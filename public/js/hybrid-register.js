/*
 * S.3.1 - Criptografia híbrida no cadastro.
 *
 * Ao enviar o formulário:
 *   a) busca a chave pública RSA do back;
 *   b) gera uma chave de sessão simétrica (AES-256-GCM) no front;
 *   c) cifra a chave de sessão com a chave pública (RSA-OAEP) e a envia ao back;
 *   d) cifra os dados do formulário com a chave de sessão;
 *   e) envia somente o conteúdo cifrado (os campos em claro não são submetidos).
 *
 * Interop com PHP/OpenSSL: RSA-OAEP usa SHA-1 (ver hybrid_crypto.php).
 */
(function () {
    'use strict';

    const form = document.getElementById('register-form');
    if (!form) {
        return;
    }

    const statusEl = document.getElementById('crypto-status');
    const pubKeyUrl = form.getAttribute('data-pubkey-url');

    function setStatus(text) {
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    // --- utilidades de codificação ---------------------------------------
    function bufToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    function pemToArrayBuffer(pem) {
        const base64 = pem
            .replace(/-----BEGIN [^-]+-----/, '')
            .replace(/-----END [^-]+-----/, '')
            .replace(/\s+/g, '');
        const binary = window.atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    // --- criptografia híbrida --------------------------------------------
    async function fetchPublicKey() {
        const response = await fetch(pubKeyUrl, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error('Falha ao obter a chave pública (HTTP ' + response.status + ').');
        }
        const data = await response.json();
        console.log('[S.3.1.a] Chave pública obtida do back:\n' + data.publicKey);
        console.log('[S.3.1.a] Fingerprint (SHA-256) da chave pública:', data.fingerprint);

        const publicKey = await window.crypto.subtle.importKey(
            'spki',
            pemToArrayBuffer(data.publicKey),
            { name: 'RSA-OAEP', hash: 'SHA-1' },
            false,
            ['encrypt']
        );
        return publicKey;
    }

    function collectFormData() {
        const consent = form.querySelector('input[name="lgpd_consent"]');
        return {
            name: (form.querySelector('input[name="name"]') || {}).value || '',
            email: (form.querySelector('input[name="email"]') || {}).value || '',
            password: (form.querySelector('input[name="password"]') || {}).value || '',
            captcha_answer: (form.querySelector('input[name="captcha_answer"]') || {}).value || '',
            lgpd_consent: consent && consent.checked ? '1' : ''
        };
    }

    function setHiddenField(name, value) {
        let field = form.querySelector('input[type="hidden"][data-crypto="' + name + '"]');
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            field.setAttribute('data-crypto', name);
            form.appendChild(field);
        }
        field.value = value;
    }

    // Remove o atributo name dos campos em claro para que NÃO sejam enviados.
    function stripPlaintextFields() {
        ['name', 'email', 'password', 'captcha_answer', 'lgpd_consent'].forEach(function (n) {
            form.querySelectorAll('[name="' + n + '"]').forEach(function (el) {
                el.removeAttribute('name');
            });
        });
    }

    async function encryptAndSubmit(event) {
        event.preventDefault();

        if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            setStatus('Estabelecendo canal seguro...');
            const publicKey = await fetchPublicKey();

            // (b) Gerar a chave de sessão simétrica no front.
            const sessionKey = await window.crypto.subtle.generateKey(
                { name: 'AES-GCM', length: 256 },
                true,
                ['encrypt']
            );
            const rawSessionKey = await window.crypto.subtle.exportKey('raw', sessionKey);
            console.log('[S.3.1.b] Chave de sessão (AES-256-GCM) gerada no front (base64):',
                bufToBase64(rawSessionKey));

            // (d) Cifrar os dados do formulário com a chave de sessão.
            const iv = window.crypto.getRandomValues(new Uint8Array(12));
            const plaintext = new TextEncoder().encode(JSON.stringify(collectFormData()));
            const ciphertext = await window.crypto.subtle.encrypt(
                { name: 'AES-GCM', iv: iv },
                sessionKey,
                plaintext
            );

            // (c) Cifrar a chave de sessão com a chave pública (RSA-OAEP).
            const encryptedSessionKey = await window.crypto.subtle.encrypt(
                { name: 'RSA-OAEP' },
                publicKey,
                rawSessionKey
            );
            console.log('[S.3.1.c] Chave de sessão cifrada com a chave pública e enviada ao back (base64):',
                bufToBase64(encryptedSessionKey));
            console.log('[S.3.1.d/e] Dados do formulário cifrados (AES-256-GCM, base64):',
                bufToBase64(ciphertext));

            // Montar o corpo a ser enviado: apenas conteúdo cifrado + _csrf.
            setHiddenField('encrypted', '1');
            setHiddenField('enc_key', bufToBase64(encryptedSessionKey));
            setHiddenField('iv', bufToBase64(iv.buffer));
            setHiddenField('payload', bufToBase64(ciphertext));
            stripPlaintextFields();

            setStatus('Dados cifrados. Enviando...');
            HTMLFormElement.prototype.submit.call(form);
        } catch (err) {
            console.error('[S.3.1] Falha na criptografia híbrida:', err);
            setStatus('Não foi possível cifrar os dados: ' + (err && err.message ? err.message : err));
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    }

    // Sem contexto seguro (HTTPS/localhost) o Web Crypto não está disponível;
    // mantém o envio em claro para não quebrar o cadastro fora do ambiente alvo.
    if (!window.crypto || !window.crypto.subtle || !pubKeyUrl) {
        console.warn('[S.3.1] Web Crypto indisponível (requer HTTPS ou localhost). Envio sem criptografia híbrida.');
        return;
    }

    form.addEventListener('submit', encryptAndSubmit);
})();
