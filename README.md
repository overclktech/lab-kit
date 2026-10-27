# Lab Kit

## O que é

Pacote Composer opcional que liga o app Laravel do cliente ao painel do Lab. Pelo painel, a equipe lista, bloqueia e desbloqueia usuários, força troca de senha, encerra sessões, vê os recursos de dados que o projeto declarou e executa as ações de negócio declaradas. O Lab chama o pacote por comandos `lab:*` do Artisan, sem porta de rede.

## Estrutura

| Pasta | Conteúdo |
|---|---|
| `src/` | O pacote (`Overclock\LabKit`) |
| `tests/` | Testes Pest sobre o Orchestra Testbench |
| `docs/decisions/` | Decisões (MADR) |

## Pré-requisitos

- PHP 8.4
- Laravel 13
- Composer 2 e Node 22 (só para desenvolver o pacote)

## Primeira execução

No app do cliente:

```bash
composer require overclock/lab-kit
```

Para o Lab gerenciar usuários e sessões, o app cria `app/LabKit/UserActions.php`, que implementa `Overclock\LabKit\Contracts\UserActions`: `allows` decide, pelas Policies do app, se o operador pode listar usuários, listar sessões e encerrar sessões; `block`, `unblock` e `forcePasswordReset` chamam as Actions do próprio app. Sem essa classe, só o `lab:capabilities` existe.

Cada comando `lab:*` lê um JSON da entrada padrão e responde um JSON na saída padrão ([ADR 0001](docs/decisions/0001-artisan-bridge-contract.md)):

```bash
echo '{"operation_id":"op-1","operator":{"id":"7","name":"Ana"},"params":{"search":"ana"}}' | php artisan lab:users
```

Para desenvolver o pacote:

```bash
composer install
npm install
composer test
```

O `npm install` instala os hooks do Git.

## Publicar

Versões em semver, por tag (`v1.2.3`) na `main`, com o CHANGELOG atualizado. Os apps instalam pelo repositório privado no GitHub.

## Verificações

```bash
composer lint
composer test
composer audit
npm audit
```

O `lint` roda Pint, Rector e Larastan. O CI roda o mesmo em todo pull request. Os hooks do Git formatam os arquivos alterados, rodam os testes antes do commit, recusam mensagem fora do Conventional Commits e recusam push direto na `main`.

## Operação

Para tirar o pacote de um app, rode `composer remove overclock/lab-kit`. O app segue funcionando; só os recursos do console do Lab deixam de responder.

## Decisões

Em [`docs/decisions/`](docs/decisions/).
