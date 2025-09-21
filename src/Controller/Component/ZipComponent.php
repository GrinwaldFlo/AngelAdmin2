<?php
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class ZipComponent extends Component
{
  public $name = 'Zip';
  public function create($destination = '', $files = array())
  {
    if (file_exists($destination))
    {
      unlink($destination);
    }

    $validFiles = array();
    if (is_array($files))
    {
      foreach ($files as $file)
      {
        if (file_exists($file))
        {
          $validFiles[] = $file;
        }
      }
    }

    if (count($validFiles) < 1)
    {
      return false;
    }

    $zip = new \ZipArchive();
    //$type = $overwrite ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE;
    $type = \ZipArchive::CREATE;
    
    $resultOpen = $zip->open($destination, $type);
    if ($resultOpen !== true)
    {
      echo $resultOpen;
      return false;
    }

    $dest = str_replace('.zip', '', basename($destination));
    foreach ($validFiles as $file)
    {
      $zip->addFile($file, $dest . DS . basename($file));
    }
    $zip->close();
    return file_exists($destination);
  }

}