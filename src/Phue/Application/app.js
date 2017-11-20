import Vue from 'vue'
import axios from 'axios'

// define component bundles
Vue.component('phue-app',        () => import(/* webpackChunkName: "phue" */ './elements/phue-app.vue'));
Vue.component('phue-app-header', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-header.vue'));
Vue.component('phue-app-canvas', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-canvas.vue'));
Vue.component('phue-app-footer', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-footer.vue'));

new Vue({
    el: '#app-container',
    mounted: function () {
        // activate axios
        Vue.http = axios.create({
            baseURL: window.phue.appBase,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Partial': 1
            }
        });
    }
});
