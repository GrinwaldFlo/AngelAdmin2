<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Controller\PrefResult;
use App\Model\Entity\Field;
use App\Model\Entity\FieldType;
use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
use App\Model\Entity\Role;
use App\View\Helper\MyHelper;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * @uses \App\View\Helper\MyHelper
 */
class MyHelperTest extends TestCase
{
    private View $View;

    private MyHelper $My;

    protected function setUp(): void
    {
        parent::setUp();
        I18n::setLocale('en');
        $this->loadRoutes();
        $request = new ServerRequest(['params' => ['controller' => 'Members', 'action' => 'index', 'plugin' => null]]);
        // Links without controller are relative to the current request
        Router::setRequest($request);
        $this->View = new View($request);
        $this->My = new MyHelper($this->View);
    }

    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function captured(callable $callback): string
    {
        ob_start();
        $callback();

        return (string)ob_get_clean();
    }

    private function field(int $style, ?string $value, array $type = []): Field
    {
        return new Field([
            'field_type_id' => 7,
            'value' => $value,
            'field_type' => new FieldType($type + ['label' => 'My field', 'style' => $style, 'member_edit' => true, 'hidden' => false, 'mandatory' => false], ['guard' => false]),
        ], ['guard' => false]);
    }

    private function stream(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    public function testPresenceStateToHtml(): void
    {
        $this->assertSame('danger', $this->My->PresenceStateToHtml(Presence::ABSENT));
        $this->assertSame('success', $this->My->PresenceStateToHtml(Presence::PRESENT));
        $this->assertSame('primary', $this->My->PresenceStateToHtml(Presence::EXCUSED));
        $this->assertSame('warning', $this->My->PresenceStateToHtml(Presence::LATE));
        $this->assertSame('black', $this->My->PresenceStateToHtml(Presence::NONE));
    }

    public function testLanguagesOnlyEnabledOnes(): void
    {
        $this->assertSame([['French', 'fr'], ['Italian', 'it']], $this->My->Languages(['fr' => 1, 'de' => 0, 'it' => '1']));
        $this->assertSame([], $this->My->Languages([]));

        $links = $this->My->LanguageLinks(['fr' => 1, 'en' => 1]);
        $this->assertStringContainsString('href="/users/set-language/fr"', $links);
        $this->assertStringContainsString('>English</a>', $links);
    }

    public function testImageAndDataFromBlob(): void
    {
        $data = 'data:image/png;base64,' . base64_encode('png');

        $this->assertSame('<img src="data:image/png;base64,' . base64_encode('png') . '" />', $this->My->ImageFromBlob($this->stream($data)));
        $this->assertSame('png', $this->My->DataFromBlob($this->stream($data)));
        $this->assertSame('', $this->My->ImageFromBlob($this->stream('')));
        $this->assertSame('', $this->My->DataFromBlob($this->stream('')));
    }

    public function testInfoTable(): void
    {
        $html = $this->captured(fn () => $this->My->InfoTable(['A', 'B'], [[1, 2], [3, 4]]));

        $this->assertStringContainsString('<thead><tr><th>A</th><th>B</th></tr></thead>', $html);
        $this->assertSame(2, substr_count($html, '<tr><td>'));

        $this->assertStringNotContainsString('<thead>', $this->captured(fn () => $this->My->InfoTable([], [])));
    }

    public function testAdminButtons(): void
    {
        $html = $this->My->adminButtons(2);

        $this->assertSame(3, substr_count($html, '<a '));
        $this->assertSame(1, substr_count($html, ' pressed'));
        $this->assertMatchesRegularExpression('#class="[^"]*pressed"[^>]*>Locations<|href="/sites"[^>]*class="[^"]*pressed"#', $html);
    }

    public function testInputFieldsByStyle(): void
    {
        $member = new \App\Model\Entity\Member(['fields' => [
            $this->field(0, 'text'),
            $this->field(3, '12'),
            $this->field(5, '2026-01-01'),
            $this->field(6, 'nothing shown'),
            $this->field(7, 'M'),
        ]], ['guard' => false]);

        $html = $this->captured(fn () => $this->My->InputFields($member, true));

        $this->assertStringContainsString('type="text" name="field[7]"', $html);
        $this->assertStringContainsString('type="number" name="field[7]"', $html);
        $this->assertStringContainsString('type="date" name="field[7]"', $html);
        $this->assertStringContainsString('<option value="M" selected="selected">M</option>', $html);
        $this->assertStringNotContainsString('nothing shown', $html);
    }

    /**
     * Yes/No field: checked when the value is set
     */
    public function testInputFieldsCheckbox(): void
    {
        $checked = fn (?string $value) => str_contains(
            $this->captured(fn () => $this->My->InputFields(new \App\Model\Entity\Member(['fields' => [$this->field(4, $value)]], ['guard' => false]), true)),
            'checked="checked"'
        );

        $this->assertTrue($checked('1'));
        $this->assertFalse($checked('0'));
        $this->assertFalse($checked(''));
        $this->assertFalse($checked(null));
    }

    public function testInputFieldsVisibilityAndRequired(): void
    {
        $member = new \App\Model\Entity\Member(['fields' => [
            $this->field(0, 'hidden', ['hidden' => true, 'label' => 'Hidden']),
            $this->field(0, 'admin', ['member_edit' => false, 'label' => 'Admin only']),
            $this->field(0, 'x', ['mandatory' => true, 'label' => 'Mandatory']),
        ]], ['guard' => false]);

        $byMember = $this->captured(fn () => $this->My->InputFields($member, false));
        $this->assertStringNotContainsString('Hidden', $byMember);
        $this->assertStringNotContainsString('Admin only', $byMember);
        $this->assertStringContainsString('required="required"', $byMember);

        $byEditor = $this->captured(fn () => $this->My->InputFields($member, true));
        $this->assertStringNotContainsString('Hidden', $byEditor);
        $this->assertStringContainsString('Admin only', $byEditor);
        $this->assertStringNotContainsString('required="required"', $byEditor);
    }

    public function testRespGridAlternatesAndShowsMissing(): void
    {
        $html = $this->captured(function () {
            $this->My->respGrid('Name', 'Bob');
            $this->My->respGrid('Phone', '', true);
            $this->My->respGrid('Fax', '');
            $this->My->respGrid('Email', '', false, true);
        });

        $this->assertStringContainsString('<div class="col-12 col-md-7" >Bob</div>', $html);
        $this->assertStringContainsString('<div class="col-12 col-md-7 divTableColor missing" >MISSING</div>', $html);
        $this->assertStringNotContainsString('Fax', $html);
        // Empty optional field, shown on request
        $this->assertStringContainsString('>Email</div>', $html);
    }

    public function testFieldArraySkipsHidden(): void
    {
        $html = $this->captured(fn () => $this->assertSame('', $this->My->fieldArray([
            $this->field(0, 'shown', ['label' => 'Shown']),
            $this->field(0, 'secret', ['label' => 'Secret', 'hidden' => true]),
        ])));

        $this->assertStringContainsString('shown', $html);
        $this->assertStringNotContainsString('secret', $html);
    }

    public function testTagsAndEcho(): void
    {
        $this->assertSame('text', $this->My->tags('', 'text'));
        $this->assertSame('<b class="x" id="y" >text</b>', $this->My->tags('b', 'text', ['class' => 'x', 'id' => 'y']));
        $this->assertSame('<i >text</i>', $this->captured(fn () => $this->My->echo('i', 'text')));
        $this->assertSame([1 => 1, 2 => 2, 4 => 4], $this->My->multiPaymentList());
    }

    public function testShowEvent(): void
    {
        $meeting = new Meeting([
            'id' => 5, 'name' => 'Camp', 'meeting_date' => new DateTime('2026-11-10 18:00'), 'doodle' => true, 'url' => 'https://example.com', 'my' => null,
        ], ['guard' => false]);
        $config = ['dateEvent' => 'dd.MM.yyyy'];

        $html = $this->captured(fn () => $this->My->showEvent('<b>Events</b>', 'Comment', [$meeting], new Role(['MemberViewAll' => true]), $config));
        $this->assertStringContainsString('<h4 >Events</h4>', $html);
        $this->assertStringContainsString('href="/meetings/view/5"', $html);
        $this->assertStringContainsString('10.11.2026 - Camp', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('/meetings/join/5', $html);

        $meeting->my = new Presence(['state' => Presence::PRESENT]);
        $html = $this->captured(fn () => $this->My->showEvent('Events', '', [$meeting], new Role(['MemberViewAll' => false]), $config));
        $this->assertStringNotContainsString('/meetings/view/5', $html);
        $this->assertStringNotContainsString('/meetings/join/5', $html);
        $this->assertStringContainsString('Registered', $html);
    }

    public function testPhotoButtonUsesClasses(): void
    {
        $edit = $this->My->butPhotoAddEdit(true, 'addPhoto', 3);
        $add = $this->My->butPhotoAddEdit(false, 'addPhoto', 3);

        $this->assertStringContainsString('href="/members/add-photo/3"', $edit);
        $this->assertStringContainsString('photo-btn-edit', $edit);
        $this->assertStringContainsString('gg-pen', $edit);
        $this->assertStringContainsString('photo-btn-add', $add);
        $this->assertStringNotContainsString('style=', $edit . $add);
    }

    public function testIconAndSymbol(): void
    {
        $this->assertSame('<span class="material-icons big">edit</span>', $this->My->icon('edit', ['class' => 'big']));
        $this->assertSame('<span class="material-symbols">&lt;x&gt;</span>', $this->My->symbol('<x>'));
        $this->assertSame('<span class="material-symbols text-success">check</span>', $this->My->symbol('check', ['class' => 'text-success']));
    }

    public function testMemberFilterLinks(): void
    {
        $html = $this->My->memberFilterLinks(['action' => 'index', 2], 1, [1, 2, 3]);

        $this->assertSame(3, substr_count($html, '<a '));
        // Pressed filter links back to no filter
        $this->assertStringContainsString('href="/members/index/2/0" class="btn btn-primary btn-sm pressed"', $html);
        $this->assertStringContainsString('href="/members/index/2/3"', $html);
    }

    public function testMemberFilterLinksOfCoach(): void
    {
        $this->View->set('isScoped', true);

        $html = $this->My->memberFilterLinks(['action' => 'index', 2], 0, [1, 2, 3, 4, 5]);

        $this->assertSame(2, substr_count($html, '<a '));
        $this->assertStringContainsString('Membership not paid', $html);
        $this->assertStringContainsString('Non inscrit', $html);
    }

    public function testTeamLinksEscapedOnce(): void
    {
        $pref = (new \ReflectionClass(PrefResult::class))->newInstanceWithoutConstructor();
        $pref->teams = [2 => 'A & B', 3 => 'C'];
        $pref->teamId = 2;
        $pref->memberFilter = 1;

        $html = $this->My->teamLinks($pref, 'index');

        $this->assertStringContainsString('>A &amp; B</a>', $html);
        $this->assertStringContainsString('href="/members/index/0/1" class="btn btn-primary btn-sm pressed"', $html);
        $this->assertStringContainsString('href="/members/index/3/1"', $html);
    }
}
