<template>
    <div class="phue-app-sys-nav">
        <router-link class="login" :to="loginHref" v-if="isGuest">{{ account.loginLabel }}</router-link>
        <span class="sys-nav-menu"></span>
    </div>
</template>

<script>
    import Vue from 'vue';

    export default {
        data () {
            return {
                account: {}
            }
        },

        computed: {
            loginHref() {
                let currentPath = location.pathname.replace(new RegExp("^" + phue.appBase), '/');
                return (this.account.loginHref || '/') + '?r=' + currentPath + location.search;
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
                    this.renderMenu();
                });
            },
            renderMenu() {
                if (this.isGuest) {
                    return;
                }

                let Component = Vue.extend({
                    template: `
                        <md-menu md-size="medium" md-align-trigger md-direction="bottom-end">
                            <a class="menu-toggle" md-menu-trigger>${this.account.username}</a>
                            <md-menu-content class="sys-nav-menu-content">
                                ${this.account.sysNavMenu}
                            </md-menu-content>
                        </md-menu>
                    `
                });
                // inject router
                Component.options.router = this.$router;
                // render menu
                let component = new Component();
                component.$mount(this.$el.querySelector('.sys-nav-menu'));
            }
        }
    }
</script>

<style lang="scss">
    @import '../scss/_variables.scss';

    .phue-app-sys-nav {
        .login {
            display: inline-block;
            line-height: $app-header-height;
            text-decoration: none;
            color: $app-text-color;
            padding: 0 8px;

            &:hover {
                color: inherit;
                opacity: 0.8;
            }
        }

        .md-menu {
            .menu-toggle {
                display: inline-block;
                line-height: $app-header-height;
                text-decoration: none;
                color: $app-text-color;
                padding: 0 8px;

                &:after {
                    content: "";
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    background: transparent url("../img/expand.png") right bottom no-repeat;
                }

                &:hover {
                    color: inherit;
                    background-color: #f9f9f9;
                    text-decoration: none;
                }
            }
        }

        @include app-sys-nav();
    }
</style>
