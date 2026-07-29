import { createRouter, createWebHashHistory } from 'vue-router';

import HistoryView from './views/HistoryView.vue';
import SaleView from './views/SaleView.vue';
import SettingsView from './views/SettingsView.vue';
import TicketsView from './views/TicketsView.vue';

export function createCashierRouter() {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      {
        path: '/',
        name: 'tickets',
        component: TicketsView,
      },
      {
        path: '/sale/:ticketId?',
        name: 'sale',
        component: SaleView,
      },
      {
        path: '/history',
        name: 'history',
        component: HistoryView,
      },
      {
        path: '/history/:orderId',
        name: 'history-order',
        component: HistoryView,
      },
      {
        path: '/settings',
        name: 'settings',
        component: SettingsView,
      },
      {
        path: '/:pathMatch(.*)*',
        redirect: '/',
      },
    ],
  });
}
