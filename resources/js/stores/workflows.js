import { defineStore } from 'pinia';
import api from '../bootstrap';

export const useWorkflowStore = defineStore('workflows', {
    state: () => ({
        workflows: [],
        workflowInstances: [],
        currentInstance: null,
        pendingActions: [],
        loading: false,
        error: null,
    }),

    getters: {
        pendingCount: (state) => state.pendingActions.length,
        
        myPendingActions: (state) => state.pendingActions,
        
        instancesByStatus: (state) => (status) => {
            return state.workflowInstances.filter(i => i.status === status);
        },
        
        inProgressInstances: (state) => {
            return state.workflowInstances.filter(i => i.status === 'in_progress');
        },
    },

    actions: {
        async fetchWorkflows() {
            this.loading = true;
            
            try {
                const response = await api.get('/workflows/definitions');
                this.workflows = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message;
            } finally {
                this.loading = false;
            }
        },

        async fetchInstances() {
            this.loading = true;
            try {
                const response = await api.get('/workflows');
                this.workflowInstances = response.data.data;
            } catch (error) {
                console.error('Erreur chargement instances workflows:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchPendingActions() {
            try {
                const response = await api.get('/workflows/my-pending');
                this.pendingActions = response.data.data;
            } catch (error) {
                console.error('Erreur chargement actions en attente:', error);
            }
        },

        async fetchInstance(id) {
            this.loading = true;
            
            try {
                const response = await api.get(`/workflows/${id}`);
                this.currentInstance = response.data.data;
                return this.currentInstance;
            } catch (error) {
                this.error = error.response?.data?.message;
                return null;
            } finally {
                this.loading = false;
            }
        },

        async initiateWorkflow(documentId, workflowId, data = {}) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await api.post(`/workflows/documents/${documentId}/initiate`, {
                    workflow_id: workflowId,
                    ...data
                });
                
                return { success: true, instance: response.data.data };
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur d\'initiation';
                return { success: false, error: this.error };
            } finally {
                this.loading = false;
            }
        },

        async submitWorkflow(instanceId, data = {}) {
            this.loading = true;
            
            try {
                const response = await api.post(`/workflows/${instanceId}/submit`, data);
                
                await this.fetchPendingActions();
                
                return { success: true, instance: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async approveStep(instanceId, data = {}) {
            this.loading = true;
            
            try {
                const response = await api.post(`/workflows/${instanceId}/approve`, data);
                
                // Rafraîchir les actions en attente
                await this.fetchPendingActions();
                
                return { success: true, instance: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async rejectStep(instanceId, data) {
            this.loading = true;
            
            try {
                const response = await api.post(`/workflows/${instanceId}/reject`, data);
                
                await this.fetchPendingActions();
                
                return { success: true, instance: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async requestRevision(instanceId, data) {
            this.loading = true;
            
            try {
                const response = await api.post(`/workflows/${instanceId}/revision`, data);
                
                await this.fetchPendingActions();
                
                return { success: true, instance: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async cancelWorkflow(instanceId, reason) {
            this.loading = true;
            
            try {
                const response = await api.post(`/workflows/${instanceId}/cancel`, {
                    reason
                });
                
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async fetchDocumentWorkflows(documentId) {
            try {
                const response = await api.get(`/documents/${documentId}/workflows`);
                return response.data.data;
            } catch (error) {
                console.error('Erreur chargement historique workflow:', error);
                return [];
            }
        },

        // ========== ADMIN METHODS ==========

        async fetchAdminWorkflows() {
            this.loading = true;
            try {
                const response = await api.get('/admin/workflows');
                this.workflows = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message;
            } finally {
                this.loading = false;
            }
        },

        async createWorkflow(data) {
            this.loading = true;
            try {
                const response = await api.post('/admin/workflows', data);
                return { success: true, data: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async updateWorkflow(id, data) {
            this.loading = true;
            try {
                const response = await api.put(`/admin/workflows/${id}`, data);
                return { success: true, data: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async deleteWorkflow(id) {
            this.loading = true;
            try {
                await api.delete(`/admin/workflows/${id}`);
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async addWorkflowStep(workflowId, data) {
            this.loading = true;
            try {
                const response = await api.post(`/admin/workflows/${workflowId}/steps`, data);
                return { success: true, data: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async updateWorkflowStep(workflowId, stepId, data) {
            this.loading = true;
            try {
                const response = await api.put(`/admin/workflows/${workflowId}/steps/${stepId}`, data);
                return { success: true, data: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async removeWorkflowStep(workflowId, stepId) {
            this.loading = true;
            try {
                await api.delete(`/admin/workflows/${workflowId}/steps/${stepId}`);
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async reorderWorkflowSteps(workflowId, steps) {
            try {
                await api.post(`/admin/workflows/${workflowId}/reorder-steps`, { steps });
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            }
        },

        async assignWorkflowTypes(workflowId, data) {
            try {
                await api.post(`/admin/workflows/${workflowId}/assign-document-types`, data);
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            }
        },
    },
});
