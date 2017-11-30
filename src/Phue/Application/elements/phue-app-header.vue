<template>
    <header class="phue-app-header">
        <div>
            <phue-app-sys-nav></phue-app-sys-nav>
            <router-link :to="base" class="home-link"></router-link>
        </div>
    </header>
</template>

<script>
    export default {
        data() {
            return {
                base: phue.appBase
            }
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

            .phue-app-sys-nav {
                position: absolute;
                right: 16px;
                height: $app-header-height;
            }
        }
    }
</style>
