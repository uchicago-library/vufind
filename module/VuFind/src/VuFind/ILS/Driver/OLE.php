<?php
/**
 * OLE ILS Driver
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   David Lacy <david.lacy@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace VuFind\ILS\Driver;
use File_MARC, PDO, PDOException, Exception,
    File_MARCXML,
    VuFind\Exception\ILS as ILSException,
    VuFindSearch\Backend\Exception\HttpErrorException,
    Zend\Json\Json,
    Zend\Http\Client,
    Zend\Http\Request;

/**
 * OLE ILS Driver
 *
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   David Lacy <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class OLE extends AbstractBase implements \VuFindHttp\HttpServiceAwareInterface
{
    /**
     * HTTP service
     *
     * @var \VuFindHttp\HttpServiceInterface
     */
    protected $httpService = null;

    /**
     * Database connection
     *
     * @var PDO
     */
    protected $db;

    /**
     * Name of database
     *
     * @var string
     */
    protected $dbName;
    
    /**
     * Location of OLE's circ service
     *
     * @var string
     */
    protected $circService;
    
    /**
     * Location of OLE's docstore service
     *
     * @var string
     */
    protected $docService;

    /**
     * Location of OLE's solr service
     *
     * @var string
     */
    protected $solrService;
     
    /**
     * OLE operator for API calls
     */
    protected $operatorId;

    /**
     * item_available_codes, the value from ole_dlvr_item_avail_stat_t that indicates that an item is available. All other codes are reflect as unavailable.
     */
    protected $item_available_codes;

    /**
     * OLE's default sort for checked out items in user account section. 
     */
    protected $coiSort;

    /**
     * Pickup location codes and display names.
     */
    protected $pickupLocations; 

    /**
     * Set the HTTP service to be used for HTTP requests.
     *
     * @param HttpServiceInterface $service HTTP service
     *
     * @return void
     */
    public function setHttpService(\VuFindHttp\HttpServiceInterface $service)
    {
        $this->httpService = $service;
    }

    /**
     * Should we check renewal status before presenting a list of items or only
     * after user requests renewal?
     *
     * @var bool
     */
    protected $checkRenewalsUpFront;
    
    /* TODO Delete
    protected $record;
    */
    //
    /**
     * Default pickup location
     *
     * @var string
     */
    protected $defaultPickUpLocation;
    
    /* */
    protected $bibPrefix;
    protected $holdingPrefix;
    protected $itemPrefix;
    
    /* */
    protected $dbvendor;
    
    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init()
    {
        if (empty($this->config)) {
            throw new ILSException('Configuration needs to be set.');
        }
        
        /* TODO: move these to the config */
        $this->bibPrefix = "wbm-";
        $this->holdingPrefix = "who-";
        $this->itemPrefix = "wio-";
        
        $this->dbvendor
            = isset($this->config['Catalog']['dbvendor'])
            ? $this->config['Catalog']['dbvendor'] : "mysql";
            
        $this->checkRenewalsUpFront
            = isset($this->config['Renewals']['checkUpFront'])
            ? $this->config['Renewals']['checkUpFront'] : true;
            
        $this->defaultPickUpLocation
            = $this->config['Holds']['defaultPickUpLocation'];

        // Define Database Name
        $this->dbName = $this->config['Catalog']['database'];
        
        // Define OLE's circualtion service
        $this->circService = $this->config['Catalog']['circulation_service'];
        
        // Define OLE's docstore service
        $this->docService = $this->config['Catalog']['docstore_service'];
        
        // Define OLE's solr service
        $this->solrService = $this->config['Catalog']['solr_service'];

        // Define OLE's Circ API operator
        $this->operatorId = $this->config['Catalog']['operatorId'];
        
        // Define OLE's available code status
        $this->item_available_codes = explode(":", $this->config['Catalog']['item_available_code']);

        // Define OLE's default sort for checked out items 
        $this->coiSort = $this->config['UserAccount']['checkedOutItemsSort'];

        // Define pickup location codes and display names
        $this->pickupLocations = $this->config['Catalog']['pickup_locations'];
 
        try {
            if ($this->dbvendor == 'oracle') {
                $tns = '(DESCRIPTION=' .
                         '(ADDRESS_LIST=' .
                           '(ADDRESS=' .
                             '(PROTOCOL=TCP)' .
                             '(HOST=' . $this->config['Catalog']['host'] . ')' .
                             '(PORT=' . $this->config['Catalog']['port'] . ')' .
                           ')' .
                         ')' .
                       ')';
                $this->db = new PDO(
                    "oci:dbname=$tns",
                    $this->config['Catalog']['user'],
                    $this->config['Catalog']['password']
                );
            } else {
                $this->db = new PDO(
                    "mysql:host=" . $this->config['Catalog']['host'] . ";port=" . $this->config['Catalog']['port'] . ";dbname=" . $this->config['Catalog']['database'],
                    $this->config['Catalog']['user'],
                    $this->config['Catalog']['password']
                );
            }
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }
        
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     */
    public function getConfig($function)
    {
        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }
    
    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $barcode The patron barcode
     * @param string $login   The patron's last name or PIN (depending on config)
     *
     * @throws ILSException
     * @return mixed          Associative array of patron info on successful login,
     * null on unsuccessful login.
     */
    public function patronLogin($barcode, $login)
    {
        // Load the field used for verifying the login from the config file, and
        // make sure there's nothing crazy in there:
        $login_field = isset($this->config['Catalog']['login_field'])
            ? $this->config['Catalog']['login_field'] : 'LAST_NAME';
        $login_field = preg_replace('/[^\w]/', '', $login_field);

        $sql = "SELECT * " .
               "FROM $this->dbName.ole_ptrn_t, $this->dbName.krim_entity_nm_t " .
               "where ole_ptrn_t.OLE_PTRN_ID=krim_entity_nm_t.ENTITY_ID AND " .
               "lower(krim_entity_nm_t.{$login_field}) = :login AND " .
               "lower(ole_ptrn_t.BARCODE) = :barcode";

        $utf8DecodeLogin = utf8_decode($login);
        $lowercaseLogin = strtolower($utf8DecodeLogin);
        $utf8DecodeBarcode = utf8_decode($barcode);
        $lowercaseBarcode = strtolower($utf8DecodeBarcode);
        
        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->bindParam(
                ':login', $lowercaseLogin, PDO::PARAM_STR
            );
            $sqlStmt->bindParam(
                ':barcode', $lowercaseBarcode, PDO::PARAM_STR
            );
            //var_dump($sqlStmt);
            $sqlStmt->execute();
            $row = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            if (isset($row['OLE_PTRN_ID']) && ($row['OLE_PTRN_ID'] != '')) {
                return array(
                    'id' => utf8_encode($row['OLE_PTRN_ID']),
                    'firstname' => utf8_encode($row['FIRST_NM']),
                    'lastname' => utf8_encode($row['LAST_NM']),
                    'cat_username' => $barcode,
                    'cat_password' => $login,
                    'email' => null,
                    'major' => null,
                    'college' => null,
                    'barcode' => $barcode);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            throw new ILSException($e->getMessage());
        }
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @throws ILSException
     * @return array        Array of the patron's profile data on success.
     */
    public function getMyProfile($patron)
    {
        $uri = $this->circService . '?service=lookupUser&patronBarcode=' . $patron['barcode'] . '&operatorId=' . $this->operatorId;

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($uri);
        
        $client = new Client();
        $client->setOptions(array('timeout' => 240));

        
        try {
            $response = $client->dispatch($request);
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }
        
        // TODO: reimplement something like this when the API starts returning the proper http status code
        /*
        if (!$response->isSuccess()) {
            throw HttpErrorException::createFromResponse($response);
        }
        */

        $content = $response->getBody();
        $xml = simplexml_load_string($content);

        $patron['email'] = '';
        $patron['address1'] = '';
        $patron['address2'] = null;
        $patron['city'] = '';
        $patron['state'] = '';
        $patron['zip'] = '';
        $patron['phone'] = '';
        $patron['group'] = '';
        
        if (!empty($xml->patronName->firstName)) {
            $patron['firstname'] = utf8_encode($xml->patronName->firstName);
        }
        if (!empty($xml->patronName->lastName)) {
            $patron['lastname'] = utf8_encode($xml->patronName->lastName);
        }
        if (!empty($xml->patronEmail->emailAddress)) {
            $patron['email'] = utf8_encode($xml->patronEmail->emailAddress);
        }
        if (!empty($xml->patronAddress->line1)) {
            $patron['address1'] = utf8_encode($xml->patronAddress->line1);
        }
        if (!empty($xml->patronAddress->line2)) {
            $patron['address2'] = utf8_encode($xml->patronAddress->line2);
        }
        if (!empty($xml->patronAddress->city)) {
            $patron['city'] = utf8_encode($xml->patronAddress->city);
        }
        if (!empty($xml->patronAddress->stateProvinceCode)) {
            $patron['state'] = utf8_encode($xml->patronAddress->stateProvinceCode);
        }
        if (!empty($xml->patronAddress->postalCode)) {
            $patron['zip'] = utf8_encode($xml->patronAddress->postalCode);
        }
        if (!empty($xml->patronPhone->phoneNumber)) {
            $patron['phone'] = utf8_encode($xml->patronPhone->phoneNumber);
        }

        return (empty($patron) ? null : $patron);

    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException - TODO
     * @throws ILSException
     * @return array        Array of the patron's transactions on success.
     */
    public function getMyTransactions($patron){
        /*Return array*/
        $transList = array();
        $sql = 'SELECT p.id AS patron_id, p.first_name AS first_name, p.last_name AS last_name, p.library_id AS library_id,
                    p.user_name AS user_name, p.email_address AS email_address,
                    l.ITM_ID AS item_id, i.HOLDINGS_ID AS holdings_id, h.BIB_ID AS bib_num, i.CURRENT_BORROWER AS borrower,
                    i.DUE_DATE_TIME AS duedate, l.CRTE_DT_TIME AS loaned_date, i.ITEM_STATUS_ID AS item_status_id,
                    l.NUM_OVERDUE_NOTICES_SENT AS overdue_notices_count, i.COPY_NUMBER AS copy_number, i.ENUMERATION AS enumeration,
                    i.CHRONOLOGY AS chronology, h.CALL_NUMBER_PREFIX AS call_number_prefix, h.CALL_NUMBER AS call_number, 
                    h.IMPRINT AS imprint, bib.CONTENT AS bib_data, i.BARCODE AS barcode,
                    loc.LOCN_NAME AS location_name,
                    l.NUM_RENEWALS AS number_of_renewals,
                    l.ole_proxy_borrower_nm,
                    it.itm_typ_cd, it.itm_typ_desc,
                    i.claims_returned,
                    r.loan_tran_id,
                    r.ole_rqst_typ_id,
                    i.item_type_id, i.temp_item_type_id
                FROM uc_people p
                JOIN ole_dlvr_loan_t l ON p.id = l.OLE_PTRN_ID
                JOIN ole_ds_item_t i ON i.BARCODE = l.ITM_ID
                JOIN ole_ds_holdings_t h ON i.HOLDINGS_ID = h.HOLDINGS_ID
                JOIN ole_ds_bib_t bib ON bib.BIB_ID = h.BIB_ID
                LEFT JOIN ole_dlvr_rqst_t r on r.loan_tran_id = l.loan_tran_id
                LEFT JOIN ole_cat_itm_typ_t it ON it.itm_typ_cd_id = coalesce(i.temp_item_type_id, i.item_type_id)
                LEFT JOIN ole_locn_t loc on loc.LOCN_CD = SUBSTRING_INDEX(h.LOCATION, \'/\', -1)
                    WHERE p.library_id = :barcode 
                        AND i.CURRENT_BORROWER = p.id';
         try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':barcode' => $patron['barcode']));

            while ($row = $stmt->fetch()) {
                $processRow = $this->processMyTransactionsData($row, $patron);
                $transList[] = $processRow;
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        /*Set the default sort order for checked out items.*/
        $sort = $this->coiSort;
        if (array_key_exists('sort', $_POST)) {
            $sort = $_POST['sort'];
        }

        switch ($sort) {
            case 'dueDate':
                /*By duedate*/
                uasort($transList, function($a, $b) { return strnatcasecmp($a['duedate'], $b['duedate']); });
                break;
            case 'loanedDate' :
                /*By date checked out*/
                uasort($transList, function($a, $b) { return strnatcasecmp($a['loanedDate'], $b['loanedDate']); });
                break;
            case 'author':
                /*Alphabetical by author*/
                usort($transList, function($a, $b){ return strcasecmp(preg_replace('/[^ \w]+/', '', $a['author']), preg_replace('/[^ \w]+/', '', $b['author'])); });
                break;
            case 'loanType':
                /*Alphabetical by item (loan) type*/
                usort($transList, function($a, $b){ return strcasecmp(preg_replace('/[^ \w]+/', '', $a['loanType']), preg_replace('/[^ \w]+/', '', $b['loanType'])); });
                break;
            default:
                /*Alphabetical*/
                usort($transList, function($a, $b){ return strcasecmp(preg_replace('/[^ \w]+/', '', $a['title']), preg_replace('/[^ \w]+/', '', $b['title'])); });
                break;
        }

        return $transList;
    }

    /**
     * Converts a date into a sortable date.
     *
     * @param string, $date a date string to be formatted.
     *
     * @returns, integer date in sortable fashion 
     * e.g. 20141023
     */
    public function sortDate($date) {
        return date('Ynj', strtotime($date));
    }
    
    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException - TODO
     * @throws ILSException
     * @return mixed        Array of the patron's fines on success.
     */
     /* TODO: this hasn't been fully implemented yet */
     
    public function getMyFines($patron)
    {

        // Variables to use later
        $fineList = array();
        $transList = $this->getMyTransactions($patron);

        // Build a request to the OLE circ API
        $uri = $this->circService . '?service=fine&patronBarcode=' . $patron['barcode'] . '&operatorId=' . $this->operatorId;
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($uri);

        $client = new Client();
        $client->setOptions(array('timeout' => 30));

        // Make a request to the OLE circ API and retrieve data
        try {
            $response = $client->dispatch($request);
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }
        $content_str = $response->getBody();
        $xml = simplexml_load_string($content_str);
        $fines = $xml->xpath('//fineItem');

        // Make a request to the database because the OLE circ API is insufficient  
        $finesData = [];
        $sql = 'select pb.ole_ptrn_id, pb.ptrn_bill_id, ft.pay_status_id,
                ft.due_dt_time, ft.check_in_dt_time_ovr_rd, ft.check_in_dt_time
                from ole_dlvr_ptrn_bill_t pb
                    left join ole_dlvr_ptrn_bill_fee_typ_t ft on pb.ptrn_bill_id = ft.ptrn_bill_id
                        where ole_ptrn_id = :id';
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $patron['id']));

            while ($row = $stmt->fetch()) {
                $finesData[$row['ptrn_bill_id']]['pay_status_id'] = $row['pay_status_id'];
                $finesData[$row['ptrn_bill_id']]['duedate'] = $row['due_dt_time'];
                if ($row['check_in_dt_time_ovr_rd']) { 
                    $finesData[$row['ptrn_bill_id']]['returndate'] = $row['check_in_dt_time_ovr_rd'];
                }
                else {
                    $finesData[$row['ptrn_bill_id']]['returndate'] = $row['check_in_dt_time'];
                }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        foreach($fines as $fine) {
            $processRow = $this->processMyFinesData($fine, $patron);

            // Add variables by checking the database against the api :-(
            $processRow['suspended'] = $finesData[$processRow['patronBillId']]['pay_status_id'] == '55';
            $processRow['duedate'] = $finesData[$processRow['patronBillId']]['duedate'];
            $processRow['returndate'] = $finesData[$processRow['patronBillId']]['returndate'];
            
            if($processRow['id']) {
                foreach($transList as $trans) {
                    if ($this->bibPrefix . $trans['id'] == $processRow['id']) {
                        $processRow['checkout'] = $trans['loanedDate'];
                        //$processRow['duedate'] = $trans['duedate'];
                        $processRow['title'] = $trans['title'];
                        $processRow['locationName'] = $trans['locationName'];
                        break;
                    }
                }
            }
            $fineList[] = $processRow;
        }

        return $fineList;

    }
    /**
     * Protected support method for getMyHolds.
     *
     * @param array $itemXml simplexml object of item data
     * @param array $patron array
     *
     * @throws DateException
     * @return array Keyed data for display by template files
     */
    protected function processMyFinesData($itemXml, $patron = false)
    {

        $recordId = (substr((string)$itemXml->catalogueId, 0, 4) == $this->bibPrefix ? substr((string)$itemXml->catalogueId, 4) : (string)$itemXml->catalogueId);
        
        $record = $this->getRecord($recordId);

        return array(
                 'amount' => (string)((float)$itemXml->amount * 100),
                 'fine' => (string)$itemXml->reason,
                 'balance' => (string)((float)$itemXml->balance * 100),
                 'billdate' => (string)$itemXml->billDate,
                 'createdate' => (string)$itemXml->dateCharged,
                 'title' => (string)$itemXml->title,
                 'patronBillId' => (string)$itemXml->patronBillId,
                 'checkout' => '',
                 'duedate' => '',
                 'id' => $recordId
             );
    }
    
    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @throws DateException - TODO
     * @throws ILSException
     * @return array        Array of the patron's holds on success.
     */
    public function getMyHolds($patron)
    {

        $holdList = array();
        
        $uri = $this->circService . '?service=holds&patronBarcode=' . $patron['barcode'] . '&operatorId=' . $this->operatorId;
        
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setUri($uri);

        $client = new Client();
        $client->setOptions(array('timeout' => 30));
            
        try {
            $response = $client->dispatch($request);
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }
        // TODO: reimplement something like this when the API starts returning the proper http status code
        /*
        if (!$response->isSuccess()) {
            throw HttpErrorException::createFromResponse($response);
        }
        */
        $content = $response->getBody();

        $xml = simplexml_load_string($content);
        
        $code = $xml->xpath('//code');
        $code = (string)$code[0][0];
        
        $holdsList = [];
        if ($code == '000') {
            $holdItems = $xml->xpath('//hold');
            
            foreach($holdItems as $item) {
                //var_dump($item);
                $processRow = $this->processMyHoldsData($item, $patron);
                //var_dump($processRow);
                $holdsList[] = $processRow;
            }
        }
        return $holdsList;

    }

    /**
     * Protected support method for getMyHolds.
     *
     * @param array $itemXml simplexml object of item data
     * @param array $patron array
     *
     * @throws DateException - TODO
     * @return array Keyed data for display by template files
     */
    protected function processMyHoldsData($itemXml, $patron = false)
    {
        $availableDateTime = (string) $itemXml->availableDate;
        $available = ($availableDateTime <= date('Y-m-d')) ? true:false;
        // JEJ CHANGE
        // Did the API change to return a string instead of date? (DL)
        //$available = ((string) $itemXml->availableStatus == 'ONHOLD') ?  true:false;
        $available = false; // COVID-19 Change: Above line commented out and this one added        

        /* Get stuff from the DB that we should be getting from the
           circ API but can't */
        $sql = "select r.item_uuid, r.hold_exp_date, r.uc_item_id, i.item_id, i.holdings_id,
                i.temp_item_type_id, i.item_type_id, loc.locn_name as item_location, h.staff_only,
                hl.locn_name as holdings_location, ityp.itm_typ_desc, cd.ole_crcl_dsk_pub_name
                    from ole_dlvr_rqst_t r
                    left join ole_ds_item_t i on i.item_id = r.uc_item_id
                    left join ole_ds_holdings_t h on h.holdings_id = i.holdings_id
                    left join ole_locn_t loc on loc.locn_cd = SUBSTRING_INDEX(i.location, '/', -1)
                    left join ole_locn_t hl on hl.locn_cd = SUBSTRING_INDEX(h.location, '/', -1)
                    left join ole_cat_itm_typ_t ityp on ityp.itm_typ_cd_id = coalesce(i.temp_item_type_id, i.item_type_id)
                    left join ole_crcl_dsk_t cd on ole_crcl_dsk_id = :deskid
                        where ole_rqst_id = :requestid";
        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':requestid' => $itemXml->requestId,
                            ':deskid' => strtolower(utf8_decode($itemXml->pickupLocation))]);

            while ($row = $stmt->fetch()) {
                $pickupLoc = $row['ole_crcl_dsk_pub_name'];
                $itemLoc = $row['item_location'];
                $loanType = $row['itm_typ_desc'];
                $hold_until_date = $row['hold_exp_date'];
                $shelvingLoc = $row['holdings_location'];
                if ($itemLoc) {
                    $shelvingLoc = $itemLoc;
            }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        return array(
            'id' => substr((string) $itemXml->catalogueId, strpos((string) $itemXml->catalogueId, '-')+1),
            'item_id' => (string) $itemXml->itemId,
            'type' => (string) $itemXml->requestType,
            'location' => (string) $pickupLoc,
            'shelvingLocation' => $shelvingLoc,
            'expire' => (string) $itemXml->expiryDate,
            'create' => (string) $itemXml->createDate,
            'position' => (string) $itemXml->priority,
            'available' => $available,
            'reqnum' => (string) $itemXml->requestId,
            'volume' => (string) $itemXml->volumeNumber,
            'publication_year' => '',
            'title' => strlen((string) $itemXml->title)
                ? (string) $itemXml->title : "unknown title",
            'status' => (string) $itemXml->availableStatus,
            'hold_until_date' => $hold_until_date,
            'loan_type' => $loanType 
        );

    }

    /**
     * UChicago helper function for calculating if
     * an item is due soon or overdue.
     *
     * @param $duedate, string
     *
     * @return associative array with boolean entries
     * for 'overdue' and 'duesoon'. Only one of these
     * can be true at a given time because an item
     * can never be overdue and due soon at the same time.
     */
    protected function getItemDueStatus($duedate)
    {
        $now = new \DateTime();
        $itemDueStatus = ['overdue' => false, 'duesoon' => false];
        try {
            $dateObj = new \DateTime($duedate);
            $diff = $now->diff($dateObj);
            $days = [0 => $diff->days,
                     1 => $diff->days * -1];
            $interval = $days[$diff->invert];
        } catch (Exception $e) {
            $interval = INF;
        }

        if ($interval < 0) {
            $itemDueStatus['overdue'] =  true;
        }
        elseif ($duedate != null and $interval < (int)$this->config['UserAccount']['due_soon']) {
            $itemDueStatus['duesoon'] = true;
        }
        return $itemDueStatus;
    }

    /**
     * Protected support method for getMyTransactions.
     *
     * @param array $row data from the database
     * @param array $patron array
     *
     * @throws DateException - TODO
     * @return array Keyed data for display by template files
     */
    protected function processMyTransactionsData($row, $patron = false)
    {
        $dateFormat = 'n/j/Y';
        $dueDate = substr((string) $row['duedate'], 0, 10);
        $dueTime = substr((string) $row['duedate'], 11);

        $loanedDate = substr((string) $row['loaned_date'], 0, 10);
        $loanedTime = substr((string) $row['loaned_date'], 11);

        $dueStatus = ($row['overdue_notices_count'] > 0) ? "overdue" : "";
        
        $locationName = $row['location_name'];
        
        $xml = simplexml_load_string($row['bib_data']);

        $title = ''; 
        $author = '';
        foreach($xml->record->xpath('*') as $field) {
            if ($field->attributes() == '245') {
                $title = (string) $field->subfield;
            }
            else if ($field->attributes() == '100') {
                $author = (string) $field->subfield;
            }
        }

        /* See if item is on indefinite loan */
        $isIndefiniteLoan = strlen($dueDate) < 1;

        /* See if the item is overdue or due soon */
        $itemDueStatus = $this->getItemDueStatus($dueDate);

        $transactions = [
            'id' => $row['bib_num'],
            'item_id' => $row['item_id'],
            'duedate' => ($isIndefiniteLoan == false ? date($dateFormat, strtotime($dueDate)) : null),
            'dueTime' => ($isIndefiniteLoan == false ? $dueTime : null),
            'loanedDate' => date($dateFormat, strtotime($loanedDate)),
            'loanedTime' => $loanedTime,
            'dueStatus' => $dueStatus,
            'volume' => $row['enumeration'],
            'copy' => $row['copy_number'],
            'callNumber' => $row['call_number_prefix'] . ' ' . $row['call_number'],
            'publication_year' => $row['imprint'],
            'renew' => $row['number_of_renewals'],
            'title' => $title != '' ? $title : "unknown title",
            'author' => $author != '' ? $author : '',
            'locationName' => $locationName,
            'barcode' => $row['barcode'],
            'overdue' => $itemDueStatus['overdue'],
            'duesoon' => $itemDueStatus['duesoon'],
            'loanTypeCode' => $row['itm_typ_cd'],
            'loanType' => $row['itm_typ_desc'] != '' ? $row['itm_typ_desc'] : "ZZZ",
            'claimsReturned' => true ? strtolower($row['claims_returned']) == 'y' : false,
            'isLost' => true ? $row['item_status_id'] == 14 : false,
            'proxyBorrower' => str_replace(',', ', ', $row['ole_proxy_borrower_nm']),
            'recalled' => $row['ole_rqst_typ_id'] == 2 
        ];
        $renewData = $this->checkRenewalsUpFront
            ? $this->isRenewable($patron['id'], $transactions['item_id'])
            : ['message' => 'renewable', 'renewable' => true];

        $transactions['renewable'] = $renewData['renewable'];
        $transactions['message'] = $isIndefiniteLoan == false ? $renewData['message'] : null;
 
        return $transactions;
    }

    /* TODO: document this */
    public function getRecord($id)
    {

        //$uri = $this->docService . '?docAction=instanceDetails&format=xml&bibIds=' . $id;
        $uri = $this->solrService . "?q=bibIdentifier:" . $this->bibPrefix . $id . "&wt=xml&rows=100000";
        /* TODO: use the zend http service and throw appropriate exception */
        $xml = simplexml_load_string(file_get_contents($uri));

        
        //$xml->registerXPathNamespace('ole', 'http://ole.kuali.org/standards/ole-instance');
        //$xml->registerXPathNamespace('circ', 'http://ole.kuali.org/standards/ole-instance-circulation');

        return $xml;
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @throws ILSException
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        $sql = 'SELECT DISTINCT loc.LOCN_NAME AS locn_name,
                    h.LOCATION AS holdings_locn_code, h.call_number_prefix AS holding_call_number_prefix, h.call_number AS holding_call_number, 
                    i.LOCATION AS item_locn_code, i.call_number_prefix AS item_call_number_prefix, i.call_number AS item_call_number, i.COPY_NUMBER AS item_copy_number,
                    status.ITEM_AVAIL_STAT_CD AS item_status_code, status.ITEM_AVAIL_STAT_NM AS item_status_name
                FROM ole_ds_holdings_t h
                    JOIN ole_ds_item_t i ON h.HOLDINGS_ID = i.HOLDINGS_ID
                    JOIN ole_dlvr_item_avail_stat_t status ON i.ITEM_STATUS_ID=status.ITEM_AVAIL_STAT_ID
                LEFT JOIN ole_locn_t loc ON loc.LOCN_CD = if(i.LOCATION is not NULL && length(i.LOCATION) > 0, SUBSTRING_INDEX(i.LOCATION, \'/\', -1), SUBSTRING_INDEX(h.LOCATION, \'/\', -1))
                    WHERE h.STAFF_ONLY = "N"
                        AND i.STAFF_ONLY = "N"
                        AND h.BIB_ID = :id';

        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $id));
 
            /*Build the return array*/
            $items = array();
            while ($row = $stmt->fetch()) {
                $item = array();
 
                /*Set convenience variables.*/
                $status = $row['item_status_code'];
                $available = (in_array($status, $this->item_available_codes) ? true:false);
                $location = $row['locn_name'];

                /*Build item array*/ 
                $item['id'] = $id;
                $item['status'] = $status;
                $item['location'] = $row['locn_name'];
                $item['reserve'] = 'N';
                $item['callnumber'] = (string) $row[2] . ' ' . $row['holding_call_number'];
                $item['availability'] = $available;
                if ($item['status'] == 'ANAL') {
                    $item['availability'] = null;
                }

                $items[] = $item; 
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }
        return $items;
    }
    
    /**
     * TODO: document this
     *
     */
    public function getItemStatus($itemXML) {

        $status = $itemXML->children('circ', true)->itemStatus->children()->codeValue;
        // TODO: enable all item statuses
        $available = (in_array($status, $this->item_available_codes)) ? true:false;

        $item['status'] = $status;
        $item['location'] = '';
        $item['reserve'] = '';
        $item['availability'] = $available;

        return $item;
    }
    
    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $idList The array of record ids to retrieve the status for
     *
     * @throws ILSException
     * @return array        An array of getStatus() return values on success.
     */
    public function getStatuses($idList)
    {
        $status = array();
        foreach ($idList as $id) {
            $isNumericBib = is_numeric($id[0]);
            if ($isNumericBib) {
                $status[] = $this->getStatus($id);
            } 
        }
        return $status;
    }

    /**
     * Checks to see if a record has analytic records. 
     *
     * @param string $id, the bib numer for the current record.
     * 
     * @return boolean
     */
    protected function hasAnalytics($id)
    {
        $retval = false;
        $sql = 'SELECT count(*) analytic_count from ole_ds_item_t
                        WHERE item_id in (
                            SELECT item_id from ole_ds_item_holdings_t
                                WHERE HOLDINGS_ID in (
                                    SELECT holdings_id from ole_ds_holdings_t
                                        WHERE BIB_ID = :id))';
        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $id));

            /*Return array*/
            while ($row = $stmt->fetch()) {

                /*Set convenience variables.*/
                $count = $row['analytic_count'];
                
                if ($count > 0) {
                    $retval = true;
                    break;
                }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }
        return $retval;
    }

    /**
     * Determines what kind of a hold type to give an item.
     * Defaults to "recall".
     *
     * @param string $status, OLE status code.
     * @param string $shelvingLocCode, shelving location code
     *
     * @return string, type of hold
     */
    protected function getHoldType($status, $shelvingLocCode) {

        // Default to page/hold, recall/holds are no longer needed
        $holdtype = 'page';

        $holdLocationCodes = [
            'XClosedCJK',
            'XClosedGen'
        ];

        // Unavailable hold statuses, should not contain
        // a status that would evaluate to AVAILABLE
        $holdStatuses = [
            'ONHOLD',
            'ONORDER',
            'INPROCESS',
            'INTRANSIT',
        ];

        $pageStatuses = [
            'AVAILABLE',
            'RECENTLY-RETURNED'
        ];

        if (($status == 'AVAILABLE' || $status == 'RECENTLY-RETURNED') && (in_array($shelvingLocCode, $holdLocationCodes))) {
            // Changed to page for COVID-19 implementation of paging service.
            //$holdtype = 'hold';
            $holdtype = 'page';
        } elseif (in_array($status, $holdStatuses)) {
            $holdtype = 'hold';
        } elseif (in_array($status, $pageStatuses)) {
            $holdtype = 'page';
        }

        return $holdtype;
    }

    /**
     *
     */
    protected function getItems($id, $holdingId, $holdingLocation, $holdingLocCodes, $holdingCallNum, $holdingCallNumDisplay, $isSerial, $holdingCallNumPrefix) {

        /*Get items by holding id*/
        $sql = 'SELECT i.ITEM_ID AS item_id, i.HOLDINGS_ID AS holdings_id, i.BARCODE AS barcode, i.URI AS uri, 
                    i.ITEM_TYPE_ID AS item_type_id, i.TEMP_ITEM_TYPE_ID as temp_item_type_id, 
                    itype.ITM_TYP_CD AS itype_code, itype.ITM_TYP_DESC AS itype_desc, 
                    istat.ITEM_AVAIL_STAT_CD AS status_code, istat.ITEM_AVAIL_STAT_NM AS status_name,
                    i.LOCATION AS location, loc.LOCN_NAME AS locn_name,
                    i.CALL_NUMBER_TYPE_ID, i.CALL_NUMBER_PREFIX, i.CALL_NUMBER, i.ENUMERATION, i.CHRONOLOGY, i.COPY_NUMBER, 
                    i.DUE_DATE_TIME, i.CHECK_OUT_DATE_TIME, i.CLAIMS_RETURNED,
                    (SELECT GROUP_CONCAT(inote.NOTE SEPARATOR ";")
                        FROM ole_ds_item_note_t inote
                        WHERE i.ITEM_ID = inote.ITEM_ID
                        AND inote.TYPE="public")
                    AS note 
                        FROM ole_ds_item_t i
                    LEFT JOIN ole_dlvr_item_avail_stat_t istat on i.ITEM_STATUS_ID = istat.ITEM_AVAIL_STAT_ID
                    LEFT JOIN ole_cat_itm_typ_t itype on if(i.TEMP_ITEM_TYPE_ID is not null, i.TEMP_ITEM_TYPE_ID, i.ITEM_TYPE_ID) = itype.ITM_TYP_CD_ID
                    LEFT JOIN ole_locn_t loc on loc.LOCN_CD = SUBSTRING_INDEX(i.LOCATION, \'/\', -1)
                        WHERE i.STAFF_ONLY = \'N\'
                            AND i.HOLDINGS_ID = :holdingId';


        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':holdingId' => $holdingId));

            /*Return array*/
            $items = array();
            while ($row = $stmt->fetch()) {
                $item = array();
        
                $callnumber = (!empty($row['CALL_NUMBER']) ? $row['CALL_NUMBER'] : $holdingCallNum);

                /*Set convenience variables.*/
                $status = $row['status_code'];
                $statusName = $row['status_name']; 
                $available = (in_array($status, $this->item_available_codes) ? true:false);
                $copyNum = $row['COPY_NUMBER'];
                $enumeration = $row['ENUMERATION'];
                $sort_enumeration_tmp = preg_split('/[\s-]/', $enumeration);
                $sort_enumeration = array_shift($sort_enumeration_tmp);
                $itemCallNumTypeId = (!empty($row['CALL_NUMBER_TYPE_ID']) ? trim($row['CALL_NUMBER_TYPE_ID']) : null);
                $itemCallNumDisplay = null;
                if ($row['CALL_NUMBER_PREFIX'] || $row['CALL_NUMBER']) {
                    $itemCallNumDisplay = trim($row['CALL_NUMBER_PREFIX']) . ' ' . $callnumber;
                }
                $itemCallNum = (isset($row['CALL_NUMBER']) ? trim($row['CALL_NUMBER']) : null);
                $itemTypeName = trim($row['itype_desc']);
                $itemLocation = $row['locn_name'];
                $itemLocCodes = $row['location'];

                $locCodesArr = explode('/', $itemLocCodes);
                $shelvingLocCode = $locCodesArr[count($locCodesArr) - 1];
                              
                /*Build the items*/ 
                $item['id'] = $id;
                $item['availability'] = $available;
                $item['status'] = $status;
                $item['status_name'] = $statusName; /* This alters the signature of the getHolding method, see https://vufind.org/wiki/development:plugins:ils_drivers#getholding. It's too painful to do this any other way. */
                $item['location'] = $holdingLocation;
                $item['reserve'] = '';
                $item['callnumbertypeid'] = $itemCallNumTypeId;
                $item['callnumber'] = $holdingCallNum;
                $item['duedate'] = (isset($row['DUE_DATE_TIME']) ? $row['DUE_DATE_TIME'] : 'Indefinite');
                $item['returnDate'] = '';
                if ($copyNum && $enumeration) {
                $item['number'] = $copyNum . ' : ' . $enumeration;
                }
                elseif ($copyNum) {
                    $item['number'] = $copyNum;
                }
                else {
                    $item['number'] = $enumeration;
                }
                $item['requests_placed'] = '';
                $item['barcode'] = $row['barcode'];
                $item['item_id'] = $row['item_id'];
                $item['is_holdable'] = true;
                $item['itemNotes'] = $row['note'];
                $item['holdtype'] = $this->getHoldType($status, $shelvingLocCode);
                /*UChicago specific?*/
                $item['claimsReturned'] = ($row['CLAIMS_RETURNED'] == 'Y' ? true : false);
                $item['sort'] = $enumeration;
                $item['itemTypeCode'] = $row['itype_code'];
                $item['itemTypeName'] = $itemTypeName;
                $item['callnumberDisplay'] = $holdingCallNumDisplay;
                $item['holdingCallNumPrefix'] = $holdingCallNumPrefix;
                $item['itemCallnumberDisplay'] = (!empty($itemCallNumDisplay) ? $itemCallNumDisplay : null);
                $item['locationCodes'] = (!empty($itemLocCodes) ? $itemLocCodes : $holdingLocCodes);
                $item['itemLocation'] = (!empty($itemLocation) ? $itemLocation : null);
    
                $items[] = $item;
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }
        
        /* See if the current holding is an eholding*/ 
        $eholdings = $this->getEholdings($id, $holdingId, $holdingLocation, $holdingCallNum, $holdingCallNumDisplay);
        $isEholding =  !empty($eholdings);
        
        /*Check for analytics if the current holding 
        is not an eholding*/
        if ($this->hasAnalytics($id) && !$isEholding) {
            $analytics = $this->getAnalytics($id, $holdingLocation, $holdingLocCodes, $holdingCallNum, $holdingCallNumDisplay);
            foreach($analytics as $anal) {
                $items[] = $anal;
            }
        }

        /*Sort numerically by copy/volume number.*/
        usort($items, function($a, $b) { return strnatcasecmp($a['sort'], $b['sort']); });
        if ($isSerial) {
            return array_reverse($items);
    }
        else {
            return $items;
        }
    }


    /**
     * Get Summary of Holdings
     *
     * Gets the extent of ownership information from OLE.
     *
     * @param string $id the record id.
     * @param string $holdingId holding specific identifier.
     * @param string $location the holding location.
     *
     * @return array of summary data for a specific holding.
     */
    protected function getSummaryHoldings($id, $holdingId, $location, $holdingCallNum, $holdingCallNumDisplay) {
        /*Get extent of ownership by bib id*/
        $sql = 'SELECT own.EXT_OWNERSHIP_ID, own.HOLDINGS_ID,
                   ot.TYPE_OWNERSHIP_NM,
                   own.ORD, own.TEXT,
                   (SELECT GROUP_CONCAT(note.NOTE SEPARATOR \';\')
                    from ole_ds_ext_ownership_note_t note 
                    where note.EXT_OWNERSHIP_ID = own.EXT_OWNERSHIP_ID
                    and note.TYPE = \'public\'
                    ) AS note
                    FROM ole_ds_ext_ownership_t own
                JOIN ole_ds_holdings_t h ON own.HOLDINGS_ID = h.HOLDINGS_ID
                LEFT JOIN ole_cat_type_ownership_t ot ON own.EXT_OWNERSHIP_TYPE_ID = ot.TYPE_OWNERSHIP_ID
                    WHERE h.STAFF_ONLY = "N"
                        AND h.BIB_ID = :id ORDER BY own.HOLDINGS_ID, ot.TYPE_OWNERSHIP_ID, own.ORD';

         try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $id));

            /*Return array*/
            $summaryHoldings = array();

            /*Get indicies and suppliments for unbound items in the serials receiving tables*/
            $unboundIndices = $this->getSerialReceiving($id, $holdingId, $location, $holdingCallNum, $holdingCallNumDisplay, $type='Index');
            $unboundSupplements = $this->getSerialReceiving($id, $holdingId, $location, $holdingCallNum, $holdingCallNumDisplay, $type='Supplementary');

            if (!empty($unboundIndices)) {
                $summaryHoldings += $unboundIndices; 
            }
            if (!empty($unboundSupplements)) {
                $summaryHoldings += $unboundSupplements; 
            }

            while ($row = $stmt->fetch()) {

                $summary = array();
                if ($holdingId == $row['HOLDINGS_ID']) { 
                    //Convienence variables
                    $summaryType = $row['TYPE_OWNERSHIP_NM'];

                    $summary['id'] = $id;
                    $summary['location'] = $location;
                    //$summary['notes'] = array($summary->note[0]->value);
                    //$summary['summary'] = array($summary->textualHoldings);
                    $summary['issues'] = ($summaryType == 'Basic Bibliographic Unit' ? array($row['TEXT'] . ' ' . $row['note']) : null);
                    $summary['indexes'] =  ($summaryType == 'Indexes' ? array($row['TEXT'] . ' ' . $row['note']) : null);
                    $summary['supplements'] = ($summaryType == 'Supplementary Material' ? array($row['TEXT'] . ' ' .$row['note']) : null);
                    $summary['availability'] = true;
                    $summary['status'] = '';
                    $summary['is_holdable'] = true;
                }
                if (!empty($summary)) {
                    $summaryHoldings[] = $summary;
                }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        //var_dump($summaryHoldings);
        return $summaryHoldings;

    }

    /**
     * Get E-Holdings 
     *
     * Gets the e-holdings.
     *
     * @param string $id the record id.
     * @param string $holdingId holding specific identifier.
     * @param string $location the holding location.
     *
     * @return array of eholdings represented as "items".
     */
    protected function getEholdings($id, $holdingId, $holdingLocation, $holdingCallNum, $holdingCallNumDisplay) {
        $sql = 'SELECT u.HOLDINGS_ID AS holdings_id, u.URI AS uri, u.TEXT AS text
                    FROM ole_ds_holdings_uri_t u
                        WHERE u.HOLDINGS_ID = :holdingId';

         try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':holdingId' => $holdingId));

            /*Return array*/
            $eHoldings = array();
            while ($row = $stmt->fetch()) {
                $item = array();

                if (!empty($row['uri'])) {
                    $item['id'] = $id;
                    $item['location'] = $holdingLocation;
                    $item['availability'] = true;
                    $item['status'] = 'AVAILABLE';
                    $item['eHolding'] = array('text' => $row['text'], 'uri' => $row['uri']);
                    $item['callnumber'] = $holdingCallNum;
                    /*UChicago Specific?*/
                    $item['callnumberDisplay'] = $holdingCallNumDisplay;
                }

                if (!empty($item)) {
                    $eHoldings[] = $item;
                }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }
        return $eHoldings;
    }


    /**
     * Get unbound periodicals and recently received items.
     *
     * @param string $id the record id.
     * @param string $holdingId holding specific identifier.
     * @param string $holdingLocation the holding location.
     *
     * @returns an array of item information for things without a barcode.
     */
    protected function getSerialReceiving($id, $holdingId, $holdingLocation, $holdingCallNum, $holdingCallNumDisplay, $type='Main') {

        /*Summary holdings types*/
        $summaryTypes = array('Index', 'Supplementary');

        /*Get serial receiving data (unbound periodicals) by holdingId*/
        $sql = 'SELECT r.INSTANCE_ID, SUBSTRING_INDEX(r.UNBOUND_LOC, \'/\', -1)  AS unbound_shelving_loc, SER_RCPT_LOC, 
                s.SER_RCPT_HIS_REC_ID, s.SER_RCV_REC_ID, s.RCV_REC_TYP, r.ACTIVE,
                   CONCAT_WS(" ", s.ENUM_LVL_1, s.ENUM_LVL_2, s.ENUM_LVL_3, s.ENUM_LVL_4, s.ENUM_LVL_5, s.ENUM_LVL_6) AS enum,
                   if(LEFT(s.CHRON_LVL_1,1)=\'(\' || LENGTH(s.CHRON_LVL_1) = 0,
                      s.CHRON_LVL_1,
                      CONCAT(\'(\', CONCAT_WS(": ", s.CHRON_LVL_1, CONCAT_WS(" ", s.CHRON_LVL_2, s.CHRON_LVL_3, s.CHRON_LVL_4)), \')\')
                   ) AS chron,
                   (SELECT loc.LOCN_NAME
                        FROM ole_locn_t loc
                        WHERE loc.LOCN_CD = unbound_shelving_loc
                    ) AS unbound_loc_name,
                   s.SER_RCPT_NOTE,
                   s.PUB_RCPT AS note
                   FROM ole_ser_rcv_his_rec s
                        JOIN ole_ser_rcv_rec r ON r.SER_RCV_REC_ID = s.SER_RCV_REC_ID
                            where s.PUB_DISPLAY = "Y"
				                and r.PUBLIC_DISPLAY = "Y"
                                and r.ACTIVE = "Y"
                                and s.RCV_REC_TYP = :type
                                and r.INSTANCE_ID = :holdingId';
 
        /*Return array*/
        $items = array();

        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':holdingId' => $this->holdingPrefix . $holdingId, ':type' => $type));

            while ($row = $stmt->fetch()) {

                $item = array();
                $unboundLocation = isset($row['unbound_loc_name']) ? $row['unbound_loc_name'] : null;
                $unboundLocCodes = isset($row['unbound_loc_codes']) ? $row['unbound_loc_codes'] : null;

                $item['id'] = $id;
                $item['location'] = $holdingLocation;
                $item['locationCodes'] = $unboundLocCodes;
                $item['availability'] = true;
                $item['status'] = 'AVAILABLE';

                $item['callnumber'] = $holdingCallNum;
                $item['callnumberDisplay'] = $holdingCallNumDisplay;
                
                /*Filter out summary holdings. These will be returned
                along with the getSummaryHoldings method.*/
                if (!in_array($row['RCV_REC_TYP'], $summaryTypes)) {
                    $item['unbound issues'] = $row['enum'] . ' ' . $row['chron'] . '^' . $unboundLocation;
                }
                $item['note'] = $row['note'];

                /*Append the proper types for summary holdings*/
                $item['indexes'] = ($type == 'Index' ? array($row['enum'] . ' ' . $row['chron'],  '') : null);
                $item['unbound supplements'] = ($type == 'Supplementary' ? array($row['enum'] . ' ' . $row['chron'] . '^' . $unboundLocation,  '') : null);

                if (!empty($item)) {
                    $items[] = $item;
                }
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }
        return array_reverse($items);
    }


    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param array  $patron Patron data
     *
     * @throws \VuFind\Exception\Date
     * @throws ILSException
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     */
    public function getHolding($id, array $patron = null)
    {
        /*Get holdings by bib id, with holdings notes.*/
        $sql = 'SELECT h.HOLDINGS_ID AS holdings_id, h.BIB_ID AS bib_id, h.location AS
                location, loc.LOCN_NAME AS locn_name,
                       h.CALL_NUMBER_TYPE_ID AS call_number_type_id,
                       h.CALL_NUMBER_PREFIX AS call_number_prefix, h.CALL_NUMBER AS
                call_number, h.COPY_NUMBER AS copy_number, bib.type, bib.level,
                       (SELECT GROUP_CONCAT(note.NOTE SEPARATOR "::|::")
                        FROM ole_ds_holdings_note_t note
                        WHERE note.HOLDINGS_ID = h.HOLDINGS_ID
                        AND note.TYPE="public"
                       ) AS note,
                       (SELECT count(*)
                        FROM ole_ds_ext_ownership_t own
                        WHERE own.HOLDINGS_ID = h.HOLDINGS_ID
                        ) AS ext_ownership_count,
                       (SELECT count(*)
                        FROM ole_ser_rcv_rec r
                        WHERE r.INSTANCE_ID = CONCAT("who-", h.HOLDINGS_ID)
                        ) AS ser_rcv_rec_count,
                       (SELECT count(*)
                        FROM ole_ds_holdings_uri_t uri
                        WHERE uri.HOLDINGS_ID = h.HOLDINGS_ID
                        ) AS uri_count,
                       (SELECT count(*)
                        FROM ole_ds_item_t itm
                        WHERE itm.HOLDINGS_ID = h.HOLDINGS_ID
                        ) AS item_count
                    FROM ole_ds_holdings_t h
                    LEFT JOIN ole_locn_t loc on loc.LOCN_CD = SUBSTRING_INDEX(h.LOCATION, \'/\', -1)
                    LEFT JOIN uc_bib_ext bib on bib.id = :id
                    WHERE h.STAFF_ONLY = "N"
                    AND h.BIB_ID =  :id';

        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $id));

            /*Final return array*/
            $items = array();
            while ($row = $stmt->fetch()) {
                /*Array for item data*/
                $item = array();
                //print_r($row);

                /*Convenience variables.*/
                $copyNum = trim($row['copy_number']);
                $holdingCallNumTypeId = trim($row['call_number_type_id']);
                $holdingCallNum = trim($row['call_number']);
                $holdingCallNumDisplay = trim($row['call_number_prefix'] . ' ' . $row['call_number'] . ' ' . $copyNum);
                $holdingCallNumPrefix = trim($row['call_number_prefix']);
                $holdingNotes = explode('::|::', $row['note']);
                $hasAnalytics = isset($row['analytic_count']) ? intval($row['analytic_count']) > 0 : null;
                $hasExtOwnership = intval($row['ext_ownership_count']) > 0;
                $hasEholdings = intval($row['uri_count']) > 0;
                $hasItems = intval($row['item_count']) > 0;
                $hasHoldingNote = (!empty($holdingNotes) && trim(implode('', $holdingNotes)) != '');
                $hasUnboundItems = intval($row['ser_rcv_rec_count']) > 0;
                $holdingId = $row['holdings_id'];
                $locationCodes = $row['location'];
                $shelvingLocation = $row['locn_name'] . ' - ' . $holdingId;
                $isSerial = (strtolower($row['type']) == 'a' && in_array(strtolower($row['level']), ['b','i','s']));

                /*Get e-holdings if they exist*/
                /*if ($hasEholdings) {
                    $eHoldings = $this->getEholdings($id, $holdingId, end(explode('/', $locationCodes)), $holdingCallNum, $holdingCallNumDisplay);
                    foreach ($eHoldings as $eHolding) {
                        $items[] = $eHolding;
                    }
                }*/
                
                /*Get serials receiving data*/
                if ($hasUnboundItems) {
                    $unboundSerials = $this->getSerialReceiving($id, $holdingId, $shelvingLocation, $holdingCallNum, $holdingCallNumDisplay);
                    foreach($unboundSerials as $unboundItem) {
                        $items[] = $unboundItem;
                    }
                }
                
                /*Build a mock item for each of the holdings if no items exist*/
                if(((!$hasItems or $hasHoldingNote) and (!empty($shelvingLocation))) and !$hasEholdings) { 
                    $item['id'] = $id; 
                    $item['location'] = $shelvingLocation; 
                    $item['callnumbertypeid'] = $holdingCallNumTypeId;
                    $item['callnumber'] = $holdingCallNum;
                    $item['holdings notes'] = $holdingNotes; 
                    $item['availability'] = true;
                    $item['status'] = '';
                    $item['is_holdable'] = true;
                    /*UChicago Specific?*/
                    $item['callnumberDisplay'] = $holdingCallNumDisplay;
                    $item['locationCodes'] = $locationCodes;

                    /*Add mock items to the final return array*/
                    $items[] = $item;
                }

                /*Get summary holdings and extent of ownership*/
                if ($hasExtOwnership) {
                    $summaryHoldings = $this->getSummaryHoldings($id, $holdingId, $shelvingLocation, $holdingCallNum, $holdingCallNumDisplay);
                    foreach($summaryHoldings as $summary) {
                        $items[] = $summary;
                    }                    
                } 

                /*Get individual item data*/           
                $oleItems = $this->getItems($id, $holdingId, $shelvingLocation, $locationCodes, $holdingCallNum, $holdingCallNumDisplay, $isSerial, $holdingCallNumPrefix);
                foreach($oleItems as $oleItem) {
                    /* Rather than pass the call number type of the
                     * holding to getItems(), I add the call number type id here.
                     * This way we don't have to change the function's signature.
                     * -jej
                     */
                    if (empty($oleItem['callnumbertypeid'])) {
                        $oleItem['callnumbertypeid'] = $holdingCallNumTypeId;
                    }
                    $items[] = $oleItem;
                }

            }

        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        // Get bound-withs
        if ($stmt->fetch() === false) {
            $item = array();
            /*Convenience variables.*/
            $boundwiths = $this->lookupBoundWith($id);
            $callNumber = $this->getCallNumberForBoundWithBib($id);
            $item['boundwiths']=$boundwiths;
            $item['callnumber'] = $callNumber;
            $item['id'] = $id;

            if (!empty($item['boundwiths'])) {
                $items[] = $item;
            }
        }

        //print_r($items);
        return $items;
    }

    public function getAnalytics($id, $holdingLocation, $holdingLocCodes, $holdingCallNum, $holdingCallNumDisplay) {

        $sql = 'select * from ole_ds_item_t i
                    LEFT JOIN ole_dlvr_item_avail_stat_t istat on i.ITEM_STATUS_ID = istat.ITEM_AVAIL_STAT_ID
                    LEFT JOIN ole_cat_itm_typ_t itype on if(i.TEMP_ITEM_TYPE_ID is not null, i.TEMP_ITEM_TYPE_ID, i.ITEM_TYPE_ID) = itype.ITM_TYP_CD_ID
                    LEFT JOIN ole_locn_t loc on loc.LOCN_CD = SUBSTRING_INDEX(i.LOCATION, \'/\', -1)
                    where i.item_id in
                    (select ih.item_id from ole_ds_item_holdings_t ih
                    where ih.HOLDINGS_ID in (select h.holdings_id from ole_ds_holdings_t h
                    where h.BIB_ID = :id))';

        try {
            /*Query the database*/
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(':id' => $id));

            /*Return array*/
            $items = array();
            while ($row = $stmt->fetch()) {
                $item = array();
        
                $callnumber = (!empty($row['CALL_NUMBER']) ? $row['CALL_NUMBER'] : $holdingCallNum);

                /*Set convenience variables.*/
                $status = $row['ITEM_AVAIL_STAT_CD'];
                $available = (in_array($status, $this->item_available_codes) ? true:false);
                $copyNum = $row['COPY_NUMBER'];
                $enumeration = $row['ENUMERATION'];
                $itemCallNumDisplay = (!empty($row['CALL_NUMBER_PREFIX']) ? trim($row['CALL_NUMBER_PREFIX']) . ' ' . $callnumber : null);
                $itemCallNum = (isset($row['CALL_NUMBER']) ? trim($row['CALL_NUMBER']) : null);
                $holdtype = ($available == true) ? "hold":"recall";
                $itemTypeArray = ($row['ITM_TYP_NM'] ? explode('-', $row['ITM_TYP_NM']) : array());
                $itemTypeName = trim($itemTypeArray[1]);
                $itemLocation = $row['LOCN_NAME'];
                $itemLocCodes = $row['LOCATION'];
                              
                /*Build the items*/ 
                $item['id'] = $id;
                $item['availability'] = $available;
                $item['status'] = $status;
                $item['location'] = (!empty($itemLocation) ? $itemLocation : $holdingLocation);
                $item['reserve'] = '';
                $item['callnumber'] = (!empty($itemCallNum) ? $itemCallNum : $holdingCallNum);
                $item['duedate'] = (isset($row['DUE_DATE_TIME']) ? $row['DUE_DATE_TIME'] : 'Indefinite') ;
                $item['returnDate'] = '';
                $item['number'] = $enumeration;
                $item['requests_placed'] = '';
                $item['barcode'] = $row['BARCODE'];
                $item['item_id'] = $row['ITEM_ID'];
                $item['is_holdable'] = true;
                //$item['itemNotes'] = $row['note']; COME BACK TO THIS
                $item['holdtype'] = $holdtype;
                /*UChicago specific?*/
                $item['claimsReturned'] = ($row['CLAIMS_RETURNED'] == 'Y' ? true : false);
                $item['sort'] = $enumeration; //preg_replace('/[^[:digit:]]/','', $copyNum) .  preg_replace('/[^[:digit:]]/','', array_shift(preg_split('/[\s-]/', $enumeration)));
                $item['itemTypeCode'] = $row['ITM_TYP_CD'];
                $item['itemTypeName'] = $itemTypeName;
                $item['callnumberDisplay'] = (!empty($itemCallNumDisplay) ? $itemCallNumDisplay : $holdingCallNumDisplay);
                $item['locationCodes'] = (!empty($itemLocCodes) ? $itemLocCodes : $holdingLocCodes);
    
                $items[] = $item;
            }
        }
        catch (Exception $e){
            throw new ILSException($e->getMessage());
        }

        /*Sort numerically by copy/volume number.*/
        usort($items, function($a, $b) { return strnatcasecmp($a['sort'], $b['sort']); });
        return $items;
    }

    /**
     * Get bound-with items.
     *
     * @param $bibid string, the bib number.
     *
     * @returns an array of arays, related bound with items. Subarrays 
     * contain the following keys: bib_id, title, url.
     */
    public function lookupBoundWith($bibid) {

        $sql = "select bib_id from ole_ds_bib_holdings_t where holdings_id in
                   (select holdings_id from ole_ds_bib_holdings_t where bib_id = '" . $bibid . "')";

        try {
            $utf8Bib = utf8_decode($bibid);
            $lowerCaseBib = strtolower($utf8Bib);
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->bindParam(
                ':bibid', $lowerCaseBib, PDO::PARAM_STR
            );
            $sqlStmt->execute();
            $check = $sqlStmt->fetchAll(PDO::FETCH_ASSOC);
            $arrayofrelatedbibs = array();
            $callNumberFieldSaved = "";
            foreach ($check as $id) {
                $bibinfo = array();
                $bibid = $id['bib_id'];
                //TODO:
                //GET RID OF THIS HARDCODING
                //$xml = file_get_contents("http://ole.lib.lehigh.edu/oledocstore/documentrest/bib?bibId=$bibid");
                $xml = file_get_contents("http://ole.uchicago.edu:8080/oledocstore/documentrest/bib?bibId=" . $bibid);
                $marc_source = new File_MARCXML($xml,File_MARCXML::SOURCE_STRING);
                $record = $marc_source->next();
                $title = $record->getField('245');
                $titleString = $title->getSubfield('a')->getData();
                $bibinfo['bib_id'] = $bibid;
                $bibinfo['title'] = $titleString;
                $recordurl = $bibid;
                $bibinfo['url'] = $recordurl;
                array_push($arrayofrelatedbibs,$bibinfo);
            }
            if (!empty($arrayofrelatedbibs)) {
                $arrayofrelatedbibs['cn'] = $this->getCallNumberForBoundWithBib($bibid);
            }
            return $arrayofrelatedbibs;
        } catch (PDOException $e) {
            throw new ILSException($e->getMessage());
        }
    }

    /**
     * Get the callnumber for bound-with by bib id. 
     *
     * @param $bibid string, the bib number.
     *
     * @returns string, call number.
     */
    public function getCallNumberForBoundWithBib($bibid) {

        //$sql = "select call_number from ole_ds_item_t where holdings_id in (select holdings_id from ole_ds_bib_holdings_t where bib_id =" . $bibid . ") limit 1";
        $sql = "select h.CALL_NUMBER_PREFIX,  h.CALL_NUMBER from ole_ds_holdings_t h where bib_id=" . $bibid;

        try {
            $sqlStmt = $this->db->prepare($sql);
            $sqlStmt->execute();
            $row = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            
            if (isset($row['call_number'])) {
                return $row['call_number'];
            } else {
                return null;
            }
        } catch (PDOException $e) {
            throw new ILSException($e->getMessage());
        }
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or throws an exception on failure of support
     * classes
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @throws ILSException - TODO
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available)
     */
    public function placeHold($holdDetails)

    {
        //Recall/Delivery Request   //Recall Request
        //Recall/Hold Request       //Recall/Hold Request
        //Hold/Delivery Request     //Hold Request
        //Hold/Hold Request         //Hold/Hold Request
        //Page/Delivery Request     //Page Request
        //Page/Hold Request         //Page/Hold Request
        //Copy Request              //Copy Request
        //In Transit Request        //In Transit Request
        //ASR Request               //ASR Request
        
        $patron = $holdDetails['patron'];
        $patronId = $patron['id'];
        $service = 'placeRequest';
        $bibId = $holdDetails['id'];
        $itemBarcode = $holdDetails['barcode'];
        $patronBarcode = $patron['barcode'];
        $pickupLocation = $holdDetails['pickUpLocation'];
        $itemId = $holdDetails['item_id'];
        $comment = urlencode($holdDetails['comment']);

        $requestType = urlencode('Recall/Hold Request');
        if ($holdDetails['holdtype'] == 'hold') {
            $requestType = urlencode('Hold/Hold Request');
        } elseif ($holdDetails['holdtype'] == 'page') {
            $requestType = urlencode('Page/Hold Request');
        }

        $uri = $this->circService . "?service={$service}&patronBarcode={$patronBarcode}&operatorId={$this->operatorId}&itemId={$itemId}&requestType={$requestType}&pickupLocation={$pickupLocation}&requestNote={$comment}";
        if ($itemBarcode) {
            $uri = $this->circService .  "?service={$service}&patronBarcode={$patronBarcode}&operatorId={$this->operatorId}&itemBarcode={$itemBarcode}&requestType={$requestType}&pickupLocation={$pickupLocation}&requestNote={$comment}";
        }
        
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setUri($uri);

        $client = new Client();
        $client->setOptions(array('timeout' => 120));

        try {
            $response = $client->dispatch($request);
        } catch (Exception $e) { 
            throw new ILSException($e->getMessage());
        }
        
        // TODO: reimplement something like this when the API starts returning the proper http status code
        /*
        if (!$response->isSuccess()) {
            throw HttpErrorException::createFromResponse($response);
        }
        */
        
        /* TODO: this will always be 201 */
        //$statusCode = $response->getStatusCode();
        $content = $response->getBody();
        
        $xml = simplexml_load_string($content);
        $msg = $xml->xpath('//message');
        $code = $xml->xpath('//code');

        $success = ((string)$code[0] == '021') ? true:false;

        return $this->returnString($success, (string)$msg[0]);

    }

    /**
     * Hold Error
     *
     * Returns a Hold Error Message
     *
     * @param string $msg An error message string
     *
     * @return array An array with a success (boolean) and sysMessage key
     */
    protected function returnString($success,$msg)
    {
        return array(
                    "success" => $success,
                    "sysMessage" => $msg
        );
    }
    
    /* TODO: config this using options from OLE */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        $responses = explode('|', $this->pickupLocations);
        $pickResponse = array();
        $i = 0;
        foreach ($responses as $response) {
            $response = explode(':', $response);
            $pickResponse[$i]['locationID'] = $response[0];
            $pickResponse[$i]['locationDisplay'] = $response[1];
            $i++;
        }
        return $pickResponse;
    }
    
    /* TODO: document this */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        return $this->defaultPickUpLocation;
    }
    
    /**
     * Determine Renewability
     *
     * This is responsible for determining if an item is renewable
     *
     * @param string $patronId The user's patron ID
     * @param string $itemId   The Item Id of item
     *
     * @return mixed Array of the renewability status and associated
     * message
     */
     /* TODO: implement this with OLE data */
    protected function isRenewable($patronId, $itemId)
    {
        $renewData['message'] = "renable";
        $renewData['renewable'] = true;

        return $renewData;
    }

    /**
     * Support method for VuFind Hold Logic. Take an array of status strings
     * and determines whether or not an item is holdable based on the
     * valid_hold_statuses settings in configuration file
     *
     * @param array $statusArray The status codes to analyze.
     *
     * @return bool Whether an item is holdable
     */
     /* TODO: implement this with OLE data */
    protected function isHoldable($item)
    {
        // User defined hold behaviour
        $is_holdable = true;
        
        return $is_holdable;
    }
    
    /**
     * Get Renew Details
     *
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     */
    public function getRenewDetails($checkOutDetails)
    {
      $renewDetails = $checkOutDetails['item_id'] . ',' . $checkOutDetails['id'];
    //$renewDetails['item_id'] = $checkOutDetails['id'];
        return $renewDetails;
    }
    
    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @throws ILSException - TODO
     * @return array of renewal information keyed by item ID
     */
     /* TODO: implement error messages from OLE once status codes are returned correctly
     HTTP/1.1 200 OK
     <renewItem>
      <message>Patron has more than $75 in Replacement Fee Charges. (OR) Patron has more than $150 in overall charges. (OR) The item has been renewed the maximum (1) number of times. (OR) </message>
    </renewItem>
    
     */
    public function renewMyItems($renewDetails)
    {
        // Convenience variables
        $patron = $renewDetails['patron'];
        $patronId = $patron['id'];
        $patronBarcode = $patron['barcode'];
        $service = 'renewItemList';
        
        // Data structure to return
        $finalResult = array();

        // Build a list of item barcodes
        $barcodes = array();
        foreach($renewDetails['details'] as $key=>$details) {
            $details_arr = explode(',', $details);
            $itemBarcode = $details_arr[0];
            $barcodes[] = $itemBarcode;
        }

        $json = array(
            'patronBarcode' => $patronBarcode,
            'operatorId' => $this->operatorId,
            'requestFormatType' => 'JSON',
            'responseFormatType' => 'JSON',
            'itemBarcodes' => $barcodes,
        );

        // Build the uri
        $u = parse_url($this->circService);
        $uri = sprintf("%s://%s:%s/olefs/rest/circ/renewItem", $u['scheme'], $u['host'], $u['port']);

        // Make the request
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));

        $request->setUri($uri);
        $request->setContent(json_encode($json));
        $client = new Client();
        $client->setEncType('text/json');
        $client->setOptions(array('timeout' => 4030));
        // invalid parameter headers passed...
        
        // Get the response
        try {
            $response = $client->dispatch($request);
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }
        $content = $response->getBody();

        // Parse the response 
        $response = json_decode($content, true);

        $i = 0;
        foreach($response['renewItemList'] as $renewal) {
            $msg = (string)$renewal['message'];
            $code = (string)$renewal['code'];
            $success = (bool)$renewal['success'];
            $newDate = (string)$renewal['newDueDate'];
            $itemBarcode = (string)$renewal['itemBarcode'];
            $finalResult['details'][$itemBarcode] = array(
                                "success" => $success,
                                "new_date" => $newDate,
                                "item_id" => $itemBarcode,
                                "sysMessage" => (string)$msg,
                                "code" => $code
                                );
            $i++;
        }

        return $finalResult;
    }
    
    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @throws ILSException
     * @return array     An array with the acquisitions data on success.
     */
    public function getPurchaseHistory($id)
    {
        // TODO
        return array();
    }
}
