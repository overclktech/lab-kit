# Changelog

Formato do [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); versões em [semver](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added

- Comandos `lab:users`, `lab:sessions` e `lab:sessions:revoke`, com entrada e saída em JSON pela entrada e saída padrão.
- Comandos `lab:users:block`, `lab:users:unblock` e `lab:users:force-password-reset`, ativos quando o app tem `App\LabKit\UserActions`.
- Registro de cada chamada no log do app: comando, operação, operador e resultado, sem parâmetros nem dados de resposta.
- Teste que garante que o pacote não registra nenhuma rota HTTP.
- Comando `lab:capabilities`: devolve em JSON a versão do Lab Kit e os comandos `lab:*` que o app atende.
