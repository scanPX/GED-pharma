import { defineStore } from 'pinia';
import api from '../bootstrap';

export const useDocumentStore = defineStore('documents', {
    state: () => ({
        documents: [],
        currentDocument: null,
        categories: [],
        types: [],
        statuses: [],
        pagination: {
            currentPage: 1,
            lastPage: 1,
            perPage: 15,
            total: 0,
        },
        filters: {
            search: '',
            category_id: null,
            type_id: null,
            status_id: null,
            owner_id: null,
            date_from: null,
            date_to: null,
            needing_review: false,
        },
        loading: false,
        error: null,
    }),

    getters: {
        getDocumentById: (state) => (id) => {
            return state.documents.find(d => d.id === id);
        },

        effectiveDocuments: (state) => {
            return state.documents.filter(d => d.status?.code === 'EFFECTIVE');
        },

        pendingApprovalCount: (state) => {
            return state.documents.filter(d => d.status?.code === 'PENDING_APPROVAL').length;
        },

        draftCount: (state) => {
            return state.documents.filter(d => d.status?.code === 'DRAFT').length;
        },

        categoriesOptions: (state) => {
            return state.categories.map(c => ({ value: c.id, label: c.name, code: c.code }));
        },

        typesOptions: (state) => {
            return state.types.map(t => ({ value: t.id, label: t.name, code: t.code }));
        },

        statusesOptions: (state) => {
            return state.statuses.map(s => ({
                value: s.id,
                label: s.name,
                code: s.code,
                color: s.color
            }));
        },
    },

    actions: {
        async fetchDocuments(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const endpoint = this.filters.needing_review ? '/documents/needing-review' : '/documents';
                const params = {
                    page,
                    per_page: this.pagination.perPage,
                    ...this.filters
                };

                // Remove internal filter toggle
                delete params.needing_review;

                // Nettoyer les filtres vides
                Object.keys(params).forEach(key => {
                    if (params[key] === null || params[key] === '') {
                        delete params[key];
                    }
                });

                const response = await api.get(endpoint, { params });
                const payload = response.data?.data || response.data;

                if (payload && Array.isArray(payload.data)) {
                    this.documents = payload.data;
                    this.pagination = {
                        currentPage: payload.current_page,
                        lastPage: payload.last_page,
                        perPage: payload.per_page,
                        total: payload.total,
                    };
                } else {
                    this.documents = Array.isArray(payload) ? payload : [];
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur de chargement';
            } finally {
                this.loading = false;
            }
        },

        async fetchDocument(id) {
            this.loading = true;
            this.error = null;

            try {
                const response = await api.get(`/documents/${id}`);
                this.currentDocument = response.data.data;
                return this.currentDocument;
            } catch (error) {
                this.error = error.response?.data?.message || 'Document non trouvé';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async createDocument(formData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await api.post('/documents', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                return { success: true, document: response.data.data };
            } catch (error) {
                // Log full backend error for debugging
                console.error('Document create error:', error.response?.data || error);
                this.error = error.response?.data?.message || 'Erreur de création';
                return { success: false, error: this.error, errors: error.response?.data?.errors, debug: error.response?.data };
            } finally {
                this.loading = false;
            }
        },

        async updateDocument(id, data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await api.put(`/documents/${id}`, data);

                // Mettre à jour dans la liste
                const index = this.documents.findIndex(d => d.id === id);
                if (index !== -1) {
                    this.documents[index] = response.data.data;
                }

                if (this.currentDocument?.id === id) {
                    this.currentDocument = response.data.data;
                }

                return { success: true, document: response.data.data };
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur de mise à jour';
                return { success: false, error: this.error };
            } finally {
                this.loading = false;
            }
        },

        async uploadVersion(documentId, formData) {
            this.loading = true;

            try {
                const response = await api.post(
                    `/documents/${documentId}/versions`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );

                return { success: true, version: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async downloadDocument(documentId, versionId = null) {
            try {
                const url = versionId
                    ? `/documents/${documentId}/download/${versionId}`
                    : `/documents/${documentId}/download`;

                const response = await api.get(url, { responseType: 'blob' });

                // Extraire le nom du fichier du header
                const contentDisposition = response.headers['content-disposition'];
                let filename = 'document';
                if (contentDisposition) {
                    const match = contentDisposition.match(/filename="(.+)"/);
                    if (match) filename = match[1];
                }

                // Télécharger
                const blob = new Blob([response.data]);
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                link.click();

                return { success: true };
            } catch (error) {
                return { success: false, error: 'Erreur de téléchargement' };
            }
        },

        async archiveDocument(documentId) {
            this.loading = true;

            try {
                const response = await api.post(`/documents/${documentId}/archive`);

                // Mettre à jour le document dans la liste
                const index = this.documents.findIndex(d => d.id === documentId);
                if (index !== -1) {
                    this.documents[index] = response.data.data;
                }

                if (this.currentDocument?.id === documentId) {
                    this.currentDocument = response.data.data;
                }

                return { success: true, document: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async deleteDocument(documentId) {
            this.loading = true;
            try {
                await api.delete(`/documents/${documentId}`);
                this.documents = this.documents.filter(d => d.id !== documentId);
                if (this.currentDocument?.id === documentId) {
                    this.currentDocument = null;
                }
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message || 'Erreur lors de la suppression' };
            } finally {
                this.loading = false;
            }
        },

        async printDocument(documentId) {
            try {
                const response = await api.get(`/documents/${documentId}/print`);
                // En ouvrant une nouvelle fenêtre pour le print (ou via un modal dédié)
                // Pour l'instant on retourne les données prêtes à l'impression
                return { success: true, data: response.data.data };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            }
        },

        async fetchCategories() {
            try {
                const response = await api.get('/documents/categories');
                this.categories = response.data.data;
            } catch (error) {
                console.error('Erreur chargement catégories:', error);
            }
        },

        async fetchTypes() {
            try {
                const response = await api.get('/documents/types');
                this.types = response.data.data;
            } catch (error) {
                console.error('Erreur chargement types:', error);
            }
        },

        async fetchStatuses() {
            try {
                const response = await api.get('/documents/statuses');
                this.statuses = response.data.data;
            } catch (error) {
                console.error('Erreur chargement statuts:', error);
            }
        },

        async fetchReferenceData() {
            await Promise.all([
                this.fetchCategories(),
                this.fetchTypes(),
                this.fetchStatuses(),
            ]);
        },

        setFilters(filters) {
            this.filters = { ...this.filters, ...filters };
        },

        resetFilters() {
            this.filters = {
                search: '',
                category_id: null,
                type_id: null,
                status_id: null,
                owner_id: null,
                date_from: null,
                date_to: null,
            };
        },
    },
});
