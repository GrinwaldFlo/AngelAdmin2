<?php

declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Entity\Content;
use App\View\Helper\PdfHelper;
use Cake\Controller\Component;
use Cake\Mailer\Message;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;
use ZipArchive;

/**
 * Subsidy requests sent to the municipalities: ready to send emails with the letter of each city
 */
class SubventionComponent extends Component
{
    /**
     * Members of the site younger than $ageFilter, grouped by city
     *
     * @param \App\Controller\PrefResult $pref
     * @param int $ageFilter 0 = all ages
     * @return array ['membersFilter' => members, 'membersCity' => members by city, 'membersCityList' => sorted cities]
     */
    public function getList($pref, int $ageFilter): array
    {
        $controller = $this->getController();
        $members = TableRegistry::getTableLocator()->get('Members')->find(
            'Members',
            teamId: $pref->teamId,
            memberFilter: $pref->memberFilter,
            siteId: $pref->siteId
        )->contain('Teams')->orderBy(['Members.first_name' => 'ASC']);

        $membersFilter = [];
        $membersCity = [];
        foreach ($members as $member) {
            if ($pref->siteId != 0 && !$controller->memberIsInSite($member, $pref->siteId)) {
                continue;
            }
            if (!$controller->memberYoungerThan($member, $ageFilter)) {
                continue;
            }
            $membersFilter[] = $member;
            $membersCity[$member->city][] = $member;
        }

        $membersCityList = array_keys($membersCity);
        sort($membersCityList);

        return compact('membersFilter', 'membersCity', 'membersCityList');
    }

    /**
     * @param int $location Content::SUBVENTIONS (letter) or Content::EMAIL_SUBVENTIONS (email body)
     * @return array<int, string> text by sort
     */
    public function getContents(int $location = Content::SUBVENTIONS): array
    {
        $contents = [];
        $rows = TableRegistry::getTableLocator()->get('Contents')->find()
            ->where(['location' => $location])
            ->orderBy(['sort' => 'ASC']);
        foreach ($rows as $row) {
            $contents[$row->sort] = $row->text;
        }
        return $contents;
    }

    /**
     * Zip with one ready to send email (.eml) per city, the letter of the city attached
     *
     * @param \App\Model\Entity\Site $site
     * @param array $subventionsData result of getList()
     * @param array<int, string> $letterContents texts of the letter by sort
     * @param string $body email body, {city} and {count} are replaced
     * @param array<string, string> $emails email of the municipality by city
     * @return string path of the zip file, to delete once sent
     */
    public function createEmailPack(
        $site,
        array $subventionsData,
        array $letterContents,
        string $body,
        array $emails
    ): string {
        $pdfHelper = new PdfHelper($this->getController()->createView());
        $zipPath = tempnam(TMP, 'subventions');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($subventionsData['membersCityList'] as $city) {
            $members = $subventionsData['membersCity'][$city];
            $fileName = $this->fileName((string)$city);
            $pdf = $pdfHelper->createSubventions($site, $letterContents, [$city => $members])->Output('', 'S');

            $message = $this->createMessage($site, $emails[$city] ?? '', $body, (string)$city, count($members));
            $message->addAttachments([$fileName . '.pdf' => ['data' => $pdf, 'mimetype' => 'application/pdf']]);
            $zip->addFromString($fileName . '.eml', $this->toEml($message));
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * @param \App\Model\Entity\Site $site
     */
    private function createMessage($site, string $to, string $body, string $city, int $count): Message
    {
        $values = ['{city}' => $city, '{count}' => (string)$count];
        if (Content::isHtml($body)) {
            $html = strtr($body, array_map('h', $values));
            $text = strtr(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "$0\n", $body))), $values);
        } else {
            $text = strtr($body, $values);
            $html = nl2br(h($text));
        }

        $message = new Message();
        $message
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setSubject(__('Request for Sports Funding') . ' - ' . $site->account_designation)
            ->setBodyText(trim($text))
            ->setBodyHtml($html);

        $from = $this->senderEmail($site);
        if ($from !== '') {
            $message->setFrom($from, (string)$site->account_designation);
        }
        // Without a known address the email is still created, the recipient is added in the email client
        if (Validation::email($to)) {
            $message->setTo($to, __('Municipal Registry') . ' ' . $city);
        }
        return $message;
    }

    /**
     * Email of the site, or of the club if the site has none
     *
     * @param \App\Model\Entity\Site $site
     */
    private function senderEmail($site): string
    {
        foreach ([$site->sender_email, $this->getController()->config['email'] ?? ''] as $email) {
            if (Validation::email((string)$email)) {
                return (string)$email;
            }
        }
        return '';
    }

    /**
     * Raw email, X-Unsent makes email clients open it as a draft
     */
    private function toEml(Message $message): string
    {
        $headers = $message->getHeadersString(['from', 'to', 'subject']);
        return $headers . "\r\nX-Unsent: 1\r\n\r\n" . $message->getBodyString();
    }

    private function fileName(string $city): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N} _-]+/u', '_', $city), ' _');
        return __('Subsidies') . ' - ' . ($name === '' ? '_' : $name);
    }
}
