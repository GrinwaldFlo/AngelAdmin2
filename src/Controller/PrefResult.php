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
     * @var \app\Model\Entity\Site[]|null null = not loaded yet
     */
    public static ?array $sites = null;

    /**
     * @param AppController $app
     */
    public function __construct(AppController $app, int|null $teamId = null, int|null $memberFilter = null, bool $siteRequired = false)
    {
        $this->siteId = $app->getSiteId($siteRequired);
        $this->site = PrefResult::getSite($this->siteId);

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
        if (PrefResult::$sites === null) {
            $sitesTable = TableRegistry::getTableLocator()->get('Sites');
            PrefResult::$sites = [];
            foreach ($sitesTable->find('all')->orderBy(['id' => 'ASC']) as $site) {
                PrefResult::$sites[$site->id] = $site;
            }
        }
        return PrefResult::$sites;
    }

    /**
     * @param int $siteId
     * @return \app\Model\Entity\Site|null null if the site doesn't exist
     */
    public static function getSite(int $siteId)
    {
        return PrefResult::getSites()[$siteId] ?? null;
    }

    /**
     * @param int|null $siteId
     * @return int $siteId if the site exists, otherwise 0
     */
    public static function validSiteId(int|null $siteId): int
    {
        return PrefResult::getSite((int)$siteId) !== null ? (int)$siteId : 0;
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
