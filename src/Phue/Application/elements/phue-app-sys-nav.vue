<template>
    <div class="phue-app-sys-nav">
        <router-link class="login" :to="loginHref" v-if="isGuest">{{ account.loginLabel }}</router-link>
    </div>
</template>

<script>
    export default {
        data () {
            return {
                account: {}
            }
        },

        computed: {
            loginHref() {
                return this.account.loginHref || '/'
            },
            isGuest() {
                if (!this.account.roles) {
                    return true;
                }

                return (this.account.roles[0] === 'guest')
            }
        },

        mounted: function () {
            this.loadAccountData();
        },

        methods: {
            loadAccountData() {
                phue.http.get('/phue/account.json').then(response => {
                    this.account = response.data;
                });
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    .phue-app-sys-nav {

        .login {
            display: inline-block;
            line-height: $app-header-height;
            text-decoration: none;
            color: $app-text-color;

            &:hover {
                color: inherit;
            }
        }

        @include app-sys-nav();
    }
</style>

