import { defineStore } from 'pinia';

let nextId = 0;

export const useToastStore = defineStore('toast', {
    state: () => ({
        items: [],
    }),

    actions: {
        push(type, message) {
            const id = ++nextId;
            this.items.push({ id, type, message });

            window.setTimeout(() => this.dismiss(id), type === 'error' ? 6000 : 4000);

            return id;
        },
        success(message) {
            return this.push('success', message);
        },
        error(message) {
            return this.push('error', message);
        },
        info(message) {
            return this.push('info', message);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    },
});
