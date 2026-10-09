import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.store('toasts', {
    items: [],
    add(message, type = 'success', timeout = 4500) {
        if (!message) return;
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), timeout);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

// Chart.js is loaded lazily so storefront pages never download it.
Alpine.data('chart', (config) => ({
    instance: null,
    links: config.links ?? [],
    async init() {
        const { default: Chart } = await import('chart.js/auto');
        const dark = document.documentElement.classList.contains('dark');
        Chart.defaults.font.family = 'Tajawal, sans-serif';
        Chart.defaults.color = dark ? '#94a3b8' : '#64748b';
        Chart.defaults.borderColor = dark ? '#1e293b' : '#e2e8f0';
        const { links, ...chartConfig } = config;
        this.instance = new Chart(this.$refs.canvas, {
            ...chartConfig,
            options: {
                ...chartConfig.options,
                onClick: (event, elements) => {
                    const url = this.links[elements[0]?.index];

                    if (url) {
                        window.location.assign(url);
                    }
                },
                onHover: (event, elements) => {
                    if (event.native) {
                        event.native.target.style.cursor = this.links[elements[0]?.index] ? 'pointer' : 'default';
                    }
                },
            },
        });
    },
    destroy() {
        this.instance?.destroy();
    },
}));

Alpine.data('gallery', (images) => ({
    images,
    active: 0,
    get current() {
        return this.images[this.active] ?? null;
    },
}));

Livewire.start();
