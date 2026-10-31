<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\AppController;
use App\Controller\Component\SubventionComponent;
use App\Controller\PrefResult;
use App\Model\Entity\Content;
use App\Test\TestSuite\TestDataTrait;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use ZipArchive;

/**
 * Members: 100 Lausanne (10 years), 101 Renens (30 years), 102 Lausanne (no birth date), 103 site 2, 104 inactive
 *
 * @uses \App\Controller\Component\SubventionComponent
 */
class SubventionComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents',
    ];

    private AppController $Controller;

    private SubventionComponent $Subvention;

    private array $zips = [];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        // The PNG logo needs the GD extension, not always installed with the CLI
        Configure::write('App.logo', 'cheerPlaceholder.jpg');
        $this->getTableLocator()->get('Sites')->insertQuery()
            ->insert(['id', 'city', 'feeMax', 'sender_email', 'sender'])
            ->values(['id' => 2, 'city' => 'Second', 'feeMax' => 1, 'sender_email' => 's2@example.com', 'sender' => 'Second'])
            ->execute();
        $this->addTeam(2, 'Juniors');
        $this->addTeam(3, 'Other site', true, 2);
        // Texts are checked in English
        $this->getTableLocator()->get('Members')->updateAll(['active' => false, 'language' => 'en'], ['id' => 1]);
        $this->addMember(100, [2], ['city' => 'Lausanne', 'first_name' => 'Ana', 'date_birth' => Date::today()->subYears(10)->format('Y-m-d')]);
        $this->addMember(101, [2], ['city' => 'Renens', 'first_name' => 'Bob', 'date_birth' => Date::today()->subYears(30)->format('Y-m-d')]);
        $this->addMember(102, [2], ['city' => 'Lausanne', 'first_name' => 'Cid']);
        $this->addMember(103, [3], ['city' => 'Lausanne', 'first_name' => 'Dan']);
        $this->addMember(104, [2], ['city' => 'Lausanne', 'first_name' => 'Eve', 'active' => false]);

        $this->Controller = $this->controller(AppController::class, $this->identity(['Admin']));
        $this->Subvention = new SubventionComponent($this->Controller->components());
    }

    protected function tearDown(): void
    {
        foreach ($this->zips as $zip) {
            @unlink($zip);
        }
        PrefResult::$sites = null;
        I18n::setLocale(I18n::getDefaultLocale());
        parent::tearDown();
    }

    private function pref(int $siteId, int $teamId = 0): PrefResult
    {
        $pref = (new \ReflectionClass(PrefResult::class))->newInstanceWithoutConstructor();
        $pref->siteId = $siteId;
        $pref->teamId = $teamId;
        $pref->memberFilter = 1;

        return $pref;
    }

    private function names(array $members): array
    {
        return array_map(fn ($member) => $member->first_name, $members);
    }

    /**
     * @return array<string, string> content of the zip by file name
     */
    private function unzip(string $path): array
    {
        $this->zips[] = $path;
        $zip = new ZipArchive();
        $zip->open($path);
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();

        return $files;
    }

    public function testGetListBySiteAndCity(): void
    {
        $list = $this->Subvention->getList($this->pref(1), 0);

        $this->assertSame(['Ana', 'Bob', 'Cid'], $this->names($list['membersFilter']));
        $this->assertSame(['Lausanne', 'Renens'], $list['membersCityList']);
        $this->assertSame(['Ana', 'Cid'], $this->names($list['membersCity']['Lausanne']));
    }

    public function testGetListAllSitesAndAge(): void
    {
        $this->assertSame(['Ana', 'Bob', 'Cid', 'Dan'], $this->names($this->Subvention->getList($this->pref(0), 0)['membersFilter']));
        // Without birth date the age is unknown: not counted
        $this->assertSame(['Ana'], $this->names($this->Subvention->getList($this->pref(1), 20)['membersFilter']));
    }

    public function testGetContents(): void
    {
        $contents = $this->getTableLocator()->get('Contents');
        $contents->deleteAll([]);
        foreach ([[Content::SUBVENTIONS, 2, 'Second'], [Content::SUBVENTIONS, 1, 'First'], [Content::EMAIL_SUBVENTIONS, 1, 'Email']] as [$location, $sort, $text]) {
            $contents->insertQuery()->insert(['location', 'sort', 'text', 'team_id'])
                ->values(['location' => $location, 'sort' => $sort, 'text' => $text, 'team_id' => 0])->execute();
        }

        $this->assertSame([1 => 'First', 2 => 'Second'], $this->Subvention->getContents());
        $this->assertSame([1 => 'Email'], $this->Subvention->getContents(Content::EMAIL_SUBVENTIONS));
    }

    public function testCreateEmailPack(): void
    {
        $site = $this->getTableLocator()->get('Sites')->get(1);
        $site->account_designation = 'Club';
        $site->sender_email = 'club@example.com';
        $list = $this->Subvention->getList($this->pref(1), 0);

        $files = $this->unzip($this->Subvention->createEmailPack($site, $list, [1 => 'Letter'], "Hello {city}\n{count} members", ['Lausanne' => 'sport@lausanne.ch', 'Renens' => 'wrong']));

        $this->assertSame(['Subsidies - Lausanne.eml', 'Subsidies - Renens.eml'], array_keys($files));
        $lausanne = $files['Subsidies - Lausanne.eml'];
        $this->assertStringContainsString('X-Unsent: 1', $lausanne);
        $this->assertStringContainsString('From: Club <club@example.com>', $lausanne);
        $this->assertStringContainsString('sport@lausanne.ch', $lausanne);
        $this->assertStringContainsString('Subject: Request for Sports Funding - Club', $lausanne);
        $this->assertStringContainsString('Hello Lausanne', $lausanne);
        $this->assertStringContainsString('2 members', $lausanne);
        $this->assertStringContainsString('Subsidies - Lausanne.pdf', $lausanne);
        // Invalid address: no recipient, added in the email client
        $this->assertStringNotContainsString('To:', $files['Subsidies - Renens.eml']);
    }

    public function testCreateEmailPackHtmlBodyAndClubSender(): void
    {
        $this->getTableLocator()->get('Configurations')->insertQuery()->insert(['label', 'value'])
            ->values(['label' => 'email', 'value' => 'admin@example.com'])->execute();
        $controller = $this->controller(AppController::class, $this->identity(['Admin']));
        $subvention = new SubventionComponent($controller->components());
        $site = $this->getTableLocator()->get('Sites')->get(1);
        $site->sender_email = 'not an email';
        $this->addMember(105, [2], ['city' => 'A&B / C', 'first_name' => 'Fay']);
        $list = $subvention->getList($this->pref(1), 0);

        $files = $this->unzip($subvention->createEmailPack($site, $list, [], '<p>Hello <b>{city}</b></p>', []));

        $this->assertArrayHasKey("Subsidies - A_B _ C.eml", $files, implode(", ", array_keys($files)));
        $eml = $files["Subsidies - A_B _ C.eml"];
        $this->assertStringContainsString('admin@example.com', $eml);
        $this->assertStringContainsString('Hello <b>A&amp;B / C</b>', quoted_printable_decode($eml));
        $this->assertStringContainsString('Hello A&B / C', quoted_printable_decode($eml));
    }
}
