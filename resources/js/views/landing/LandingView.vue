<script setup>
import AppLogo from '../../components/ui/AppLogo.vue';
import Icon from '../../components/ui/Icon.vue';

const features = [
    { icon: 'database', title: 'Golden records', text: 'Uma única versão confiável de cada cliente, fornecedor ou produto, promovida por normalização automática.' },
    { icon: 'plug', title: 'Integrações assíncronas', text: 'ERPs, CRMs e sistemas legados conectados com sincronizações em fila, rastreáveis do início ao fim.' },
    { icon: 'shield', title: 'Auditoria completa', text: 'Toda mutação registrada com ator, antes/depois e payload — governança pronta para compliance.' },
    { icon: 'building', title: 'Multi-tenant isolado', text: 'Cada organização com seu workspace, papéis e permissões — isolamento em três camadas.' },
    { icon: 'layers', title: 'Relacionamentos', text: 'Hierarquias, duplicidades e referências entre registros de um mesmo objeto mestre.' },
    { icon: 'key', title: 'API-first com tokens', text: 'REST versionada com Sanctum, 2FA e tokens revogáveis por dispositivo.' },
];

const steps = [
    { title: 'Conecte as fontes', text: 'ERP, CRM, legado — cada registro entra com tipo, sistema de origem e external_id.' },
    { title: 'Normalize e promova', text: 'Regras de domínio limpam e-mails, telefones e chaves; o registro vira o golden record.' },
    { title: 'Audite e integre', text: 'Versão, trilha de auditoria e sincronizações em segundo plano, com API para consumir.' },
];
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Nav -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                <RouterLink to="/" aria-label="Início">
                    <AppLogo />
                </RouterLink>
                <nav class="flex items-center gap-1">
                    <a href="/docs" target="_blank" class="btn btn-ghost text-xs">Documentação</a>
                    <RouterLink :to="{ name: 'login' }" class="btn btn-ghost text-xs">Entrar</RouterLink>
                    <RouterLink :to="{ name: 'register' }" class="btn btn-primary btn-sm">Criar conta</RouterLink>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="border-b border-slate-200">
            <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-20">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600 uppercase tracking-wide">
                        Master Data Management · SaaS B2B
                    </span>
                    <h1 class="mt-5 text-3xl leading-tight font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Dados mestres confiáveis,<br />
                        <span class="text-slate-400">do caos à régua de ouro.</span>
                    </h1>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-600">
                        Uma plataforma multi-tenant que reúne registros de ERPs, CRMs e sistemas legados, normaliza,
                        promove <strong class="font-semibold text-slate-900">golden records</strong> e audita cada
                        alteração — sem precisar adivinhar quem é o seu cliente.
                    </p>
                    <div class="mt-7 flex flex-wrap items-center gap-2.5">
                        <RouterLink :to="{ name: 'register' }" class="btn btn-primary">
                            Começar agora
                            <Icon name="arrow-right" class="h-4 w-4" />
                        </RouterLink>
                        <a href="#recursos" class="btn btn-secondary">Conhecer recursos</a>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Contas demo com papéis de admin, manager e viewer.</p>
                </div>

                <!-- Product mock -->
                <div class="hidden lg:block">
                    <div class="border border-slate-200 bg-white">
                        <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 bg-red-400" />
                                <span class="h-2.5 w-2.5 bg-amber-400" />
                                <span class="h-2.5 w-2.5 bg-emerald-400" />
                            </div>
                            <span class="text-[11px] text-slate-400">acme · entidades</span>
                        </div>
                        <div class="grid grid-cols-3 border-b border-slate-200">
                            <div class="border-r border-slate-200 p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase">Entidades</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">1.284</p>
                            </div>
                            <div class="border-r border-slate-200 p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase">Golden</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">312</p>
                            </div>
                            <div class="p-3">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase">Fontes</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">4</p>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div class="flex items-center justify-between px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-7 w-7 place-items-center bg-slate-900 text-[10px] font-bold text-white">A</span>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800">ACME Corporation</p>
                                        <p class="text-[10px] text-slate-400">erp · CUST-8871 · v3</p>
                                    </div>
                                </div>
                                <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-semibold text-white">master</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-7 w-7 place-items-center bg-slate-200 text-[10px] font-bold text-slate-600">S</span>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800">Sync Salesforce</p>
                                        <p class="text-[10px] text-slate-400">há 2 min · full</p>
                                    </div>
                                </div>
                                <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">completed</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-7 w-7 place-items-center bg-slate-200 text-[10px] font-bold text-slate-600">A</span>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800">Auditoria</p>
                                        <p class="text-[10px] text-slate-400">entity.normalized</p>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400">há 5 min</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-right text-[11px] text-slate-400">Matching entre fontes: 98,4% · isolado por tenant</p>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-b border-slate-200 bg-slate-50/60">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="grid gap-px overflow-hidden border border-slate-200 bg-slate-200 sm:grid-cols-3">
                    <div v-for="(step, index) in steps" :key="step.title" class="bg-white p-5">
                        <p class="font-mono text-xs font-semibold text-slate-400">0{{ index + 1 }}</p>
                        <h3 class="mt-2 text-sm font-bold text-slate-900">{{ step.title }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ step.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="recursos" class="mx-auto max-w-6xl scroll-mt-10 px-4 py-16 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Recursos</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                    Tudo que a governança de dados exige
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Multi-tenant, RBAC por workspace e API consumível por integradores — construído como um SaaS de verdade.
                </p>
            </div>

            <div class="mt-8 grid gap-px overflow-hidden border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="feature in features" :key="feature.title"
                    class="group bg-white p-5 transition-colors duration-200 hover:bg-slate-50">
                    <span class="grid h-9 w-9 place-items-center rounded bg-slate-100 text-slate-600 transition-colors duration-200 group-hover:bg-slate-900 group-hover:text-white">
                        <Icon :name="feature.icon" class="h-4 w-4" />
                    </span>
                    <h3 class="mt-4 text-sm font-bold text-slate-900">{{ feature.title }}</h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ feature.text }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <div class="border border-slate-900 bg-slate-900 px-6 py-12 text-center">
                <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Pronto para ter dados de que você confia?
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-slate-300">
                    Crie um workspace e veja entidades, normalização, integrações e auditoria funcionando em minutos.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-2.5">
                    <RouterLink :to="{ name: 'register' }" class="btn bg-white text-slate-900 hover:bg-slate-200">
                        Criar conta demo
                    </RouterLink>
                    <a href="/docs" target="_blank" class="btn border border-slate-600 text-slate-200 hover:bg-slate-800">
                        Explorar a API
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-6 sm:px-6">
                <AppLogo />
                <div class="flex items-center gap-5 text-xs text-slate-500">
                    <a href="/docs" target="_blank" class="transition hover:text-slate-900">API Docs</a>
                    <RouterLink :to="{ name: 'login' }" class="transition hover:text-slate-900">Entrar</RouterLink>
                    <span class="text-slate-300">·</span>
                    <span class="text-slate-400">Laravel · Sanctum · Vue</span>
                </div>
            </div>
        </footer>
    </div>
</template>
