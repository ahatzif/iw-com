import { createApp } from 'vue';

import App from './App.vue';
import { createCashierRouter } from './router.js';
import { createCashierStore } from './store/cashierStore.js';

const configEl = document.getElementById('iw-cashier-config');
const cashierConfig = configEl ? JSON.parse(configEl.textContent || '{}') : {};

const app = createApp(App);

app.provide('cashierStore', createCashierStore(cashierConfig));
app.use(createCashierRouter());
app.mount('#cashier-app');
