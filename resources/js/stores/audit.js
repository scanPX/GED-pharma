import { defineStore } from 'pinia';
import api from '../bootstrap';

export const useAuditStore = defineStore('audit', {
    state: () => ({
        logs: [],
        statistics: null,
        loading: false,
        error: null,
        pagination: {
            currentPage: 1,
            lastPage: 1,
            total: 0,
        },
    }),

    actions: {
        async fetchLogs(filters = {}, page = 1) {
            this.loading = true;
            try {
                const params = { page, ...filters };
                // Clean empty filters
                Object.keys(params).forEach(key => {
                    if (!params[key]) delete params[key];
                });

                const response = await api.get('/audit', { params });
                const payload = response.data?.data || response.data;

                if (payload && Array.isArray(payload.data)) {
                    this.logs = payload.data;
                    this.pagination.currentPage = payload.current_page;
                    this.pagination.lastPage = payload.last_page;
                    this.pagination.total = payload.total;
                } else {
                    this.logs = Array.isArray(payload) ? payload : [];
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur chargement audit';
            } finally {
                this.loading = false;
            }
        },

        async verifyIntegrity() {
            try {
                const response = await api.get('/audit/verify-integrity');
                return response.data;
            } catch (error) {
                return { valid: false, message: 'Erreur lors de la vérification' };
            }
        },

        async fetchStatistics() {
            try {
                const response = await api.get('/audit/statistics');
                this.statistics = response.data.data;
                return this.statistics;
            } catch (error) {
                console.error('Erreur chargement statistiques:', error);
                return null;
            }
        },

        async exportAudit(filters = {}) {
            try {
                const response = await api.get('/audit/export', {
                    params: filters,
                    responseType: 'blob'
                });
                return response.data;
            } catch (error) {
                console.error('Erreur export audit:', error);
                throw error;
            }
        },

        async generateReport(data) {
            try {
                const response = await api.post('/audit/report', data, {
                    responseType: 'blob'
                });
                return response.data;
            } catch (error) {
                console.error('Erreur génération rapport:', error);
                throw error;
            }
        }
    }
});
