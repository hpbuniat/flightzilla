<?php
/**
 * flightzilla
 *
 * Copyright (c) 2012-2013, Hans-Peter Buniat <hpbuniat@googlemail.com>.
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
 * @package   flightzilla
 * @author    Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license   http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Ticket\Source;

use Flightzilla\Model\Ticket\Type\Bug;
use Flightzilla\Model\Timeline\Date;

/**
 * Query Bugzilla as ticket-source
 *
 * @author    Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license   http://opensource.org/licenses/BSD-3-Clause
 * @version   Release: @package_version@
 * @link      https://github.com/hpbuniat/flightzilla
 */
class Bugzilla extends \Flightzilla\Model\Ticket\AbstractSource {

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

    const BUG_PARAM_FIELD_REPORTER = 'reporter';

    const BUG_FLAG_REQUEST = '?';

    const BUG_FLAG_GRANTED = '+';

    const BUG_FLAG_DENIED = '-';

    const BUG_FLAG_CANCELLED = 'X';

    /**
     * Products to use
     *
     * @var array
     */
    private $_product = array();

    /**
     * Users to use
     *
     * @var array
     */
    private $_user = array();

    /**
     * List of all open tickets
     *
     * @var Bug[]
     */
    private $_openBugs = array();

    /**
     * List of all reopened tickets
     *
     * @var Bug[]
     */
    private $_reopenedBugs = array();

    /**
     * List of all fixed tickets
     *
     * @var Bug[]
     */
    private $_fixedBugs = array();

    /**
     * List of all tickets, which are already in the stable-branch
     *
     * @var Bug[]
     */
    private $_aFixedTrunk = array();

    /**
     * List of all tickets, which can be merged
     *
     * @var Bug[]
     */
    private $_aFixedToMerge = array();

    /**
     * The list of all tickets
     *
     * @var Bug[]
     */
    private $_aFixed = array();

    /**
     * The list of all tickets
     *
     * @var Bug[]
     */
    private $_allBugs = array();

    /**
     * The list of all tickets, relevant to the selected (current) project
     *
     * @var Bug[]
     */
    private $_aAllTicketsCurrentProject = array();

    /**
     * @var \Flightzilla\Model\Ticket\Type\Theme[]
     */
    private $_aThemes = array();

    /**
     * @var \Flightzilla\Model\Ticket\Type\Project[]
     */
    private $_aProjects = array();

    /**
     * The list of all tickets, which are not assigned to a theme
     *
     * @var Bug[]
     */
    private $_aUnthemed = array();

    /**
     * Get-parameter for the next request
     *
     * @var string
     */
    private $_getParameter = '';

    /**
     * Cache for bug-list requests
     *
     * @var array
     */
    private $_aBugsListCache;

    /**
     * The summary-request result
     *
     * @var array
     */
    private $_summary = null;

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
     * @param \Flightzilla\Model\Resource\Manager $oResource
     * @param \Zend\Http\Client                   $oHttpClient
     * @param \Zend\Config\Config                 $oConfig
     */
    public function __construct(\Flightzilla\Model\Resource\Manager $oResource, \Zend\Http\Client $oHttpClient, \Zend\Config\Config $oConfig) {

        $this->_config    = $oConfig;
        $this->_oResource = $oResource;
        $this->_client    = $oHttpClient;
        $this->_client->setEncType(\Zend\Http\Client::ENC_FORMDATA);

        $this->_oDate = new Date();

        $this->user($this->_config->bugzilla->login);
    }

    /**
     * Set the project
     *
     * @param  string $sProject
     *
     * @return $this
     */
    public function setProject($sProject) {

        $aProjects = $this->_config->bugzilla->projects->$sProject->toArray();

        $this->_aTeam = $aProjects['team'];
        foreach ($this->_aTeam as $sLogin => $aMember) {
            $this->_oResource->registerResource(\Flightzilla\Model\Resource\Builder::build($sLogin, $aMember));
        }

        $this->_aProject = $aProjects['products'];
        foreach ($this->_aProject as $aPortal) {
            $this->product($aPortal['name']);
        }

        return $this;
    }

    /**
     * Get the number of bugs
     *
     * @return int
     */
    public function getCount() {

        $iCount = 0;
        foreach ($this->getAllBugs() as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                $iCount++;
            }
        }

        return $iCount;
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
        $page   = $this->_request(self::BUG_LIST);
        $bugIds = $this->_getBugIdsFromPage($page);

        $this->_setGetParameter(self::BUG_PARAM_CTYPE, 'html');
        $this->_setGetParameter(self::BUG_PARAM_DETAILED, 'on');
        $this->_setGetParameter(self::BUG_PARAM_REPORT, '1');
        $this->_setGetParameter(self::BUG_PARAM_GROUP, 'owner');
        $this->_setGetParameter(self::BUG_PARAM_BUG_IDS, implode(',', $bugIds));

        $this->_setGetParameter(self::BUG_PARAM_START_DATE, $sDate);
        $this->_setGetParameter(self::BUG_PARAM_END_DATE, $sDate);
        $page    = $this->_request(self::BUG_SUMMARY);
        $oDom    = new \Zend\Dom\Query($page);
        $oTables = $oDom->execute('table.owner tr');
        foreach ($oTables as $oTable) {
            $oDocument = new \DomDocument();
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
     * @return $this
     */
    public function product($sProduct = '') {

        if (empty($sProduct) !== true) {
            $this->_product[] = $sProduct;
            $this->_product   = array_unique($this->_product);
        }

        return $this;
    }

    /**
     * Add a user
     *
     * @param  string $sUser
     *
     * @return $this
     */
    public function user($sUser = '') {

        if (empty($sUser) !== true) {
            $this->_user[] = $sUser;
            $this->_user   = array_unique($this->_user);
        }

        return $this;
    }

    /**
     * Add static params
     *
     * @return $this
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

        $this->_setGetParameter('limit', 0);

        return $this;
    }

    /**
     * Login to bugzilla
     *
     * @return void
     *
     * @throws \InvalidArgumentException If no login-parameters are set
     */
    private function _loginToBugzilla() {

        if (file_exists($this->_sCookie) !== true) {
            $this->_client->setMethod(\Zend\Http\Request::METHOD_POST);

            $aPost = $this->_client->getRequest()->getPost()->toArray();
            $aPost = array_merge(array(
                'Bugzilla_login'         => $this->_oAuth->getLogin(),
                'Bugzilla_password'      => $this->_oAuth->getPassword(),
                'GoAheadAndLogIn'        => 'Log in',
                'Bugzilla_restrictlogin' => true
            ), $aPost);
            $this->_client->setParameterPost($aPost);
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
     * @param mixed  $value
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
        $sResult = $this->_client->send()->getBody();

        $this->_resetAllParameter();

        return $sResult;
    }

    /**
     * Update a bugzilla-ticket
     *
     * @param  \Flightzilla\Model\Ticket\Source\AbstractWriter $oWriter
     *
     * @return void
     *
     * @throws \Flightzilla\Model\Ticket\Source\Writer\Exception
     */
    public function updateTicket(\Flightzilla\Model\Ticket\Source\AbstractWriter $oWriter) {

        $this->_client->setEncType(\Zend\Http\Client::ENC_URLENCODED);
        $this->_client->setUri($this->_config->bugzilla->baseUrl . '/' . self::BUG_PROCESS);
        $this->_client->setParameterPost($oWriter->getPayload());
        $this->_client->setMethod(\Zend\Http\Request::METHOD_POST);

        $this->_loginToBugzilla();

        $sResult = $this->_client->send()->getBody();

        $sException = '';
        $aMatches   = array();
        if (preg_match('/\<td bgcolor="#ff0000"\>([\s\S]*)\<\/td\>/', $sResult, $aMatches) > 0
            or preg_match('/\<td id="error_msg" class="throw_error"\>([\s\S]*)\<\/td\>/', $sResult, $aMatches) > 0
            or preg_match('/I need a legitimate login and password to continue./i', $sResult, $aMatches) > 0
        ) {
            $sException = sprintf('Bugzilla Exception while updating: %s', trim(strip_tags($aMatches[1])));
            throw new \Flightzilla\Model\Ticket\Source\Writer\Exception($sException);
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
     * @param  array   $aBugIds
     * @param  boolean $bCache
     *
     * @return array<Bug>
     */
    private function _getXmlFromBugIds(array $aBugIds, $bCache = true) {

        $aCacheHits = $aReturn = $aTemp = $aRequest = array();
        $bCache = ($bCache or $this->_config->bugzilla->useOnlyCache);

        foreach ($aBugIds as $iBugId) {
            if ($bCache === true and empty($this->_allBugs[$iBugId]) !== true) {
                $aTemp[]             = $this->_allBugs[$iBugId];
                $aCacheHits[$iBugId] = $iBugId;
            }
            else {
                $oBug = $this->_oCache->getItem($this->_getBugHash($iBugId));
                if ($bCache === true and $oBug instanceof \Flightzilla\Model\Ticket\AbstractType) {
                    $aTemp[]             = $oBug;
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
                $xml = @simplexml_load_string($sResponse);
                if (empty($xml) !== true) {
                    foreach ($xml as $bug) {
                        $oBug    = \Flightzilla\Model\Ticket\Type::factory($bug);
                        $aTemp[] = $oBug;
                    }
                }

                unset($xml);
            }
            else {
                \Zend\Debug\Debug::dump($sResponse, __FILE__ . ':' . __LINE__);
                exit;
            }

            unset($sResponse);
        }

        foreach ($aTemp as $oBug) {
            /* @var $oBug Bug */
            $iId = $oBug->id();

            $bAdd = true;
            foreach ($this->_aProject as $aProduct) {
                if (strtolower($aProduct['name']) === strtolower($oBug->product)) {
                    if (isset($aProduct['theme']) === true) {
                        $bAdd     = false;
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
                $oBug->inject($this, $this->_oResource, $this->_oDate);
                $this->_oResource->addTicket($oBug);

                $aReturn[$iId] = $oBug;
                if ($oBug->isClosed() !== true) {
                    if ($oBug->isTheme() !== true) {
                        $this->_allBugs[$iId] = $oBug;
                    }
                    elseif ($oBug->isContainer() === true) {
                        $this->_aThemes[$iId] = $oBug;
                    }

                    if ($oBug->isProject() === true) {
                        $this->_aProjects[$iId] = $oBug;
                    }
                }
            }

            if (empty($aCacheHits[$iId]) === true) {
                $this->_oCache->setItem($this->_getBugHash($iId), $oBug);
            }
        }

        ksort($aReturn);
        return $aReturn;
    }

    /**
     * Refresh Tickets, which have been changed within a number of days
     *
     * @param  string $sDays
     *
     * @return $this
     */
    public function getChangedTicketsWithinDays($sDays = '0d') {
        if (empty($sDays) !== true) {
            $this->_addParams();
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_REOPENED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_UNCONFIRMED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_CONFIRMED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_NEW);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_ASSIGNED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_VERIFIED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_RESOLVED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_CLOSED);
            $this->_setGetParameter(self::BUG_PARAM_CHANGE_DATE_FROM, $sDays);
            $this->_setGetParameter(self::BUG_PARAM_CHANGE_DATE_TO, 'Now');
            $page   = $this->_request(self::BUG_LIST);
            $bugIds = $this->_getBugIdsFromPage($page);

            $bugs = $this->getBugListByIds($bugIds, false);
            $this->_preSort($bugs);

            unset($page, $bugIds, $bugs);
        }

        return $this;
    }

    /**
     * Get all bugs
     *
     * @return $this
     */
    public function getBugList() {
        $sToken = md5('get-bug-list' . serialize($this->_aProject) . date('dmy'));
        $bugIds = $this->_oCache->getItem($sToken);

        if (empty($bugIds) === true) {
            $this->_addParams();
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_REOPENED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_UNCONFIRMED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_CONFIRMED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_NEW);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_ASSIGNED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_VERIFIED);
            $this->_setGetParameter(self::BUG_PARAM_STATUS, Bug::STATUS_RESOLVED);
            $page   = $this->_request(self::BUG_LIST);
            $bugIds = $this->_getBugIdsFromPage($page);

            $this->_oCache->setItem($sToken, $bugIds);
        }

        $bugs = $this->getBugListByIds($bugIds, true);
        $this->_preSort($bugs);

        unset($bugs, $page, $bugIds);
        return $this;
    }

    /**
     * Presort the tickets to groups, depending on the status
     *
     * @param  array $aTickets
     *
     * @return $this
     */
    protected function _preSort($aTickets) {
        foreach ($aTickets as $oTicket) {

            /* @var $oTicket Bug */
            switch ($oTicket->getStatus()) {
                case Bug::STATUS_REOPENED:
                    $this->_reopenedBugs[$oTicket->id()] = $oTicket;
                    break;

                case Bug::STATUS_VERIFIED:
                case Bug::STATUS_RESOLVED:
                    $this->_fixedBugs[$oTicket->id()] = $oTicket;
                    break;

                default:
                    $this->_openBugs[$oTicket->id()] = $oTicket;
                    break;
            }
        }


        ksort($this->_openBugs);
        ksort($this->_fixedBugs);
        ksort($this->_reopenedBugs);
        return $this;
    }

    /**
     * Get all bugs for the relevant projects
     *
     * @return array
     */
    public function getAllBugs() {

        if (empty($this->_aAllTicketsCurrentProject) === true) {
            $this->_aAllTicketsCurrentProject = array();
            foreach ($this->_allBugs as $oTicket) {
                foreach ($this->_aProject as $aProduct) {
                    if (strtolower($aProduct['name']) === strtolower($oTicket->product)) {
                        $this->_aAllTicketsCurrentProject[$oTicket->id()] = $oTicket;
                    }
                }
            }
        }

        return $this->_aAllTicketsCurrentProject;
    }

    /**
     * Get all open tickets
     *
     * @return array
     */
    public function getOpenBugs() {
        if (empty($this->_openBugs) === true) {
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

        if (empty($this->_fixedBugs) === true) {
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

        if (empty($this->_reopenedBugs) === true) {
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

        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isContainer() === false and $oTicket->isOrga() === false and $oTicket->isStatusAtMost(Bug::STATUS_CONFIRMED) and $oTicket->isWorkedOn(Bug::STATUS_CLOSED) !== true) {
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get all untouched tickets
     *
     * @return array
     */
    public function getUntouched() {

        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isContainer() === false and $oTicket->isOrga() === false and $oTicket->isWorkedOn() !== true and $oTicket->isStatusAtMost(Bug::STATUS_UNCONFIRMED) === true) {
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
        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isContainer() === false and $oTicket->isOrga() === false and $oTicket->isConcept() === false and $oTicket->isWorkedOn() === true
                and ($oTicket->isStatusAtLeast(Bug::STATUS_CONFIRMED) or $oTicket->hasFlag(Bug::FLAG_COMMENT, self::BUG_FLAG_REQUEST) === true)
            ) {
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
        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isWip() === true) {
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
        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isConcept() === true and $oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) === false) {
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
     * @param  boolean      $bCache Allow cache
     *
     * @return array
     */
    public function getBugListByIds($mIds, $bCache = true) {

        if (is_array($mIds) === false) {
            $aMatch = array();
            preg_match_all('!\d+!', $mIds, $aMatch);
            if (empty($aMatch[0]) !== true) {
                $mIds = $aMatch[0];
            }
        }

        $aReturn = array();
        if (empty($mIds) !== true) {
            sort($mIds);
            $sHash = md5(serialize($mIds));
            if ($bCache !== true or empty($this->_aBugsListCache[$sHash]) === true) {
                $this->_aBugsListCache[$sHash] = $this->_getXmlFromBugIds($mIds, $bCache);
            }

            $aReturn = $this->_aBugsListCache[$sHash];
        }

        return $aReturn;
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

        $this->_aFixedToMerge = array();

        $fixedBugs = $this->getFixedBugs();
        foreach ($fixedBugs as $bug) {

            /* @var $bug Bug */
            if ($bug->isMerged() === true or $bug->isMostLikelyInTrunk() === true) {
                $this->_aFixedTrunk[$bug->id()] = $bug;
            }

            if (empty($this->_aFixedTrunk[$bug->id()]) === true) {
                $aProjects = $bug->getProjects();

                if ($bug->isMergeable() === true or ($bug->hasFlag(Bug::FLAG_MERGE, self::BUG_FLAG_GRANTED) !== true and $bug->hasFlag(Bug::FLAG_DBCHANGE, self::BUG_FLAG_REQUEST) === true)) {
                    $aDepends = $this->getBugListByIds($bug->getDepends($this));
                    $bMergeable = $bug->isMergeable();
                    foreach ($aDepends as $oDependBug) {
                        /* @var $oDependBug Bug */
                        if ($oDependBug->isMergeable() !== true and $oDependBug->couldBeInTrunk() !== true) {
                            $bMergeable = false;
                        }
                    }

                    if ($bMergeable === true) {
                        foreach ($aProjects as $oProject) {
                            /* @var $oProject Bug */
                            foreach ($oProject->getDependsAsStack() as $oSibling) {
                                /* @var $oSibling Bug */
                                if ($oSibling->couldBeInTrunk() !== true and $oSibling->isMerged() !== true and $oSibling->isMergeable() !== true) {
                                    $bMergeable = false;
                                    break 2;
                                }
                            }
                        }
                    }

                    if ($bMergeable === true) {
                        $this->_aFixedToMerge[$bug->id()] = $bug;
                    }
                    else {
                        $this->_aFixed[$bug->id()] = $bug;
                    }
                }
                elseif (empty($aProjects) === true) {
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
     * @param  array  $aStack
     *
     * @return array
     */
    public function getBugsWithFlag($sFlag = '', $sStatus = null, $aStack = array()) {

        $aResult = array();
        if (empty($aStack) === true) {
            $aStack = $this->_allBugs;
        }

        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if (empty($sFlag) === true or ($oTicket->hasFlag($sFlag, $sStatus) === true and ($sStatus === self::BUG_FLAG_REQUEST or ($oTicket->hasFlag($sFlag, self::BUG_FLAG_REQUEST) !== true)))) {
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
            /* @var $oTicket Bug */

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
     * Get the projects
     *
     * @return \Flightzilla\Model\Ticket\Type\Project[]
     */
    public function getProjects() {

        return $this->_aProjects;
    }

    /**
     * Get the themes as stack
     *
     * @return array
     */
    public function getThemesAsStack() {

        $aStack = array();
        foreach ($this->_aThemes as $oTheme) {
            $aStack[$oTheme->id()] = $oTheme->title();
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get projects as stack
     *
     * @return array
     */
    public function getProjectsAsStack() {

        $aStack = array();
        foreach ($this->_aProjects as $oTheme) {
            $aStack[$oTheme->id()] = $oTheme->title();
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
        $back  = array();
        foreach ($aBugs as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->hasFlag(Bug::FLAG_TESTSERVER, self::BUG_FLAG_REQUEST)) {
                $back[$oTicket->id()] = $oTicket;
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
        foreach ($aMemberBugs as $sName => $aTickets) {
            if ($this->_oResource->hasResource($sName) === true) {
                $aTeam[$sName] = $aTickets;
            }
        }

        $oSorting = new \Flightzilla\Model\Project\Sorting($this);
        foreach ($aTeam as $sName => $aTickets) {
            $aStack        = $oSorting->setStack($aTickets)->getSortedBugs();
            $aTeam[$sName] = array();
            foreach ($aStack as $oTicket) {
                if ((string) $oTicket->getResource() === $sName) {
                    $aTeam[$sName][] = $oTicket;
                }
            }
        }

        ksort($aTeam);
        unset($oSorting);

        return $aTeam;
    }

    /**
     * Get all bugs per member
     *
     * @return array
     */
    public function getMemberBugs() {

        $aOpenBugs = $this->getOpenBugs();
        $aOpenBugs = array_merge($aOpenBugs, $this->getReopenedBugs());

        $aMember   = array();
        foreach ($aOpenBugs as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isContainer() !== true) {
                $sName             = (string) $oBug->getResource();
                $aMember[$sName][] = $oBug;
            }
        }

        ksort($aMember);
        return $aMember;
    }

    /**
     * Create a lookup for weekly-sprints
     *
     * @param  array $aTeamBugs Tickets of the configured team-members
     *
     * @return array
     */
    public function getWeekSprint($aTeamBugs) {
        $aSprint = array();

        $iNextWeek = strtotime('next sunday');
        foreach ($aTeamBugs as $sName => $aTickets) {
            $aSprint[$sName] = $this->_oDate->getWeeks();
            foreach ($aTickets as $oTicket) {
                /* @var $oTicket Bug */
                $sWeek = $oTicket->getWeek();

                if ($sWeek !== false) {
                    $bAdded = false;
                    foreach ($aSprint[$sName] as $sWeekAlias => $aWeek) {
                        if ($aWeek['title'] === $sWeek) {
                            $bAdded = true;
                            $aSprint[$sName][$sWeekAlias]['tickets'][$oTicket->id()] = $oTicket;
                            break;
                        }
                    }

                    // all open tickets from past sprints will be added to the current week
                    if (($bAdded === false or $sWeek === $aSprint[$sName][Date::WEEK_PREVIOUS]['title']) and $iNextWeek > $this->_oDate->getDateFromWeek($sWeek) and $oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) !== true) {
                        $aSprint[$sName][Date::WEEK_CURRENT]['tickets'][$oTicket->id()] = $oTicket;
                    }
                }
            }
        }

        return $aSprint;
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
     * Get all tickets, which are part of a theme and not yet merge-able
     *
     * @return array
     */
    public function getThemedTickets() {

        $this->getOpenBugs();
        $aThemed = array();
        foreach ($this->_allBugs as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->doesBlock() === true and $oBug->isContainer() !== true and $oBug->isMerged() === false and $oBug->isMergeable() === false) {
                $aThemed[$oBug->id()] = $oBug;
            }
        }

        ksort($aThemed);
        return $this->_findUnthemedBugs()->_sortBugsToThemes($aThemed);
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
        foreach ($aBugs as $oBug) {
            /* @var $oBug Bug */
            $aThemes = array_merge($aThemes, $oBug->blocks());
        }

        $aThemeBugs = $this->getBugListByIds(array_unique($aThemes));
        foreach ($aThemeBugs as $oTheme) {
            /* @var $oTheme Bug */
            if ($oTheme->isContainer() === true) {
                $this->_aThemes[$oTheme->id()] = $oTheme;
            }
        }

        $aThemed = array();
        foreach ($aBugs as $oBug) {
            /* @var $oBug Bug */
            $mTheme = $this->_findTheme($oBug);
            if ($mTheme !== false) {
                $sTheme                        = $this->_aThemes[$mTheme]->id() . ' ' . $this->_aThemes[$mTheme]->title();
                $aThemed[$sTheme][$oBug->id()] = $oBug;
                $this->_allBugs[$oBug->id()]   = $oBug;
                unset($sTheme);
            }
        }

        $aKeys = array_keys($aThemed);
        natsort($aKeys);

        $aFinal = array();
        foreach ($aKeys as $sKey) {
            $aFinal[$sKey] = $aThemed[$sKey];
        }

        unset($aThemes, $aThemed, $aThemeBugs);
        return $aFinal;
    }

    /**
     * Find bugs which are not yet assigned to a theme
     *
     * @return $this
     */
    private function _findUnthemedBugs() {

        $openBugs         = $this->getOpenBugs();
        $this->_aUnthemed = array();
        foreach ($openBugs as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isContainer() !== true) {
                if ($oBug->doesBlock() !== true) {
                    $this->_aUnthemed[$oBug->id()] = $oBug;
                }
                else {
                    if ($this->_findTheme($oBug) === false) {
                        $this->_aUnthemed[$oBug->id()] = $oBug;
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
     * @param  Bug $oBug
     *
     * @return int
     */
    private function _findTheme($oBug) {

        $mReturn = false;
        if ($oBug instanceof Bug) {
            $iBug = $oBug->id();
            if (isset($this->_aFindThemeCache[$iBug]) === true) {
                $mReturn = $this->_aFindThemeCache[$iBug];
            }
            else {
                $aBlocks = $oBug->blocks();
                foreach ($aBlocks as $iBlock) {
                    $iBlock = trim($iBlock);
                    if ($iBlock != '0') {
                        if (empty($this->_aThemes[$iBlock]) !== true or empty($this->_aProjects[$iBlock]) !== true) {
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
            if (empty($this->_aThemes[$mReturn]) === true or empty($this->_aProjects[$mReturn]) === true) {
                $oTheme = $this->getBugById($mReturn);
                $this->_aThemes[$mReturn] = $oTheme;
                if ($oTheme->isProject() === true) {
                    $this->_aProjects[$mReturn] = $oTheme;
                }
            }

            $oBug->setTheme($mReturn);
        }

        return $mReturn;
    }

    /**
     * Get a bug by id
     *
     * @param  int $iBug
     *
     * @return Bug
     *
     * @throws Bug\Exception if a bug is not found
     */
    public function getBugById($iBug) {
        if (empty($iBug) === true) {
            throw new Bug\Exception(Bug\Exception::INSUFFICIENT_DATA);
        }

        if (isset($this->_allBugs[$iBug]) !== true) {
            $aList = $this->getBugListByIds(array($iBug));
            if (isset($aList[$iBug]) === true) {
                $this->_allBugs[$iBug] = $aList[$iBug];
            }

            unset($aList);
        }

        if (empty($this->_allBugs[$iBug]) === true) {
            throw new Bug\Exception(Bug\Exception::INSUFFICIENT_DATA);
        }

        return $this->_allBugs[$iBug];
    }

    /**
     * @param Bug $oBug
     *
     * @return bool|\Flightzilla\Model\Ticket\Type\Project
     */
    public function getProject(Bug $oBug) {

        foreach ($this->_aProjects as $oTicket) {
            if ($oTicket->isProject() === true and $oTicket->doesDependOn($oBug, $this) === true) {
                return $oTicket;
            }
        }

        return false;
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
     * Get the bug-stats
     *
     * @return array
     */
    public function getStats() {

        if (empty($this->_aStats) === true) {
            $iCount        = $this->getCount();
            $this->_aStats = array(
                Bug::WORKFLOW_ESTIMATED   => 0,
                Bug::WORKFLOW_ORGA        => 0,
                Bug::WORKFLOW_UNESTIMATED => 0,
                Bug::WORKFLOW_INPROGRESS  => 0,
                Bug::WORKFLOW_ACTIVE      => 0,
                Bug::WORKFLOW_TESTING     => 0,
                Bug::WORKFLOW_MERGE       => 0,
                Bug::WORKFLOW_DEADLINE    => 0,
                Bug::WORKFLOW_SCREEN      => 0,
                Bug::WORKFLOW_COMMENT     => 0,
                Bug::WORKFLOW_FAILED      => 0,
                Bug::WORKFLOW_QUICK       => 0,
                Bug::WORKFLOW_TRANSLATION => 0,
                Bug::WORKFLOW_TIMEDOUT    => 0,
            );

            $iTimeoutLimit = $this->_config->tickets->workflow->timeout;
            foreach ($this->getAllBugs() as $oBug) {
                /* @var $oBug Bug */
                if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                    $bShouldHaveEstimation = true;
                    if ($oBug->isOrga() === true) {
                        $this->_aStats[Bug::WORKFLOW_ORGA]++;
                        $bShouldHaveEstimation = false;
                    }

                    if ($oBug->isEstimated()) {
                        $this->_aStats[Bug::WORKFLOW_ESTIMATED]++;
                    }
                    elseif ($bShouldHaveEstimation === true) {
                        $this->_aStats[Bug::WORKFLOW_UNESTIMATED]++;
                    }

                    if ($oBug->isWorkedOn()) {
                        $this->_aStats[Bug::WORKFLOW_INPROGRESS]++;
                    }

                    if ($oBug->isActive()) {
                        $this->_aStats[Bug::WORKFLOW_ACTIVE]++;
                    }

                    if ($oBug->hasFlag(Bug::FLAG_TESTING, self::BUG_FLAG_REQUEST)) {
                        $this->_aStats[Bug::WORKFLOW_TESTING]++;
                    }

                    if ($oBug->isFailed()) {
                        $this->_aStats[Bug::WORKFLOW_FAILED]++;
                    }

                    if ($oBug->isMergeable()) {
                        $this->_aStats[Bug::WORKFLOW_MERGE]++;
                    }

                    if ($oBug->deadlineStatus()) {
                        $this->_aStats[Bug::WORKFLOW_DEADLINE]++;
                    }

                    if ($oBug->hasFlag(Bug::FLAG_SCREEN, self::BUG_FLAG_REQUEST)) {
                        $this->_aStats[Bug::WORKFLOW_SCREEN]++;
                    }

                    if ($oBug->hasFlag(Bug::FLAG_COMMENT, self::BUG_FLAG_REQUEST)) {
                        $this->_aStats[Bug::WORKFLOW_COMMENT]++;
                    }

                    if ($oBug->isQuickOne()) {
                        $this->_aStats[Bug::WORKFLOW_QUICK]++;
                    }

                    if ($oBug->isOnlyTranslation()) {
                        $this->_aStats[Bug::WORKFLOW_TRANSLATION]++;
                    }

                    if ($oBug->isChangedWithinLimit($iTimeoutLimit) !== true) {
                        $this->_aStats[Bug::WORKFLOW_TIMEDOUT]++;
                    }
                }
            }

            $this->_percentify($this->_aStats, $iCount);
        }

        return $this->_aStats;
    }

    /**
     * Return all statuses with percentage.
     *
     * @return array
     */
    public function getStatuses() {

        if (empty($this->_aStatuses) === true) {
            $this->_aStatuses = array();

            $iCount = $this->getCount();
            foreach ($this->getAllBugs() as $oBug) {
                /* @var $oBug Bug */
                if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                    $sStatus = (string) $oBug->getStatus();
                    if (empty($this->_aStatuses[$sStatus]) === true) {
                        $this->_aStatuses[$sStatus] = 0;
                    }

                    $this->_aStatuses[$sStatus]++;
                }
            }

            $this->_percentify($this->_aStatuses, $iCount);
            ksort($this->_aStatuses);
        }

        return $this->_aStatuses;
    }

    /**
     * Get the ticket-throughput-diff for this week
     *
     * @return int
     */
    public function getThroughPut() {

        $iDiff = 0;
        $iCompare = strtotime('last monday');
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            if ($oTicket->isContainer() !== true) {
                if ($oTicket->getCreationTime() > $iCompare) {
                    $iDiff++;
                }

                if ($oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) === true and $oTicket->getLastActivity() > $iCompare) {
                    $iDiff--;
                }
            }
        }

        return $iDiff;
    }

    /**
     * Get the number of days which are used to determine the ticket-throughput
     *
     * @return int
     */
    public function getThroughPutDays() {
        return ceil((time() - strtotime('last monday')) / 86400);
    }

    /**
     * Get all tickets with deadlines as stack
     *
     * @return array
     */
    public function getDeadlineStack() {
        $aStack = array();
        foreach ($this->getAllBugs() as $oTicket) {
            /* @var $oTicket Bug */
            $mDeadline = strtotime($oTicket->getDeadline());
            if ($mDeadline !== false and $oTicket->isStatusAtMost(Bug::STATUS_REOPENED) === true and $oTicket->isContainer() !== true) {
                if (empty($aStack[$mDeadline]) === true) {
                    $aStack[$mDeadline] = array();
                }

                $aStack[$mDeadline][] = $oTicket;
            }
        }

        ksort($aStack);
        return $aStack;
    }

    /**
     * Get the chuck-status
     *
     * @return string
     */
    public function getChuckStatus() {

        $sStatus = \Flightzilla\Model\Chuck::OK;
        if (empty($this->_aStats[Bug::WORKFLOW_INPROGRESS]) === true) {
            $this->getStats();
            $this->getStatuses();
        }

        if ($this->_aStats[Bug::WORKFLOW_UNESTIMATED]['per'] > 10) {
            $sStatus = \Flightzilla\Model\Chuck::WARN;
        }
        elseif ($this->_aStatuses[Bug::STATUS_UNCONFIRMED]['per'] > 10) {
            $sStatus = \Flightzilla\Model\Chuck::WARN;
        }

        if (empty($this->_aStatuses[Bug::STATUS_REOPENED]) !== true and $this->_aStatuses[Bug::STATUS_REOPENED]['num'] > 1) {
            $sStatus = \Flightzilla\Model\Chuck::ERROR;
        }
        elseif ($this->_aStats[Bug::WORKFLOW_FAILED]['per'] > 2) {
            $sStatus = \Flightzilla\Model\Chuck::ERROR;
        }
        elseif ($this->_aStats[Bug::WORKFLOW_UNESTIMATED]['per'] > 15) {
            $sStatus = \Flightzilla\Model\Chuck::WARN;
        }
        elseif ($this->_aStatuses[Bug::STATUS_UNCONFIRMED]['per'] > 15) {
            $sStatus = \Flightzilla\Model\Chuck::WARN;
        }

        return $sStatus;
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getPriorities() {

        $aPriorities = array();

        $iCount = $this->getCount();
        foreach ($this->getAllBugs() as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                $sPriority = (string) $oBug->priority;
                if (empty($aPriorities[$sPriority]) === true) {
                    $aPriorities[$sPriority] = 0;
                }

                $aPriorities[$sPriority]++;
            }
        }

        $this->_percentify($aPriorities, $iCount);
        ksort($aPriorities);

        return $aPriorities;
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getSeverities() {

        $aSeverities = array();

        $iCount = $this->getCount();
        foreach ($this->getAllBugs() as $oBug) {
            /* @var $oBug Bug */
            if ($oBug->isClosed() !== true and $oBug->isContainer() !== true) {
                $sSeverity = (string) $oBug->bug_severity;
                if (empty($aSeverities[$sSeverity]) === true) {
                    $aSeverities[$sSeverity] = 0;
                }

                $aSeverities[$sSeverity]++;
            }
        }

        $this->_percentify($aSeverities, $iCount);
        ksort($aSeverities);

        return $aSeverities;
    }

    /**
     * Get the percentage of each type in the stack
     *
     * @param  array $aStack
     * @param  int   $iCount
     *
     * @return Bugzilla
     */
    protected function _percentify(array &$aStack, $iCount) {

        $mStat = null;
        foreach ($aStack as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => ($iCount === 0) ? 0 : round(($mStat / $iCount) * 100, 2)
            );
        }

        unset($mStat);
        return $this;
    }
}
