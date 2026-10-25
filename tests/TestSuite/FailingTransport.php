<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use Cake\Mailer\Message;
use Cake\Mailer\AbstractTransport;
use Cake\Network\Exception\SocketException;

/**
 * Mail transport refusing every email, like Gmail once the sending limit is reached
 */
class FailingTransport extends AbstractTransport
{
    public const ERROR = '550 Daily user sending limit exceeded';

    public function send(Message $message): array
    {
        throw new SocketException(self::ERROR);
    }
}
