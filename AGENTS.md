# AGENTS.md

## Périmètre de l’IA

- Travaille exclusivement sur le frontend de MesAnimaux, notamment les templates Twig, les feuilles de style, le JavaScript, les ressources visuelles et les tests de l’interface.
- Les entités Doctrine et les contrôleurs Symfony, notamment `src/Entity/` et `src/Controller/`, sont strictement en lecture seule pour l’IA. N’y crée, ne modifie, ne déplace et ne supprime aucun fichier, y compris via un générateur ou un outil de formatage. Si une évolution nécessite une intervention backend, signale-la au responsable du projet.
- Le projet utilise Symfony 8.1 et requiert PHP 8.4 ou supérieur. Vérifie `composer.json` et `symfony.lock` avant de t’appuyer sur un composant ou une recette Flex.

## Maquette et fonctionnalités

- Utilise le dossier `Maquette/` comme référence pour construire le site. Vérifie la fidélité de la mise en page, des visuels, de la navigation et des états d’interface représentés dans les maquettes.
- Implémente uniquement les fonctionnalités déjà discutées et validées par le responsable du projet. Demande sa validation avant d’ajouter une fonctionnalité dont le périmètre est incertain.
- Si une fonctionnalité visible est absente, indisponible ou pas encore initialisée, informe le visiteur à l’endroit où il essaie de l’utiliser. Affiche par exemple « Ce site est une version de test. Cette fonctionnalité n’est pas disponible pour le moment. » N’affiche jamais une confirmation de réussite pour une action qui n’a pas été exécutée.

## Tests et publication

- Avant chaque livraison, exécute les tests pertinents, vérifie les pages modifiées dans le navigateur aux tailles d’écran pertinentes et teste les parcours touchés, y compris les messages des fonctionnalités indisponibles. Signale tout contrôle bloqué ou en échec.
- Si aucun dépôt local n’existe, initialise-le avec `git init`. Utilise `origin` pour le dépôt GitHub `https://github.com/LoicHoareeau/MesAnimaux.git` et vérifie sa destination avant de pousser.
- Pour chaque modification prête à livrer, inspecte le diff, ajoute seulement les fichiers concernés, crée un commit et pousse-le sur la branche de travail vers `origin`. Préserve les changements locaux préexistants et garde les secrets hors du commit. Vérifie le résultat du push avant d’annoncer la livraison.

Les conventions Symfony ci-dessous s’appliquent uniquement dans le périmètre frontend défini ci-dessus.

## Adding features: Flex, not hand-wiring

Install new capabilities with `composer require <package>` (e.g. `symfony/lock`,
`symfony/messenger`, `orm-pack`) and let the Flex recipe register the bundle and
generate its config. Don't hand-edit `config/bundles.php` or hand-write a bundle's
base config; that's what the recipe is for. Don't skip a good-fit component just
because it isn't installed yet; installing it is one command.

## Conventions

Follow https://symfony.com/doc/current/best_practices.html to write idiomatic
Symfony:

- Use PHP attributes for framework metadata, and not only on controllers:
  `#[Route]`, `#[MapRequestPayload]`, `#[IsGranted]` on actions, `#[Assert\...]`
  on properties, `#[AsCommand]`, `#[AsEventListener]`, `#[AsMessageHandler]`, and
  `#[AsAlias]` / `#[AsTaggedItem]` / `#[Autoconfigure]` on services. No YAML or
  XML routing.
- Rely on autowiring and autoconfiguration. Type-hint constructor arguments and
  let the container resolve them. Where a type-hint can't express it, stay in the
  class with `#[Autowire]` (parameters, env vars, expressions) or `#[Target]` (one
  of several implementations of an interface). A YAML service definition is the
  last resort, not the first.
- Controllers extend `AbstractController`, stay thin, and delegate to services.
- Use the framework for what it already does: Form for server-rendered forms,
  Validator for validation, Serializer for JSON, Messenger for async work,
  Security (voters, authenticators) for access control, Twig `path()`/`url()`
  instead of hardcoded URLs.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand. If neither package is
  installed yet, `composer require` them rather than falling back to manual
  parsing.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion. A hand-built flag or
  lock file looks fine in review and is usually wrong under concurrency.

## Everyday workflow

- Run the app with `symfony serve -d`, and commands with `symfony console ...`
  (or `bin/console` when the Symfony CLI isn't available).
- When something fails, read `var/log/dev.log` and the web profiler
  (`/_profiler`) before changing code.
- If `maker-bundle` is installed, prefer `bin/console make:*` with every argument
  passed up front and `--no-interaction` where supported: makers prompt on a
  terminal by default, which hangs a non-interactive shell. If a maker still
  needs interactive input, hand-write the code instead.
- If Doctrine ORM is installed, schema changes go through migrations
  (`bin/console make:migration`, then `doctrine:migrations:migrate`), never
  `doctrine:schema:update` or hand-written SQL.
- `.env` is committed and holds defaults only. Real secrets belong in `.env.local`
  (git-ignored) or the secrets vault (`bin/console secrets:set`), read via
  `%env(...)%`.

## Testing

Install `symfony/test-pack` if it isn't already. Functional/HTTP tests extend
`WebTestCase`; service-level tests extend `KernelTestCase`. Run
`php bin/phpunit` (falls back to `vendor/bin/phpunit`). A feature isn't done
until it has a test that exercises it the way a caller would, an HTTP request for
a controller or a service call for a service, not just "it didn't throw."

## Code style

Symfony's coding standard, the `@Symfony` php-cs-fixer ruleset (a PSR-12-derived
superset). Run `vendor/bin/php-cs-fixer fix` if `friendsofphp/php-cs-fixer` is
installed; it isn't part of the skeleton by default.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `bin/console about`: versions, environment, paths.
- `bin/console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `bin/console lint:container`, plus `lint:twig templates/` and
  `lint:yaml config/` where those packages are installed: validate before running.
- Read the installed source and docblocks under `vendor/`.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs).
