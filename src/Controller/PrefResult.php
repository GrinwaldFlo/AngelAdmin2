<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\ORM\TableRegistry;

class PrefResult
{
    public int $siteId;
    public int $teamId;
    public int $memberFilter;

    public $members;
    /**
     * @var \app\Model\Entity\Team[]
     */
    public $teams;
    public $site;
    /**
     * @var \app\Model\Entity\Site[]
     */
    public static array $sites;

    /**
     * @param AppController $app
     */
    public function __construct(AppController $app, int|null $teamId = null, int|null $memberFilter = null, bool $siteRequired = false)
    {
        $this->siteId = $app->getSiteId($siteRequired);
        $this->site = $this->siteId > 0 ? PrefResult::getSites()[$this->siteId] ?? null : null;

        $this->teamId = $app->getPrefSession('teamId', $teamId, 0);
        $this->teams = $app->getTeamsActiv($this->siteId);
        $this->teamId = $this->validateTeamId($this->teamId, $this->teams);

        $this->memberFilter = $app->getPrefSession('memberFilter', $memberFilter, 1);
    }

    /**
     * Get all sites indexed by id (loaded once)
     *
     * @return \app\Model\Entity\Site[]
     */
    public static function getSites(): array
    {
        if (empty(PrefResult::$sites)) {
            $sitesTable = TableRegistry::getTableLocator()->get('Sites');
            PrefResult::$sites = [];
            foreach ($sitesTable->find('all')->orderBy(['id' => 'ASC']) as $site) {
                PrefResult::$sites[$site->id] = $site;
            }
        }
        return PrefResult::$sites;
    }

    /**
     * Checks if $teamId exists in $teams array, otherwise sets it to null.
     *
     * @param int $teamId
     * @param array $teams
     * @return int
     */
    public static function validateTeamId($teamId, $teams): int
    {
        if (empty($teamId)) {
            return 0;
        }
        // If $teams is associative (id => name), check keys
        if (is_array($teams) && !in_array($teamId, array_keys($teams))) {
            return 0;
        }
        return $teamId;
    }

}
