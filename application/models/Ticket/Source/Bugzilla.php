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

    const BUG_PROCESS = 'process_bug.cgi';

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

    const BUG_PARAM_FIELD_ASSIGNED_TO = 'assigned_to';

    const BUG_PARAM_FIELD_BLOCKED = 'blocked';

    const BUG_PARAM_FIELD_DEADLINE = 'deadline';

    const BUG_PARAM_FIELD_FLAGTYPE_NAME = 'flagtypes.name';

    const BUG_PARAM_FIELD_RELEASE_WEEK = 'cf_release_week';

    const BUG_PARAM_FIELD_REPORTER = 'reporter';

    const BUG_FLAG_REQUEST = '?';
    const BUG_FLAG_GRANTED = '+';
    const BUG_FLAG_DENIED  = '-';
    const BUG_FLAG_CANCELLED = 'X';

    private $_sCookie = null;

    private $_config = null;

    private $_client = null;

    private $_product = array();

    private $_user = array();

    private $_openBugs = null;

    private $_reopenedBugs = null;

    private $_fixedBugs = null;

    private $_aFixedTrunk = array();

    private $_aFixedToMerge = array();

    private $_aFixed = array();

    private $_summary = null;

    /**
     * The list of all tickets
     *
     * @var array[Model_Ticket_AbstractType]
     */
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
     *
     * @var int
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
     *
     * @param boolean $bFilterProductConfig
    */
    public function __construct($bFilterProductConfig = true) {
        $this->_config = Zend_Registry::get('_Config')->model;
        $this->_client = new Zend_Http_Client();
        $this->_client->setEncType(Zend_Http_Client::ENC_FORMDATA);
        $this->_sCookie = '/tmp/cookieBugzilla';
        if (isset($this->_config->bugzilla->http->cookiePath) === true) {
            $this->_sCookie = $this->_config->bugzilla->http->cookiePath . 'cookieBugzilla';
        }

        @unlink($this->_sCookie);
        $aCurlOptions = array(
            CURLOPT_COOKIEFILE => $this->_sCookie,
            CURLOPT_COOKIEJAR => $this->_sCookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (isset($this->_config->bugzilla->http->proxy) === true) {
            $aCurlOptions[CURLOPT_PROXY] = $this->_config->bugzilla->http->proxy;
        }

        $this->_client->setConfig(array(
            'timeout' => 120,
            'adapter' => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => $aCurlOptions
        ));

        $this->_oCache = Zend_Registry::get('_Cache');

        $this->user($this->_config->bugzilla->login);
        if ($bFilterProductConfig === true) {
            $aPortals = $this->_config->bugzilla->portal;
            foreach ($aPortals as $portal) {
                $this->product($portal->name);
            }
        }

        $this->_aTeam = $this->_config->bugzilla->team->toArray();
        $this->getBugsChangedToday();
    }

    /**
     * Set the bugzilla-results to the view
     *
     * @param  Zend_View $oView
     * @param  string $sMode
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function setView(Zend_View $oView, $sMode = 'list') {
        $oView->bugsReopened = $this->getReopenedBugs();
        $oView->bugsTestserver = $this->getUpdateTestserver();
        $oView->bugsBranch = $this->getFixedBugsInBranch();
        $oView->bugsTrunk = $this->getFixedBugsInTrunk();
        $oView->bugsFixed = $this->getFixedBugsUnknown();
        $oView->bugsOpen = $this->getThemedOpenBugs();
        $oView->bugsUnthemed = $this->getUnthemedBugs();
        if ($sMode === 'board') {

            // concepts
            $oView->allScreenWip = $this->getOpenConcepts();
            $oView->allScreenApproved = $this->getBugsWithFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '+');

            // stack
            $oView->allBugsOpen = $this->getFilteredList($this->getUnworkedWithoutOrganization(), $oView->allScreenWip);

            // testing
            $oView->allBugsTesting = $this->getBugsWithFlag(Model_Ticket_Type_Bug::FLAG_TESTING, '?');

            // developtment wating, wip
            $oView->openWaiting = $this->getWaiting();
            $oView->bugsWip = $this->getInprogress();

            // development - ready
            $aFixedWithoutTesting = $this->getFilteredList($oView->bugsFixed, $oView->allBugsTesting);
            $oView->bugsFixedWithoutTesting = $this->getFilteredList($aFixedWithoutTesting, $oView->allScreenApproved);
        }

        $oView->aMemberBugs = $this->getMemberBugs();
        $oView->aTeamBugs = $this->getTeamBugs($oView->aMemberBugs);

        $oView->iTotal = $this->getCount();
        $oView->aStats = $this->getStats();
        $oView->aStatuses = $this->getStatuses();
        $oView->aPriorities = $this->getPriorities();
        $oView->aSeverities = $this->getSeverities();
        $oView->sChuck = $this->getChuckStatus();
        $oView->aThemes = $this->getThemesAsStack();

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

        $this->_percentify($aPriorities, $iCount);
        return $aPriorities;
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getSeverities() {
        $aSeverities = array();

        $iCount = count($this->_allBugs);
        foreach ($this->_allBugs as $oBug) {
            $sSeverity = (string) $oBug->bug_severity;
            if (empty($aSeverities[$sSeverity]) === true) {
                $aSeverities[$sSeverity] = 0;
            }

            $aSeverities[$sSeverity]++;
        }

        $this->_percentify($aSeverities, $iCount);
        arsort($aSeverities);

        return $aSeverities;
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
                sprintf('href="%s/show_bug', $this->_config->bugzilla->baseUrl)
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
     *
     * @throws Zend_Exception If no login-parameters are set
     */
    private function _loginToBugzilla() {
        if (Zend_Registry::isRegistered('_login') !== true or Zend_Registry::isRegistered('_password') !== true) {
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
     * @return string
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
     * Set a get parameter
     *
     * @param string $key
     * @param mixed $value
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
     * Do a bugzilla-request
     *
     * @param  string $option
     *
     * @return string
     */
    private function _request($option) {
        $this->_iCount++;

        $queryString = rtrim($this->_getParameter, '&');
        $this->_client->setUri($this->_config->bugzilla->baseUrl . '/' . $option . '?' . $queryString);
        $this->_loginToBugzilla();
        $sResult = $this->_client->request()->getBody();
        $this->_resetAllParameter();

        return $sResult;
    }

    /**
     * Update a bugzila-ticket
     *
     * @param  Model_Ticket_Source_AbstractWriter $oWriter
     *
     * @return void
     *
     * @throws Model_Ticket_Type_Source_Writer_Exception
     */
    public function updateTicket(Model_Ticket_Source_AbstractWriter $oWriter) {
        $this->_client->setMethod(Zend_Http_Client::POST);
        $this->_client->setParameterPost($oWriter->getPayload());
        $this->_client->setUri($this->_config->bugzilla->baseUrl . '/' . self::BUG_PROCESS);
        $sResult = $this->_client->request()->getBody();

        $aMatches = array();
        if (preg_match('/\<td bgcolor="#ff0000"\>([\s\S]*)\<\/td\>/', $sResult, $aMatches) > 0) {
            throw new Model_Ticket_Type_Source_Writer_Exception('Bugzilla Exception while updating: ' . trim(strip_tags($aMatches[1])));
        }
        elseif (preg_match('/\<td id="error_msg" class="throw_error"\>([\s\S]*)\<\/td\>/', $sResult, $aMatches) > 0) {
            throw new Model_Ticket_Type_Source_Writer_Exception('Bugzilla Exception while updating: ' . trim(strip_tags($aMatches[1])));
        }

        $this->_resetAllParameter();
    }

    /**
     * Clear all parameters
     *
     * @return void
     */
    private function _resetAllParameter() {
        $this->_client->resetParameters(true);
        $this->_getParameter = "";
    }

    /**
     * Get the bug-ids from a page
     *
     * @param  string $page
     *
     * @return array
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
        $aCacheHits = $aReturn = $aTemp = $aRequest = array();
        foreach ($aBugIds as $iBugId) {
            if ($bCache === true and empty($this->_allBugs[$iBugId]) !== true) {
                $aTemp[] = $this->_allBugs[$iBugId];
                $aCacheHits[$iBugId] = $iBugId;
            }
            else {
                $oBug = $this->_oCache->load($this->_getBugHash($iBugId));
                if ($oBug instanceof Model_Ticket_AbstractType and $bCache === true) {
                    $aTemp[] = $oBug;
                    $aCacheHits[$iBugId] = $iBugId;
                }
                else {
                    $aRequest[] = $iBugId;
                }
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
                Zend_Debug::dump($sResponse);
                exit;
            }

            unset($sResponse);
        }

        $aConfig = $this->_config->bugzilla->portal->toArray();
        foreach ($aTemp as $oBug) {
            $iId = $oBug->id();

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
                $aReturn[$iId] = $oBug;
                if ($oBug->isClosed() !== true and $oBug->isTheme() !== true) {
                    $this->_allBugs[$iId] = $oBug;
                }
                elseif ($oBug->isTheme() === true) {
                    $this->_aThemes[$iId] = $oBug;
                }
            }

            if (empty($aCacheHits[$iId]) === true) {
                $this->_oCache->save($oBug, $this->_getBugHash($iId));
            }
        }

        ksort($aReturn);
        return $aReturn;
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
     * Get all bugs
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    public function getBugList() {
        $this->_addParams();
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_REOPENED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_UNCONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_CONFIRMED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_NEW);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_ASSIGNED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_VERIFIED);
        $this->_setGetParameter(self::BUG_PARAM_STATUS, Model_Ticket_Type_Bug::STATUS_RESOLVED);
        $page = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);
        $bugs = $this->getBugListByIds($bugIds, true);

        $this->_openBugs = $this->_fixedBugs = $this->_reopenedBugs = array();
        foreach ($bugs as $bug) {
            switch ($bug->getStatus()) {
                case Model_Ticket_Type_Bug::STATUS_REOPENED:
                    $this->_reopenedBugs[$bug->id()] = $bug;
                    break;


                case Model_Ticket_Type_Bug::STATUS_VERIFIED:
                case Model_Ticket_Type_Bug::STATUS_RESOLVED:
                    $this->_fixedBugs[$bug->id()] = $bug;
                    break;

                default:
                    $this->_openBugs[$bug->id()] = $bug;
                    break;
            }
        }

        unset($bugs, $page, $bugIds);
        ksort($this->_openBugs);
        ksort($this->_fixedBugs);
        ksort($this->_reopenedBugs);
        return $this;
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
     * Get all open tickets
     *
     * @return array
     */
    public function getOpenBugs() {
        if (is_null($this->_openBugs) === true) {
            $this->getBugList();
        }

        return $this->_openBugs;
    }

    /**
     * Get all fixed tickets
     *
     * @return array
     */
    public function getFixedBugs() {
        if (is_null($this->_fixedBugs) === true) {
            $this->getBugList();
        }

        return $this->_fixedBugs;
    }

    /**
     * Get reopened tickets
     *
     * @return array
     */
    public function getReopenedBugs() {
        if (is_null($this->_reopenedBugs) === true) {
            $this->getBugList();
        }

        return $this->_reopenedBugs;
    }

    /**
     * Get all open tickets, which are not themes, no organization and not yet worked on
     *
     * @return array
     */
    public function getUnworkedWithoutOrganization() {
        if (empty($this->_openBugs) === true) {
            $this->getOpenBugs();
        }

        $aStack = array();
        foreach ($this->_openBugs as $oTicket) {
            if ($oTicket->isTheme() === false and $oTicket->isOrga() === false and $oTicket->isWorkedOn() !== true) {
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get all open tickets, which are not themes, no organization and not yet worked on
     *
     * @return array
     */
    public function getWaiting() {
        if (empty($this->_openBugs) === true) {
            $this->getOpenBugs();
        }

        $aStack = array();
        foreach ($this->_openBugs as $oTicket) {
            /* @var $oTicket Model_Ticket_Type_Bug */
            if ($oTicket->isTheme() === false and $oTicket->isOrga() === false and $oTicket->isConcept() === false and $oTicket->isStatusAtLeast(Model_Ticket_Type_Bug::STATUS_CONFIRMED)
                and ($oTicket->isWorkedOn() === true or $oTicket->hasFlag(Model_Ticket_Type_Bug::FLAG_COMMENT, '?') === true)) {
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get all open tickets which we are working on
     *
     * @return array
     */
    public function getInprogress() {
        if (empty($this->_openBugs) === true) {
            $this->getOpenBugs();
        }

        $aStack = array();
        foreach ($this->_openBugs as $oTicket) {
            if ($oTicket->isTheme() === false and $oTicket->isOrga() === false and $oTicket->isConcept() === false and $oTicket->getStatus() === Model_Ticket_Type_Bug::STATUS_ASSIGNED) {
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get all open concepts
     *
     * @return array
     */
    public function getOpenConcepts() {
        if (empty($this->_openBugs) === true) {
            $this->getOpenBugs();
        }

        $aStack = array();
        foreach ($this->_openBugs as $oTicket) {
            if ($oTicket->isConcept() === true) {
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get a list of bugs by id
     *
     * @param  array|string $mIds
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
     * Get all fixed tickets which are in the integration-branch
     *
     * @return array
     */
    public function getFixedBugsInBranch() {
        if ($this->_aFixedToMerge) {
            return $this->_aFixedToMerge;
        }

        $fixedBugs = $this->getFixedBugs();
        $this->_aFixedToMerge = array();
        foreach ($fixedBugs as $bug) {
            /* @var $bug Model_Ticket_Type_Bug */
            if ($bug->isMerged()) {
                $this->_aFixedTrunk[$bug->id()] = $bug;
            }
            elseif($bug->couldBeInTrunk() === true) {
                $aBlocked = $this->getBugListByIds($bug->blocks());
                $bTrunk = (empty($aBlocked) === true and $bug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '+') === true) ? false : true;
                $bOnlyOrganizationTickets = (empty($aBlocked) === true) ? false : true;
                foreach ($aBlocked as $oBlocked) {
                    if ($oBlocked->isTheme() !== true and $oBlocked->isConcept() !== true) {
                        $bOnlyOrganizationTickets = false;
                    }

                    if ($oBlocked->couldBeInTrunk() !== true and $oBlocked->isMerged() !== true) {
                        $bTrunk = false;
                    }
                }

                if ($bTrunk === true and $bOnlyOrganizationTickets === false) {
                    $this->_aFixedTrunk[$bug->id()] = $bug;
                }
            }

            if (empty($this->_aFixedTrunk[$bug->id()]) === true) {
                if ($bug->isMergeable() === true or ($bug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '+') !== true and $bug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE, '?'))) {
                    $aDepends = $this->getBugListByIds($bug->getDepends($this));
                    $bFixed = true;
                    foreach ($aDepends as $oDependBug) {
                        if ($oDependBug->isMergeable() !== true and $oDependBug->couldBeInTrunk() !== true) {
                            $bFixed = false;
                        }
                    }

                    if ($bFixed === true) {
                        $this->_aFixedToMerge[$bug->id()] = $bug;
                    }
                    else {
                        $this->_aFixed[$bug->id()] = $bug;
                    }
                }
                else {
                    $this->_aFixed[$bug->id()] = $bug;
                }
            }
        }

        ksort($this->_aFixedTrunk);
        ksort($this->_aFixed);
        ksort($this->_aFixedToMerge);
        return $this->_aFixedToMerge;
    }


    /**
     * Get all fixed-tickets which are already in the stable-branch
     *
     * @param  string $sFlag
     * @param  string $sStatus
     *
     * @return array
     */
    public function getFixedBugsInTrunk($sFlag = '', $sStatus = null) {
        if (empty($this->_aFixedTrunk) === true) {
            $this->getFixedBugsInBranch();
        }

        return (empty($sFlag) !== true) ? $this->getBugsWithFlag($sFlag, $sStatus, $this->_aFixedTrunk) : $this->_aFixedTrunk;
    }

    /**
     * Get all fixed-tickets which are not yet in the stable-branch
     *
     * @param  string $sFlag
     * @param  string $sStatus
     *
     * @return array
     */
    public function getFixedBugsUnknown($sFlag = '', $sStatus = null) {
        if (empty($this->_aFixed) === true) {
            $this->getFixedBugsInBranch();
        }

        return (empty($sFlag) !== true) ? $this->getBugsWithFlag($sFlag, $sStatus, $this->_aFixed) : $this->_aFixed;
    }

    /**
     * Get all tickets with the given flag & value
     *
     * @param  string $sFlag
     * @param  string $sStatus
     * @param  array $aStack
     *
     * @return array
     */
    public function getBugsWithFlag($sFlag = '', $sStatus = null, $aStack = array()) {
        $aResult = array();
        if (empty($aStack) === true) {
            $aStack = $this->_allBugs;
        }

        foreach ($this->_allBugs as $oTicket) {
            if (empty($sFlag) === true or ($oTicket->hasFlag($sFlag, $sStatus) === true and ($sStatus === '?' or ($oTicket->hasFlag($sFlag, '?') !== true)))) {
                $aResult[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aResult);
        return $aResult;
    }

    /**
     * Filter a list of tickets by another list of tickets
     *
     * @param  array $aStack
     * @param  array $aFilter
     *
     * @return array
     */
    public function getFilteredList(array $aStack, array $aFilter) {
        foreach ($aFilter as $oTicket) {
            $iIndex = $oTicket->id();
            if (isset($aStack[$iIndex]) === true) {
                unset($aStack[$iIndex]);
            }
        }

        ksort($aStack);
        return $aStack;
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

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get all tickets that need a testserver-update
     *
     * @return array
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
                $sName = $oBug->getAssignee();
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
     * Get all open bugs, which are part of a theme
     *
     * @return array
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
     * Sort bugs to existing themes
     *
     * @param  array $aBugs
     *
     * @return array
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

        $aKeys = array_keys($aThemed);
        natsort($aKeys);

        $aFinal = array();
        foreach($aKeys as $sKey) {
            $aFinal[$sKey] = $aThemed[$sKey];
        }

        unset($aThemes, $aThemed, $aThemeBugs);
        return $aFinal;
    }

    /**
     * Find bugs which are not yet assigned to a theme
     *
     * @return array
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
     * Find the theme to a bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return int
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

        if ($mReturn !== false) {
            $oBug->setTheme($mReturn);
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
     *
     * @throws Exception
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
     * Get the bug-stats
     *
     * @return array
     */
    public function getStats() {
        if (empty($this->_aStats) === true) {
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
                Model_Ticket_Type_Bug::WORKFLOW_TIMEDOUT => 0,
            );

            $iTimeoutLimit = $this->_config->tickets->workflow->timeout;

            foreach ($this->_allBugs as $oBug) {
                /* @var $oBug Model_Ticket_Type_Bug */

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

                if ($oBug->isChangedWithinLimit($iTimeoutLimit) !== true) {
                    $this->_aStats[Model_Ticket_Type_Bug::WORKFLOW_TIMEDOUT]++;
                }
            }

            $this->_percentify($this->_aStats, $iCount);
        }

        return $this->_aStats;
    }

    /**
     * Return all bugzilla statuses with percentage.
     *
     * @return array
     */
    public function getStatuses() {
        if (empty($this->_aStatuses) === true) {
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

            $this->_percentify($this->_aStatuses, $iCount);
        }

        return $this->_aStatuses;
    }

    /**
     * Get the percentage of each type in the stack
     *
     * @param  array $aStack
     * @param  int $iCount
     *
     * @return Model_Ticket_Source_Bugzilla
     */
    protected function _percentify(array &$aStack, $iCount) {
        $mStat = null;
        foreach ($aStack as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => round(($mStat / $iCount) * 100, 2)
            );
        }

        unset($mStat);
        return $this;
    }
}
