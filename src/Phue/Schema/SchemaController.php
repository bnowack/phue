<?php

namespace Phue\Schema;

use Phue\Application\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phue Schema Controller
 *
 */
class SchemaController
{

    /**
     * Checks/Updates the schema an generates a list of applied changes
     *
     * @param Application $app
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response
     */
    public function showSchemaChanges(Application $app, $routeConfig)
    {
        $appliedChanges = $app->schema->checkSchema();

        // render changes via phue-schema-changes
        $routeConfig->contentTemplate = "Phue/Application/templates/element.html.twig";
        $routeConfig->element = 'phue-schema-changes';
        $routeConfig->elementData = [
            'heading' => $routeConfig->heading,
            'changes' => $appliedChanges
        ];
        return $app->render($routeConfig->pageTemplate, $routeConfig);
    }
}
