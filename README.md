# Phue

Phue is a PHP micro-framework for applications driven by Vue.js.

It uses
* [Silex](http://silex.sensiolabs.org/) for server-side code (routing, configuration, security, base templates, ...)
* [Vue.js](https://vuejs.org/) for client-side code

## Principles

* Routes (and their Silex handlers) are defined in a central config
* UI intelligence and app logic is defined in Vue components
* Static content is defined in Vue components or (ideally) in twig templates
* A basic SEO-friendly page template is defined in Silex
    * title
    * header
    * footer
    * canvas
    * content
        * static content: from a view template
        * dynamic content: from a handler query, converted to bot-friendly markup (e.g. RDFa)
* Silex can return the page template without surrounding layout markup
    * used by client code that loads a view dynamically (via ajax)
    * ?partials=true
    * response contains only meta data, import-links and content partials
    * client code transitions to new view and updates nav, title, partials, etc. while keeping the page layout
* Client code does not know about routes (unless an element has element-level sub-routes)
    * changed routes trigger a server call and the view gets refreshed (planned: views and partials can be flagged as static)

## Development

### phpspec dev

 * `composer run spec CLASSNAME desc|run`