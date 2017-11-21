<template>
    <div class="phue-app-exception">
        <md-card>
            <md-card-header>
                <div class="md-title">{{ code }} {{ message }}</div>
            </md-card-header>

            <md-card-content>
                <slot></slot>
            </md-card-content>
        </md-card>
    </div>
</template>

<script>
    module.exports = {
        props: [
            'code',
            'message'
        ],
        mounted() {
            this.shortenPaths();
        },
        data() {
            return {

            }
        },
        methods: {
            /**
             * Moves long path labels into title and keep just the file name
             */
            shortenPaths() {
                this.$el.querySelectorAll('.file').forEach(function(element) {
                    element.setAttribute('title', element.textContent);
                    element.textContent = element.textContent.replace(/^.*\/([^\/]+)/, '$1');
                })
            }
        }
    }
</script>

<style lang="scss">/// not scoped because .trace is in slot, not in template
    @import '../scss/_variables.scss';

    .phue-app-exception {
        @include max-width-container();

        display: block;
        margin: 8px 0 16px 0;

        .md-card-header {
            background-color: #f9f9f9;
            padding: 2px 16px 6px;

            .md-title {
                font-size: 16px;
                color: $app-error-color;
            }
        }

        .md-card-content {
            ul.trace {
                line-height: 24px;
                .file {
                    border-bottom: 1px dotted #333;
                }
            }
        }
    }
</style>

