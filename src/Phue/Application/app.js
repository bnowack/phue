import Vue from 'vue'

// enable vue-material components
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdCard').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdList').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdIcon').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdDivider').then(cmp => { Vue.use(cmp.default)});

// enable phue components
Vue.component('phue-app', () => import(/* webpackChunkName: "phue" */ './elements/phue-app.vue'));
Vue.component('phue-app-canvas', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-canvas.vue'));
Vue.component('phue-app-content', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-content.vue'));
Vue.component('phue-app-exception', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-exception.vue'));
Vue.component('phue-app-footer', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-footer.vue'));
Vue.component('phue-app-header', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-header.vue'));
Vue.component('phue-schema-changes', () => import(/* webpackChunkName: "phue" */ '../Schema/elements/phue-schema-changes.vue'));

// delay instance creation so that lazy-loaded components get a tad more time
Vue.nextTick(() => {
    window.phue.vue = new Vue({
        el: '#app-container'
});
