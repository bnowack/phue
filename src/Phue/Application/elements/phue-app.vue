
<template>
    <div class="phue-app">
        <slot></slot>
    </div>
</template>

<script>
    import Vue from 'vue'
    import axios from 'axios'

    export default {
        data() {
            return {
            }
        },

        created() {
        },

        mounted() {
            this.extendEnvironment();
            this.activateAxios();
            this.activateRoutes();
            this.fadeIn();
        },

        methods: {
            /**
             * Adds forEach method to NodeLists and HTMLCollections (lacks in PhantomJS)
             */
            extendEnvironment() {
                NodeList.prototype.forEach = Array.prototype.forEach;
                HTMLCollection.prototype.forEach = Array.prototype.forEach;
            },

            /**
             * Activates axios for API calls
             */
            activateAxios() {
                Vue.http = axios.create({
                    baseURL: window.phue.appBase,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Content-Only': 1
                    }
                });
            },

            /**
             * Catches any route changes
             */
            activateRoutes() {
                let router = window.phue.vue.$options.router;
                router.afterEach(this.onRouteChange);
            },

            /**
             * Fetches the view associated with a changed route
             */
            onRouteChange(to) {
                // fade out content node
                let $oldContent = document.querySelector('.phue-app-content');
                $oldContent.classList.add('outdated');
                // load new content
                Vue.http.get(to.fullPath).then(this.renderContent);
            },

            /**
             * Re-renders the content area with given response
             */
            renderContent(response) {
                // update title
                let $title = document.querySelector('title');
                let title = response.headers['x-page-title'] || $title.innerText;
                let titleSuffix = $title.getAttribute('data-suffix') || '';
                $title.innerText = `${title}${titleSuffix}`;

                // replace content area with dynamic component
                let Component = Vue.extend({
                    template: `
                        <transition name="fade">
                            <phue-app-content v-if="ready">${response.data}</phue-app-content>
                        </transition>
                    `,
                    data() {
                        return {
                            ready: false
                        }
                    }
                });
                let component = new Component();
                component.$mount(this.$el.querySelector('.phue-app-content'));
                component.ready = true;// kick off transition
            },

            /**
             * Sets the app container's opacity so that it appears on initial page load
             */
            fadeIn() {
                document.querySelector('#app-container').style.opacity = 1;
            }
        }
    };
</script>

<style src="../scss/app-theme.scss" lang="scss"></style>
<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    .phue-app {
        display: block;
        background-color: $app-bg-color;

        min-height: 100vh;
        padding-bottom: $app-footer-height;
        box-sizing: border-box;
    }
</style>
