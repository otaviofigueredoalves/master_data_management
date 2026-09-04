<p align="center">
  <strong>Plataforma SaaS de MDM</strong><br>
  Master Data Management — API SaaS B2B multi-tenant (PHP · Laravel 13)
</p>

---

Uma plataforma **SaaS B2B para Gestão de Dados Mestres (MDM)** com cara de produção, construída como vitrine de nível sênior. Ela combina **Laravel Fortify** (autenticação web + 2FA), **Laravel Socialite** (login com Google / GitHub / Microsoft) e **Laravel Sanctum** (API REST baseada em tokens) com **tenancy por linha em banco único**, uma camada de **RBAC baseada em associações (membership)** sobre o Spatie Permission, **jobs de sincronização** de integrações em fila e **auditoria completa**.

## Por que esta arquitetura

| Preocupação | Decisão | Justificativa |
|---|---|---|
| Auth (web) | **Laravel Fortify** (login, cadastro, 2FA, reset de senha) | Backend de autenticação headless — a implementação padrão e testada |
| Auth (API) | **Laravel Sanctum** com personal access tokens (`Bearer`) | Emissão de tokens simples e segura por dispositivo; sem overhead de servidor OAuth |
| Login social | **Laravel Socialite** (Google, GitHub, Microsoft) | Auto-habilitado quando as credenciais de ambiente existem; Microsoft importa para o perfil B2B/enterprise |
| Tenancy | **Banco único, por linha** via `tenant_id` + header `X-Tenant-ID` | Compatível com o schema existente; operação mais simples que banco-por-tenant; isolamento suficiente para o demo |
| RBAC | **Baseado em associação**: `tenant_users.role_id` → Role Spatie; role global `super-admin` ignora escopos de tenant | Um único catálogo de roles/permissões; papéis avaliados no contexto do tenant ativo |
| Assíncrono | Job de fila `RunIntegrationSync` na fila `sync` | Integrações demoradas nunca bloqueiam a API |
| Observabilidade | Tabela `audit_logs` + respostas estruturadas | Toda mutação é rastreável por tenant |
| Testes | Suíte de features PHPUnit (23 testes) | Auth, isolamento de tenancy, RBAC, CRUD de entidades, dispatch de sync, convites |

> O scaffold original misturava dois modelos de RBAC (`role_id` numérico vs. Spatie) e referenciava classes que não existiam. Esta build **unifica** os dois: `tenant_users.role_id` é uma FK real para `roles.id` do Spatie, e as permissões de tenant são resolvidas através da associação ativa — não pelas roles globais do usuário.

## Stack

- **Laravel 13** · PHP 8.3+ (testado em PHP 8.5)
- **Fortify** v1.39 — login/cadastro/2FA
- **Sanctum** — tokens de API
- **Socialite** v5 — Google, GitHub, Microsoft
- **spatie/laravel-permission** — catálogo de roles & permissões
- **MySQL** (padrão) / SQLite (testes, em memória)
- **Redis** — cache, fila, sessão (configurável)
- **PHPUnit** + **Pint** (preset Laravel)

## Começando

```bash
composer install
cp .env.example .env
php artisan key:generate

# Banco (padrão: mysql, db `mdm_saas`; ou mude o .env para sqlite para um início rápido)
php artisan migrate --seed        # cria as tabelas + tenant demo/roles/usuários
php artisan serve

# Worker de fila (necessário para os jobs de sync de integração rodarem de fato)
php artisan queue:work --queue=sync
```

### Acessos de demonstração (seed)

| Papel | E-mail | Senha |
|---|---|---|
| Admin do tenant (owner) | `demo@mdmsaas.test` | `password` |
| Manager do tenant | `manager@mdmsaas.test` | `password` |
| Viewer do tenant | `viewer@mdmsaas.test` | `password` |
| Super-admin da plataforma | `platform@mdmsaas.test` | `password` |

O seeder cria o tenant **Acme Corporation** com 5 entidades MDM e uma integração Salesforce.

## Modelo de tenancy

Toda tabela com escopo de tenant carrega uma coluna `tenant_id`. A associação mora em `tenant_users` (`user_id`, `tenant_id`, `role_id`, `is_active`).

**Resolução de contexto** (`app/Support/TenantContext.php` + middleware `EnsureUserHasTenant`):

1. Header `X-Tenant-ID` da requisição
2. `current_tenant_id` na sessão
3. `users.current_tenant_id` (último tenant usado, persistido)
4. Se o usuário tem exatamente uma associação ativa, ela é selecionada automaticamente

O middleware **nega** requisições a tenants dos quais o usuário não é membro ativo e faz o bind de um `TenantContext`, para que controllers e middlewares nunca "adivinhem" o escopo. **Super-admins** da plataforma podem atuar em qualquer tenant sem associação.

## Autorização (RBAC)

- **Roles (por tenant):** `admin`, `manager`, `user` (viewer) — semeadas no `RolePermissionSeeder`.
- **Permissões** granulares (ex.: `create mdm entities`, `manage integrations`, `run sync`, `view audit logs`) mapeadas para as roles no seeder.
- Middlewares de rota `tenant.auth` + `permission:<nome>`/`role:<nome>` aplicam o acesso com escopo de tenant; as policies (`MdmEntityPolicy`, `IntegrationPolicy`, `UserPolicy`, `TenantPolicy`) adicionam checagens por modelo, incluindo guardas entre tenants — um usuário do tenant A jamais lê/grava registros do tenant B, mesmo com uma URL forjada.
- `super-admin` é uma role de **nível plataforma** (Spatie `HasRoles` no usuário) que ignora os escopos de tenant.

```
tenant_users.role_id ──► spatie roles  ──► spatie permissions  (escopo do tenant)
users.roles (global)  ──► super-admin                          (escopo da plataforma)
```

## Fluxos de autenticação

### Web (Fortify)
`/login`, `/register`, `/forgot-password`, `/reset-password`, `/confirm-password`, `/two-factor-challenge` — além dos botões de **Socialite** na tela de login (exibidos apenas quando as env vars do provedor estão definidas). Após o login, o usuário escolhe/cria um workspace em `/tenants/select`.

### API (Sanctum)
```
POST /api/v1/auth/login       { email, password, code? }   → { token, user }
POST /api/v1/auth/register    { name, email, password }    → { token, user }
POST /api/v1/auth/logout      (Bearer)                     → revoga o token
GET  /api/v1/me               (Bearer)
```

- Login com **rate limit** (`throttle:5,1`) contra força bruta.
- **2FA**: se habilitado, `POST auth/login` retorna `422` pedindo o `code` até que um TOTP válido seja enviado; os tokens só são emitidos após o código passar.
- Nas chamadas seguintes com escopo de tenant, envie `X-Tenant-ID: <tenant_id>` e `Authorization: Bearer <token>`.
- Uma rota de conveniência `/demo-login` autentica você como o admin demo.

## Interface web (SPA Vue)

Além da API, a aplicação entrega uma **SPA em Vue 3 + Vite** (Tailwind CSS) que cobre toda a jornada do navegador. Depois de instalar as dependências, suba o Vite em desenvolvimento (`npm install && npm run dev`) ou gere os assets (`npm run build`).

**Fluxo de telas:**

| Etapa | Tela | O que acontece |
|---|---|---|
| Pública | `/` (landing) | Página institucional; usuários autenticados são redirecionados ao dashboard |
| Autenticação | `/entrar`, `/criar-conta` | Login/cadastro (Fortify + botões Socialite quando configurados) |
| Aceitar convite | `/convite/:token` | O convidado aceita o convite e entra no workspace |
| Onboarding | `/onboarding` | Usuário sem workspace cria o primeiro (vira `admin`) |
| App | `/dashboard`, `/entidades`, `/integracoes`, `/sincronizacoes` | Área operacional do workspace |
| Governança | `/membros`, `/auditoria` | Gestão de pessoas e trilha de auditoria |
| Conta | `/configuracoes` | Perfil, tokens de API e catálogo de papéis |
| Docs | `/docs` | Documentação interativa da API (abre em nova aba) |

A barra lateral organiza os itens em **Workspace** (Dashboard, Entidades MDM, Integrações, Sincronizações), **Governança** (Membros, Auditoria) e **Conta** (Configurações). No topo, o **seletor de workspace** permite alternar entre tenants e criar novos; o rodapé da barra mostra seu **papel no workspace**. A interface é dirigida por RBAC: botões e ações só aparecem se o seu papel tiver a permissão correspondente (o backend continua sendo a autoridade de segurança).

## Visão geral da API REST (`/api/v1`)

| Área | Endpoints |
|---|---|
| Auth | `auth/login`, `auth/register`, `auth/logout` |
| Perfil | `me` (GET/PUT) |
| Tokens | `tokens` (GET/POST/DELETE) |
| Tenancy | `tenants` (GET/POST), `tenants/switch`, `tenants/{tenant}` (GET/PUT/DELETE) |
| Workspace | `dashboard`, `roles` |
| Membros | `users` (GET/POST/PUT/DELETE) |
| Convites | `invitations` (GET/POST), `invitations/{token}/accept` |
| Entidades MDM | `mdm-entities` CRUD + `{id}/normalize`, `{id}/relations` |
| Integrações | `integrations` CRUD + `{id}/sync` |
| Jobs de sync | `sync-jobs` (GET/show) |
| Auditoria | `audit-logs` (GET) |

Toda resposta segue o envelope:

```json
{ "success": true, "message": "…", "data": { … } }
```

Erros são `422` (validação), `403` (autorização/tenant), `401` (não autenticado), com `{ "success": false, "message": "…", "errors": {} }`.

## Domínio MDM

- **Entidades** (`mdm_entities`) guardam o registro-fonte (`data`) e uma projeção normalizada (`normalized_data`), com controle de concorrência otimista via `version` incrementada a cada update e flag `is_master` para designar o registro dourado (padrão golden record).
- **Normalização** (`app/Services/MdmNormalizationService.php`) é um motor de regras por tipo de entidade (trim/maiúsculas em nomes, minúsculas em e-mails etc.), usado por `POST mdm-entities/{id}/normalize` para promover uma entidade a master.
- **Relacionamentos** (`mdm_entity_relations`) expressam vínculos pai/filho e de referência entre entidades, com restrições por tipo.

## Integrações & sync

- `integrations` guarda credenciais **criptografadas** (cast `encrypted:array`) e configurações por tenant.
- `POST integrations/{id}/sync` **valida que a integração pertence ao tenant ativo**, cria um registro de `sync_job` (`pending`) e despacha `App\Jobs\RunIntegrationSync` para a fila `sync`.
- O job simula a troca com o provedor (fonte de dados configurável), faz upsert de entidades via o serviço de integração, marca o job como `completed`/`failed` e **audita cada passo** — inclusive quando roda num worker sem usuário autenticado (o `AuditLogger` cai para `system`/o iniciador do job).
- Consulte `sync-jobs` para acompanhar o progresso — o padrão amigável de sincronização longa em SaaS.

## Auditoria

Todos os endpoints de mutação gravam em `audit_logs`: ator, tenant, ação (ex.: `entity.updated`), modelo-alvo e payloads antes/depois. Um serviço `AuditLogger` seguro para contexto de fila mantém a consistência entre requisições HTTP e jobs em fila.

## Notas de segurança

- Senhas com hash (bcrypt 12 via padrões do Laravel); tokens Sanctum com escopo por dispositivo e revogáveis.
- Valores de `credentials` criptografados em repouso.
- Acesso entre tenants retorna `403` mesmo quando o ID pertence a outro tenant.
- Recursos sensíveis dependentes de env (provedores Socialite, S3) degradam com elegância quando não configurados.
- Rotas de mutação da API protegidas por middleware de permissão **e** por policies de modelo.

## Testes

```bash
php artisan test          # 23 testes de feature (SQLite em memória, migrate fresh por teste)
vendor/bin/pint           # estilo de código (preset Laravel)
```

Cobertura: autenticação e ciclo de vida do token, aceitação/expiração de convites, isolamento de tenant (`403` entre tenants), aplicação de role/permissão, CRUD de entidades MDM + versionamento + normalização, efeitos colaterais de auditoria e dispatch de sync.

## Documentação complementar

Guias e explicações detalhadas (em pt-BR) vivem em `general_notes/`:

| Documento | Conteúdo |
|---|---|
| [`general_notes/EXPLICACAO-DA-APLICACAO.md`](general_notes/EXPLICACAO-DA-APLICACAO.md) | Lógica interna e decisões de arquitetura |
| [`general_notes/EXPLICACAO-PASSO-A-PASSO.md`](general_notes/EXPLICACAO-PASSO-A-PASSO.md) | Passo a passo didático de uso da aplicação |
| [`general_notes/GUIA-POSTMAN.md`](general_notes/GUIA-POSTMAN.md) | Guia para testar a API no Postman |
| [`general_notes/DESIGN-SYSTEM.md`](general_notes/DESIGN-SYSTEM.md) | Design system do SPA Vue |
| [`general_notes/DEPLOY-LARAVEL-CLOUD.md`](general_notes/DEPLOY-LARAVEL-CLOUD.md) | Guia de deploy no Laravel Cloud |

## Estrutura de destaque do projeto

```
app/
  Support/TenantContext.php          # tenant + associação resolvidos para a requisição
  Http/Middleware/                   # tenant.auth / permission / role
  Http/Controllers/Api/V1/           # controllers completos da API REST
  Policies/                          # autorização por modelo com ciência de tenant
  Services/                          # AuditLogger, IntegrationService, MdmNormalizationService, TenantService
  Jobs/RunIntegrationSync.php        # ciclo de vida do sync em fila
  Actions/Fortify/                   # CreateNewUser, updates de senha/perfil/2FA
routes/
  api.php                            # /api/v1 (Sanctum)
  web.php                            # landing, docs, seleção de tenant, SPA fallback, Socialite, demo-login
resources/
  js/                                # SPA Vue 3 (telas do workspace + componentes de UI)
  views/                             # blades de entrada: spa, docs, landing
database/
  seeders/                           # catálogo de roles/permissões + tenant e usuários demo
tests/Feature/                       # suíte comportamental
```

---

Consulte `resources/views/docs.blade.php` (servida em `/docs`) para a documentação interativa da API.
