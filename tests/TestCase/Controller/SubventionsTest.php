<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PrefResult;
use App\Model\Entity\Content;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ZipArchive;

/**
 * Subsidy requests: emails of the municipalities and email pack
 *
 * @uses \App\Controller\AdminController
 */
class SubventionsTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Members',
        'app.Users',
        'app.Roles',
        'app.Sites',
        'app.Teams',
        'app.TeamsMembers',
        'app.Configurations',
        'app.Contents',
        'app.SubventionCities',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->enableCsrfToken();
        // The PNG logo needs the GD extension, not always installed with the CLI
        Configure::write('App.logo', 'cheerPlaceholder.jpg');
        $this->getTableLocator()->get('Configurations')->insertQuery()
            ->insert(['label', 'value'])
            ->values(['label' => 'email', 'value' => 'club@example.com'])
            ->execute();
        // Member 1 (team 1 of site 1) lives in Lausanne, texts are checked in English
        $this->getTableLocator()->get('Members')->updateAll(['city' => 'Lausanne', 'language' => 'en'], ['id' => 1]);
    }

    private function login(): void
    {
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(1)]);
    }

    private function postEmail(string $email): void
    {
        $this->post('/admin/subvention-emails', ['cities' => [['city' => 'Lausanne', 'email' => $email]]]);
    }

    private function addContent(int $location, int $sort, string $text): void
    {
        $this->getTableLocator()->get('Contents')->insertQuery()
            ->insert(['text', 'location', 'url', 'sort'])
            ->values(['text' => $text, 'location' => $location, 'url' => '', 'sort' => $sort])
            ->execute();
    }

    /**
     * @return array<string, string> content of the downloaded zip by file name
     */
    private function zipFiles(): array
    {
        $path = tempnam(TMP, 'test');
        file_put_contents($path, (string)$this->_response->getBody());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);
        return $files;
    }

    public function testNotLoggedIn(): void
    {
        $this->get('/admin/subvention-emails');
        $this->assertRedirectContains('/users/login');
        $this->get('/admin/subventions-email-pack');
        $this->assertRedirectContains('/users/login');
    }

    public function testNotAdmin(): void
    {
        $this->getTableLocator()->get('Roles')->updateAll(['Admin' => 0], ['id' => 1]);
        $this->login();
        foreach (['subventions', 'subventions-pdf', 'subvention-emails', 'subventions-email-pack'] as $action) {
            $this->get('/admin/' . $action);
            $this->assertRedirect('/');
        }
    }

    public function testSubventionsPage(): void
    {
        $this->login();
        $this->get('/admin/subventions');
        $this->assertResponseOk();
        $this->assertResponseContains('Lausanne');
        $this->assertResponseContains('/admin/subventions-email-pack/0');
        $this->assertResponseContains('/admin/subvention-emails/0');
    }

    public function testSubventionsPdf(): void
    {
        $this->addContent(Content::SUBVENTIONS, 1, 'Letter text');
        $this->login();
        $this->get('/admin/subventions-pdf');
        $this->assertResponseOk();
        $this->assertStringStartsWith('%PDF', (string)$this->_response->getBody());
    }

    public function testEmailsPage(): void
    {
        $this->login();
        $this->get('/admin/subvention-emails');
        $this->assertResponseOk();
        $this->assertResponseContains('Lausanne');
        // No body content yet
        $this->assertResponseContains('The email body is empty');
    }

    public function testSaveEmails(): void
    {
        $this->login();
        $this->postEmail(' greffe@lausanne.ch ');
        $this->assertRedirect(['controller' => 'Admin', 'action' => 'subventions', 0]);
        $cities = $this->getTableLocator()->get('SubventionCities');
        $this->assertSame(['Lausanne' => 'greffe@lausanne.ch'], $cities->emailsByCity(['Lausanne']));

        // Updated, not duplicated
        $this->postEmail('other@lausanne.ch');
        $this->assertSame(['Lausanne' => 'other@lausanne.ch'], $cities->emailsByCity(['Lausanne']));
        $this->assertSame(1, $cities->find()->count());
    }

    public function testSaveInvalidEmail(): void
    {
        $this->login();
        $this->enableRetainFlashMessages();
        $this->postEmail('not an email');
        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid email address: Lausanne');
        $this->assertSame(0, $this->getTableLocator()->get('SubventionCities')->find()->count());
    }

    public function testEmailPack(): void
    {
        $this->addContent(Content::SUBVENTIONS, 1, 'Letter text');
        $body = '<p>Hello {city}, here are {count} members &amp; their letter</p>';
        $this->addContent(Content::EMAIL_SUBVENTIONS, 1, $body);
        $this->getTableLocator()->get('SubventionCities')->saveEmails(['Lausanne' => 'greffe@lausanne.ch']);

        $this->login();
        $this->get('/admin/subventions-email-pack');
        $this->assertResponseOk();
        $this->assertContentType('application/zip');
        $this->assertHeaderContains('Content-Disposition', 'Subsidies.zip');

        $files = $this->zipFiles();
        $this->assertSame(['Subsidies - Lausanne.eml'], array_keys($files));
        $eml = $files['Subsidies - Lausanne.eml'];
        $this->assertStringContainsString('To: Municipal Registry Lausanne <greffe@lausanne.ch>', $eml);
        $this->assertStringContainsString('From: Lorem ipsum dolor sit amet <club@example.com>', $eml);
        $this->assertStringContainsString('Subject: Request for Sports Funding - Lorem ipsum dolor sit amet', $eml);
        $this->assertStringContainsString('X-Unsent: 1', $eml);
        $this->assertStringContainsString('Hello Lausanne, here are 1 members & their letter', $eml);
        $this->assertStringContainsString('<p>Hello Lausanne, here are 1 members &amp; their letter</p>', $eml);
        $this->assertStringContainsString('filename="Subsidies - Lausanne.pdf"', $eml);
        $this->assertStringContainsString('Content-Type: application/pdf', $eml);
    }

    public function testEmailPackWithoutAddress(): void
    {
        $this->addContent(Content::EMAIL_SUBVENTIONS, 1, "Hello {city}\nBye");

        $this->login();
        $this->get('/admin/subventions-email-pack');
        $this->assertResponseOk();

        $eml = $this->zipFiles()['Subsidies - Lausanne.eml'];
        $this->assertStringNotContainsString("\r\nTo:", $eml);
        $this->assertStringContainsString("Hello Lausanne\r\nBye", $eml);
        $this->assertStringContainsString('Hello Lausanne<br />', $eml);
    }
}
