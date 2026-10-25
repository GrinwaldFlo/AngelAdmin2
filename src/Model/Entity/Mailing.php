<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Mailing Entity: one batch of emails sent to teams (the log line of the batch)
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $teams names of the teams that received the mailing
 * @property string $team_id_list ids of the teams that received the mailing, comma separated
 * @property int $status
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \Cake\I18n\DateTime|null $sentDate when the last pending email has been sent
 *
 * @property \App\Model\Entity\MailingItem[] $mailing_items
 */
class Mailing extends Entity
{
    public const IN_PROGRESS = 0;
    public const DONE = 1;

    /**
     * Placeholders available in the subject and the content, replaced for each recipient
     * @return array [placeholder => label]
     */
    public static function placeholders(): array
    {
        return [
            '{first_name}' => __('First name'),
            '{last_name}' => __('Last name'),
            '{full_name}' => __('Full name'),
            '{my_page}' => __('Link to the personal page'),
        ];
    }

    // Tags starting or ending a block: no line break is added next to them, they already break the line
    private const BLOCK_TAG = '<\/?(?:p|div|br|hr|ul|ol|li|h[1-6]|table|thead|tbody|tfoot|tr|td|th|blockquote|pre)\b[^>]*>';

    /**
     * Values of the placeholders for a member, as plain text
     * @param string $myPageUrl URL of the personal page of the member
     * @return array [placeholder => value]
     */
    private static function values(Member $member, string $myPageUrl): array
    {
        return [
            '{first_name}' => (string)$member->first_name,
            '{last_name}' => (string)$member->last_name,
            '{full_name}' => $member->FullName,
            '{my_page}' => $myPageUrl,
        ];
    }

    /**
     * Replace the placeholders of a plain text (the subject) with the values of a member
     * @param string $myPageUrl URL of the personal page of the member
     */
    public static function render(string $text, Member $member, string $myPageUrl): string
    {
        return strtr($text, self::values($member, $myPageUrl));
    }

    /**
     * HTML version of a content: the values are escaped, {my_page} is a "My page" link
     * (only the URL inside a tag, e.g. <a href="{my_page}">) and the line returns are kept
     * @param string $myPageUrl URL of the personal page of the member
     */
    public static function renderHtml(string $content, Member $member, string $myPageUrl): string
    {
        $values = array_map('h', self::values($member, $myPageUrl));
        $html = preg_replace_callback('/<[^>]*>/', fn ($tag) => strtr($tag[0], $values), $content);
        $values['{my_page}'] = '<a href="' . h($myPageUrl) . '">' . h(__('My page')) . '</a>';

        return self::lineBreaks(strtr($html, $values));
    }

    /**
     * Plain text version of a content, for the email clients not showing HTML
     * @param string $myPageUrl URL of the personal page of the member
     */
    public static function renderText(string $content, Member $member, string $myPageUrl): string
    {
        // Keep the address of the links: <a href="url">text</a> => text (url)
        $text = preg_replace_callback('/<a\b[^>]*href\s*=\s*["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', function ($link) {
            $label = trim(strip_tags($link[2]));

            return $label === '' || $label === $link[1] ? $link[1] : $label . ' (' . $link[1] . ')';
        }, str_replace("\r\n", "\n", $content));
        $text = preg_replace(['/<br\s*\/?>\n?/i', '/<li\b[^>]*>/i'], ["\n", '- '], $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // The values are put once the tags are removed, so a value looking like a tag is kept
        $text = strtr($text, self::values($member, $myPageUrl));

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Turn the line returns of an HTML content into <br>, except next to block tags (paragraphs, lists...)
     */
    public static function lineBreaks(string $html): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $html));
        $last = count($lines) - 1;
        foreach ($lines as $i => $line) {
            $endsBlock = preg_match('/' . self::BLOCK_TAG . '\s*$/i', $line);
            $nextStartsBlock = $i < $last && preg_match('/^\s*' . self::BLOCK_TAG . '/i', $lines[$i + 1]);
            if ($i < $last && !$endsBlock && !$nextStartsBlock) {
                $lines[$i] .= '<br>';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Placeholders of a text that would not be replaced: unknown names and unbalanced braces
     * @return array the wrong placeholders as written in the text
     */
    public static function invalidPlaceholders(string $text): array
    {
        preg_match_all('/\{[^{}]*\}/u', $text, $matches);
        $invalid = array_diff($matches[0], array_keys(self::placeholders()));

        // A brace left once the placeholders are removed is a placeholder not opened or not closed
        preg_match_all('/\S*[{}]\S*/u', preg_replace('/\{[^{}]*\}/u', ' ', $text), $stray);

        return array_values(array_unique(array_merge($invalid, $stray[0])));
    }

    public function isDone(): bool
    {
        return $this->status === self::DONE;
    }

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'title' => true,
        'content' => true,
        'teams' => true,
        'team_id_list' => true,
        'status' => true,
        'sentDate' => true,
        'mailing_items' => true,
    ];
}
