<template>
    <section class="phue-app-canvas">
        <slot></slot>
    </section>
</template>

<script>

    module.exports = {
        data: function () {
            return {

            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    .phue-app-canvas {
        @include max-width-container();
    }
</style>

