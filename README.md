# Card Leak Checker Feature Tests

## CÓDIGO DA BRANCH DE TESTES POSSUI CONFIGURAÇÔES APENAS PARA DESENVOLVER NOVAS FEATURES LOCALMENTE, NÃO SENDO SEGURO O UPLOAD EM PRODUÇÃO

## TODAS AS FEATURES TESTADAS NESSE AMBIENTE DEVEM PASSAR POR HOMOLOGAÇÃO

## Aviso Legal

Este projeto é uma simulação controlada destinada exclusivamente para testes de cibersegurança e uso educacional.

Não há interação com sistemas reais de pagamento nem processamento de dados reais de cartões de crédito.

Qualquer tentativa de uso deste código para acesso não autorizado, fraude ou atividades ilegais é estritamente proibida.

Ao utilizar este repositório, você concorda em usá-lo de forma responsável e em conformidade com as leis aplicáveis.


## IMPORTANTE 

Colocar no banco de dados do php a seguinte query para fazer o bypass da confirmação de e-mail (APENAS PARA FINS DE TESTES LOCAIS):

UPDATE users SET email_verified = 1 WHERE email = '<seuemailcadastrado.com>';
