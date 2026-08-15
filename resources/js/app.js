import './bootstrap';

import Alpine from 'alpinejs';
import { registerSW } from 'virtual:pwa-register';

window.Alpine = Alpine;

Alpine.start();

registerSW({ immediate: true });
