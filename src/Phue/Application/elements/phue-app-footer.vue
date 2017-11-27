<template>
    <footer>
        <div>
            powered by <a href="https://github.com/bnowack/phue">Phue</a>
            <span class="version">v{{ version }}</span>
        </div>
    </footer>
</template>

<script>
    export default {
        data: function () {
            return {
                version: 0
            }
        },

        mounted: function () {
            this.version = window.phue.appVersion;
        }
    }
</script>

<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    footer {
        display: block;
        width: 100%;
        position: fixed;
        bottom: 0;
        line-height: $app-footer-height;
        background: $app-footer-bg-color;
        color: #999;
        font-size: 14px;
        font-family: "Open Sans Semibold", sans-serif;
        text-align: right;
        margin-top: 20px;
    }

    footer > div {
        @include max-width-container();
    }

    a {
        text-decoration: none;
        color: #bbb !important;
    }

    .version {
        font-size: 0.9em;
    }
</style>
