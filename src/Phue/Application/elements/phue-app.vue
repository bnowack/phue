
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
             * Sets the app container's opacity so that it appears
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
