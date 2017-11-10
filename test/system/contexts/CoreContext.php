<?php

namespace Test\System\Context;

use Behat\Behat\Context\Context;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Testwork\Tester\Result\TestResult;
use SplFileInfo;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Core Behat hooks and generic helper functions
 */
class CoreContext extends RawMinkContext implements Context
{

    /** @var \Behat\Gherkin\Node\ScenarioNode */
    protected $currentScenario;

    /** @var object */
    protected $settings;

    /** @var object */
    protected $contexts;

    /**
     * Collects all field-label-to-field selectors from all contexts
     */
    public function getFieldSelectors()
    {
        $result = [];
        foreach ($this->contexts as $context) {
            if (!empty($context->fieldSelectors)) {
                $result = array_merge($result, $context->fieldSelectors);
            }
        }

        return $result;
    }

    /**
     * Returns the field selector for a given field label
     *
     * Labels have to be defined in the (sub-)context
     *
     * @param string $fieldLabel
     *
     * @return \Behat\Mink\Element\NodeElement|mixed|null
     */
    public function getFieldSelectorByLabel($fieldLabel)
    {
        $selectors = $this->getFieldSelectors();
        return isset($selectors[$fieldLabel])
            ? $selectors[$fieldLabel]
            : $fieldLabel;
    }

    /**
     * Returns the field node for a given label
     *
     * Labels have to be defined in the (sub-)context
     *
     * @param string $fieldLabel
     *
     * @return \Behat\Mink\Element\NodeElement|mixed|null
     *
     * @throws ElementNotFoundException
     */
    public function getFieldByLabel($fieldLabel)
    {
        $selector = $this->getFieldSelectorByLabel($fieldLabel);
        $field = $this->getSession()->getPage()->find('css', $selector);
        if (!$field) {// try id, name, etc.
            $field = $this->getSession()->getPage()->findField($fieldLabel);
        }

        if (!$field) {
            throw new ElementNotFoundException(
                $this->getSession()->getDriver(),
                'form field',
                'id|name|label|value|placeholder',
                $fieldLabel
            );
        }

        return $field;
    }

    /**
     * Returns the full path to a given fixture filename (first match in all fixture directories)
     *
     * @param $fileName
     *
     * @return mixed
     */
    public function getFullFixturePath($fileName, $assertFileExists = false)
    {
        $dir = new \RecursiveDirectoryIterator($this->settings->fixtures->dir);
        $filter = new \RecursiveCallbackFilterIterator($dir, function (SplFileInfo $file) use ($fileName) {
            // skip self and parent dir
            if (preg_match('/^\.+$/', $file->getFilename())) {
                return false;
            }

            // recurse
            if ($file->isDir()) {
                return true;
            }

            // file found
            if ($file->isFile() && $file->getFilename() === $fileName) {
                return true;
            }

            return false;
        });
        $iterator = new \RecursiveIteratorIterator($filter);
        foreach ($iterator as $info) {
            return $info->getPathname();// return first match
        }

        if ($assertFileExists) {
            throw new FileNotFoundException("Fixture '$fileName' could not be found");
        }

        return null;
    }

    public function isJson($value)
    {
        return !!preg_match('/^[\/\{\[]/', $value);
    }

    /**
     * Extracts custom settings (e.g. credentials) from behat.yml
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function extractSettings(BeforeScenarioScope $scope)
    {
        $this->settings = json_decode(json_encode($scope->getSuite()->getSettings()));
    }

    /**
     * Makes all contexts loaded via `behat.yml` available to all sub-contexts, e.g. for access to MinkContext
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function setUpContexts(BeforeScenarioScope $scope)
    {
        $this->contexts = (object)[];
        $environment = $scope->getEnvironment();/** @var InitializedContextEnvironment $environment*/
        foreach ($environment->getContextClasses() as $contextClass) {
            // convert `My\Namespace\MyContext` to `myContext`
            $shortName = lcfirst(preg_replace('/^.*\\\([^\\\]+)/', '\\1', $contextClass));
            $this->contexts->$shortName = $environment->getContext($contextClass);
        }
    }

    /**
     * Keeps track of the currently processed Scenario
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function setScenario(BeforeScenarioScope $scope)
    {
        $this->currentScenario = $scope->getScenario();
    }

    public function isBrowserTest()
    {
        $driver = $this->getSession()->getDriver();
        return ($driver instanceof Selenium2Driver);
    }

    /**
     * Wait for async calls to finish
     *
     * @AfterStep
     *
     * @param AfterStepScope $scope
     * @todo improve detection of finished navigation (not just `wait(1500)`)
     */
    public function waitAfterNavigationStep(AfterStepScope $scope)
    {
        // ignore hook in sub-contexts
        if (get_class($this) !== self::class) {
            return;
        }

        // ignore non-UI steps
        if (!$this->isBrowserTest()) {
            return;
        }

        $stepText = $scope->getStep()->getText();
        $isNavigationStep =
            (strpos($stepText, 'I go to') !== false) ||
            (strpos($stepText, 'I follow') !== false);

        if ($isNavigationStep) {
            //$this->getSession()->wait(500, "document.querySelector('html')");
            $this->getSession()->wait(1500);
        }
    }
}
