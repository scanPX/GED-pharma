import { defineStore } from 'pinia';
import api from '@/bootstrap';

export const useSignatureStore = defineStore('signature', {
    state: () => ({
        signatures: [],
        currentSignature: null,
        loading: false,
        error: null,
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        }
    }),

    actions: {
        async fetchSignatures(filters = {}) {
            this.loading = true;
            try {
                const response = await api.get('/ged/signatures', { params: filters });
                this.signatures = response.data.data.data;
                this.pagination = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    total: response.data.data.total
                };
            } catch (err) {
                this.error = 'Erreur lors de la récupération des signatures';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async fetchSignature(id) {
            this.loading = true;
            try {
                const response = await api.get(`/ged/signatures/${id}`);
                this.currentSignature = response.data.data;
                return response.data;
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async verifySignature(id) {
            try {
                const response = await api.get(`/ged/signatures/${id}/verify`);
                return response.data;
            } catch (err) {
                throw err.response?.data || err;
            }
        },

        async revokeSignature(id, reason) {
            this.loading = true;
            try {
                const response = await api.post(`/ged/signatures/${id}/revoke`, { reason });
                return response.data;
            } catch (err) {
                throw err.response?.data || err;
            } finally {
                this.loading = false;
            }
        }
    }
});
