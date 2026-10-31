<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\CrudControllerTestCase;

/**
 * @uses \App\Controller\ContentsController
 */
class ContentsControllerTest extends CrudControllerTestCase
{
    protected string $url = '/contents';

    protected string $table = 'Contents';

    protected string $field = 'text';

    protected string $right = 'Editor';

    protected function validData(): array
    {
        return ['text' => 'Hello', 'location' => 12, 'url' => '', 'team_id' => '', 'sort' => 1];
    }

    /**
     * No team is saved as 0, the column is not nullable
     */
    public function testAddWithoutTeam(): void
    {
        $this->login();

        $this->post('/contents/add', $this->validData());

        $content = $this->getTableLocator()->get('Contents')->find()->where(['location' => 12])->firstOrFail();
        $this->assertSame(0, $content->team_id);
    }

    public function testInfoPage(): void
    {
        $this->login([]);

        $this->get('/contents/info');

        $this->assertResponseOk();
    }
}
