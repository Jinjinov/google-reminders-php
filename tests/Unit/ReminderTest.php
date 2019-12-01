<?php

namespace Jinjinov\GoogleReminders\Tests\Unit;

use Jinjinov\GoogleReminders\Reminder;

class ReminderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase constructor
     */
    public function testConstructor()
    {
        $reminder = new Reminder();
        $this->assertInstanceOf(Reminder::class, $reminder);
    }
}
