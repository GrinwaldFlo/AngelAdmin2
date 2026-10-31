<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SubventionCitiesTable;
use Cake\TestSuite\TestCase;

/**
 * @uses \App\Model\Table\SubventionCitiesTable
 */
class SubventionCitiesTableTest extends TestCase
{
    protected array $fixtures = ['app.SubventionCities'];

    private SubventionCitiesTable $SubventionCities;

    protected function setUp(): void
    {
        parent::setUp();
        $this->SubventionCities = $this->getTableLocator()->get('SubventionCities');
    }

    public function testValidationDefault(): void
    {
        $this->assertSame([], $this->SubventionCities->newEntity(['city' => 'Lausanne', 'email' => ''])->getErrors());
        $this->assertArrayHasKey('city', $this->SubventionCities->newEntity(['city' => ''])->getErrors());
        $this->assertArrayHasKey('city', $this->SubventionCities->newEntity(['city' => str_repeat('a', 101)])->getErrors());
        $this->assertArrayHasKey('email', $this->SubventionCities->newEntity(['city' => 'Lausanne', 'email' => 'wrong'])->getErrors());
    }

    public function testEmailsByCityEmpty(): void
    {
        $this->assertSame([], $this->SubventionCities->emailsByCity([]));
    }

    public function testSaveEmailsThenEmailsByCity(): void
    {
        $this->assertSame([], $this->SubventionCities->saveEmails(['Lausanne' => ' sport@lausanne.ch ', 'Renens' => '']));

        $this->assertSame(
            ['Lausanne' => 'sport@lausanne.ch', 'Renens' => '', 'Morges' => ''],
            $this->SubventionCities->emailsByCity(['Lausanne', 'Renens', 'Morges'])
        );
    }

    public function testSaveEmailsUpdatesExistingCity(): void
    {
        $this->SubventionCities->saveEmails(['Lausanne' => 'old@lausanne.ch']);
        $this->SubventionCities->saveEmails(['Lausanne' => 'new@lausanne.ch']);

        $this->assertSame(1, $this->SubventionCities->find()->count());
        $this->assertSame(['Lausanne' => 'new@lausanne.ch'], $this->SubventionCities->emailsByCity(['Lausanne']));
    }

    public function testSaveEmailsReturnsInvalidCities(): void
    {
        $this->SubventionCities->saveEmails(['Lausanne' => 'ok@lausanne.ch']);

        $this->assertSame(['Lausanne', 'Renens'], $this->SubventionCities->saveEmails(['Lausanne' => 'wrong', 'Renens' => 'wrong too', 'Morges' => 'ok@morges.ch']));
        // The invalid one is not changed
        $this->assertSame(['Lausanne' => 'ok@lausanne.ch', 'Morges' => 'ok@morges.ch'], $this->SubventionCities->emailsByCity(['Lausanne', 'Morges']));
    }
}
