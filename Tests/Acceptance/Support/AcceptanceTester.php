<?php

declare(strict_types=1);

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;
    use FrameSteps;
    /**
     * Define custom actions here
     */

    /**
     * @see https://maslosoft.com/blog/2018/04/04/codeception-acceptance-check-if-element-is-visible/
     */
    public function haveVisible($element): bool
    {
        $I = $this;
        $value = false;
        $I->executeInSelenium(function(\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver)use($element, &$value)
        {
            try
            {
                $element = $webDriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($element));
                $value = $element instanceof \Facebook\WebDriver\Remote\RemoteWebElement;
            }
            catch (Exception $e)
            {
                // Swallow exception silently
            }
        });
        return $value;
    }
}
