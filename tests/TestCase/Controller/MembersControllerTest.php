<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Test\TestSuite\TestDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MembersController Test Case
 *
 * @uses \App\Controller\MembersController
 */
class MembersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use TestDataTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Members',
        'app.Bills',
        'app.Presences',
        'app.Users',
        'app.Teams',
        'app.TeamsMembers',
        'app.Sites',
        'app.Meetings',
        'app.Roles',
        'app.Configurations',
        'app.Fields',
        'app.FieldTypes',
    ];

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->get('/members');
        $this->assertResponseCode(302);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->get('/members/view/1');
        $this->assertResponseCode(302);
    }

    public function testViewSortsAttendanceNewestFirst(): void
    {
        $meetings = $this->getTableLocator()->get('Meetings');
        $presences = $this->getTableLocator()->get('Presences');
        // Created out of order so the ids don't match the dates
        foreach (['2020-01-10 18:00:00', '2020-01-24 18:00:00', '2020-01-17 18:00:00'] as $date) {
            $meeting = $meetings->saveOrFail($meetings->newEntity(['meeting_date' => new DateTime($date), 'name' => 'Training', 'team_id' => 1]));
            $presences->saveOrFail($presences->newEntity(['meeting_id' => $meeting->id, 'member_id' => 1, 'state' => 1]));
        }

        $this->sessionAs(1);
        $this->get('/members/view/1');

        $this->assertResponseOk();
        $dates = array_map(
            fn($item) => $item->meeting->meeting_date->format('Y-m-d'),
            array_values($this->viewVariable('attendance')['2019-2020'])
        );
        // 2020-03-29 comes from the Meetings fixture
        $this->assertSame(['2020-03-29', '2020-01-24', '2020-01-17', '2020-01-10'], $dates);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->get('/members/add');
        $this->assertResponseCode(302);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->get('/members/edit/1');
        $this->assertResponseCode(302);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/delete/1');
        $this->assertResponseCode(302);
    }

    /**
     * Language menu of the layout
     */
    public function testSetLanguageOfTheLoggedMember(): void
    {
        $this->sessionAs(1);
        $this->configRequest(['environment' => ['HTTP_REFERER' => 'http://localhost/members/view/1']]);

        $this->get('/members/set-language/de');

        $this->assertRedirect('/members/view/1');
        $this->assertSame('de', $this->getTableLocator()->get('Members')->get(1)->language);
        $this->assertSession('de', 'lng');
    }

    public function testSetLanguageWithoutLogin(): void
    {
        $this->get('/members/set-language/it');

        $this->assertRedirect('/');
        $this->assertSession('it', 'lng');
    }

    public function testSetUnknownLanguage(): void
    {
        $this->sessionAs(1);

        $this->get('/members/set-language/xx');

        $this->assertRedirect('/');
        $this->assertSame('fr', $this->getTableLocator()->get('Members')->get(1)->language);
    }

    /**
     * Public registration form
     */
    public function testRegisterForm(): void
    {
        PrefResult::$sites = null;

        $this->get('/members/register');

        $this->assertResponseOk();
        $this->assertResponseContains('name="first_name"');
    }

    /**
     * A known name with another email shows the contact message and the form again, filled with the posted data
     */
    public function testRegisterKnownNameWithAnotherEmail(): void
    {
        PrefResult::$sites = null;
        $this->addMember(100, [1]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $form = $this->memberForm(['first_name' => 'First100', 'last_name' => 'Last100', 'email' => 'x@example.ch']);
        $this->post('/members/register', $form);

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('ContactInfo'));
        $this->assertResponseContains('href="mailto:');
        $this->assertResponseContains('value="x@example.ch"');
        $members = $this->getTableLocator()->get('Members');
        $this->assertSame(1, $members->find()->where(['first_name' => 'First100'])->count());
    }
}
