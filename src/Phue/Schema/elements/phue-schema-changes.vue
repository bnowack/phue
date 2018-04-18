<template>
    <md-card class="phue-schema-changes">
        <md-card-header>
            <div class="md-title">{{ heading }}</div>
        </md-card-header>

        <md-card-content>
            <md-list class="md-double-line">
                <template v-for="change in formattedChanges">
                    <md-list-item >
                        <md-icon :md-src="iconPath('done-icon.svg')" />
                        <div class="md-list-item-text">
                            <span>{{ change.name }}</span>
                            <span>(applied {{ change.appliedString }})</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
                </template>
            </md-list>
        </md-card-content>
        <slot></slot>
    </md-card>
</template>

<script>

    export default {
        props: [
            'heading',
            'changes'
        ],
        data() {
            return {
                moment: null
            }
        },
        created() {
            import(/* webpackChunkName: "moment" */ 'moment').then(moment => {
                this.moment = moment;
            });
        },
        computed: {
            /**
             * Add an `appliedString` property to each change object when moment becomes available
             */
            formattedChanges() {
                let moment = this.moment;
                return this.changes.map(function (change) {
                    change.appliedString = moment
                        ? moment(change.applied * 1000).fromNow()
                        : '...';

                    return change;
                });
            }
        },
        methods: {
            iconPath(fileName) {
                return phue.appBase + phue.phueSrc + 'Phue/Application/img/' + fileName;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .phue-schema-changes {

        .md-card-header {
            background-color: #f9f9f9;
        }

        .md-divider {
            background-color: rgba(0,0,0,.12);

            &:last-child {
                display: none;
            }
        }
    }
</style>
