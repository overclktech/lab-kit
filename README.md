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

- PHP 8.4 ou 8.5
- Laravel 13
- Composer 2 e Node 22 (só para desenvolver o pacote)

## Primeira execução

No app do cliente:

```bash
composer require overclock/lab-kit
```

Para o Lab gerenciar usuários e sessões, o app cria `app/LabKit/UserActions.php`, que implementa `Overclock\LabKit\Contracts\UserActions`: `allows` decide, pelas Policies do app, se o operador pode listar usuários, listar sessões e encerrar sessões; `block`, `unblock` e `forcePasswordReset` chamam as Actions do próprio app. Sem essa classe, só o `lab:capabilities` existe.

Para o console de dados, o app declara recursos em `app/LabKit/`, um arquivo `*Resource.php` por recurso ([ADR 0002](docs/decisions/0002-read-only-data-resources.md)):

```php
class OrderResource extends LabResource
{
    public static string $model = Order::class;

    public function fields(): array
    {
        return [Field::id(), Field::text('number')->filterable(), Field::money('total'), Field::dateTime('created_at')];
    }
}
```

A leitura passa pela Policy do model: o método `labViewAny(Operator $operator)` decide; sem ele, o recurso é recusado. `lab:resources` descreve os recursos que o operador pode ver e `lab:resources:list` lista os registros (`{"resource":"order","filters":{"number":"1001"},"page":1}`). Campo com nome sensível (senha, token, segredo, 2FA) ou oculto no model (`$hidden`) não pode ser declarado.

Ações de negócio chamam o código do app ([ADR 0003](docs/decisions/0003-resource-actions.md)): o recurso declara `Action::make('refund', RefundOrder::class)->confirm()->requiresMfa()`, a classe implementa `Overclock\LabKit\Contracts\ResourceAction` e a Policy do model decide em `labRun(Operator $operator, Model $record, string $action)`. `lab:resources:action` recebe a versão (`_version`) que a listagem mostrou e recusa com `conflict` se o registro mudou, mesmo no mesmo segundo. A ação precisa registrar o que fez no próprio registro (um estado, uma data); se o registro ficar igual, a chamada é desfeita e responde `unchanged`.

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

Versões em semver, por tag (`v1.2.3`) na `main`, com o CHANGELOG atualizado. O pacote é público no Packagist (`composer require overclock/lab-kit`), sem credencial no build do app ([ADR 0004](docs/decisions/0004-apache-license-and-public-package.md)).

## Licença

Apache-2.0 ([LICENSE](LICENSE)). A licença não concede uso do nome nem da marca Overclock.

## Verificações

```bash
composer lint
composer test
composer audit
npm audit
```

O `lint` roda Pint, Rector e Larastan. O CI roda o mesmo em todo pull request. Os hooks do Git formatam os arquivos alterados, rodam os testes antes do commit, recusam mensagem fora do Conventional Commits e recusam push direto na `main`.

## Operação

Cada chamada do Lab fica no log do app (`Lab Kit call`), com comando, ID da operação, operador e resultado. Parâmetros e respostas nunca entram no log.

Para tirar o pacote de um app, rode `composer remove overclock/lab-kit`. O app segue funcionando; só os recursos do console do Lab deixam de responder.

## Decisões

Em [`docs/decisions/`](docs/decisions/).
