<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useToastStore } from './stores/toast';
import ToastHost from './components/ui/ToastHost.vue';

const router = useRouter();
const auth = useAuthStore();
const toast = useToastStore();

function handleUnauthorized() {
    if (!auth.token) {
        return;
    }

    auth.clearSession();
    toast.error('Sua sessão expirou. Entre novamente para continuar.');

    if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' });
    }
}

onMounted(() => {
    window.addEventListener('mdm:unauthorized', handleUnauthorized);
});

onBeforeUnmount(() => {
    window.removeEventListener('mdm:unauthorized', handleUnauthorized);
});
</script>

<template>
    <ToastHost />
    <router-view />
</template>
