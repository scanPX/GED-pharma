import { defineStore } from 'pinia';
import api from '@/bootstrap';

export const useTrainingStore = defineStore('training', {
    state: () => ({
        myTrainings: [],
        allTrainings: [],
        currentTraining: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchMyTrainings() {
            this.loading = true;
            try {
                const response = await api.get('/ged/training');
                this.myTrainings = response.data.data;
            } catch (err) {
                this.error = 'Erreur lors de la récupération de vos formations';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async fetchAllTrainings(filters = {}) {
            this.loading = true;
            try {
                const response = await api.get('/ged/training/all', { params: filters });
                this.allTrainings = response.data.data;
            } catch (err) {
                this.error = 'Erreur lors de la récupération des formations';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async fetchTraining(id) {
            this.loading = true;
            try {
                const response = await api.get(`/ged/training/${id}`);
                this.currentTraining = response.data.data;
                return response.data;
            } catch (err) {
                this.error = 'Erreur lors de la récupération de la formation';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async assignTraining(data) {
            this.loading = true;
            try {
                const response = await api.post('/ged/training/assign', data);
                return response.data;
            } catch (err) {
                throw err.response?.data || err;
            } finally {
                this.loading = false;
            }
        },

        async startTraining(id) {
            try {
                const response = await api.post(`/ged/training/${id}/start`);
                return response.data;
            } catch (err) {
                throw err.response?.data || err;
            }
        },

        async acknowledgeTraining(id, data) {
            this.loading = true;
            try {
                const response = await api.post(`/ged/training/${id}/acknowledge`, data);
                return response.data;
            } catch (err) {
                throw err.response?.data || err;
            } finally {
                this.loading = false;
            }
        }
    }
});
