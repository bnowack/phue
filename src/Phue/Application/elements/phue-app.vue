
<template>
    <div class="phue-app">
        <slot></slot>
    </div>
</template>

<script src="./phue-app.js"></script>

<style src="../scss/app-theme.scss" lang="scss"></style>
<style lang="scss">
    @import '../scss/_variables.scss';

    .phue-app {
        box-sizing: border-box;
        min-height: 100vh;
        padding-bottom: $app-footer-height;
    }
</style>
