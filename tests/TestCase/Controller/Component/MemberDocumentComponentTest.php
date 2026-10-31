<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\MembersController;
use App\Controller\PrefResult;
use App\Model\Entity\Member;
use App\Test\TestSuite\TestDataTrait;
use Authorization\Exception\ForbiddenException;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * Files are written in the folder of member 100, removed after each test
 *
 * @uses \App\Controller\Component\MemberDocumentComponent
 */
class MemberDocumentComponentTest extends TestCase
{
    use TestDataTrait;

    protected array $fixtures = [
        'app.Members', 'app.Users', 'app.Roles', 'app.Sites', 'app.Teams', 'app.TeamsMembers', 'app.TeamsCoaches',
        'app.Configurations', 'app.Contents', 'app.MemberDocs',
    ];

    private MembersController $Controller;

    private string $upload;

    protected function setUp(): void
    {
        parent::setUp();
        PrefResult::$sites = null;
        $this->addMember(100, [1], ['registered' => 0]);
        $this->getTableLocator()->get('Members')->updateAll(['language' => 'en'], ['id' => 1]);
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberEditAll']));
        $this->upload = TMP . 'member_document_upload';
    }

    protected function tearDown(): void
    {
        $folder = $this->member()->MemberPath;
        foreach (glob($folder . '*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($folder);
        @unlink($this->upload);
        PrefResult::$sites = null;
        parent::tearDown();
    }

    private function member(): Member
    {
        return $this->getTableLocator()->get('Members')->get(100);
    }

    private function file(string $content, string $type, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        file_put_contents($this->upload, $content);

        return new UploadedFile($this->upload, strlen($content), $error, 'file', $type);
    }

    private function flashes(): array
    {
        return array_column($this->Controller->getRequest()->getSession()->read('Flash.flash') ?? [], 'element');
    }

    public function testAddPhotoWithoutFile(): void
    {
        $this->assertFalse($this->Controller->MemberDocument->addPhoto(100, []));
        $this->assertFalse($this->Controller->MemberDocument->addPhotoId(100, []));
        $this->assertSame([], $this->flashes());
    }

    public function testAddPhotoUploadError(): void
    {
        $this->assertFalse($this->Controller->MemberDocument->addPhoto(100, ['submittedfile' => $this->file('', 'image/jpeg', UPLOAD_ERR_INI_SIZE)]));
        $this->assertFalse($this->Controller->MemberDocument->addPhotoId(100, ['submittedfile' => $this->file('', 'image/jpeg', UPLOAD_ERR_PARTIAL)]));
        $this->assertSame(['flash/error', 'flash/error'], $this->flashes());
    }

    public function testAddPhotoNotAPicture(): void
    {
        $this->assertFalse($this->Controller->MemberDocument->addPhoto(100, ['submittedfile' => $this->file('%PDF', 'application/pdf')]));
        $this->assertFalse($this->Controller->MemberDocument->addPhoto(100, ['submittedfile' => $this->file('text', 'image/jpeg')]));

        $this->assertSame(['flash/error', 'flash/error'], $this->flashes());
        $this->assertFalse($this->member()->ImgExists);
    }

    public function testAddPhotoResizesThePicture(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is needed to process the pictures');
        }
        $jpeg = (string)file_get_contents(WWW_ROOT . 'img' . DS . 'cheerPlaceholder.jpg');

        $this->assertTrue($this->Controller->MemberDocument->addPhoto(100, ['submittedfile' => $this->file($jpeg, 'image/jpeg')]));

        foreach ([100, 200, 300, 500, 1000, 2000] as $size) {
            $this->assertFileExists($this->member()->GetImgPath($size));
        }
    }

    public function testAddPhotoNeedsTheRightToEdit(): void
    {
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberViewAll']));

        $this->expectException(ForbiddenException::class);
        $this->Controller->MemberDocument->addPhoto(100, []);
    }

    public function testAddPhotoAllowedByMemberLink(): void
    {
        $this->Controller = $this->controller(MembersController::class, $this->identity(['MemberViewAll']));

        $this->assertFalse($this->Controller->MemberDocument->addPhotoId(100, [], true));
    }

    public function testProcessBatchRegister(): void
    {
        $this->assertTrue($this->Controller->MemberDocument->processBatchRegister(100, ['submittedfile1' => $this->file('%PDF-1.4', 'application/pdf')], 2026));

        $member = $this->member();
        $this->assertTrue($member->registered);
        $this->assertSame('%PDF-1.4', file_get_contents($member->GetRegPath(2026)));
        $this->assertSame(['flash/success'], $this->flashes());
    }

    public function testProcessBatchRegisterPdfOnly(): void
    {
        $this->assertFalse($this->Controller->MemberDocument->processBatchRegister(100, ['submittedfile1' => $this->file('x', 'image/jpeg')], 2026));
        $this->assertFalse($this->Controller->MemberDocument->processBatchRegister(100, ['submittedfile1' => $this->file('', 'application/pdf', UPLOAD_ERR_NO_FILE)], 2026));
        $this->assertFalse($this->Controller->MemberDocument->processBatchRegister(100, [], 2026));

        $this->assertFalse($this->member()->registered);
    }

    public function testGetMemberDocuments(): void
    {
        $this->getTableLocator()->get('MemberDocs')->deleteAll([]);
        $this->getTableLocator()->get('MemberDocs')->insertQuery()
            ->insert(['name', 'title', 'created', 'modified'])
            ->values(['name' => 'medical', 'title' => 'Medical form', 'created' => '2026-01-01', 'modified' => '2026-01-01'])
            ->values(['name' => 'other', 'title' => 'Other', 'created' => '2026-01-01', 'modified' => '2026-01-01'])
            ->execute();
        $member = $this->member();
        $member->CheckFolder();
        file_put_contents($member->GetDocPath('medical'), 'pdf');

        $this->assertSame(['medical' => ['url' => $member->GetDocUrl('medical'), 'title' => 'Medical form']], $this->Controller->MemberDocument->getMemberDocuments($member));
    }
}
