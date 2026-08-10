<p align="center"><img src="/public/favicon.ico" width="64" alt="MDM SaaS"></p>

## Sobre

**MDM SaaS** é uma plataforma B2B multi-tenant de governança de dados mestres (Master
Data Management), construída com Laravel.

> Documentação completa de uso e arquitetura em construção — consulte os guias na
> pasta `general_notes/` (fora do versionamento).

## Stack

- **Backend**: Laravel 13 (PHP 8.3+)
- **Frontend**: Vite
- **Banco de dados**: MySQL (SQLite em testes)

## Requisitos

- PHP >= 8.3
- Composer
- Node.js 22+ / npm

## Configuração

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev
```

Acesse `http://localhost:8000`.

## Testes

```bash
php artisan test
```
