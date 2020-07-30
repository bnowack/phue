<template>
    <a @click="signOut"><slot></slot></a>
</template>

<script>
    export default {
        data () {
            return {
            }
        },

        props: ['href', 'token'],

        methods: {
            signOut() {
                phue.http.postForm(this.href, {
                    token: this.token
                }).then(response => {
                    if (response.data.successHref) {
                        // do a page redirect as the layout may change for logged-out users
                        window.location.href = phue.appBase + response.data.successHref.replace(/^\/+/, '');
                    } else {
                        alert(response.data.message);
                    }
                });
            }
        }
    }
</script>

