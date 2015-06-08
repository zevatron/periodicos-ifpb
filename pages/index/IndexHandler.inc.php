<?php

/**
 * @file pages/index/IndexHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndexHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('classes.handler.Handler');

class IndexHandler extends Handler {
	/**
	 * Constructor
	 **/
	function IndexHandler() {
		parent::Handler();
	}

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$router =& $request->getRouter();
		$templateMgr =& TemplateManager::getManager();
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalPath = $router->getRequestedContextPath($request);
		$templateMgr->assign('helpTopicId', 'user.home');
		$journal =& $router->getContext($request);
		if ($journal) {
			// Assign header and content for home page
			$templateMgr->assign('displayPageHeaderTitle', $journal->getLocalizedPageHeaderTitle(true));
			$templateMgr->assign('displayPageHeaderLogo', $journal->getLocalizedPageHeaderLogo(true));
			$templateMgr->assign('displayPageHeaderTitleAltText', $journal->getLocalizedSetting('homeHeaderTitleImageAltText'));
			$templateMgr->assign('displayPageHeaderLogoAltText', $journal->getLocalizedSetting('homeHeaderLogoImageAltText'));
			$templateMgr->assign('additionalHomeContent', $journal->getLocalizedSetting('additionalHomeContent'));
			$templateMgr->assign('homepageImage', $journal->getLocalizedSetting('homepageImage'));
			$templateMgr->assign('homepageImageAltText', $journal->getLocalizedSetting('homepageImageAltText'));
			$templateMgr->assign('journalDescription', $journal->getLocalizedSetting('description'));

			$displayCurrentIssue = $journal->getSetting('displayCurrentIssue');
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue =& $issueDao->getCurrentIssue($journal->getId(), true);
			if ($displayCurrentIssue && isset($issue)) {
				import('pages.issue.IssueHandler');
				// The current issue TOC/cover page should be displayed below the custom home page.
				IssueHandler::_setupIssueTemplate($request, $issue);
			}

			$enableAnnouncements = $journal->getSetting('enableAnnouncements');
			if ($enableAnnouncements) {
				$enableAnnouncementsHomepage = $journal->getSetting('enableAnnouncementsHomepage');
				if ($enableAnnouncementsHomepage) {
					$numAnnouncementsHomepage = $journal->getSetting('numAnnouncementsHomepage');
					$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
					$announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $numAnnouncementsHomepage);
					$templateMgr->assign('announcements', $announcements);
					$templateMgr->assign('enableAnnouncementsHomepage', $enableAnnouncementsHomepage);
				}
			}
			$templateMgr->display('index/journal.tpl');
		} else {
			$site =& Request::getSite();

			if ($site->getRedirect() && ($journal = $journalDao->getById($site->getRedirect())) != null) {
				$request->redirect($journal->getPath());
			}

			$templateMgr->assign('intro', $site->getLocalizedIntro());
			$templateMgr->assign('journalFilesPath', $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/');

			// If we're using paging, fetch the parameters
			$usePaging = $site->getSetting('usePaging');
			if ($usePaging) $rangeInfo =& $this->getRangeInfo('journals');
			else $rangeInfo = null;
			$templateMgr->assign('usePaging', $usePaging);

			// Fetch the alpha list parameters
			$searchInitial = Request::getUserVar('searchInitial');
			$templateMgr->assign('searchInitial', $searchInitial);
			$templateMgr->assign('useAlphalist', $site->getSetting('useAlphalist'));

			$journals =& $journalDao->getJournals(
				true,
				$rangeInfo,
				$searchInitial?JOURNAL_FIELD_TITLE:JOURNAL_FIELD_SEQUENCE,
				$searchInitial?JOURNAL_FIELD_TITLE:null,
				$searchInitial?'startsWith':null,
				$searchInitial
			);
			$revistas =& $journalDao->getJournals(
				true,
				$rangeInfo,
				$searchInitial?JOURNAL_FIELD_TITLE:JOURNAL_FIELD_SEQUENCE,
				$searchInitial?JOURNAL_FIELD_TITLE:null,
				$searchInitial?'startsWith':null,
				$searchInitial
			);


			//Como as sessões não são globais, foi necessário colocar 
			//Esse trecho de código que permite verificar as sessões 
			//E passar a URL de login para o formulário da Index (Custom)
			if (!defined('SESSION_DISABLE_INIT')) {
				$session =& Request::getSession();
				$templateMgr->assign_by_ref('userSession', $session);
				$templateMgr->assign('loggedInUsername', $session->getSessionVar('username'));
				$loginUrl = Request::url(null, 'login', 'signIn');
				if (Config::getVar('security', 'force_login_ssl')) {
					$loginUrl = String::regexp_replace('/^http:/', 'https:', $loginUrl);
				}
				$templateMgr->assign('userBlockLoginUrl', $loginUrl);
			}

			$templateMgr->assign_by_ref('journals', $journals);
			$templateMgr->assign_by_ref('revistas', $revistas);
			$templateMgr->assign_by_ref('site', $site);

			$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));

			// $templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			// $templateMgr->display('index/site.tpl');
			$templateMgr->display('portalpadrao/layout.tpl');
			$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
			// $announcements =& $announcementDao->getById(2);
//			$announcements =& $announcementDao->getAll(1);
			// echo "<pre>", print_r($announcements->_data["id"]);
			// echo "====";
			// echo "<pre>", print_r(isSet($announcements));
			// echo "<pre>", print_r($announcements->records->fields);

			// $announcements =& $announcementDao->getNumAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, 2, 4);
			// $templateMgr->assign('announcements', $announcements);
			// $templateMgr->display('portalpadrao/test.tpl');
			// echo "<pre>", print_r($announcements);

			// $announcements =& $announcementDao->getAll();
			// $templateMgr->assign('announcements', $announcements);
			// $templateMgr->display('portalpadrao/test.tpl');

		}
	}
}

?>
