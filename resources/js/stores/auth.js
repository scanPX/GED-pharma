import { defineStore } from 'pinia';
import api from '../bootstrap';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('ged_token'),
        permissions: [],
        roles: [],
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user && !!state.token,

        hasPermission: (state) => (permission) => {
            // Admin has all permissions
            if (state.roles.includes('admin')) {
                return true;
            }
            return state.permissions.includes(permission) || state.permissions.includes('*');
        },

        hasRole: (state) => (role) => {
            return state.roles.includes(role);
        },

        isAdmin: (state) => state.roles.includes('admin'),

        canApprove: (state) => {
            return state.user?.can_approve_documents ||
                state.roles.some(r => ['qa_manager', 'qa_analyst', 'admin'].includes(r));
        },

        canSign: (state) => {
            return state.user?.has_signature_pin || false;
        },

        userName: (state) => state.user?.name || 'Utilisateur',

        userInitials: (state) => {
            if (!state.user?.name) return '??';
            return state.user.name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);
        },
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                // Login via GED API
                const response = await api.post('/auth/login', credentials);

                this.token = response.data.token;
                this.user = response.data.user;
                this.permissions = response.data.user.permissions || [];
                // Extract role names/slugs properly - handle both object and string formats
                const roles = response.data.user.roles || [];
                this.roles = roles.map(r => typeof r === 'string' ? r : (r.slug || r.name));

                console.log('Login successful - Roles:', this.roles, 'Permissions:', this.permissions);

                localStorage.setItem('ged_token', this.token);
                localStorage.setItem('ged_user', JSON.stringify(this.user));

                return { success: true };
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur de connexion';
                return { success: false, error: this.error };
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await api.post('/auth/logout');
            } catch (e) {
                // Continuer même en cas d'erreur
            }
            this.clearAuth();
        },

        async logoutAll() {
            this.loading = true;
            try {
                await api.post('/auth/logout-all');
                this.clearAuth();
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async refresh() {
            try {
                const response = await api.post('/auth/refresh');
                this.token = response.data.token;
                localStorage.setItem('ged_token', this.token);
                return true;
            } catch (error) {
                return false;
            }
        },

        clearAuth() {
            this.user = null;
            this.token = null;
            this.permissions = [];
            this.roles = [];

            localStorage.removeItem('ged_token');
            localStorage.removeItem('ged_user');
        },

        async checkAuth() {
            if (!this.token) return false;

            try {
                const response = await api.get('/auth/me');

                this.user = response.data.user;
                this.permissions = response.data.user.permissions || [];
                // Extract role names/slugs properly - handle both object and string formats
                const roles = response.data.user.roles || [];
                this.roles = roles.map(r => typeof r === 'string' ? r : (r.slug || r.name));

                console.log('Auth check - Roles:', this.roles, 'Permissions:', this.permissions);

                return true;
            } catch (error) {
                this.logout();
                return false;
            }
        },

        async verifyPassword(password) {
            try {
                const response = await api.post('/auth/verify-password', { password });
                return response.data.valid;
            } catch (error) {
                return false;
            }
        },

        async verifyPin(pin) {
            try {
                const response = await api.post('/auth/verify-pin', { pin });
                return response.data.valid;
            } catch (error) {
                return false;
            }
        },

        async changePassword(currentPassword, newPassword, newPasswordConfirmation) {
            this.loading = true;

            try {
                await api.post('/auth/change-password', {
                    current_password: currentPassword,
                    password: newPassword,
                    password_confirmation: newPasswordConfirmation,
                });
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        async setSignaturePin(password, pin) {
            this.loading = true;

            try {
                await api.post('/auth/set-signature-pin', { password, pin });
                this.user.has_signature_pin = true;
                return { success: true };
            } catch (error) {
                return { success: false, error: error.response?.data?.message };
            } finally {
                this.loading = false;
            }
        },

        // Initialize from localStorage
        initializeFromStorage() {
            const storedUser = localStorage.getItem('ged_user');
            if (storedUser && this.token) {
                try {
                    this.user = JSON.parse(storedUser);
                    this.permissions = this.user.permissions || [];
                    // Extract role names/slugs properly - handle both object and string formats
                    const roles = this.user.roles || [];
                    this.roles = roles.map(r => typeof r === 'string' ? r : (r.slug || r.name));

                    console.log('Initialized from storage - Roles:', this.roles, 'Permissions:', this.permissions);
                } catch (e) {
                    this.logout();
                }
            }
        },
    },
});
