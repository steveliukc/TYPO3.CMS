<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

/*
 * This file is part of the TYPO3 CMS project.
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

use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeTransportFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MailerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Mailer
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = $this->getMockBuilder(Mailer::class)
            ->setMethods(['emitPostInitializeMailerSignal'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function injectedSettingsAreNotReplacedByGlobalSettings()
    {
        $settings = ['transport' => 'mbox', 'transport_mbox_file' => '/path/to/file'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail'];
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
        $this->assertAttributeSame($settings, 'mailSettings', $this->subject);
    }

    /**
     * @test
     */
    public function globalSettingsAreUsedIfNoSettingsAreInjected()
    {
        $settings = ($GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail']);
        $this->subject->__construct();
        $this->assertAttributeSame($settings, 'mailSettings', $this->subject);
    }

    /**
     * Data provider for wrongConfigurationThrowsException
     *
     * @return array Data sets
     */
    public static function wrongConfigurationProvider()
    {
        return [
            'smtp but no host' => [['transport' => 'smtp']],
            'sendmail but no command' => [['transport' => 'sendmail']],
            'mbox but no file' => [['transport' => 'mbox']],
            'no instance of Swift_Transport' => [['transport' => ErrorPageController::class]]
        ];
    }

    /**
     * @test
     * @param $settings
     * @dataProvider wrongConfigurationProvider
     */
    public function wrongConfigurationThrowsException($settings)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1291068569);

        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function providingCorrectClassnameDoesNotThrowException()
    {
        $this->subject->injectMailSettings(['transport' => FakeTransportFixture::class]);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function noPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function emptyPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function givenPortSettingIsRespected()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(12345, $port);
    }

    /**
     * @test
     * @dataProvider getRealTransportReturnsNoSpoolTransportProvider
     */
    public function getRealTransportReturnsNoSpoolTransport($settings)
    {
        $this->subject->injectMailSettings($settings);
        $transport = $this->subject->getRealTransport();

        $this->assertInstanceOf(\Swift_Transport::class, $transport);
        $this->assertNotInstanceOf(\Swift_SpoolTransport::class, $transport);
    }

    /**
     * Data provider for getRealTransportReturnsNoSpoolTransport
     *
     * @return array Data sets
     */
    public static function getRealTransportReturnsNoSpoolTransportProvider()
    {
        return [
            'without spool' => [[
                'transport' => 'mail',
                'spool' => '',
            ]],
            'with spool' => [[
                'transport' => 'mail',
                'spool' => 'memory',
            ]],
        ];
    }
}
