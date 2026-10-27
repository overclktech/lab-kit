# Lab Kit

Pacote Composer opcional (`overclock/lab-kit`) que liga o app Laravel do cliente ao painel do Lab, como o SDK do Sentry: entra com `composer require` e sai com `composer remove` sem quebrar o app.

## Onde fica cada parte

- `src/`: o pacote; `LabKitServiceProvider` é descoberto pelo Laravel.
- `tests/`: Pest sobre o Orchestra Testbench.

## Regras que mais pesam

- Só a ponte: listar, bloquear e desbloquear usuários, forçar troca de senha, sessões, recursos de dados declarados e ações de negócio declaradas. Login, cadastro, 2FA, login social e bloqueio ficam no código do app (repositório-modelo).
- A ponte é por comandos `lab:*` do Artisan, sem porta de rede.
- O app nunca depende do pacote: removê-lo não pode quebrar nada.
- Segredo nunca em log, argumento de comando ou saída de comando.
- Identificadores em inglês, sem comentários no código; textos que o usuário lê em português.
- Semver com CHANGELOG (Keep a Changelog); quebra de contrato só em versão maior, com guia de atualização.
- Commits em Conventional Commits, em inglês, sem coautoria de IA. Nunca push na `main`.

## Definição de pronto

1. Critérios de aceite da história cumpridos, com teste.
2. `composer lint` e `composer test` verdes: Pint, Rector, Larastan (nível máximo) e Pest.
3. `composer audit` e `npm audit` sem vulnerabilidade.
4. README e CHANGELOG atualizados no mesmo pull request.
