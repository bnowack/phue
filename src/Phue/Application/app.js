import Vue from 'vue'
import axios from 'axios'

// enable selected vue-material elements
import regeneratorRuntime from 'babel-regenerator-runtime'; // required for partial vue-material imports
import { MdCard } from 'vue-material/dist/components';

// define component bundles
Vue.component('phue-app',        () => import(/* webpackChunkName: "phue" */ './elements/phue-app.vue'));
Vue.component('phue-app-header', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-header.vue'));
Vue.component('phue-app-canvas', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-canvas.vue'));
Vue.component('phue-app-footer', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-footer.vue'));

new Vue({
    el: '#app-container',
    created() {
        Vue.use(MdCard);
    },
    mounted() {
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
