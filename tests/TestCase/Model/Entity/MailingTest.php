<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Mailing;
use App\Model\Entity\Member;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

class MailingTest extends TestCase
{
    private const URL = 'https://club.test/members/my-page/abc?a=1&b=2';

    private string $locale;

    protected function setUp(): void
    {
        parent::setUp();
        // Texts are checked in English, whatever locale a previous test left
        $this->locale = I18n::getLocale();
        I18n::setLocale('en_US');
    }

    protected function tearDown(): void
    {
        I18n::setLocale($this->locale);
        parent::tearDown();
    }

    private function member(): Member
    {
        return new Member(['first_name' => 'Tom & <Jerry>', 'last_name' => 'Smith']);
    }

    public function testRenderSubjectIsPlainText(): void
    {
        $this->assertSame('Hi Tom & <Jerry> ' . self::URL, Mailing::render('Hi {first_name} {my_page}', $this->member(), self::URL));
    }

    public function testRenderHtmlEscapesValues(): void
    {
        $html = Mailing::renderHtml('<b>{full_name}</b>', $this->member(), self::URL);

        $this->assertSame('<b>Tom &amp; &lt;Jerry&gt; Smith</b>', $html);
    }

    public function testRenderHtmlMyPageIsALink(): void
    {
        $html = Mailing::renderHtml('See {my_page}', $this->member(), self::URL);

        $this->assertSame('See <a href="https://club.test/members/my-page/abc?a=1&amp;b=2">My page</a>', $html);
    }

    public function testRenderHtmlMyPageInsideATagIsTheUrl(): void
    {
        $html = Mailing::renderHtml('<a href="{my_page}" title="{first_name}">Click</a> or {my_page}', $this->member(), self::URL);

        $this->assertSame(
            '<a href="https://club.test/members/my-page/abc?a=1&amp;b=2" title="Tom &amp; &lt;Jerry&gt;">Click</a>'
            . ' or <a href="https://club.test/members/my-page/abc?a=1&amp;b=2">My page</a>',
            $html
        );
    }

    public function testLineBreaksInPlainLines(): void
    {
        $this->assertSame("Hello<br>\n<br>\nBye<br>\nSee you", Mailing::lineBreaks("Hello\r\n\r\nBye\nSee you"));
    }

    public function testLineBreaksNotAddedNextToBlocks(): void
    {
        $html = "<p>Intro</p>\n\n<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>\nText <b>bold</b>\n<h2>Title</h2>\nEnd";

        $this->assertSame("<p>Intro</p>\n\n<ul>\n  <li>One</li>\n  <li>Two</li>\n</ul>\nText <b>bold</b>\n<h2>Title</h2>\nEnd", Mailing::lineBreaks($html));
    }

    public function testLineBreaksAfterInlineTag(): void
    {
        $this->assertSame("<b>One</b><br>\n<a href=\"x\">Two</a>", Mailing::lineBreaks("<b>One</b>\n<a href=\"x\">Two</a>"));
    }

    public function testRenderTextRemovesTagsAndKeepsLinks(): void
    {
        $content = "<p>Hello {first_name} &amp; co</p>\n<ul>\n<li>One</li>\n</ul>\nLine<br>Next\n"
            . "<a href=\"https://example.com\">Site</a> {my_page} <a href=\"{my_page}\">{my_page}</a>";

        $this->assertSame(
            "Hello Tom & <Jerry> & co\n\n- One\n\nLine\nNext\nSite (https://example.com) " . self::URL . ' ' . self::URL,
            Mailing::renderText($content, $this->member(), self::URL)
        );
    }

    public function testInvalidPlaceholders(): void
    {
        $this->assertSame([], Mailing::invalidPlaceholders('<p>{first_name} {last_name}</p> <a href="{my_page}">x</a>'));
        $this->assertSame(['{name}', '{my_page'], Mailing::invalidPlaceholders('{name} {my_page {full_name}'));
    }
}
