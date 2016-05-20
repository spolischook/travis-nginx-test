<?php
namespace Oro\Component\Messaging\Tests\Router;

use Oro\Component\Messaging\Router\Recipient;
use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Message;

class RecipientTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = $this->getMock(Message::class);

        $recipient = new Recipient($this->getMock(Destination::class), $message);

        $this->assertSame($message, $recipient->getMessage());
    }

    public function testShouldAllowGetDestinationSetInConstructor()
    {
        $destination = $this->getMock(Destination::class);

        $recipient = new Recipient($destination, $this->getMock(Message::class));

        $this->assertSame($destination, $recipient->getDestination());
    }
}
