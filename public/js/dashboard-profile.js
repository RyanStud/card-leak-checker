(function () {
    const cepInput = document.getElementById('cep');
    const cpfInput = document.getElementById('cpf');
    const streetInput = document.getElementById('address_street');
    const neighborhoodInput = document.getElementById('address_neighborhood');
    const cityInput = document.getElementById('address_city');
    const stateInput = document.getElementById('address_state');
    const cepStatus = document.getElementById('cep_status');

    const onlyDigits = (value) => value.replace(/\D/g, '');

    async function fetchCepData(cepDigits) {
        if (cepStatus) {
            cepStatus.textContent = 'Buscando CEP...';
        }

        try {
            const response = await fetch('https://viacep.com.br/ws/' + cepDigits + '/json/');
            if (!response.ok) {
                if (cepStatus) {
                    cepStatus.textContent = 'Nao foi possivel consultar o CEP agora.';
                }
                return;
            }

            const data = await response.json();
            if (data.erro) {
                if (cepStatus) {
                    cepStatus.textContent = 'CEP nao encontrado.';
                }
                return;
            }

            if (streetInput && !streetInput.value.trim()) {
                streetInput.value = data.logradouro || '';
            }

            if (neighborhoodInput && !neighborhoodInput.value.trim()) {
                neighborhoodInput.value = data.bairro || '';
            }

            if (cityInput && !cityInput.value.trim()) {
                cityInput.value = data.localidade || '';
            }

            if (stateInput && !stateInput.value.trim()) {
                stateInput.value = data.uf || '';
            }

            if (cepStatus) {
                cepStatus.textContent = 'Endereco preenchido automaticamente.';
            }
        } catch (e) {
            if (cepStatus) {
                cepStatus.textContent = 'Falha de conexao ao consultar o CEP.';
            }
        }
    }

    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            const digits = onlyDigits(cpfInput.value).slice(0, 11);
            cpfInput.value = digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        });
    }

    if (cepInput) {
        let lastFetchedCep = '';

        cepInput.addEventListener('input', function () {
            const digits = onlyDigits(cepInput.value).slice(0, 8);
            cepInput.value = digits.replace(/(\d{5})(\d)/, '$1-$2');

            if (digits.length === 8 && digits !== lastFetchedCep) {
                lastFetchedCep = digits;
                fetchCepData(digits);
            }
        });

        cepInput.addEventListener('blur', function () {
            const cepDigits = onlyDigits(cepInput.value);
            if (cepDigits.length === 8 && cepDigits !== lastFetchedCep) {
                lastFetchedCep = cepDigits;
                fetchCepData(cepDigits);
            }
        });
    }
})();
