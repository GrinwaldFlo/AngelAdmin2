<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Utility\ImageHelper;

/**
 * Member Document Component
 * 
 * Handles document and photo uploads for members
 */
class MemberDocumentComponent extends Component
{
    /**
     * Check if uploaded file is a valid image (JPEG or HEIC)
     */
    private function isValidImageFile($fileobject): array
    {
        $controller = $this->getController();
        
        // Load Image component for validation
        if (!isset($controller->Image)) {
            $controller->loadComponent('Image');
        }
        
        // Use Image component's validation
        return $controller->Image->validateUploadedImage($fileobject);
    }

    /**
     * Process image file - convert HEIC to JPEG if needed
     */
    private function processImageFile($fileobject, $targetPath): bool
    {
        $controller = $this->getController();
        $validation = $this->isValidImageFile($fileobject);
        
        if (!$validation['valid']) {
            $controller->Flash->error($validation['errorMessage']);
            return false;
        }
        
        if ($validation['isHeic']) {
            if (!ImageHelper::isHeicSupported()) {
                $controller->Flash->error(__("HEIC format is not supported"));
                return false;
            }
            
            // Create temporary file for HEIC
            $tempHeicPath = tempnam(sys_get_temp_dir(), 'heic_upload_');
            
            // Handle file stream that might have been consumed during validation
            try {
                $fileobject->moveTo($tempHeicPath);
            } catch (\Exception $e) {
                // If moveTo fails, try to get the stream and write it
                try {
                    $stream = $fileobject->getStream();
                    $stream->rewind(); // Try to rewind stream
                    $content = $stream->getContents();
                    file_put_contents($tempHeicPath, $content);
                } catch (\Exception $e2) {
                    $controller->Flash->error(__("Failed to process image"));
                    if (file_exists($tempHeicPath)) {
                        unlink($tempHeicPath);
                    }
                    return false;
                }
            }
            
            // Convert HEIC to JPEG
            if (!ImageHelper::convertHeicToJpeg($tempHeicPath, $targetPath)) {
                unlink($tempHeicPath);
                $controller->Flash->error(__("Failed to process image"));
                return false;
            }
            
            // Clean up temp file
            unlink($tempHeicPath);
            
        } else {
            // Direct JPEG upload
            try {
                $fileobject->moveTo($targetPath);
            } catch (\Exception $e) {
                // If moveTo fails, try to get the stream and write it
                try {
                    $stream = $fileobject->getStream();
                    $stream->rewind(); // Try to rewind stream
                    $content = $stream->getContents();
                    file_put_contents($targetPath, $content);
                } catch (\Exception $e2) {
                    $controller->Flash->error(__("Failed to process image"));
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Add photo to member
     */
    public function addPhoto($id, $fileData, bool $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id);

        if (!$isAllowed)
        {
            $controller->Authorization->authorize($member, 'Edit');
        }

        if (empty($fileData)) {
            return false;
        }

        $fileobject = $fileData['submittedfile'];

        if ($fileobject->getError() != 0) {
            $controller->Flash->error(__("Failed to send picture"));
            return false;
        }
        
        $member->CheckFolder();
        $imgOri = $member->GetImgPath(2000);
        
        // Process image file (JPEG or HEIC)
        if (!$this->processImageFile($fileobject, $imgOri)) {
            return false;
        }
        
        $controller->Image->rotateFromExif($imgOri);
        
        $ar = array(100, 200, 300, 500, 1000, 2000);
        foreach ($ar as $value) {
            $url = $member->GetImgPath($value);
            $controller->Image->resize($imgOri, $url, 0, $value, 90);
        }
        
        $controller->Flash->success(__('The picture has been saved.'));
        return true;
    }

    /**
     * Add ID photo to member
     */
    public function addPhotoId($id, $fileData, bool $isAllowed = false)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($id);

        if (!$isAllowed) {
            $controller->Authorization->authorize($member, 'Edit');
        }

        if (empty($fileData)) {
            return false;
        }

        $fileobject = $fileData['submittedfile'];

        if ($fileobject->getError() != 0) {
            $controller->Flash->error(__("Failed to send picture"));
            return false;
        }
        
        $member->CheckFolder();
        $imgOri = $member->GetImgIdPath(2000);
        
        // Process image file (JPEG or HEIC)
        if (!$this->processImageFile($fileobject, $imgOri)) {
            return false;
        }
        
        $controller->Image->rotateFromExif($imgOri);
        
        $ar = array(100, 200, 300, 500, 1000, 2000);
        foreach ($ar as $value) {
            $url = $member->GetImgIdPath($value);
            $controller->Image->resize($imgOri, $url, 0, $value, 90);
        }
        
        $controller->Flash->success(__('The picture has been saved.'));
        return true;
    }

    /**
     * Process batch registration document upload
     */
    public function processBatchRegister($memberId, $fileData, $year)
    {
        $controller = $this->getController();
        $member = $controller->Members->get($memberId, contain: ['Teams']);
        
        if (empty($fileData)) {
            return false;
        }

        $fileobject1 = $fileData['submittedfile1'];

        if ($fileobject1->getError() != 0) {
            $controller->Flash->error(__("Failed to send document"));
            return false;
        }
        
        if ($fileobject1->getClientMediaType() != "application/pdf") {
            $controller->Flash->error(__("PDF only"));
            return false;
        }

        $member->CheckFolder();
        $docPath = $member->GetRegPath($year);
        $fileobject1->moveTo($docPath);

        $member->registered = true;
        if ($controller->Members->save($member)) {
            $controller->Flash->success(__('{0} is registed', $member->fullName));
            return true;
        } else {
            $controller->Flash->error(__('Failed to register member'));
            return false;
        }
    }

    /**
     * Get member documents
     */
    public function getMemberDocuments($member)
    {
        $controller = $this->getController();
        $memberDocs = $controller->fetchTable('MemberDocs');

        $docs = $memberDocs->find('all', order: ['title' => 'asc']);

        $files = [];
        foreach ($docs as $key => $value) {
            if ($member->DocExists($value->name)) {
                $files[$value->name]['url'] = $member->GetDocUrl($value->name);
                $files[$value->name]['title'] = $value->title;
            }
        }
        
        return $files;
    }
}
