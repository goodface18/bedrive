<?php include 'php/boot.php'; ?><!DOCTYPE html>
<html lang="en">
<head>
    <base href="install_files/">
    
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <title>Installation</title>

    <!-- Styles -->
    <link href='https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons' rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Base URL -->
    <?php if (!isset($fatalError)): ?>
        <script>
            installerBaseUrl = '<?= $installer->getBaseUrl() ?>';
        </script>
    <?php endif ?>

    <style>
        [v-cloak] {
            display: none;
        }
    </style>
</head>
<body>
<div id="app">
    <?php if (isset($fatalError)): ?>
        <div class="container">
            <div class="callout callout-danger"><?= $fatalError ?></div>
        </div>
    <?php else: ?>
        <v-app v-cloak>
            <img class="logo" src="../client/assets/images/logo-dark.png" alt="logo">
            <v-stepper v-model="currentStep">
                <v-stepper-header>
                    <v-stepper-step :complete="steps.introduction.completed" step="1">Introduction</v-stepper-step>

                    <v-divider></v-divider>

                    <v-stepper-step :complete="steps.requirements.completed" step="2">Requirements</v-stepper-step>

                    <v-divider></v-divider>

                    <v-stepper-step :complete="steps.filesystem.completed" step="3">Filesystem</v-stepper-step>

                    <v-divider></v-divider>

                    <v-stepper-step :complete="steps.database.completed" step="4">Database</v-stepper-step>

                    <v-divider></v-divider>

                    <v-stepper-step :complete="steps.admin.completed" step="5">Admin Account</v-stepper-step>

                    <v-divider></v-divider>

                    <v-stepper-step :complete="steps.final.completed" step="6">Install</v-stepper-step>
                </v-stepper-header>

                <v-progress-linear :indeterminate="true" v-if="loading"></v-progress-linear>

                <div class="error-container red white--text" v-if="errorMessage">{{errorMessage}}</div>

                <v-stepper-items>
                    <v-stepper-content step="1" class="introduction-step">
                        <p class="margin-top-none">Welcome to {{appName}}. Before getting started, we need some information on the database. You will need to know the following items before proceeding.</p>

                        <ol>
                            <li>Database host</li>
                            <li>Database name</li>
                            <li>Database username</li>
                            <li>Database password</li>
                        </ol>

                        <p>Most likely these items were supplied to you by your Web Host. If you don’t have this information, then you will need to contact them before you can continue.</p>

                        <p>Installer will insert this information inside a configuration file so {{appName}} can communicate with your database.</p>

                        <p>Need more help? <a href="https://support.vebto.com/help-center/articles/35/37/34/installation" target="_blank">See installation guide.</a></p>

                        <v-btn color="primary" @click="nextStep()" :disabled="!canGoToNextStep">Continue</v-btn>
                    </v-stepper-content class=>

                    <v-stepper-content step="2" class="requirements-step">
                        <v-list two-line>
                            <template v-for="(value, key) in steps.requirements.data">
                                <v-list-tile>
                                    <v-list-tile-content>
                                        <v-list-tile-title>{{key}}</v-list-tile-title>
                                        <v-list-tile-sub-title v-if="!value.result" class="red--text">{{value.errorMessage}}</v-list-tile-sub-title>
                                    </v-list-tile-content>

                                    <v-list-tile-action>
                                        <v-icon class="green--text" v-if="value.result">check_circle</v-icon>
                                        <v-icon class="red--text" v-if="!value.result">error</v-icon>
                                    </v-list-tile-action>
                                </v-list-tile>

                                <v-divider></v-divider>
                            </template>
                        </v-list>

                        <v-btn color="primary" v-if="canGoToNextStep" @click="nextStep()" :disabled="loading">Continue</v-btn>
                        <v-btn color="primary" v-if="!canGoToNextStep" @click="checkRequirements(true)" :disabled="loading">Check Again</v-btn>
                    </v-stepper-content>

                    <v-stepper-content step="3" class="filesystem-step">
                        <v-list two-line>
                            <template v-for="item in steps.filesystem.data">
                                <v-list-tile>
                                    <v-list-tile-content>
                                        <v-list-tile-title>{{item.path}}</v-list-tile-title>
                                        <v-list-tile-sub-title v-if="!item.result" class="red--text" v-html="item.errorMessage"></v-list-tile-sub-title>
                                    </v-list-tile-content>

                                    <v-list-tile-action>
                                        <v-icon class="green--text" v-if="item.result">check_circle</v-icon>
                                        <v-icon class="red--text" v-if="!item.result">error</v-icon>
                                    </v-list-tile-action>
                                </v-list-tile>

                                <v-divider></v-divider>
                            </template>
                        </v-list>

                        <v-btn color="primary" v-if="canGoToNextStep" @click="nextStep()" :disabled="loading">Continue</v-btn>
                        <v-btn color="primary" v-if="!canGoToNextStep" @click="checkFilesystem(true)" :disabled="loading">Check Again</v-btn>
                    </v-stepper-content>

                    <v-stepper-content step="4" class="database-step">
                        <p>Below you should enter your database connection details. If you're not sure about these, contact your hosting provider.</p>
                        <form @submit.prevent="validateAndInsertDatabaseCredentials()" class="many-inputs">
                            <div class="input-container">
                                <label for="host">Database Host</label>
                                <input id="host" type="text" v-model="databaseForm.db_host" required>
                            </div>

                            <div class="input-container">
                                <label for="name">Database Name</label>
                                <input id="name" type="text" v-model="databaseForm.db_database" required>
                            </div>

                            <div class="input-container">
                                <label for="db_username">Database Username</label>
                                <input id="db_username" type="text" v-model="databaseForm.db_username" required>
                            </div>

                            <div class="input-container">
                                <label for="db_password">Database Password</label>
                                <input id="db_password" type="text" v-model="databaseForm.db_password">
                            </div>

                            <div class="input-container">
                                <label for="prefix">Database Prefix</label>
                                <input id="prefix" type="text" v-model="databaseForm.prefix" placeholder="Optional">
                            </div>

                            <v-btn type="submit" color="primary" :disabled="loading">Continue</v-btn>
                        </form>
                    </v-stepper-content>

                    <v-stepper-content step="5" class="admin-step">
                        <form @submit.prevent="validateAdminCredentials()" class="many-inputs">
                            <div class="input-container">
                                <label for="username">Admin Username</label>
                                <input id="username" type="text" v-model="adminForm.username" required>
                            </div>

                            <div class="input-container">
                                <label for="email">Admin Email</label>
                                <input id="email" type="email" v-model="adminForm.email" required>
                            </div>

                            <div class="input-container">
                                <label for="password">Admin Password</label>
                                <input id="password" type="password" v-model="adminForm.password" required>
                            </div>

                            <div class="input-container">
                                <label for="password_confirmation">Password Confirmation</label>
                                <input id="password_confirmation" type="password" v-model="adminForm.password_confirmation" required>
                            </div>

                            <v-btn type="submit" color="primary" :disabled="loading">Continue</v-btn>
                        </form>
                    </v-stepper-content>

                    <v-stepper-content step="6" class="install-step">
                       <div v-if="!steps.final.completed">
                           <p>Everything seems to be in order. Click button below to start the installation. It might take some time, make sure not to close this browser window until installation is completed.</p>
                           <v-btn color="primary" @click="installApplication()" :disabled="loading || steps.final.completed">Install</v-btn>
                       </div>

                        <div v-if="steps.final.completed">
                            <h2 class="install-completed-header">Installation has been successfully completed!</h2>

                            <div class="site-urls">
                                <div class="col">
                                    <h4>Website address</h4>
                                    <div>Your website is located at this URL:</div>
                                    <p><a :href="installerBaseUrl">{{installerBaseUrl}}</a></p>
                                </div>

                                <div class="col">
                                    <h4>Administration Area</h4>
                                    <div>Use the following link to log into the administration area:</div>
                                    <p><a :href="installerBaseUrl+'/admin/settings'">{{installerBaseUrl+'/admin'}}</a></p>
                                </div>
                            </div>

                            <div class="support-links-container">
                                <h4>Support and Questions</h4>
                                <div>If you have any issues or questions please submit a ticket <a target="_blank" href="https://support.vebto.com">here</a>. Thanks!</div>
                            </div>

                            <v-alert :value="true" type="info">
                                If installation page still appears after visiting one of the above urls or reloading this page, you will need to delete
                                <strong>install_files</strong> directory from your server manually, because installer was not able to do it automatically.
                            </v-alert>
                        </div>
                    </v-stepper-content>
                </v-stepper-items>
            </v-stepper>
        </v-app>
    <?php endif ?>
</div>

<!-- Load Vue.js -->
<script src="js/vue.js"></script>
<script src="js/vueify.js"></script>
<script src="js/axios.js"></script>
<script src="js/app.js"></script>

</body>
</html>