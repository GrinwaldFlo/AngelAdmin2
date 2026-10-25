<?php
namespace App\Controller\Component;
use App\Model\Entity\Mailing;
use App\Model\Entity\MailingItem;
use Cake\Controller\Component;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validation;
use Throwable;

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

    $config = $this->getController()->config;
    $this->deliver($config['email'], $config['emailName'], $title, ['content' => $content, 'url' => $url]);
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

    $this->deliver($member->email, $member->FullName, $title, ['content' => $content]);
//    echo "Mail sent to member";
  }

  /**
   * Send the next pending emails of a mailing to teams
   * Stops at the first sending error (the SMTP server often refuses every next email, e.g. Gmail sending limit):
   * the failed email stays pending and is retried after the other pending ones on the next call
   * @param int $limit maximum number of emails sent by this call
   * @return array ['sent', 'pending', 'invalid', 'total', 'done', 'error' => message of the sending error or null]
   */
  public function sendMailing(Mailing $mailing, int $limit): array
  {
    $items = TableRegistry::getTableLocator()->get('MailingItems');
    $members = TableRegistry::getTableLocator()->get('Members');
    $clubName = $this->getController()->config['clubName'];
    $error = null;

    foreach ($items->nextPending($mailing->id, $limit) as $item) {
      if (!Validation::email($item->email)) {
        $item->status = MailingItem::INVALID;
        $item->error = __('Invalid email address');
        $items->saveOrFail($item);
        continue;
      }

      $myPageUrl = Router::url(['controller' => 'Members', 'action' => 'myPage', $members->ensureHash($item->member)], true);
      try {
        $this->deliver(
          $item->email,
          $item->member->FullName,
          $clubName . ' - ' . Mailing::render($mailing->title, $item->member, $myPageUrl),
          [
            'html' => Mailing::renderHtml($mailing->content, $item->member, $myPageUrl),
            'text' => Mailing::renderText($mailing->content, $item->member, $myPageUrl),
          ],
          'mailing',
        );
      } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $item->attempts++;
        $item->error = mb_substr($error, 0, 500);
        $items->saveOrFail($item);
        break;
      }

      $item->status = MailingItem::SENT;
      $item->sent = DateTime::now();
      $item->error = null;
      $items->saveOrFail($item);
    }

    $counts = $items->countsByMailing([$mailing->id])[$mailing->id];
    if ($counts['pending'] == 0 && !$mailing->isDone()) {
      $mailing->status = Mailing::DONE;
      $mailing->sentDate = DateTime::now();
      TableRegistry::getTableLocator()->get('Mailings')->saveOrFail($mailing);
      Log::info(sprintf('Mailing #%d "%s" sent to teams %s: %d/%d emails sent, %d invalid addresses',
        $mailing->id, $mailing->title, $mailing->teams, $counts['sent'], $counts['total'], $counts['invalid']));
    }

    return $counts + ['done' => $mailing->isDone(), 'error' => $error];
  }

  /**
   * Send an email from the club address
   * @param array $vars view variables of the template (the default template shows 'content')
   */
  private function deliver(string $toEmail, string $toName, string $title, array $vars, string $template = 'default'): void
  {
    $config = $this->getController()->config;
    $mailer = new Mailer();

    $mailer->setViewVars(['title' => $title] + $vars);

    $mailer
      ->setEmailFormat('both')
      ->setTo($toEmail, $toName)
      ->setFrom($config['email'], $config['emailName'])
      ->setSubject($title)
      ->viewBuilder()
      ->setTemplate($template)
      ->setLayout('default');

    $mailer->deliver();
  }

}
