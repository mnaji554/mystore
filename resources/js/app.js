import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

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

Alpine.data('deliveryLocationPicker', (initialLatitude, initialLongitude) => ({
    map: null,
    marker: null,
    leaflet: null,
    mapOpen: false,
    locationMessage: '',
    savedLatitude: initialLatitude === null ? null : Number(initialLatitude),
    savedLongitude: initialLongitude === null ? null : Number(initialLongitude),
    pendingLatitude: initialLatitude === null ? null : Number(initialLatitude),
    pendingLongitude: initialLongitude === null ? null : Number(initialLongitude),
    async openMap() {
        this.mapOpen = true;
        this.locationMessage = '';
        this.pendingLatitude = this.savedLatitude;
        this.pendingLongitude = this.savedLongitude;
        await this.$nextTick();

        try {
            const [{ default: L }] = await Promise.all([
                import('leaflet'),
                import('leaflet/dist/leaflet.css'),
            ]);
            this.leaflet = L;
            L.Icon.Default.mergeOptions({
                iconUrl: markerIcon,
                iconRetinaUrl: markerIconRetina,
                shadowUrl: markerShadow,
            });
            const hasLocation = this.pendingLatitude !== null && this.pendingLongitude !== null;
            const center = hasLocation ? [this.pendingLatitude, this.pendingLongitude] : [24.7136, 46.6753];

            if (!this.map) {
                this.map = L.map(this.$refs.map).setView(center, hasLocation ? 15 : 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);
                this.map.on('click', (event) => this.setPendingLocation(event.latlng.lat, event.latlng.lng, L));
            } else {
                this.map.setView(center, hasLocation ? 15 : 6);
            }

            this.map.invalidateSize();

            if (hasLocation) {
                this.setPendingLocation(...center, L);
            } else if (this.marker) {
                this.marker.remove();
                this.marker = null;
            }
        } catch (error) {
            console.error('Unable to load the delivery location map.', error);
            this.locationMessage = 'تعذر تحميل الخريطة. تحقق من اتصال الإنترنت ثم حاول مرة أخرى.';
        }
    },
    setPendingLocation(latitude, longitude, leaflet) {
        this.pendingLatitude = latitude;
        this.pendingLongitude = longitude;

        if (this.marker) {
            this.marker.setLatLng([latitude, longitude]);
            return;
        }

        this.marker = leaflet.marker([latitude, longitude], { draggable: true }).addTo(this.map);
        this.marker.on('dragend', (event) => {
            const position = event.target.getLatLng();
            this.pendingLatitude = position.lat;
            this.pendingLongitude = position.lng;
        });
    },
    useCurrentLocation() {
        this.locationMessage = '';

        if (!window.isSecureContext) {
            this.locationMessage = 'يلزم اتصال آمن (HTTPS) أو فتح الموقع من localhost لاستخدام موقعك الحالي.';
            return;
        }

        if (!navigator.geolocation) {
            this.locationMessage = 'متصفحك لا يدعم تحديد الموقع.';
            return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            this.setPendingLocation(latitude, longitude, this.leaflet);
            this.map.setView([latitude, longitude], 16);
        }, (error) => {
            this.locationMessage = error.code === 1
                ? 'تم رفض إذن الموقع أو حظره. فعّل إذن الموقع من إعدادات المتصفح ثم أعد المحاولة.'
                : 'تعذر تحديد موقعك. يمكنك اختيار النقطة مباشرة من الخريطة.';
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    },
    async confirmLocation() {
        if (this.pendingLatitude === null || this.pendingLongitude === null) {
            return;
        }

        try {
            await this.$wire.setDeliveryLocation(this.pendingLatitude, this.pendingLongitude);
            this.savedLatitude = this.pendingLatitude;
            this.savedLongitude = this.pendingLongitude;
            this.locationMessage = '';
            this.mapOpen = false;
        } catch (error) {
            console.error('Unable to save the delivery location.', error);
            this.locationMessage = 'تعذر حفظ الموقع. يرجى المحاولة مرة أخرى.';
        }
    },
    closeMap() {
        this.mapOpen = false;
        this.locationMessage = '';
    },
    clearSelection() {
        this.savedLatitude = null;
        this.savedLongitude = null;
        this.pendingLatitude = null;
        this.pendingLongitude = null;
        this.marker?.remove();
        this.marker = null;
    },
    destroy() {
        this.map?.remove();
    },
}));

Livewire.start();
