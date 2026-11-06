<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * The shop pages work on one city: the one chosen in the menu (or the only site)
 * Used by the shop controllers (items, member orders, orders and stock)
 *
 * @mixin \App\Controller\AppController
 */
trait ShopSiteTrait
{
    /**
     * City of the shop pages
     *
     * @return int 0 = all locations chosen in the menu, the page asks to choose one (see renderChooseSite())
     */
    protected function shopSiteId(): int
    {
        $siteId = $this->getSiteId();
        $sites = PrefResult::getSites();
        if ($siteId === 0 && count($sites) === 1) {
            $siteId = (int)array_key_first($sites);
            $this->set('navSiteId', $siteId);
        }
        // City shown in the title of the page
        $this->set('shopSite', PrefResult::getSite($siteId));

        return $siteId;
    }

    /**
     * Page asking to choose a city in the menu
     */
    protected function renderChooseSite(): Response
    {
        return $this->render('/Shop/choose_site');
    }

    /**
     * Flash and back to the page, for the changes made without a chosen city
     */
    protected function redirectChooseSite(): Response
    {
        $this->Flash->error(__('Choose a location in the menu first.'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * The items of another city than the chosen one are not accessible
     *
     * @throws \Cake\Http\Exception\NotFoundException
     */
    protected function checkShopSite(int $itemSiteId): void
    {
        if ($itemSiteId !== $this->shopSiteId()) {
            throw new NotFoundException(__('This item belongs to another location.'));
        }
    }
}
