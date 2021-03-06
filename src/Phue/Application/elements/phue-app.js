import Vue from 'vue'
import axios from 'axios'
import qs from 'qs'

export default {
    data() {
        return {
            contentComponent: null
        }
    },

    created() {
    },

    mounted() {
        this.extendEnvironment();
        this.activateAxios();
        this.activateRoutes();
        this.fadeIn();
    },

    methods: {
        /**
         * Adds forEach method to NodeLists and HTMLCollections (lacks in PhantomJS)
         */
        extendEnvironment() {
            NodeList.prototype.forEach = Array.prototype.forEach;
            HTMLCollection.prototype.forEach = Array.prototype.forEach;
        },

        /**
         * Activates axios for API calls
         */
        activateAxios() {
            phue.http = axios.create({
                baseURL: phue.appBase,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Content-Only': 1
                }
            });
            phue.http.postForm = function(url, data) {
                return window.phue.http.post(
                    url,
                    qs.stringify(data),
                    {
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    }
                )
            }
        },

        /**
         * Catches any route changes
         */
        activateRoutes() {
            this.$router.afterEach(this.onRouteChange);
        },

        /**
         * Fetches the view associated with a changed route
         */
        onRouteChange(to) {
            // fade out content node
            let $oldContent = document.querySelector('.phue-app-content');
            $oldContent.classList.add('outdated');
            // load new content
            let href = to.fullPath;
            href += href.indexOf('?') === -1
                ? '?content-only=1'
                : '&content-only=1';
            phue.http.get(href)
                .then(this.renderContent)
                .catch((error) => {
                    if (error && error.response) {
                        this.renderContent(error.response);
                    } else {
                        throw 'An error occurred';
                    }
                }
            );
        },

        /**
         * Re-renders the content area with given response
         */
        renderContent(response) {
            // update title
            let $title = document.querySelector('title');
            let title = response.headers['x-page-title'] || $title.innerText;
            let titleSuffix = $title.getAttribute('data-suffix') || '';
            $title.innerText = `${title.replace(titleSuffix, '')}${titleSuffix}`;

            // update appView
            phue.appView = response.headers['x-app-view'];

            // destroy old component
            if (this.contentComponent) {
                this.contentComponent.$destroy();
            }

            // replace content area with dynamic component
            let Component = Vue.extend({
                template: `
                    <transition name="fade">
                        <phue-app-content v-if="ready">${response.data}</phue-app-content>
                    </transition>
                `,
                data() {
                    return {
                        ready: false
                    }
                }
            });
            // inject router
            Component.options.router = this.$router;
            // inject vuex
            if (this.$store) {
                Component.options.store = this.$store;
            }

            // instantiate component
            this.contentComponent = new Component();
            this.contentComponent.$mount(this.$el.querySelector('.phue-app-content'));
            this.contentComponent.ready = true;// kick off transition
        },

        /**
         * Sets the app container's opacity so that it appears on initial page load
         */
        fadeIn() {
            document.querySelector('#app-container').style.opacity = 1;
        }
    }
};
