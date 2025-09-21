<?php
namespace App\Controller\Component;
use Cake\Controller\Component;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class MailComponent extends Component
{
  public $name = 'Mail';
  public function Test()
  {
    debug("Hello");
  }

  public function mailNotif($title, $content, $member, $idNotif = '')
  {
    if (empty($member))
    {
      $url = "";
      $title = $title;
    }
    else
    {
      $url = Router::url(array('controller' => 'members', 'action' => 'view', $member->id), true);
      $title = $title . ' - ' . $member->FullName;
      $content = $content . ' ' . $url;
    }

    $mailer = new Mailer();

    $mailer->setViewVars(['url' => $url]);
    $mailer->setViewVars(['title' => $title]);
    $mailer->setViewVars(['content' => $content]);

    $mailer
      ->setEmailFormat('both')
      ->setTo($this->getController()->config['email'], $this->getController()->config['emailName'])
      ->setFrom($this->getController()->config['email'], $this->getController()->config['emailName'])
      ->setSubject($title)
      ->viewBuilder()
      ->setTemplate('default')
      ->setLayout('default');

    $mailer->deliver();
//    echo "Mail sent notif";
  }

  public function mailToMember($title, $content, $member)
  {
    if (empty($member))
    {
      return;
    }

    $title = $this->getController()->config['clubName'] . ' - ' . $title;
    $content = __("Dear")." ".$member->FullName.",\n".$content;

    $mailer = new Mailer();

    $mailer->setViewVars(['content' => $content]);
    $mailer->setViewVars(['title' => $title]);

    $mailer
      ->setEmailFormat('both')
      ->setTo($member->email, $member->FullName)
      ->setFrom($this->getController()->config['email'], $this->getController()->config['emailName'])
      ->setSubject($title)
      ->viewBuilder()
      ->setTemplate('default')
      ->setLayout('default');

    $mailer->deliver();
//    echo "Mail sent to member";
  }

}
