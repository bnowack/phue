<template>
    <form @submit="onSubmit" method="POST" action="">
        <md-card class="phue-password-change-form">
            <md-card-header>
                <div class="md-title">{{ heading }}</div>
            </md-card-header>

            <md-card-content>

                <md-field :class="{'md-invalid': showPasswordError}">
                    <label for="oldPassword">{{ oldPasswordLabel }}</label>
                    <md-input type="password" name="oldPassword" id="oldPassword" v-model="formData.oldPassword" :disabled="sending" />
                    <span class="md-error">{{ apiResponse.message }}</span>
                </md-field>

                <md-field :class="{'md-invalid': showPasswordError}">
                    <label for="newPassword">{{ newPasswordLabel }}</label>
                    <md-input type="password" name="newPassword" id="newPassword" v-model="formData.newPassword" :disabled="sending" />
                    <span class="md-error">{{ apiResponse.message }}</span>
                </md-field>

                <md-field :class="{'md-invalid': showPasswordConfirmationError}">
                    <label for="passwordConfirmation">{{ passwordConfirmationLabel }}</label>
                    <md-input type="password" name="passwordConfirmation" id="passwordConfirmation" v-model="formData.passwordConfirmation" :disabled="sending" />
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
            'oldPasswordLabel',
            'newPasswordLabel',
            'passwordConfirmationLabel',
            'buttonLabel'
        ],
        data() {
            return {
                formData: {
                    token: this.token,
                    oldPassword: '',
                    newPassword: '',
                    passwordConfirmation: ''
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
            showPasswordError() {
                return this.apiResponse.success === false && this.apiResponse.errorField === 'password';
            },
            showPasswordConfirmationError() {
                return this.apiResponse.success === false && this.apiResponse.errorField === 'passwordConfirmation';
            },
            showGenericError() {
                return this.apiResponse.success === false && !this.apiResponse.errorField;
            }
        },
        methods: {
            onSubmit(event) {
                event.preventDefault();
                this.changePassword();
            },
            changePassword() {
                this.sending = true;
                phue.http.postForm(location.href, this.formData).then(response => {
                    this.apiResponse = response.data;
                    this.sending = false;
                    if (response.data.success) {
                        this.formData.oldPassword = this.formData.newPassword = this.formData.passwordConfirmation = '';
                    }
                });
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '../../Application/scss/_variables.scss';

    .phue-password-change-form {
        @include single-form-view();
    }
</style>
