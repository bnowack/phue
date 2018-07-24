import Vue from 'vue'

// activate router
import VueRouter from 'vue-router'
Vue.use(VueRouter);

// enable vue-material components
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdCard').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdList').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdIcon').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdDivider').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md" */'vue-material/dist/components/MdMenu').then(cmp => { Vue.use(cmp.default)});

import(/* webpackChunkName: "md-forms" */'vue-material/dist/components/MdButton').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md-forms" */'vue-material/dist/components/MdField').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md-forms" */'vue-material/dist/components/MdSnackbar').then(cmp => { Vue.use(cmp.default)});
import(/* webpackChunkName: "md-forms" */'vue-material/dist/components/MdProgress').then(cmp => { Vue.use(cmp.default)});

// enable phue components
Vue.component('phue-app', () => import(/* webpackChunkName: "phue" */ './elements/phue-app.vue'));
Vue.component('phue-app-canvas', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-canvas.vue'));
Vue.component('phue-app-content', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-content.vue'));
Vue.component('phue-app-exception', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-exception.vue'));
Vue.component('phue-app-footer', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-footer.vue'));
Vue.component('phue-app-header', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-header.vue'));
Vue.component('phue-app-sys-nav', () => import(/* webpackChunkName: "phue" */ './elements/phue-app-sys-nav.vue'));
Vue.component('phue-schema-changes', () => import(/* webpackChunkName: "phue" */ '../Schema/elements/phue-schema-changes.vue'));

Vue.component('phue-login-form', () => import(/* webpackChunkName: "security" */ '../Security/elements/phue-login-form.vue'));
Vue.component('phue-logout-link', () => import(/* webpackChunkName: "security" */ '../Security/elements/phue-logout-link.vue'));
Vue.component('phue-password-change-form', () => import(/* webpackChunkName: "security" */ '../Security/elements/phue-password-change-form.vue'));

// delay instance creation so that lazy-loaded components get a tad more time
setTimeout(() => {
    window.phue.vue = new Vue({
        el: '#app-container',
        router: new VueRouter({
            mode: 'history'
        })
    });
}, 250);
