
<template>
    <div class="phue-app">
        <slot></slot>
    </div>
</template>

<script>
    export default {
        data: function () {
            return {
            }
        },

        created() {
        },

        mounted() {
            this.extendEnvironment()
            this.fadeIn();
        },

        methods: {
            extendEnvironment() {
                // add forEach method to NodeLists and HTMLCollections (lacks in PhantomJS)
                NodeList.prototype.forEach = Array.prototype.forEach;
                HTMLCollection.prototype.forEach = Array.prototype.forEach;
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
