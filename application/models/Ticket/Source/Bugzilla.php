<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Query Bugzilla as ticket-source
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Ticket_Source_Bugzilla extends Model_Ticket_AbstractSource {

    /**
     * Bugzilla-constants
     *
     * @var string
     */
    const BUG_LIST = 'buglist.cgi';

    const BUG_SHOW = 'show_bug.cgi';

    const BUG_SUMMARY = 'summarize_time.cgi';

    const BUG_PARAM_PRODUCT = 'product';

    const BUG_PARAM_COMPONENT = 'component';

    const BUG_PARAM_STATUS = 'bug_status';

    const BUG_PARAM_RESOLUTION = 'resolution';

    const BUG_PARAM_VERSION = 'version';

    const BUG_PARAM_CTYPE = 'ctype';

    const BUG_PARAM_EXCLUDEFIELD = 'excludefield';

    const BUG_PARAM_ASSIGNEE = 'email';

    const BUG_PARAM_CMDTYPE = 'cmdtype';

    const BUG_PARAM_REMACTION = 'remaction';

    const BUG_PARAM_NAMEDCMD = 'namedcmd';

    const BUG_PARAM_SHARER = 'sharer_id';

    const BUG_PARAM_DETAILED = 'detailed';

    const BUG_PARAM_GROUP = 'group_by';

    const BUG_PARAM_REPORT = 'do_report';

    const BUG_PARAM_BUG_IDS = 'id';

    const BUG_PARAM_START_DATE = 'start_date';

    const BUG_PARAM_END_DATE = 'end_date';

    const BUG_PARAM_CHANGE_DATE_FROM = 'chfieldfrom';

    const BUG_PARAM_CHANGE_DATE_TO = 'chfieldto';

    private $_sCookie = null;

    private $_config = null;

    private $_client = null;

    private $_product = array();

    private $_user = array();

    private $_openBugs = null;

    private $_fixedBugs = null;

    private $_aFixedTrunk = array();

    private $_branchRelations = array();

    private $_summary = null;

    private $_allBugs = array();

    private $_aThemes = array();

    private $_aUnthemed = array();

    private $_getParameter = "";

    /**
     * Cache for bug-list requests
     *
     * @var array
     */
    private $_aBugsListCache;

    /**
     * Stats
     *
     * @var array
     */
    private $_aStats = array();

    /**
     * All available Bugzilla statuses
     *
     * @var array
    */
    private $_aStatuses = array();

    /**
     * The cache-instance
     *
     * @var Zend_Cache_Core
    */
    protected $_oCache = null;

    /**
     * Count for bugzilla-requests
     */
    protected $_iCount = 0;

    /**
     * The known team-members
     *
     * @var array
     */
    protected $_aTeam = array();

    /**
     * Cache for _findTheme
     *
     * @var array
    */
    protected $_aFindThemeCache = array();

    /**
     * Do all Bug-related action
    */
    public function __construct($bFilterProductConfig = true) {
        $this->_config = Zend_Registry::get('_Config')->model->bugzilla;
        $this->_client = new Zend_Http_Client();
        $this->_client->setEncType(Zend_Http_Client::ENC_FORMDATA);
        $this->_sCookie = '/tmp/cookieBugzilla';
        if (isset($this->_config->http->cookiePath) === true) {
            $this->_sCookie = $this->_config->http->cookiePath . 'cookieBugzilla';
        }

        @unlink($this->_sCookie);
        $this->_client->setConfig(array(
            'timeout' => 120,
            'adapter' => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(
                CURLOPT_COOKIEFILE => $this->_sCookie,
                CURLOPT_COOKIEJAR => $this->_sCookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            )
        ));

        $this->_oCache = Zend_Registry::get('_Cache');

        $this->user($this->_config->login);
        if ($bFilterProductConfig === true) {
            $aPortals = Zend_Registry::get('_Config')->model->bugzilla->portal;
            foreach ($aPortals as $portal) {
                $onePortal = array(
                    'name' => $portal->name,
                    'value' => urlencode(serialize($portal->toArray()))
                );

                $this->product($portal->name);
            }
        }

        $this->_aTeam = Zend_Registry::get('_Config')->model->bugzilla->team->toArray();
        $this->getBugsChangedToday();
    }

    /**
     * Set the bugzilla-results to the view
     *
     * @param  Zend_View $oView
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function setView(Zend_View $oView) {
        $oView->bugsReopened = $this->getReopendBugs();
        $oView->bugsTestserver = $this->getUpdateTestserver();
        $oView->bugsBranch = $this->getFixedBugsInBranch();
        $oView->bugsTrunk = $this->getFixedBugsInTrunk();
        $oView->bugsFixed = $this->getFixedBugsUnknown();
        $oView->bugsOpen = $this->getThemedOpenBugs();
        $oView->bugsUnthemed = $this->getUnthemedBugs();
        $oView->aMemberBugs = $this->getMemberBugs();
        $oView->aTeamBugs = $this->getTeamBugs($oView->aMemberBugs);

        $oView->iTotal = $this->getCount();
        $oView->aStats = $this->getStats();
        $oView->aStatuses = $this->getStatuses();
        $oView->aPriorities = $this->getPriorities();
        $oView->sChuck = $this->getChuckStatus();

        return $this;
    }

    /**
     * Get the chuck-status
     *
     * @return string
     */
    public function getChuckStatus() {
        $sStatus = Model_Chuck::OK;
        if ($this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_INPROGRESS]['num'] === 0) {
            $this->getStats();
            $this->getStatuses();
        }

        if ($this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_UNESTIMATED]['per'] > 10) {
            $sStatus = Model_Chuck::WARN;
        }
        elseif ($this->_aStatuses[Model_Ticket_Type_Bug::STATUS_UNCONFIRMED]['per'] > 10) {
            $sStatus = Model_Chuck::WARN;
        }

        if ($this->_aStatuses[Model_Ticket_Type_Bug::STATUS_REOPENED]['num'] > 1) {
            $sStatus = Model_Chuck::ERROR;
        }
        elseif ($this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_FAILED]['per'] > 2) {
            $sStatus = Model_Chuck::ERROR;
        }
        elseif ($this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_UNESTIMATED]['per'] > 15) {
            $sStatus = Model_Chuck::WARN;
        }
        elseif ($this->_aStatuses[Model_Ticket_Type_Bug::STATUS_UNCONFIRMED]['per'] > 15) {
            $sStatus = Model_Chuck::WARN;
        }

        return $sStatus;
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getPriorities() {
        $aPriorities = array(
            'P1' => 0,
            'P2' => 0,
            'P3' => 0,
            'P4' => 0,
            'P5' => 0,
        );

        $iCount = count($this->_allBugs);
        foreach ($this->_allBugs as $oBug) {
            $aPriorities[(string) $oBug->priority]++;
        }

        $mStat = null;
        foreach ($aPriorities as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => ($iCount > 0) ? round(($mStat / $iCount) * 100, 2) : 0
            );
        }

        unset($mStat);
        return $aPriorities;
    }

    /**
     * Get the number of bugs
     *
     * @return int
     */
    public function getCount() {
        return count($this->_allBugs);
    }

    /**
     * Get the bug-stats
     *
     * @return array
     */
    public function getStats() {
        $iCount = count($this->_allBugs);
        $this->_aStats = array(
            Model_Ticket_Type_Bug::WORKFLOW_ESTIMATED => 0,
            Model_Ticket_Type_Bug::WORKFLOW_ORGA => 0,
            Model_Ticket_Type_Bug::WORKFLOW_UNESTIMATED => 0,
            Model_Ticket_Type_Bug::WORKFLOW_INPROGRESS => 0,
            Model_Ticket_Type_Bug::WORKFLOW_ACTIVE => 0,
            Model_Ticket_Type_Bug::WORKFLOW_TESTING => 0,
            Model_Ticket_Type_Bug::WORKFLOW_MERGE => 0,
            Model_Ticket_Type_Bug::WORKFLOW_DEADLINE => 0,
            Model_Ticket_Type_Bug::WORKFLOW_SCREEN => 0,
            Model_Ticket_Type_Bug::WORKFLOW_COMMENT => 0,
            Model_Ticket_Type_Bug::WORKFLOW_FAILED => 0,
            Model_Ticket_Type_Bug::WORKFLOW_QUICK => 0,
            Model_Ticket_Type_Bug::WORKFLOW_TRANSLATION => 0,
        );
        foreach ($this->_allBugs as $oBug) {
            $bShouldHaveEstimation = true;
            if ($oBug->isOrga()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_ORGA]++;
                $bShouldHaveEstimation = false;
            }

            if ($oBug->isEstimated()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_ESTIMATED]++;
            }
            elseif ($bShouldHaveEstimation === true) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_UNESTIMATED]++;
            }

            if ($oBug->isWorkedOn()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_INPROGRESS]++;
            }

            if ($oBug->isActive()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_ACTIVE]++;
            }

            if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING,'?')) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_TESTING]++;
            }

            if ($oBug->isFailed()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_FAILED]++;
            }

            if ($oBug->isMergeable()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_MERGE]++;
            }

            if ($oBug->deadlineStatus()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_DEADLINE]++;
            }

            if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '?')) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_SCREEN]++;
            }

            if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_COMMENT, '?')) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_COMMENT]++;
            }

            if ($oBug->isQuickOne()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_QUICK]++;
            }

            if ($oBug->isOnlyTranslation()) {
                $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_TRANSLATION]++;
            }
        }

        $mStat = null;
        foreach ($this->_aStats as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => ($iCount > 0) ? round(($mStat / $iCount) * 100, 2) : 0
            );
        }

        unset($mStat);
        return $this->_aStats;
    }

    /**
     * Get a summary-overview
     *
     * @param  string $sDate
     *
     * @return array
     */
    public function getSummary($sDate) {
        if ($this->_summary) {
            return $this->_summary;
        }

        $this->_summary = array();
        $this->_setGetParameter(self::BUG_PARAM_CMDTYPE, 'runnamed');
        $this->_setGetParameter(self::BUG_PARAM_NAMEDCMD, 'wochenbericht_fluege');
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);

        $this->_setGetParameter(self::BUG_PARAM_CTYPE, 'html');
        $this->_setGetParameter(self::BUG_PARAM_DETAILED, 'on');
        $this->_setGetParameter(self::BUG_PARAM_REPORT, '1');
        $this->_setGetParameter(self::BUG_PARAM_GROUP, 'owner');
        $this->_setGetParameter(self::BUG_PARAM_BUG_IDS, implode(',', $bugIds));

        $this->_setGetParameter(self::BUG_PARAM_START_DATE, $sDate);
        $this->_setGetParameter(self::BUG_PARAM_END_DATE, $sDate);
        $page = $this->_request(self::BUG_SUMMARY);
        $oDom = new Zend_Dom_Query($page);
        $oTables = $oDom->query('table.owner tr');
        foreach ($oTables as $oTable) {
            $oDocument = new DomDocument();
            $oDocument->appendChild($oDocument->importNode($oTable, true));
            $sContent = trim(preg_replace(array(
                '/(width="\d+%?")/',
                '/href="show_bug/'
            ), array(
                '',
                'href="http://bugzilla.unister-gmbh.de/show_bug'
            ), $oDocument->saveHTML()));
            if (strlen($sContent) > 0) {
                $this->_summary[] = $sContent;
            }

            unset($sContent, $oDocument);
        }

        return $this->_summary;
    }

    /**
     * Add a product
     *
     * @param  string $sProduct
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function product($sProduct = '') {
        if ($sProduct == '') {
            return $this;
        }

        $this->_product[] = $sProduct;
        $this->_product = array_unique($this->_product);
        return $this;
    }

    /**
     * Add a user
     *
     * @param  string $sUser
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function user($sUser = '') {
        if ($sUser == '') {
            return $this;
        }

        $this->_user[] = $sUser;
        $this->_user = array_unique($this->_user);
        return $this;
    }

    /**
     * Add static params
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    private function _addParams() {
        $this->_resetAllParameter();
        foreach ($this->_product as $product) {
            $this->_setGetParameter(self::BUG_PARAM_PRODUCT, $product);
        }

        $i = 1;
        foreach ($this->_user as $user) {
            $this->_setGetParameter(self::BUG_PARAM_ASSIGNEE . $i, $user);
            $this->_setGetParameter('emailassigned_to' . $i, 1);
            $this->_setGetParameter('emailreporter' . $i, 1);
            $this->_setGetParameter('emailtype' . $i, 'exact');
            $i++;
        }

        return $this;
    }

    /**
     * Login to bugzilla
     *
     * @return void
     */
    private function _loginToBugzilla() {
        if (!Zend_Registry::isRegistered('_login') || !Zend_Registry::isRegistered('_password')) {
            throw new Zend_Exception('no password or login set for Bugzilla');
        }

        if (file_exists($this->_sCookie) !== true) {
            $this->_client->setMethod(Zend_Http_Client::POST);
            $this->_client->setParameterPost(array(
                'Bugzilla_login' => Zend_Registry::get('_login'),
                'Bugzilla_password' => Zend_Registry::get('_password'),
                'GoAheadAndLogIn' => 'Log in',
                'Bugzilla_restrictlogin' => true
            ));
        }
    }

    /**
     * Execute a search
     *
     * @param  array $params
     *
     * @return <type>
     */
    private function _search($params) {
        $idQueryString = "";
        foreach ($params as $key => $value) {
            $idQueryString .= urlencode($key) . "=" . urlencode($value) . "&";
        }

        $idQueryString = rtrim($idQueryString, "&");
        echo $idQueryString;
        return $this->_request(self::BUG_LIST . "?$idQueryString");
    }

    /**
     *
     * @param <type> $key
     * @param <type> $value
     */
    private function _setGetParameter($key, $value) {
        if (is_array($value) === true) {
            foreach ($value as $v) {
                $this->_setGetParameter($key, $v);
            }
        }
        else {
            $this->_getParameter .= urlencode($key) . "=" . urlencode($value) . "&";
        }
    }

    /**
     *
     * @param <type> $option
     * @return <type>
     */
    private function _request($option) {
        $this->_iCount++;

        $queryString = rtrim($this->_getParameter, "&");
        $this->_client->setUri($this->_config->baseUrl . "/" . $option . "?$queryString");
        $this->_loginToBugzilla();
        $return = $this->_client->request()->getBody();
        $this->_resetAllParameter();

        return $return;
    }

    /**
     *
     */
    private function _resetAllParameter() {
        $this->_client->resetParameters(true);
        $this->_getParameter = "";
    }

    /**
     *
     * @param <type> $page
     * @return <type>
     */
    private function _getBugIdsFromPage($page) {
        $matches = array();
        preg_match_all('#<form method="post" action="show_bug\.cgi">\s*<input type="hidden" name="ctype" value="xml">\s*(.*?)\s*</form>#s', $page, $matches);
        if (empty($matches[1]) === true) {
            return array();
        }

        $hiddenFields = $matches[1][0];
        preg_match_all('#<input type="hidden" name="id" value="([0-9]+)">#s', $hiddenFields, $matches);
        return $matches[1];
    }

    /**
     * Create the hash of a bug
     *
     * @param  int $iBugId
     *
     * @return string
     */
    protected function _getBugHash($iBugId) {
        return md5(date('dm') . (string) $iBugId);
    }

    /**
     * Request bug-detail from bugzilla
     *
     * @param  array $aBugIds
     * @param  boolean $bCache
     *
     * @return array<Model_Ticket_Type_Bug>
     */
    private function _getXmlFromBugIds(array $aBugIds, $bCache = true) {
        $aReturn = $aTemp = $aRequest = array();
        foreach ($aBugIds as $iBugId) {
            $oBug = $this->_oCache->load($this->_getBugHash($iBugId));
            if ($oBug instanceof Model_Ticket_AbstractType and $bCache === true) {
                $aTemp[] = $oBug;
            }
            else {
                $aRequest[] = $iBugId;
            }
        }

        if (empty($aRequest) !== true) {
            $this->_setGetParameter(self::BUG_PARAM_CTYPE, 'xml');
            $this->_setGetParameter(self::BUG_PARAM_EXCLUDEFIELD, 'attachmentdata');
            $this->_setGetParameter('id', $aRequest);
            $sResponse = $this->_request(self::BUG_SHOW);
            $sResponse = preg_replace('!<div style=".*?">BugZilla.*?</div>!i', '', $sResponse);

            if (strpos($sResponse, '<title>Invalid Username Or Password</title>') === false) {
                $xml = simplexml_load_string($sResponse);
                foreach ($xml as $bug) {
                    $oBug = new Model_Ticket_Type_Bug($bug);
                    $aTemp[] = $oBug;
                }

                unset($xml);
            }
            else {
                Zend_Debug::dump($sResponse, 'Duration:' . (microtime(true) - STARTTIME) .  ' - ' . __FILE__ . ':' . __LINE__ . PHP_EOL);
                exit;
            }

            unset($sResponse);
        }

        $aConfig = $this->_config->portal->toArray();
        foreach ($aTemp as $oBug) {
            $bAdd = true;
            foreach ($aConfig as $aProduct) {
                if (strtolower($aProduct['name']) === strtolower($oBug->product)) {
                    if (isset($aProduct['theme'])) {
                        $bAdd = false;
                        $aBlocked = $oBug->blocks();
                        if (is_array($aBlocked) === true) {
                            foreach ($aBlocked as $sBlocks) {
                                if ($aProduct['theme'] === $sBlocks) {
                                    $bAdd = true;
                                }
                            }
                        }
                    }
                    elseif (isset($aProduct['exclude_components']) === true) {
                        if (in_array($oBug->component, $aProduct['exclude_components']) === true) {
                            $bAdd = false;
                        }
                    }
                }
            }

            if ($bAdd === true) {
                $aReturn[$oBug->id()] = $oBug;
                if ($oBug->isClosed() !== true and $oBug->isTheme() !== true) {
                    $this->_allBugs[$oBug->id()] = $oBug;
                }
                elseif ($oBug->isTheme() === true) {
                    $this->_aThemes[$oBug->id()] = $oBug;
                }
            }

            $this->_oCache->save($oBug, $this->_getBugHash($oBug->id()));
        }

        ksort($aReturn);
        return $aReturn;
    }

    /**
     * Get all bugs
     *
     * @return array
     */
    public function getAllBugs() {
        return $this->_allBugs;
    }

    /**
     *
     * @return <type>
     */
    public function getOpenBugs() {
        if ($this->_openBugs) {
            return $this->_openBugs;
        }

        $this->_addParams();
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_REOPENED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_UNCONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_CONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_NEW);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_ASSIGNED);
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);
        $this->_openBugs = $this->getBugListByIds($bugIds);
        return $this->_openBugs;
    }

    /**
     *
     * @return <type>
     */
    public function getFixedBugs() {
        if (empty($this->_fixedBugs) !== true) {
            return $this->_fixedBugs;
        }

        $this->_addParams();
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_VERIFIED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_RESOLVED);
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);
        $this->_fixedBugs = $this->getBugListByIds($bugIds);

        return $this->_fixedBugs;
    }

    /**
     * Get a list of bugs by id
     *
     * @param  array|string $aIds
     * @param  boolean $bCache Allow cache
     *
     * @return array
     */
    public function getBugListByIds($mIds, $bCache = true) {
        $aIds = $mIds;
        if (is_array($aIds) === false) {
            $aIds = explode(',', (string) $aIds);
        }

        sort($aIds);
        $sHash = md5(serialize($aIds));
        if (empty($this->_aBugsListCache[$sHash]) === true) {
            $this->_aBugsListCache[$sHash] = $this->_getXmlFromBugIds($aIds, $bCache);
        }

        return $this->_aBugsListCache[$sHash];
    }

    /**
     * Refresh the cached bugs, which have been changed today
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function getBugsChangedToday() {
        $this->_addParams();
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_REOPENED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_UNCONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_CONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_NEW);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_ASSIGNED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_VERIFIED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_RESOLVED);
        $this->_setGetParameter(self::BUG_PARAM_CHANGE_DATE_FROM, '0d');
        $this->_setGetParameter(self::BUG_PARAM_CHANGE_DATE_TO, 'Now');
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);
        $bugs = $this->getBugListByIds($bugIds, false);
        unset($page, $bugIds, $bugs);

        return $this;
    }

    /**
     *
     * @return <type>
     */
    public function getReopendBugs() {
        $this->_addParams();
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_REOPENED);
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);
        $bugs = $this->getBugListByIds($bugIds);
        return ($bugs) ? $bugs : array();
    }

    /**
     *
     * @return <type>
     */
    public function getFixedBugsInBranch() {
        $fixedBugs = $this->getFixedBugs();
        $back = array();
        foreach ($fixedBugs as $bug) {
            if ($bug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '?') or ($bug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '+') !== true and $bug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE, '?'))) {
                $back[$bug->id()] = $bug;
            }
        }

        ksort($back);
        return $back;
    }

    /**
     * Get the themes
     *
     * @return array
     */
    public function getThemes() {
        return $this->_aThemes;
    }


    /**
     * Get the themes as stack
     *
     * @return array
     */
    public function getThemesAsStack() {
        $aStack = array();
        foreach ($this->_aThemes as $oTheme) {
            $aStack[$oTheme->id()] = (string) $oTheme->short_desc;
        }

        return $aStack;
    }

    /**
     *
     * @return <type>
     */
    public function getUpdateTestserver() {
        $aBugs = array_merge($this->getFixedBugs(), $this->getOpenBugs());
        $back = array();
        foreach ($aBugs as $bug) {
            if ($bug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTSERVER, '?')) {
                $back[$bug->id()] = $bug;
            }
        }

        ksort($back);
        return $back;
    }

    /**
     *
     * @return <type>
     */
    public function getFixedBugsInTrunk() {
        if (empty($this->_aFixedTrunk) !== true) {
            return $this->_aFixedTrunk;
        }

        $fixedBugs = $this->getFixedBugs();
        $back = array();
        foreach ($fixedBugs as $bug) {
            if ($bug->isMerged()) {
                $back[$bug->id()] = $bug;
            }
            elseif($bug->couldBeInTrunk() === true) {
                $aBlocked = $this->getBugListByIds($bug->blocks());
                $bTrunk = true;
                foreach ($aBlocked as $oBlocked) {
                    if (($oBlocked->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '+') !== true or $oBlocked->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '?') === true or $bug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '?') === true)
                        and $oBlocked->isClosed() !== true and $oBlocked->isTheme() !== true and $oBlocked->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN) !== true) {
                        $bTrunk = false;
                    }
                }

                if ($bTrunk === true) {
                    $back[$bug->id()] = $bug;
                }
            }
        }

        ksort($back);
        $this->_aFixedTrunk = $back;
        return $back;
    }

    /**
     *
     * @return <type>
     */
    public function getFixedBugsUnknown() {
        $fixedBugs = $this->getFixedBugs();
        $back = array();
        foreach ($fixedBugs as $bug) {
            if ($bug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE) === false and $bug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTSERVER, '?') === false and isset($this->_aFixedTrunk[$bug->id()]) === false) {
                $back[$bug->id()] = $bug;
            }
        }

        ksort($back);
        return $back;
    }

    /**
     * Get all bugs of known members
     *
     * @param  array $aMemberBugs
     *
     * @return array
     */
    public function getTeamBugs($aMemberBugs) {
        $aTeam = array();
        foreach ($aMemberBugs as $sName => $aBugs) {
            foreach ($this->_aTeam as $sTeamName) {
                if (stripos($sName, $sTeamName) !== false) {
                    $aTeam[$sName] = $aBugs;
                    break;
                }
            }
        }

        return $aTeam;
    }

    /**
     * Get all bugs per member
     *
     * @return array
     */
    public function getMemberBugs() {
        $aOpenBugs = $this->getOpenBugs();
        $aMember = array();
        foreach ($aOpenBugs as $oBug) {
            if ($oBug->isTheme() !== true) {
                $sName = (string) $oBug->assignee_name;
                $aMember[$sName][] = $oBug;
            }
        }

        ksort($aMember);
        return $aMember;
    }

    /**
     * Get all unthemed bugs
     *
     * @return array
     */
    public function getUnthemedBugs() {
        return $this->_aUnthemed;
    }

    /**
     *
     * @return <type>
     */
    public function getThemedOpenBugs() {
        $openBugs = $this->getOpenBugs();
        $aOpen = array();
        foreach ($openBugs as $bug) {
            if ($bug->doesBlock() === true and $bug->isTheme() !== true) {
                $aOpen[$bug->id()] = $bug;
            }
        }

        ksort($aOpen);
        return $this->_findUnthemedBugs()->_sortBugsToThemes($aOpen);
    }

    /**
     *
     * @param <type> $aBugs
     * @return <type>
     */
    private function _sortBugsToThemes($aBugs) {
        $aThemes = array();
        foreach ($aBugs as $bug) {
            $aThemes = array_merge($aThemes, $bug->blocks());
        }

        $aThemeBugs = $this->getBugListByIds(array_unique($aThemes));
        $aThemes = array();
        foreach ($aThemeBugs as $oTheme) {
            if ($oTheme->isTheme()) {
                $aThemes[$oTheme->id()] = $oTheme;
            }
        }

        $aThemed = array();
        foreach ($aBugs as $oBug) {
            $mTheme = $this->_findTheme($oBug);
            if ($mTheme !== false) {
                $oTheme = $aThemes[$mTheme];
                $sTheme = $oTheme->id() . ' ' . $oTheme->title();
                $aThemed[$sTheme][] = $oBug;
                $this->_allBugs[$oBug->id()] = $oBug;
                unset($oTheme, $sTheme);
            }
        }

        unset($aThemes, $aThemeBugs);
        ksort($aThemed);
        return $aThemed;
    }

    /**
     *
     * @return <type>
     */
    private function _findUnthemedBugs() {
        $openBugs = $this->getOpenBugs();
        $this->_aUnthemed = array();
        foreach ($openBugs as $bug) {
            if ($bug->isTheme() !== true) {
                if ($bug->doesBlock() !== true) {
                    $this->_aUnthemed[$bug->id()] = $bug;
                }
                else {
                    if ($this->_findTheme($bug) === false) {
                        $this->_aUnthemed[$bug->id()] = $bug;
                    }
                }
            }
        }

        ksort($this->_aUnthemed);
        return $this;
    }

    /**
     *
     */
    private function _findTheme($oBug) {
        $mReturn = false;
        if ($oBug instanceof Model_Ticket_Type_Bug) {
            $iBug = $oBug->id();
            if (isset($this->_aFindThemeCache[$iBug]) === true) {
                $mReturn = $this->_aFindThemeCache[$iBug];
            }
            else {
                $aBlocks = $oBug->blocks();
                foreach ($aBlocks as $iBlock) {
                    $iBlock = trim($iBlock);
                    if ($iBlock != '0') {
                        if (empty($this->_aThemes[$iBlock]) !== true) {
                            $mReturn = $iBlock;
                            break;
                        }
                        elseif (empty($this->_openBugs[$iBlock]) !== true) {
                            $mReturn = $this->_findTheme($this->_openBugs[$iBlock]);
                            break;
                        }
                    }
                }
            }
        }

        return $mReturn;
    }

    /**
     * Get a bug by id
     *
     * @param  int $iBug
     *
     * @return Model_Ticket_Type_Bug
     *
     * @throws Exception if a bug is not found
     */
    public function getBugById($iBug) {
        if (isset($this->_allBugs[$iBug]) !== true) {
            $aList = $this->getBugListByIds(array($iBug));
            $this->_allBugs[$iBug] = $aList[$iBug];
            unset($aList);
        }

        return $this->_allBugs[$iBug];
    }

    /**
     * Get the team
     *
     * @return array
     */
    public function getTeam() {
        return $this->_aTeam;
    }

    /**
     * Get the first date, when worked time was entered
     *
     * @return int
     */
    public function getFirstWorkedDate() {
        $iTimestamp = PHP_INT_MAX;
        foreach ($this->_allBugs as $oBug) {
            $iTemp = $oBug->getFirstWorkDate();
            if (empty($iTemp) !== true and $iTemp < $iTimestamp) {
                $iTimestamp = $iTemp;
            }
        }

        if (empty($iTimestamp) === true) {
            throw new Exception();
        }

        return $iTimestamp;
    }

    /**
     * Return all bugzilla statuses with percentage.
     *
     * @return array
     */
    public function getStatuses() {
        $this->_aStatuses = array(
            Model_Ticket_Type_Bug::STATUS_UNCONFIRMED => 0,
            Model_Ticket_Type_Bug::STATUS_NEW => 0,
            Model_Ticket_Type_Bug::STATUS_CONFIRMED => 0,
            Model_Ticket_Type_Bug::STATUS_ASSIGNED => 0,
            Model_Ticket_Type_Bug::STATUS_RESOLVED => 0,
            Model_Ticket_Type_Bug::STATUS_REOPENED => 0,
            Model_Ticket_Type_Bug::STATUS_VERIFIED => 0,
            Model_Ticket_Type_Bug::STATUS_CLOSED => 0
        );

        $iCount = count($this->_allBugs);
        foreach ($this->_allBugs as $oBug) {
            $this->_aStatuses[(string) $oBug->bug_status]++;
        }

        $mStat = null;
        foreach ($this->_aStatuses as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => round(($mStat / $iCount) * 100, 2)
            );
        }

        unset($mStat);
        return $this->_aStatuses;
    }
}