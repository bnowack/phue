<template>
    <header class="phue-app-header">
        <div>
            <router-link :to="base"></router-link>
        </div>
    </header>
</template>

<script>
    import Vue from 'vue'

    export default {
        data() {
            return {
                base: '/'
            }
        },

        created() {
            this.base = window.phue.appBase;
        }
    }
</script>

<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    .phue-app-header {
        display: block;
        height: $app-header-height;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);

        > div {
            @include max-width-container();
            position: relative;
            height: $app-header-height;

            a {
                display: inline-block;
                height: $app-header-height;
                width: 40px;
                background: transparent url("../img/favicon.png") center no-repeat;
                background-size: 32px 32px;
            }

    }
</style>

