<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Client;
use Symfony\Component\Emoji\EmojiTransliterator;

class SlackComponent extends Component
{
    private $userCache = [];
    private $cacheFile = null;
    private $cacheTtlDays = 10;

    public function __construct($collection, $config = [])
    {
        parent::__construct($collection, $config);

        $this->cacheFile = CACHE . 'slack_users.json';
        if (file_exists($this->cacheFile)) {
            $fileMTime = filemtime($this->cacheFile);
            if ($fileMTime !== false && (time() - $fileMTime) > ($this->cacheTtlDays * 86400)) {
                @unlink($this->cacheFile);
                $this->userCache = [];
            } else {
                $this->userCache = json_decode(file_get_contents($this->cacheFile), true) ?? [];
            }
        } else {
            $this->userCache = [];
        }
    }

    public function getSlack(): array
    {
        // Cache file for Slack messages
        $slackCacheFile = CACHE . 'slack_messages.json';
        $cacheTtlSeconds = 1800; // 30 minutes

        // Try to load from cache
        if (file_exists($slackCacheFile)) {
            $fileMTime = filemtime($slackCacheFile);
            if ($fileMTime !== false && (time() - $fileMTime) < $cacheTtlSeconds) {
                $latestSlackMessages = json_decode(file_get_contents($slackCacheFile), true) ?? [];
                return $latestSlackMessages;
            }
        }

        $slackToken = Configure::read('Slack.token');
        $channelId = Configure::read('Slack.channelId');

        $http = new Client();
        $response = $http->get('https://slack.com/api/conversations.history', [
            'channel' => $channelId,
            'limit' => 3
        ], [
            'headers' => [
                'Authorization' => 'Bearer ' . $slackToken,
            ]
        ]);

        $latestSlackMessages = [];
        if ($response->isOk()) {
            $data = $response->getJson();
            if (!empty($data['messages'])) {
                foreach ($data['messages'] as $message) {
                    if (!empty($message['text'])) {
                        $date = isset($message['ts']) ? date('d.m.Y H:i:s', (int) $message['ts']) : '';
                        $sender = isset($message['user']) ? $message['user'] : 'unknown';

                        $text = $this->slackToNiceMessage($message['text']);
                        $latestSlackMessages[] = "{$date} | {$this->getSlackUser($sender)}: {$text}";
                    }
                }
            }
        } else {
            $latestSlackMessages[] = 'Error fetching Slack messages: ' . $response->getReasonPhrase();
        }

        // Save messages to local cache file
        file_put_contents($slackCacheFile, json_encode($latestSlackMessages));
        return $latestSlackMessages;
    }

    public function slackToNiceMessage($message): string
    {
        $transliterator = EmojiTransliterator::create('github-emoji');
        $message = $transliterator->transliterate($message);
        $message = str_replace("\n", "<br>", $message);

        $message = preg_replace_callback(
            '/<([^|>]+)\|([^>]+)>/',
            function ($matches) {
                $url = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
                $text = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
                return "<a href=\"{$url}\" target=\"_blank\">{$text}</a>";
            },
            $message
        );

        $message = preg_replace_callback(
            '/<@([A-Z0-9]+)>/',
            function ($matches) {
                return '@' . $this->getSlackUser($matches[1]);
            },
            $message
        );
        return $message;
    }

    public function getSlackUser(string $userId): string
    {
        if (isset($this->userCache[$userId])) {
            return $this->userCache[$userId];
        }

        $slackToken = Configure::read('Slack.token');
        $http = new Client();
        $userResponse = $http->get('https://slack.com/api/users.info', [
            'user' => $userId
        ], [
            'headers' => [
                'Authorization' => 'Bearer ' . $slackToken,
            ]
        ]);
        if ($userResponse->isOk()) {
            $userData = $userResponse->getJson();
            if (!empty($userData['user']['real_name'])) {
                $realName = $userData['user']['real_name'];
                $this->userCache[$userId] = $realName;
                file_put_contents($this->cacheFile, json_encode($this->userCache));
                return $realName;
            } else {
                return $userResponse->getStringBody();
            }
        } else {
            return $userResponse->getReasonPhrase();
        }
    }
}