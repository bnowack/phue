<template>
    <section class="phue-app-content">
        <slot></slot>
    </section>
</template>

<script>
    module.exports = {
        data() {
            return {
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import '../scss/_variables.scss';

    .phue-app-content{
        transition: opacity 0.5s;

        &.outdated {
            opacity: 0
        }

        @include app-content();
    }
</style>

