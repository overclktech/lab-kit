# Changelog

Formato do [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); versões em [semver](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added

- Licença Apache-2.0 e distribuição pública pelo Packagist, sem token no build do app.
- Ações de recursos: `Action::make(nome, classe)` com `confirm()` e `requiresMfa()`, classe do app que implementa `ResourceAction`, comando `lab:resources:action` autorizado pela Policy do model (`labRun`), com trava do registro e recusa de edição concorrente pela versão (`conflict`), um HMAC de todos os atributos com a chave do app; ação que não altera o registro é desfeita (`unchanged`); listagens trazem `_key` e `_version`.
- Recursos de dados só leitura: `LabResource` e `Field` em `app/LabKit/*Resource.php`, comandos `lab:resources` e `lab:resources:list` com filtros por campo liberado e paginação, autorizados pela Policy do model (`labViewAny`); campos sensíveis ou ocultos recusados.
- Comandos `lab:users`, `lab:sessions` e `lab:sessions:revoke`, com entrada e saída em JSON pela entrada e saída padrão.
- Comandos `lab:users:block`, `lab:users:unblock` e `lab:users:force-password-reset`.
- Todos os comandos de usuários e sessões exigem `App\LabKit\UserActions` no app e passam pela autorização dele (`allows`); recusa responde `forbidden`.
- Registro de cada chamada no log do app: comando, operação, operador e resultado, sem parâmetros nem dados de resposta, inclusive quando o app lança exceção (`internal_error`).
- Teste que garante que o pacote não registra nenhuma rota HTTP.
- Comando `lab:capabilities`: devolve em JSON a versão do Lab Kit e os comandos `lab:*` que o app atende.
