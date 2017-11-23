import Vue from 'vue'

// enable selected vue-material elements
import { MdCard } from 'vue-material/dist/components';

// define component bundles
Vue.component('phue-app', () => import(/* webpackChunkName: "phue" */ './elements/phue-app.vue'));
Vue.component('phue-app-exception', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-exception.vue'));
Vue.component('phue-app-header', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-header.vue'));
Vue.component('phue-app-canvas', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-canvas.vue'));
Vue.component('phue-app-content', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-content.vue'));
Vue.component('phue-app-footer', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-footer.vue'));

new Vue({
    el: '#app-container',
    created() {
        Vue.use(MdCard);
    }
});
