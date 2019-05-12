<?php
declare(strict_types=1);

namespace Colorcube\Auto404\Service;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageGenerator;

abstract class PageRenderingService
{

    /**
     * Start TSFE page rendering with new page id and exit
     *
     * This is not so sweet as we have to put most of the tsfe rendering code here.
     * We do this because then we don't need a redirect then.
     *
     * @param int $pageUid
     * @param int $languageUid
     * @param null $tsfe
     */
    public static function renderPageAndExit(int $pageUid, int $languageUid, $tsfe=null)
    {
        $tsfe = $tsfe ?: $GLOBALS['TSFE'];

        // we're messing with TSFE here, but hey we're exit anyway

        // we don't want to go in a 404 loop
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = '';

        // override the requested page
        $tsfe->id = $pageUid;
        $tsfe->type = 0;

        // let's start again

        $tsfe->fetch_the_id();

        $tsfe->id = ($tsfe->contentPid = (int)$tsfe->id);


        // this is from RequestHandler
        // would be nice to a have a function for that in the core ...


        // Starts the template
        # $this->timeTracker->push('Start Template', '');
        $tsfe->initTemplate();
        # $this->timeTracker->pull();
        // Get from cache
        # $this->timeTracker->push('Get Page from cache', '');
        $tsfe->getFromCache();
        # $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $tsfe->getConfigArray();
        // Setting language and locale
        # $this->timeTracker->push('Setting language and locale', '');
        $tsfe->settingLanguage();
        $tsfe->settingLocale();
        # $this->timeTracker->pull();

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        $tsfe->convPOSTCharset();

        $tsfe->initializeRedirectUrlHandlers();

        $tsfe->handleDataSubmission();

        // Check for shortcut page and redirect
        $tsfe->checkPageForShortcutRedirect();
        $tsfe->checkPageForMountpointRedirect();

        // Generate page
        $tsfe->setUrlIdToken();
        # $this->timeTracker->push('Page generation', '');
        if ($tsfe->isGeneratePage()) {
            $tsfe->generatePage_preProcessing();
            $temp_theScript = $tsfe->generatePage_whichScript();
            if ($temp_theScript) {
                include $temp_theScript;
            } else {
                $tsfe->preparePageContentGeneration();
                // Content generation
                if (!$tsfe->isINTincScript()) {
                    PageGenerator::renderContent();
                    $tsfe->setAbsRefPrefix();
                }
            }
            $tsfe->generatePage_postProcessing();
        } elseif ($tsfe->isINTincScript()) {
            $tsfe->preparePageContentGeneration();
        }
        $tsfe->releaseLocks();
        # $this->timeTracker->pull();

        // Render non-cached parts
        if ($tsfe->isINTincScript()) {
            # $this->timeTracker->push('Non-cached objects', '');
            $tsfe->INTincScript();
            # $this->timeTracker->pull();
        }

        // Output content
        $sendTSFEContent = false;
        if ($tsfe->isOutputting()) {
            # $this->timeTracker->push('Print Content', '');
            $tsfe->processOutput();
            $sendTSFEContent = true;
            # $this->timeTracker->pull();
        }
        // Store session data for fe_users
        $tsfe->storeSessionData();
        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);

        // Hook for end-of-frontend
        $tsfe->hook_eofe();
        // Finish timetracking
        # $this->timeTracker->pull();

        echo $tsfe->content;
        exit();

    }


    /**
     * Shows an 404 error page and exists
     *
     * @param string|null $homePageUrl
     */
    public static function showSimple404PageAndExit($homePageUrl = null)
    {
        if (!$homePageUrl) {
            $requestedDomain = GeneralUtility::getIndpEnv('TYPO3_HOST');
            $homePageUrl = (GeneralUtility::getIndpEnv('TYPO3_SSL') ? "https" : "http") . "://" . $requestedDomain . "?is404=1";
        }

        $homePageUrl = htmlspecialchars($homePageUrl);

        die ('<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Simple HttpErrorPages | MIT X11 License | https://github.com/AndiDittrich/HttpErrorPages -->

    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <title>We\'ve got some trouble | 404 - Page not found</title>

    <style type="text/css">/*! normalize.css v5.0.0 | MIT License | github.com/necolas/normalize.css */html{font-family:sans-serif;line-height:1.15;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0}article,aside,footer,header,nav,section{display:block}h1{font-size:2em;margin:.67em 0}figcaption,figure,main{display:block}figure{margin:1em 40px}hr{box-sizing:content-box;height:0;overflow:visible}pre{font-family:monospace,monospace;font-size:1em}a{background-color:transparent;-webkit-text-decoration-skip:objects}a:active,a:hover{outline-width:0}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}b,strong{font-weight:inherit}b,strong{font-weight:bolder}code,kbd,samp{font-family:monospace,monospace;font-size:1em}dfn{font-style:italic}mark{background-color:#ff0;color:#000}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}audio,video{display:inline-block}audio:not([controls]){display:none;height:0}img{border-style:none}svg:not(:root){overflow:hidden}button,input,optgroup,select,textarea{font-family:sans-serif;font-size:100%;line-height:1.15;margin:0}button,input{overflow:visible}button,select{text-transform:none}[type=reset],[type=submit],button,html [type=button]{-webkit-appearance:button}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring,button:-moz-focusring{outline:1px dotted ButtonText}fieldset{border:1px solid silver;margin:0 2px;padding:.35em .625em .75em}legend{box-sizing:border-box;color:inherit;display:table;max-width:100%;padding:0;white-space:normal}progress{display:inline-block;vertical-align:baseline}textarea{overflow:auto}[type=checkbox],[type=radio]{box-sizing:border-box;padding:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-cancel-button,[type=search]::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}details,menu{display:block}summary{display:list-item}canvas{display:inline-block}template{display:none}[hidden]{display:none}/*! Simple HttpErrorPages | MIT X11 License | https://github.com/AndiDittrich/HttpErrorPages */body,html{width:100%;height:100%;background-color:#21232a}body{color:#fff;text-align:center;text-shadow:0 2px 4px rgba(0,0,0,.5);padding:0;min-height:100%;-webkit-box-shadow:inset 0 0 75pt rgba(0,0,0,.8);box-shadow:inset 0 0 75pt rgba(0,0,0,.8);display:table;font-family:"Open Sans",Arial,sans-serif}h1{font-family:inherit;font-weight:500;line-height:1.1;color:inherit;font-size:36px}h1 small{font-size:68%;font-weight:400;line-height:1;color:#777}a{text-decoration:none;color:#fff;font-size:inherit;border-bottom:dotted 1px #707070}.lead{color:silver;font-size:21px;line-height:1.4}.cover{display:table-cell;vertical-align:middle;padding:0 20px}footer{position:fixed;width:100%;height:40px;left:0;bottom:0;color:#a0a0a0;font-size:14px}</style>
</head>

<body>
    <div class="cover">
        <h1>Resource not found <small>Error 404</small></h1>
        <p class="lead">The requested page could not be found.</p>
        <p class="lead">Go to the <a href="'.htmlspecialchars($homePageUrl).'">home page</a>.</p>
    </div>
</body>
</html>

        ');
    }
}
