<?php
namespace App\Model\Behavior;
use Cake\ORM\Behavior;

class ToolsBehavior extends Behavior
{
  function formatTel($val)
  {
    if (strlen($val) < 10)
      return $val;

    $val = str_replace(" ", "", $val);
    $val = str_replace("/", "", $val);
    $val = str_replace(".", "", $val);
    if (strpos($val, '0041') === 0)
    {
      $val = "0" . substr($val, 4);
    }
    elseif (strpos($val, '+41') === 0)
    {
      $val = "0" . substr($val, 3);
    }

    if (strlen($val) != 10)
      return $val;

    return substr($val, 0, 3) . " " . substr($val, 3, 3) . " " . substr($val, 6, 2) . " " . substr($val, 8, 2);
  }

  function formatAddress($val)
  {
    if (strlen($val) < 5)
      return $val;

    $val = str_replace("Avenue ", "Av. ", $val);
    $val = str_replace("Route ", "Rte. ", $val);
    $val = str_replace("Chemin ", "Ch. ", $val);
    $val = str_replace("avenue ", "Av. ", $val);
    $val = str_replace("route ", "Rte. ", $val);
    $val = str_replace("chemin ", "Ch. ", $val);

    return $val;
  }

}