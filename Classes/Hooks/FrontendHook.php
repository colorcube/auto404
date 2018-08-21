<?php

namespace Colorcube\Auto404\Hooks;


use Colorcube\Auto404\Service\PageRenderingService;
use Colorcube\Auto404\Service\PageService;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 *
 */
class FrontendHook
{
    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $tsfe;


    /**
     * This is the main function called by the hook in the t3 core
     *
     * @param $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe
     */
    public function pageErrorHandler($params, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe)
    {
        $this->log(__METHOD__);

        $this->tsfe = $tsfe;

        // $tsfe->id might already have a different id here than the requested. xxxurl extensions put the id in GET
        $requestedId = (int)GeneralUtility::_GET('id');
        $this->log('Originally requested page is '.$requestedId);

        $this->checkIfWereInALoop();

        $this->checkIfPageExistsOnOtherDomainAndRedirect($requestedId, $tsfe->sys_language_uid);

        $this->showErrorPage($tsfe->sys_language_uid);

        $this->redirectToRootPageOnDomain($tsfe->sys_language_uid);

        $this->log('We\'re out of options and show internal 404 page');
        PageRenderingService::showSimple404PageAndExit();
    }


    /**
     * Shows a simple 404 page when we're here because the redirected page wasn't found
     */
    protected function checkIfWereInALoop()
    {
        $this->log(__METHOD__);

        if (GeneralUtility::_GP('is404')) {
            $this->log('Yes we are in a 404 loop and show internal 404 page');

            PageRenderingService::showSimple404PageAndExit();
        }
    }


    /**
     * If the requested page exists but is on another domain we redirect to that domain and page
     *
     * @param int $pageId
     * @param int $languageUid
     */
    protected function checkIfPageExistsOnOtherDomainAndRedirect(int $pageId, int $languageUid)
    {
        $this->log(__METHOD__);

        $pageRecord = PageService::getPageWithOverlay($pageId, $languageUid);

        if ($pageRecord) {
            $this->log('Yes we found the requested page: '.$pageRecord['uid']);

            $requestedDomain = GeneralUtility::getIndpEnv('HTTP_HOST');
            $this->log('Requested domain: '.$requestedDomain);

            $rootPageUidForRequestedDomain = PageService::getRootPageUidForDomain($requestedDomain);
            $rootPageUidForTargetPage = PageService::getRootPageUidForPage($pageRecord['uid']);

            if ($rootPageUidForRequestedDomain && $rootPageUidForTargetPage && $rootPageUidForRequestedDomain !== $rootPageUidForTargetPage) {
                $this->log('The requested page '.$pageRecord['uid'].' is on another domain');

                $parameter = GeneralUtility::_GET();
                $parameter['is404'] = 1;

                $destinationUrl = $this->generateLinkToOtherDomain($pageRecord['uid'], $parameter);
                if ($destinationUrl) {
                    $this->log('redirect with 301 to  '.$destinationUrl);
                    HttpUtility::redirect($destinationUrl, HttpUtility::HTTP_STATUS_301);
                } else {
                    $this->log('Failing to generate an url for page '.$pageRecord['uid']);
                }
            } else {
                $this->log('Page '.$pageRecord['uid'].' is not on another domain');
            }
        } else {
            $this->log('Page '.$pageId.' not found');
        }
    }


    /**
     * Show the 404 page if there's one as TYPO3 page
     *
     * @param int $languageUid
     */
    protected function showErrorPage(int $languageUid)
    {
        $this->log(__METHOD__);

        $requestedDomain = GeneralUtility::getIndpEnv('HTTP_HOST');
        $this->log('Requested domain: '.$requestedDomain);
        $rootPageUid = PageService::getRootPageUidForDomain($requestedDomain);

        if ($rootPageUid) {
            $this->log('Search below page '.$rootPageUid.' for 404 page with module=\'http404\' or alias=\'http404\'');
            $errorPageRecord = PageService::getPageByAliasWithOverlay('http404', $rootPageUid, $languageUid);

            if ($errorPageRecord) {
                $this->log('404 page found: '.$errorPageRecord['uid']);
                PageRenderingService::renderPageAndExit($errorPageRecord['uid'], $languageUid, $this->tsfe);
            }
        } else {
            $this->log('Failing to find the root page for domain  '.$requestedDomain);
            $this->log('404 page not found');
        }
    }


    /**
     * Let's got to the start/home page if possible
     *
     * @param $languageUid
     */
    protected function redirectToRootPageOnDomain($languageUid)
    {
        $this->log(__METHOD__);

        $requestedDomain = GeneralUtility::getIndpEnv('HTTP_HOST');
        $rootPageUid = PageService::getRootPageUidForDomain($requestedDomain);

        if ($rootPageUid) {
            $destinationUrl = $this->generateLinkToOtherDomain($rootPageUid, ['is404' => 1]);
            $this->log('Redirect with 404 to root page of domain: '.$destinationUrl);
            HttpUtility::redirect($destinationUrl, HttpUtility::HTTP_STATUS_404);
        } else {
            $this->log('Failing to find the root page for domain  '.$requestedDomain);
        }
    }


    /**
     * Generates an url which might be on another domain
     *
     * @param int $pageUid
     * @param array $parameter
     * @return string
     */
    protected function generateLinkToOtherDomain(int $pageUid, array $parameter)
    {
        $this->log(__METHOD__);

        unset($parameter['id']);

        $conf = [];
        $conf['parameter'] = $pageUid . ',' . $this->tsfe->type;
        if ($parameter) {
            $conf['additionalParams'] .= GeneralUtility::implodeArrayForUrl('', $parameter);
        }
        $conf['forceAbsoluteUrl'] = true;

        $this->tsfe->config['config']['typolinkEnableLinksAcrossDomains'] = true;
        $this->tsfe->config['config']['typolinkCheckRootline'] = true;
        $this->tsfe->config['config']['absRefPrefix'] = '/';

        if (!$this->tsfe->tmpl) {
            $this->tsfe->initTemplate();
        }

        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $cObj->typoLink_URL($conf);
    }


    /**
     * well ... logging
     *
     * @param $msg
     */
    protected function log($msg)
    {
        # error_log($msg);
    }
}

