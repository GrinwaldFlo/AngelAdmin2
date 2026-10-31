<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\SlackComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Http\Client\Response;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;

/**
 * Slack is never called: the http client is mocked, the cache files of the app are kept aside
 *
 * @uses \App\Controller\Component\SlackComponent
 */
class SlackComponentTest extends TestCase
{
    use HttpClientTrait;

    private const CACHE_FILES = ['slack_users.json', 'slack_messages.json'];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Slack.channelId', 'C1');
        foreach (self::CACHE_FILES as $file) {
            if (file_exists(CACHE . $file)) {
                rename(CACHE . $file, CACHE . $file . '.bak');
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (self::CACHE_FILES as $file) {
            @unlink(CACHE . $file);
            if (file_exists(CACHE . $file . '.bak')) {
                rename(CACHE . $file . '.bak', CACHE . $file);
            }
        }
        parent::tearDown();
    }

    private function slack(): SlackComponent
    {
        return new SlackComponent(new ComponentRegistry(new Controller(new ServerRequest())));
    }

    private function json(array $data, int $status = 200): Response
    {
        return $this->newClientResponse($status, ['Content-Type: application/json'], json_encode($data));
    }

    private function mockUser(string $id, string $name): void
    {
        $this->mockClientGet('https://slack.com/api/users.info?user=' . $id, $this->json(['ok' => true, 'user' => ['real_name' => $name]]));
    }

    public function testGetSlackUserIsCached(): void
    {
        $this->mockUser('U1', 'Ana Muster');

        $this->assertSame('Ana Muster', $this->slack()->getSlackUser('U1'));
        $this->assertSame(['U1' => 'Ana Muster'], json_decode(file_get_contents(CACHE . 'slack_users.json'), true));

        // A new component reads the cache file, without calling Slack
        $this->cleanupMockResponses();
        $this->assertSame('Ana Muster', $this->slack()->getSlackUser('U1'));
    }

    public function testOldUserCacheIsIgnored(): void
    {
        file_put_contents(CACHE . 'slack_users.json', json_encode(['U1' => 'Old name']));
        touch(CACHE . 'slack_users.json', time() - 11 * 86400);
        $this->mockUser('U1', 'New name');

        $this->assertSame('New name', $this->slack()->getSlackUser('U1'));
    }

    /**
     * The name is shown in the messages: never the raw answer of Slack
     */
    public function testUnknownUserShowsItsId(): void
    {
        $this->mockClientGet('https://slack.com/api/users.info?user=U2', $this->json(['ok' => false, 'error' => 'user_not_found']));
        $this->mockClientGet('https://slack.com/api/users.info?user=U3', $this->json([], 500));

        $this->assertSame('U2', $this->slack()->getSlackUser('U2'));
        $this->assertSame('U3', $this->slack()->getSlackUser('U3'));
        $this->assertFileDoesNotExist(CACHE . 'slack_users.json');
    }

    public function testSlackToNiceMessage(): void
    {
        $this->mockUser('U1', 'Ana');

        $message = $this->slack()->slackToNiceMessage("Hello <@U1> :smile:\nSee <https://example.com?a=1&b=2|the \"site\">");

        $this->assertSame('Hello @Ana 😄<br>See <a href="https://example.com?a=1&amp;b=2" target="_blank">the &quot;site&quot;</a>', $message);
    }

    public function testGetSlackFormatsAndCachesMessages(): void
    {
        $this->mockClientGet('https://slack.com/api/conversations.history?channel=C1&limit=3', $this->json(['ok' => true, 'messages' => [
            ['text' => 'First', 'user' => 'U1', 'ts' => '1767225600.000100'],
            ['text' => '', 'user' => 'U1'],
            ['text' => 'No user'],
        ]]));
        $this->mockUser('U1', 'Ana');
        $this->mockClientGet('https://slack.com/api/users.info?user=unknown', $this->json(['ok' => false]));

        $messages = $this->slack()->getSlack();

        $this->assertSame([date('d.m.Y H:i:s', 1767225600) . ' | Ana: First', ' | unknown: No user'], $messages);
        $this->assertSame($messages, json_decode(file_get_contents(CACHE . 'slack_messages.json'), true));
    }

    public function testGetSlackUsesTheRecentCache(): void
    {
        file_put_contents(CACHE . 'slack_messages.json', json_encode(['cached']));

        $this->assertSame(['cached'], $this->slack()->getSlack());
    }

    public function testGetSlackError(): void
    {
        $this->mockClientGet('https://slack.com/api/conversations.history?channel=C1&limit=3', new Response(['HTTP/1.1 503 Service Unavailable'], ''));

        $this->assertSame(['Error fetching Slack messages: Service Unavailable'], $this->slack()->getSlack());
    }
}
