var app = new Vue({
    el: '#app',
    created: function() {
        this.$vuetify.theme.primary = '#f44336';
    },
    data: function() {
        return {
            appName: 'BeDrive',
            errorMessage: null,
            loading: false,

            currentStep: 1,
            steps: {
                introduction: {
                    number: 1,
                    completed: true,

                },
                requirements: {
                    number: 2,
                    completed: false,
                    data: {}
                },
                filesystem: {
                    number: 3,
                    completed: false,
                    data: {}
                },
                database: {
                    number: 4,
                    completed: false,
                },
                admin: {
                    number: 5,
                    completed: false,
                },
                final: {
                    number: 6,
                    completed: false,
                }
            },

            databaseForm: {
                db_host: 'localhost',
                db_database: '',
                db_username: 'root',
                db_password: '',
                db_prefix: '',
            },

            adminForm: {
                username: '',
                email: '',
                password: '',
                password_confirmation: '',
            }
        }
    },
    computed: {
        canGoToNextStep() {
            var vue = this;
            var key = Object.keys(this.steps).find(function(key) {
                return vue.steps[key].number === vue.currentStep;
            });
            return !this.loading && this.steps[key] && this.steps[key].completed;
        }
    },
    watch: {
        currentStep(stepNumber, oldStep) {
            if (stepNumber === this.steps.requirements.number) {
                this.checkRequirements();
            } else if (stepNumber === this.steps.filesystem.number) {
                this.checkFilesystem();
            }

        }
    },
    methods: {
        nextStep() {
            this.currentStep = this.currentStep + 1;
        },

        checkRequirements(nextStep) {
            var vue = this;
            this.callBackend('onCheckRequirements').then(function(response) {
                if ( ! response) return;
                vue.steps.requirements.data = response.data;
                vue.steps.requirements.completed = vue.noIssues(response.data);
                if (nextStep && vue.steps.filesystem.completed) {
                    vue.nextStep();
                }
            });
        },

        checkFilesystem(nextStep) {
            var vue = this;
            this.callBackend('onCheckFileSystem').then(function(response) {
                if ( ! response) return;
                vue.steps.filesystem.data = response.data;
                vue.steps.filesystem.completed = vue.noIssues(response.data);
                if (nextStep && vue.steps.filesystem.completed) {
                    vue.nextStep();
                }
            });
        },

        validateAndInsertDatabaseCredentials() {
            var vue = this;
            this.callBackend('onValidateAndInsertDatabaseCredentials', this.databaseForm).then(function() {
                if ( ! vue.errorMessage) {
                    vue.steps.database.completed = true;
                    vue.nextStep();
                }
            });
        },

        validateAdminCredentials() {
            var vue = this;
            this.callBackend('onValidateAdminCredentials').then(function() {
                if ( ! vue.errorMessage) {
                    vue.steps.admin.completed = true;
                    vue.nextStep();
                }
            });
        },

        installApplication() {
            var vue = this;
            this.callBackend('onInstallApplication', vue.adminForm).then(function() {
                if ( ! vue.errorMessage) {
                    vue.steps.final.completed = true;
                }
            });
        },

        noIssues(results) {
            return !Object.keys(results).some(function(key) {
                return !results[key].result;
            });
        },

        callBackend(handler, params) {
            this.loading = true;

            var vue = this;
            var data = new FormData();
            data.set('handler', handler);

            if (params) {
                Object.keys(params).forEach(function(key) {
                    data.set(key, params[key]);
                });
            }

            return axios({
                method: 'post',
                url: window.location.pathname,
                data: data,
                config: { headers: {'Content-Type': 'multipart/form-data' }}
            }).then(function (response) {
                vue.loading = false;
                vue.errorMessage = null;
                return response;
            }).catch(function (error) {
                vue.loading = false;
                vue.errorMessage = error.response.data;
                return false;
            });
        }
    }
});