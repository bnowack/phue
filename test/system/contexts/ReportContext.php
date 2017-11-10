<?php

namespace Test\System\Context;

use Behat\Behat\Context\Context;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Report context
 *
 */
class ReportContext extends CoreContext implements Context
{

    protected static $report = [];

    /**
     * @BeforeSuite
     */
    public static function initReport()
    {
        self::$report = [];
    }

    /**
     * Adds a report entry
     *
     * @AfterStep
     *
     * @param AfterStepScope $scope
     */
    public function onAfterStep(AfterStepScope $scope)
    {
        $feature = $scope->getFeature();
        $scenario = $this->currentScenario;
        $step = $scope->getStep();
        $resultCodeLabels = [
            TestResult::PASSED => 'passed',
            TestResult::FAILED => 'failed',
            TestResult::PENDING => 'pending',
            TestResult::SKIPPED => 'skipped',
            30 => 'undefined'
        ];

        $stepResult = $resultCodeLabels[$scope->getTestResult()->getResultCode()];

        $screenShot = null;
        if ($stepResult === 'failed') {
            $driver = $this->getSession()->getDriver();

            if (($driver instanceof Selenium2Driver) && ($screenShot = $driver->getScreenshot())) {
                $screenShot = 'data:image/png;base64,' . base64_encode($screenShot);
            }
        }

        self::$report[] = [
            'feature' => [
                'file' => $feature->getFile(),
                'title' => $feature->getTitle(),
                'description' => $feature->getDescription()
            ],
            'scenario' => [
                'title' => $scenario->getTitle(),
                'tags' => $scenario->getTags()
            ],
            'step' => [
                'keyword' => $step->getKeyword(),
                'text' => $step->getText(),
                'result' => $stepResult,
                'timestamp' => time(),
                'screenshot' => $screenShot
            ]
        ];
    }

    /**
     * @AfterSuite
     *
     * @param AfterSuiteScope $scope
     */
    public static function writeReport(AfterSuiteScope $scope)
    {
        $settings = json_decode(json_encode($scope->getSuite()->getSettings()));
        $fileName = $settings->report->fileName;

        // write JSON report
        $jsonPath = $settings->report->path . $fileName . '.json';
        echo "Writing report to '{$settings->report->path}$fileName.(json|html)'";
        file_put_contents($jsonPath, json_encode(self::$report, JSON_PRETTY_PRINT));

        // write HTML report
        $htmlPath = $settings->report->path . $fileName . '.html';
        $report = file_get_contents($settings->report->src . 'report.html');
        $report = str_replace('{{title}}', $settings->report->title, $report);
        $report = str_replace('[]', json_encode(self::$report), $report);
        file_put_contents($htmlPath, $report);

        // copy assets
        $assets = [
            'report.js',
            'report.css'
        ];

        foreach ($assets as $asset) {
            $srcPath = $settings->report->src .$asset;
            $targetPath = $settings->report->path .$asset;
            if (!file_exists($targetPath)) {
                copy($srcPath, $targetPath);
            }
        }
    }
}
