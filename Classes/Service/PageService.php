<?php
declare(strict_types=1);

namespace Colorcube\Auto404\Service;


use Colorcube\SimulateStaticUrls\Service\FrontendControllerService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

abstract class PageService
{

    /**
     * Get a page record by alias etc. This is used to find the 4040 TYPO3 page
     *
     * @param string $alias
     * @param int $rootPageUid
     * @param int $languageUid
     * @return array|mixed
     */
    public static function getPageByAliasWithOverlay(string $alias, int $rootPageUid, int $languageUid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('alias', $queryBuilder->createNamedParameter($alias, \PDO::PARAM_STR)),
                    $queryBuilder->expr()->eq('module', $queryBuilder->createNamedParameter($alias, \PDO::PARAM_STR))
                ),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($rootPageUid, \PDO::PARAM_INT))
            );

        $pageRecord = $queryBuilder->execute()->fetch();

        if ($pageRecord && $languageUid > 0) {
            $pageRecord = FrontendControllerService::getPageRepository()->getPageOverlay($pageRecord, $languageUid);

        }

        return $pageRecord;
    }


    /**
     * Get a page record in a given language
     *
     * @param int $pageUid
     * @param int $languageUid
     * @return array|NULL
     */
    public static function getPageWithOverlay(int $pageUid, int $languageUid)
    {
        $pageRecord = BackendUtility::getRecord('pages', $pageUid);

        if ($pageRecord && $languageUid > 0) {
            $pageRecord = FrontendControllerService::getPageRepository()->getPageOverlay($pageRecord, $languageUid);
        }

        return $pageRecord;
    }


    /**
     * For a given domain we find the root page of the corresponding website
     *
     * @param string $domain
     * @return null
     */
    public static function getRootPageUidForDomain(string $domain)
    {
        $domain = explode(':', $domain);
        $domain = strtolower(preg_replace('/\\.$/', '', $domain[0]));
        $domain = preg_replace('/\\/*$/', '', $domain);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select(
                'pages.uid',
                'sys_domain.pid'
            )
            ->from('pages')
            ->from('sys_domain')
            ->where(
                $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('sys_domain.pid')),
                $queryBuilder->expr()->eq(
                    'sys_domain.hidden',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'sys_domain.domainName',
                        $queryBuilder->createNamedParameter($domain, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_domain.domainName',
                        $queryBuilder->createNamedParameter($domain . '/', \PDO::PARAM_STR)
                    )
                ),
                'pages.deleted=0'
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (!$row) {
            return null;
        }

        return $row['pid'];
    }


    /**
     * For a given page id we find the root page of the corresponding website
     * 
     * @param int $pageUid
     * @return int|null
     */
    public static function getRootPageUidForPage(int $pageUid)
    {
        // Initialize the page-select functions to check rootline:
        $temp_sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $temp_sys_page->init(false);

        $rootline = $temp_sys_page->getRootLine($pageUid);
        if ($rootline) {
            return (int)$rootline[0]['uid'];
        }

        return null;
    }

}
