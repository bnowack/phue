<?php

namespace Test\System\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;

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
}
