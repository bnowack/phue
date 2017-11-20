<template>
    <header class="phue-app-header">
        <div>
            <a :href="base"></a>
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

    header {
        display: block;
        height: 40px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }

    header > div {
        @include max-width-container();
        position: relative;
    }

    a {
        display: inline-block;
        height: 40px;
        width: 40px;
        background: transparent url("../img/favicon.png") center no-repeat;
        background-size: 32px 32px;
    }
</style>

