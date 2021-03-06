<template>
    <form @submit="onSubmit" method="POST" action="">
        <md-card class="phue-login-form">
            <md-card-header>
                <div class="md-title">{{ heading }}</div>
            </md-card-header>

            <md-card-content>

                <md-field :class="{'md-invalid': showAccountError}">
                    <label for="username">{{ usernameLabel }}</label>
                    <md-input name="username" id="username" v-model="formData.username" :disabled="sending" />
                    <span class="md-error">{{ apiResponse.message }}</span>
                </md-field>

                <md-field :class="{'md-invalid': showAccountError}">
                    <label for="password">{{ passwordLabel }}</label>
                    <md-input type="password" name="password" id="password" v-model="formData.password" :disabled="sending" />
                    <span class="md-error">{{ apiResponse.message }}</span>
                </md-field>

            </md-card-content>

            <md-progress-bar md-mode="indeterminate" v-if="sending" />

            <md-card-actions>
                <span class="message md-error" :class="{'active': showGenericError}">
                    {{ apiResponse.message }}
                </span>
                <span class="message success" :class="{'active': apiResponse.success}">
                    {{ apiResponse.message }}
                </span>
                <md-button type="submit" class="md-primary md-raised" :disabled="sending">{{ buttonLabel }}</md-button>
            </md-card-actions>
        </md-card>

        <md-snackbar :md-active="apiResponse.success" md-position="left">{{ apiResponse.message }}</md-snackbar>
    </form>
</template>

<script>
    export default {
        props: [
            'heading',
            'token',
            'usernameLabel',
            'passwordLabel',
            'buttonLabel'
        ],
        data() {
            return {
                formData: {
                    token: this.token,
                    username: '',
                    password: ''
                },
                sending: false,
                apiResponse: {
                    success: null,
                    message: '',
                    errorField: '',
                    successHref: ''
                }
            }
        },
        computed: {
            showAccountError() {
                return this.apiResponse.success === false && this.apiResponse.errorField === 'account';
            },
            showGenericError() {
                return this.apiResponse.success === false && !this.apiResponse.errorField;
            }
        },
        methods: {
            onSubmit(event) {
                event.preventDefault();
                this.signIn();
            },
            signIn() {
                this.sending = true;
                phue.http.postForm(location.href, this.formData).then(response => {
                    this.apiResponse = response.data;
                    if (response.data.success) {
                        this.onSuccess();
                    } else {
                        this.sending = false;
                    }
                });
            },
            onSuccess() {
                // do a page redirect as the layout may change for logged-in users (and for Chrome to save passwords)
                let successUrl = phue.appBase + this.apiResponse.successHref.replace(/^\/+/, '');
                setTimeout(function () {
                    window.location.href = successUrl;
                }, 1500);
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '../../Application/scss/_variables.scss';

    .phue-login-form {
        @include single-form-view();
    }
</style>

