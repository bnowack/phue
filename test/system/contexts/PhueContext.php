<?php

namespace Test\System\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Phue step definitions
 */
class PhueContext extends CoreContext implements Context
{

    /**
     * @Given I am not logged in
     * @When I sign out
     * @When I log out
     */
    public function logOut()
    {
        $this->getSession()->reset();
    }

    /**
     * @Then the response should be valid JSON
     */
    public function assertResponseIsJson()
    {
        $content = $this->getSession()->getPage()->getContent();
        Assert::assertJson($content);
    }

    /**
     * @When I click on the header logo
     */
    public function clickOnTheHeaderLogo()
    {
        $minkContext = $this->contexts->minkContext;/** @var MinkContext $minkContext */

        $linkSelector = '.phue-app-header a.home-link';

        $this->getSession()->wait(2000, "document.querySelector('$linkSelector')");

        $minkContext->getSession()->getPage()->find('css', $linkSelector)->click();
    }
}
