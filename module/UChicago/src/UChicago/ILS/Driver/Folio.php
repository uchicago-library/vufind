<?php

namespace UChicago\ILS\Driver;

class Folio extends \VuFind\ILS\Driver\Folio
{
    /**
     * Helper function to retrieve paged results from FOLIO API
     *
     * @param string $responseKey Key containing values to collect in response
     * @param string $interface   FOLIO api interface to call
     * @param array  $query       CQL query
     *
     * @return array
     */
    protected function getPagedResults($responseKey, $interface, $query = [], $holdings = null)
    {
        $count = 0;
        $limit = 1000;
        $offset = 0;

        $eHoldingTypeId = $this->config['Holdings']['electronic_holding_type_id'] ?? '';
        $onOrderLocId = $this->config['Holdings']['on_order_loc_id'] ?? '';

        do {
            $combinedQuery = array_merge($query, compact('offset', 'limit'));
            $response = $this->makeRequest(
                'GET',
                $interface,
                $combinedQuery
            );
            $json = json_decode($response->getBody());
            if (!$response->isSuccess() || !$json) {
                $msg = $json->errors[0]->message ?? json_last_error_msg();
                throw new ILSException($msg);
            }
            $total = $json->totalRecords ?? 0;
            if (isset($holdings) && $total === 0 && ($holdings->holdingsTypeId != $eHoldingTypeId
                || $holdings->effectiveLocationId == $onOrderLocId)) {
                yield $holdings;
            }
            $previousCount = $count;
            foreach ($json->$responseKey ?? [] as $item) {
                $count++;
                if ($count % $limit == 0) {
                    $offset += $limit;
                }
                yield $item ?? '';
            }
            // Continue until the count reaches the total records
            // found, if count does not increase, something has gone
            // wrong. Stop so we don't loop forever.
        } while ($count < $total && $previousCount != $count);
    }


    /**
     * Get loan data by item ID. There should only be 1 result
     * but we loop over a generator.
     *
     * @param string $itemId
     *
     * @return string
     */
    protected function getDuedate($itemId)
    {
        $query = [
            'query' => '(itemId=="' . $itemId
                . '" NOT discoverySuppress==true)'
        ];
        foreach ($this->getPagedResults(
            'loans', '/loan-storage/loans', $query
        ) as $loan) {
            return $this->dateConverter->convertToDisplayDate(
                "Y-m-d H:i",
                $loan->dueDate
            );
        }
    }


    /**
     * Get data about a loan type.
     *
     * @param string $loanTypeId, UUID
     *
     * @return array
     */
    protected function getLoanTypeData($loanTypeId)
    {
        $name = '';
        $response = $this->makeForgivingRequest(
            'GET', '/loan-types/' . $loanTypeId
        );
        if ($response->isSuccess()) {
            $data = json_decode($response->getBody());
            $name = $data->name ?? '';
        }
        return compact('name');
    }


    /**
     * This method queries the ILS for holding information.
     *
     * @param string $bibId   Bib-level id
     * @param array  $patron  Patron login information from $this->patronLogin
     * @param array  $options Extra options (not currently used)
     *
     * @return array An array of associative holding arrays
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getHolding($bibId, array $patron = null, array $options = [])
    {
        $instance = $this->getInstanceByBibId($bibId);
        $query = [
            'query' => '(instanceId=="' . $instance->id
                . '" NOT discoverySuppress==true)'
        ];
        $items = [];

        $purchaseHistory = [];
        if (isset($this->config['Holdings']['purchase_history'])
            && $this->config['Holdings']['purchase_history'] === 'split'
        ) {
            $purchaseHistory = $this->getPurchaseHistoryData($bibId);
        }

        $serialIDs = $this->config['Holdings']['is_serial_stat_codes'] ?? [];
        $isSerial = count(array_intersect($serialIDs, $instance->statisticalCodeIds)) > 0;

        foreach ($this->getPagedResults(
            'holdingsRecords', '/holdings-storage/holdings', $query
        ) as $holding) {
            $query = [
                'query' => '(holdingsRecordId=="' . $holding->id
                    . '" NOT discoverySuppress==true)'
            ];
            $notesFormatter = function ($note) {
                return !($note->staffOnly ?? false)
                    && !empty($note->note) ? $note->note : '';
            };
            $textFormatter = function ($supplement) {
                $format = '%s %s';
                $supStat = $supplement->statement;
                $supNote = $supplement->note;
                $statement = trim(sprintf($format, $supStat, $supNote));
                return $statement ?? '';
            };
            $holdingNotes = array_filter(
                array_map($notesFormatter, $holding->notes ?? [])
            );
            $hasHoldingNotes = !empty(implode($holdingNotes));
            $holdingsStatements = array_map(
                $textFormatter,
                $holding->holdingsStatements ?? []
            );
            $holdingsSupplements = array_map(
                $textFormatter,
                $holding->holdingsStatementsForSupplements ?? []
            );
            $holdingsIndexes = array_map(
                $textFormatter,
                $holding->holdingsStatementsForIndexes ?? []
            );
            $holdingCallNumber = $holding->callNumber ?? '';
            $holdingCallNumberPrefix = $holding->callNumberPrefix ?? '';

            $purchases = [];
            foreach ($purchaseHistory as $historyItem) {
                if ($holding->id == $historyItem['holdings_id']) {
                    $purchases[] = $historyItem;
                }
            }

            $holdingCopyNumber = $holding->copyNumber ?? '';
            $UCcopyNumber = $holdingCopyNumber;
            $holdingData = clone $holding;
            $holdingData->status = (object) ['name' => ''];
            $holdingLocationId = $holding->effectiveLocationId;
            $holdingLocationData = $this->getLocationData($holdingLocationId);
            $holdingLocationName = $holdingLocationData['name'];
            $holdingLocationCode = $holdingLocationData['code'];


            foreach ($this->getPagedResults(
                'items', '/item-storage/items', $query, $holdingData
            ) as $item) {
                $itemNotes = array_filter(
                    array_map($notesFormatter, $item->notes ?? [])
                );
                $locationId = $item->effectiveLocationId;
                $locationData = $this->getLocationData($locationId);
                $locationName = $locationData['name'];
                $locationCode = $locationData['code'];
                $callNumberData = $this->chooseCallNumber(
                    $holdingCallNumberPrefix,
                    $holdingCallNumber,
                    $item->itemLevelCallNumberPrefix ?? '',
                    $item->itemLevelCallNumber ?? ''
                );
                $enum = $item->enumeration ?? '';

                // Get duedate
                $dueDate = '';
                if ($item->status->name == 'Not Available') {
                    $dueDate = $this->getDuedate($item->id);
                }

                // Override holdings copy number with item copy number if it exists.
                $itemCopyNumber = $item->copyNumber ?? '';
                if (!empty($itemCopyNumber)) {
                    $UCcopyNumber = $itemCopyNumber;
                }

                $itemStatCodeIds = $item->statisticalCodeIds;
                $itemAvailableStatCodes = $this->config['Holdings']['item_available_stat_codes'] ?? [];
                $itemHasAvailableStatCode = count(
                    array_intersect($itemStatCodeIds, $itemAvailableStatCodes)
                ) >= 1;
                $itemHideStatusStatCodes = $this->config['Holdings']['item_hide_status_stat_codes'] ??  [];
                $itemHasHideStatCode = count(
                    array_intersect($itemStatCodeIds, $itemHideStatusStatCodes)
                ) >= 1;

                $loanTypeName = '';
                $tempLoanTypeId = $item->temporaryLoanTypeId ?? '';
                $permLoanTypeId = $item->permanentLoanTypeId ?? '';
                $loanTypeId = !empty($tempLoanTypeId) ? $tempLoanTypeId : $permLoanTypeId;
                if (!empty($loanTypeId)) {
                    $loanData = $this->getLoanTypeData($loanTypeId);
                    $loanTypeName = $loanData['name'];
                }

                $items[] = $callNumberData + [
                    'id' => $bibId,
                    'item_id' => $item->id,
                    'holding_id' => $holding->id,
                    'number' => $enum ? $UCcopyNumber . ' : ' . $enum : $UCcopyNumber,
                    'barcode' => $item->barcode ?? '',
                    'status' => $item->status->name,
                    'availability' => $item->status->name == 'Available' || $itemHasAvailableStatCode,
                    'is_holdable' => $this->isHoldable($locationName),
                    'holdings_notes'=> $hasHoldingNotes ? $holdingNotes : null,
                    'item_notes' => !empty(implode($itemNotes)) ? $itemNotes : null,
                    'issues' => $holdingsStatements,
                    'supplements' => $holdingsSupplements,
                    'indexes' => $holdingsIndexes,
                    'location' => $locationName,
                    'location_code' => $locationCode,
                    'reserve' => 'TODO',
                    'addLink' => true,
                    'purchase_history' => $purchases,
                    'uc_copy_number' => $UCcopyNumber,
                    'holding_location' => $holdingLocationName,
                    'holding_location_code' => $holdingLocationCode,
                    'holding_callnumber_prefix' => $holdingCallNumberPrefix,
                    'holding_callnumber' => $holdingCallNumber,
                    'duedate' => $dueDate,
                    'hide_status' => $itemHasHideStatCode,
                    'item_statistical_code' => $itemStatCodeIds[0] ?? '',
                    'loan_type_id' => $loanTypeId,
                    'loan_type_name' => $loanTypeName,
                    'holding_copy_number' => $holdingCopyNumber,
                ];
            }
        }
        usort($items, function($a, $b) { return strnatcasecmp($a['number'], $b['number']); });
        if ($isSerial) {
            return array_reverse($items);
        }
        return $items;
    }

    /**
     * Get the unbound location for purchase history
     * if one exhists.
     *
     * @param array $notes
     *
     * @return string
     */
    protected function getPurchaseHistoryLocation($notes)
    {
        $phLocTypeId = $this->config['Holdings']['ph_loc_type_id'] ?? '';
        foreach ($notes as $note) {
            if ($note->holdingsNoteTypeId === $phLocTypeId && $note->staffOnly != true) {
                return $note->note;
            } 
        }
        return '';
    }

    /**
     * Get Purchase History Data
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial). It is used
     * by getHoldings() and getPurchaseHistory() depending on whether the purchase
     * history is displayed by holdings or in a separate list.
     *
     * @param string $id The record id to retrieve the info for
     *
     * @return array     An array with the acquisitions data on success.
     */
    protected function getPurchaseHistoryData($bibID)
    {
        $enumTemplate = $this->config['Holdings']['enumeration_format'] ?? '%%enum%%';
        $chronTemplate = $this->config['Holdings']['chronology_format'] ?? ' %%chron%%';
        $instance = $this->getInstanceByBibId($bibID);
        $query = [
            'query' => '(instanceId=="' . $instance->id
                . '" NOT discoverySuppress==true)'
        ];
        $purchaseHistory = [];
        foreach ($this->getPagedResults(
            'holdingsRecords', '/holdings-storage/holdings', $query
        ) as $holding) {
            $notes = $holding->notes ?? [];
            $holdingId = $holding->id;
            if (property_exists($holding, 'receivingHistory')) {
                $unboundLocation = $this->getPurchaseHistoryLocation($notes);
                foreach($holding->receivingHistory->entries as $entry) {
                    if ($entry->publicDisplay) {
                        $enum = $entry->enumeration;
                        $chron = $entry->chronology;
                        $issue = '';
                        if ($enum) {
                            $issue .= str_replace('%%enum%%', $enum, $enumTemplate);
                        }
                        if ($chron) {
                            $issue .= str_replace('%%chron%%', $chron, $chronTemplate);
                        }
                        array_push(
                            $purchaseHistory,
                            ['issue' => $issue,
                             'holdings_id' => $holdingId,
                             'unbound_location' => $unboundLocation]
                        );
                    }
                }
            }
        }
        return $purchaseHistory;
    }

    /**
     * This method returns information on recently received issues of a serial.
     *
     *     Input: Bibliographic record ID
     *     Output: Array of associative arrays, each with a single key:
     *         issue - String describing the issue
     *
     * Currently, most drivers do not implement this method, instead always returning
     * an empty array. It is only necessary to implement this in more detail if you
     * want to populate the “Most Recent Received Issues” section of the record
     * holdings tab.
     */
    public function getPurchaseHistory($bibID)
    {
        // Return empty array if purchase history is disabled or embedded
        // in holdings
        $setting = $this->config['Holdings']['purchase_history'] ?? true;
        return (!$setting || $setting === 'split')
            ? [] : $this->getPurchaseHistoryData($bibID);
    }

    /**
     * This method queries the ILS for a patron's current checked out items
     *
     * Input: Patron array returned by patronLogin method
     * Output: Returns an array of associative arrays.
     *         Each associative array contains these keys:
     *         duedate - The item's due date (a string).
     *         dueTime - The item's due time (a string, optional).
     *         dueStatus - A special status – may be 'due' (for items due very soon)
     *                     or 'overdue' (for overdue items). (optional).
     *         id - The bibliographic ID of the checked out item.
     *         source - The search backend from which the record may be retrieved
     *                  (optional - defaults to Solr). Introduced in VuFind 2.4.
     *         barcode - The barcode of the item (optional).
     *         renew - The number of times the item has been renewed (optional).
     *         renewLimit - The maximum number of renewals allowed
     *                      (optional - introduced in VuFind 2.3).
     *         request - The number of pending requests for the item (optional).
     *         volume – The volume number of the item (optional).
     *         publication_year – The publication year of the item (optional).
     *         renewable – Whether or not an item is renewable
     *                     (required for renewals).
     *         message – A message regarding the item (optional).
     *         title - The title of the item (optional – only used if the record
     *                                        cannot be found in VuFind's index).
     *         item_id - this is used to match up renew responses and must match
     *                   the item_id in the renew response.
     *         institution_name - Display name of the institution that owns the item.
     *         isbn - An ISBN for use in cover image loading
     *                (optional – introduced in release 2.3)
     *         issn - An ISSN for use in cover image loading
     *                (optional – introduced in release 2.3)
     *         oclc - An OCLC number for use in cover image loading
     *                (optional – introduced in release 2.3)
     *         upc - A UPC for use in cover image loading
     *               (optional – introduced in release 2.3)
     *         borrowingLocation - A string describing the location where the item
     *                         was checked out (optional – introduced in release 2.4)
     *
     * @param array $patron Patron login information from $this->patronLogin
     *
     * @return array Transactions associative arrays
     */
    public function getMyTransactions($patron)
    {
        $pnum = 1;
        if (isset($_GET['page'])) {
            $pnum = (int)$_GET['page'];
        }
        $query = ['query' => 'userId==' . $patron['id'] . ' and status.name==Open'];
        $transactions = [];
        $bib = null; // null bib for items we're not currently showing on the page.
        foreach ($this->getPagedResults(
            'loans', '/circulation/loans', $query
        ) as $trans) {

            $authors = implode(', ', array_map(function($c) {
                return $c->name;
            }, $trans->item->contributors ?? []));

            $loanDate = date_create($trans->loanDate);
            $date = date_create($trans->dueDate);
            $transactions[] = [
                'duedate' => date_format($date, "j M Y"),
                'dueTime' => date_format($date, "g:i:s a"),
                // TODO: Due Status
                // 'dueStatus' => $trans['itemId'],
                'id' => $bib,
                'item_id' => $trans->item->id,
                'barcode' => $trans->item->barcode,
                'renew' => $trans->renewalCount ?? 0,
                'renewable' => true,
                'title' => $trans->item->title ?? '',
                'location' => '',
                'loan_policy_id' => $trans->loanPolicyId ?? '',
                'loan_policy' => '',
                'status' => $trans->item->status->name ?? '',
                'callnumber' => '',
                'recalled' => $trans->action == 'recallrequested',
                'duedate_raw' => $date,
                'loandate_raw' => $loanDate,
                'item_instance_id' => $trans->item->instanceId,
                'authors' => $authors,
            ];
        }

        // Get the sort order
        $sort = $this->getCoiSort();

        // Sort before getting item informaion.
        if (!empty($transactions)) {
            switch($sort) {
                case 'title':
                    usort($transactions, $this->alphaStringComp('title'));
                    break;
                case 'author':
                    usort($transactions, $this->alphaStringComp('authors'));
                    break;
                default:
                    usort($transactions, $this->dateComp('duedate_raw'));
                    break;
            }
        }

        // Only get item information and bib number, etc. for items that we're planning
        // to show on the screen at any given time.
        $pageSize = $this->config['MyAccount']['checked_out_page_size'] ?? 50;
        $maxPage = $pageSize * $pnum;
        $minPage = $maxPage - $pageSize;
        $slice = array_slice($transactions, $minPage, $pageSize, $preserve_keys=true);
        foreach ($slice as $i => $trans) {
            $callnumber = '';
            $locationData = '';
            $itemId = $trans['item_id'];
            $query = ['query' => 'id==' . $itemId];
            // There is always only 1 item in this loop which is why we can set the return value
            // outside of it. We only use this because the generator executes faster.
            foreach ($this->getPagedResults(
                'items', '/item-storage/items', $query
            ) as $item) {
                if (!empty($item->effectiveCallNumberComponents->callNumber)) {
                    $callnumber = $item->effectiveCallNumberComponents->callNumber;
                }
                if (!empty($item->copyNumber)) {
                    $callnumber .= ' ' . $item->copyNumber;
                }
                $effectiveLocationId = $item->effectiveLocationId;
                $locationData = $this->getLocationData($effectiveLocationId);

                $loanPolicyId = $trans['loan_policy_id'];
                $loanPolicyData = '';
                if (!empty($loanPolicyId)) {
                    $loanPolicyData = $this->getLoanPolicyData($loanPolicyId);
                }

                // Cache bib numbers by item id to improve login speed for users
                // who login a second time. For some reason getBibId is very slow.
                $cacheKey = 'loanBibMap';
                $loanBibMap = $this->getCachedData($cacheKey);
                if (!empty($loanBibMap[$itemId])) {
                    $bib = $loanBibMap[$itemId]['bib'];
                } else {
                    $bib = $this->getBibId($trans['item_instance_id']);
                    $loanBibMap[$itemId] = compact('bib');
                }
                $this->putCachedData($cacheKey, $loanBibMap);

                // Set bib number and item information
                $transactions[$i]['id'] = $bib;
                $transactions[$i]['location'] = $locationData['name'] ?? '';
                $transactions[$i]['loan_policy'] = $loanPolicyData['desc'] ?? '';
                $transactions[$i]['callnumber'] = $callnumber;
            }
        }
        return $transactions;
    }

    /**
     * This method queries the ILS for a patron's current holds
     *
     * Input: Patron array returned by patronLogin method
     * Output: Returns an array of associative arrays, one for each hold associated
     * with the specified account. Each associative array contains these keys:
     *     type - A string describing the type of hold – i.e. hold vs. recall
     * (optional).
     *     id - The bibliographic record ID associated with the hold (optional).
     *     source - The search backend from which the record may be retrieved
     * (optional - defaults to Solr). Introduced in VuFind 2.4.
     *     location - A string describing the pickup location for the held item
     * (optional). In VuFind 1.2, this should correspond with a locationID value from
     * getPickUpLocations. In VuFind 1.3 and later, it may be either
     * a locationID value or a raw ready-to-display string.
     *     reqnum - A control number for the request (optional).
     *     expire - The expiration date of the hold (a string).
     *     create - The creation date of the hold (a string).
     *     position – The position of the user in the holds queue (optional)
     *     available – Whether or not the hold is available (true/false) (optional)
     *     item_id – The item id the request item (optional).
     *     volume – The volume number of the item (optional)
     *     publication_year – The publication year of the item (optional)
     *     title - The title of the item
     * (optional – only used if the record cannot be found in VuFind's index).
     *     isbn - An ISBN for use in cover image loading (optional)
     *     issn - An ISSN for use in cover image loading (optional)
     *     oclc - An OCLC number for use in cover image loading (optional)
     *     upc - A UPC for use in cover image loading (optional)
     *     cancel_details - The cancel token, or a blank string if cancel is illegal
     * for this hold; if omitted, this will be dynamically generated using
     * getCancelHoldDetails(). You should only fill this in if it is more efficient
     * to calculate the value up front; if it is an expensive calculation, you should
     * omit the value entirely and let getCancelHoldDetails() do its job on demand.
     * This optional feature was introduced in release 3.1.
     *
     * @param array $patron Patron login information from $this->patronLogin
     *
     * @return array Associative array of holds information
     */
    public function getMyHolds($patron)
    {
        $query = [
            'query' => '(requesterId == "' . $patron['id'] . '"  ' .
            'and status == Open*)'
        ];
        $holds = [];
        foreach ($this->getPagedResults(
            'requests', '/request-storage/requests', $query
        ) as $hold) {
            $pickupServicePoint = '';
            $pickupServicePointId = $hold->pickupServicePointId;
            $servicePointData = $this->getServicePointById($pickupServicePointId);
            if (!empty($servicePointData)) {
                $pickupServicePoint = $servicePointData->servicepoints[0]->name;
            }
            $requestDate = date_create($hold->requestDate);
            // Set expire date if it was included in the response
            $expireDate = isset($hold->requestExpirationDate)
                ? date_create($hold->requestExpirationDate) : null;
            // Set holdShelfExpirationDate if it was included in the response
            $holdShelfExpirationDate = isset($hold->holdShelfExpirationDate)
                ? date_format(date_create($hold->holdShelfExpirationDate), "j M Y") : null;
            $holds[] = [
                'type' => $hold->requestType,
                'create' => date_format($requestDate, "j M Y"),
                'expire' => isset($expireDate)
                    ? date_format($expireDate, "j M Y") : "",
                'id' => $this->getBibId(null, null, $hold->itemId),
                'item_id' => $hold->itemId,
                'reqnum' => $hold->id,
                'title' => $hold->instance->title ?? $hold->item->title ?? '',
                'status' => $hold->status,
                'pickup_service_point' => $pickupServicePoint,
                'hold_shelf_expiration_date' => $holdShelfExpirationDate
            ];
        }
        return $holds;
    }

    /**
     * TEMPORARY: This should only be needed until we upgrade to a version
     * of VuFind with the fixes for the Folio Lotus release.
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details.
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available)
     */
    public function placeHold($holdDetails)
    {
        $default_request = $this->config['Holds']['default_request'] ?? 'Hold';
        try {
            $requiredBy = $this->dateConverter->convertFromDisplayDate(
                'Y-m-d',
                $holdDetails['requiredBy']
            );
        } catch (Exception $e) {
            $this->throwAsIlsException($e, 'hold_date_invalid');
        }
        $instance = $this->getInstanceByBibId($holdDetails['id']);
        $requestBody = [
            'itemId' => $holdDetails['item_id'],
            'holdingsRecordId' => $holdDetails['holding_id'] ?? '',
            'instanceId' => $instance->id,
            'requestLevel' => 'Item',
            'requestType' => $holdDetails['status'] == 'Available'
                ? 'Page' : $default_request,
            'requesterId' => $holdDetails['patron']['id'],
            'requestDate' => date('c'),
            'fulfilmentPreference' => 'Hold Shelf',
            'requestExpirationDate' => $requiredBy,
            'patronComments' => $holdDetails['comment'] ?? '',
            'pickupServicePointId' => $holdDetails['pickUpLocation']
        ];
        $response = $this->makeRequest(
            'POST',
            '/circulation/requests',
            json_encode($requestBody)
        );
        if ($response->isSuccess()) {
            $json = json_decode($response->getBody());
            $result = [
                'success' => true,
                'status' => $json->status
            ];
        } else {
            try {
                $json = json_decode($response->getBody());
                $result = [
                    'success' => false,
                    'status' => $json->errors[0]->message
                ];
            } catch (Exception $e) {
                $this->throwAsIlsException($e, $response->getBody());
            }
        }
        return $result;
    }

    /**
     * This is a copy of VuFind/ILS/Driver/AbstractAPI.php with the case statement
     * taken out. We do not want to throw a RecordMissing for an entire record when
     * one of the auxiliary data APIs returns a 404.
     *
     * @param string $method  GET/POST/PUT/DELETE/etc
     * @param string $path    API path (with a leading /)
     * @param array  $params  Parameters object to be sent as data
     * @param array  $headers Additional headers
     *
     * @return \Laminas\Http\Response
     */
    public function makeForgivingRequest($method = "GET", $path = "/", $params = [],
        $headers = []
    ) {
        $client = $this->httpService->createClient(
            $this->config['API']['base_url'] . $path,
            $method,
            120
        );

        // Add default headers and parameters
        $req_headers = $client->getRequest()->getHeaders();
        $req_headers->addHeaders($headers);
        [$req_headers, $params] = $this->preRequest($req_headers, $params);

        if ($this->logger) {
            $this->debugRequest($method, $path, $params, $req_headers);
        }

        // Add params
        if ($method == 'GET') {
            $client->setParameterGet($params);
        } else {
            if (is_string($params)) {
                $client->getRequest()->setContent($params);
            } else {
                $client->setParameterPost($params);
            }
        }
        $response = $client->send();
        return $response;
    }

    /**
     * See if a patron has automated blocks.
     *
     * @param string $patronId, UUID
     *
     * @return array
     */
    public function getAutomatedBlocks($patronId)
    {
        $response = $this->makeForgivingRequest(
            'GET', '/automated-patron-blocks/' . $patronId
        );
        $data = json_decode($response->getBody());
        return $data->automatedPatronBlocks;
    }

    /**
     * See if a patron has manual blocks.
     *
     * @param string $patronId, UUID
     *
     * @return array
     */
    public function getManualBlocks($patronId)
    {
        $query = ['query' => 'userId==' . $patronId];
        $response = $this->makeForgivingRequest(
            'GET', '/manualblocks', $query
        );
        $data = json_decode($response->getBody());
        return $data->manualblocks;
    }

    /**
     * See if a patron is active in Folio.
     *
     * @param string $patronId, UUID
     *
     * @return bool
     */
    public function isPatronActive($patronId)
    {
        $query = ['query' => 'id==' . $patronId];
        $response = $this->makeForgivingRequest(
            'GET', '/users', $query
        );
        $data = json_decode($response->getBody());
        if (count($data->users) > 0) {
            return $data->users[0]->active;
        }
        return false;
    }

    /**
     * Check for account blocks.
     *
     * @param array $patron The patron data
     *
     * @return array|boolean    An array of block messages or false
     */
    public function getAccountBlocks($patron)  {
        $blocks = [];
        if (!$this->isPatronActive($patron['id'])) {
            array_push($blocks, 'Patron account is inactive. Contact ipo@uchicago.edu with any questions.');
        }
        foreach ($this->getAutomatedBlocks($patron['id']) as $block) {
            array_push($blocks, $block->message);
        }
        foreach ($this->getManualBlocks($patron['id']) as $manBlock) {
            array_push($blocks, $manBlock->patronMessage);
        }
        return $blocks;
    }

    /**
     * Gets loan policies from the /loan-policy-storage/loan-policies/
     * endpoint and sets an array of loan policy IDs to descriptions.
     * Descriptions are set from description.
     *
     * @return array
     */
    protected function getLoanPolicies()
    {
        $cacheKey = 'loanPolicyMap';
        $loanPolicyMap = $this->getCachedData($cacheKey);
        if (null === $loanPolicyMap) {
            $loanPolicyMap = [];
            foreach ($this->getPagedResults(
                'loanPolicies', '/loan-policy-storage/loan-policies'
            ) as $loanPolicy) {
                if (isset($loanPolicy->description)) {
                $desc = $loanPolicy->description;
                $loanPolicyMap[$loanPolicy->id] = compact('desc');
                }
            }
        }
        $this->putCachedData($cacheKey, $loanPolicyMap);
        return $loanPolicyMap;
    }

    /**
     * Get loan policy data by ID.
     *
     * @param string $loanPolicyId, UUID
     *
     * @return array
     */
    public function getLoanPolicyData($loanPolicyId)
    {
        $loanPolicyMap = $this->getLoanPolicies();
        $desc = '';
        if (array_key_exists($loanPolicyId, $loanPolicyMap)) {
            return $loanPolicyMap[$loanPolicyId];
        } else {
            // if key is not found in cache, the loan policy could have
            // been added before the cache expired so check again
            $loanPolicyResponse = $this->makeRequest(
                'GET', '/loan-policy-storage/loan-policies/' . $loanPolicyId
            );
            if ($loanPolicyResponse->isSuccess()) {
                $loanPolicy = json_decode($loanPolicyResponse->getBody());
                $desc = $location->description;
            }
        }
        return compact('desc');
    }

    /**
     * Get service point by ID.
     *
     * @param string $itemId, UUID
     *
     * @return array
     */
    public function getServicePointById($servicePointId)
    {
        $query = ['query' => 'id==' . $servicePointId];
        $response = $this->makeForgivingRequest(
            'GET', '/service-points', $query
        );
        $data = json_decode($response->getBody());
        return $data;
    }

    protected function dateComp($key)
    {
        return function ($a, $b) use ($key) {
            if ($a[$key] == $b[$key]) {
                return $a['id'] < $b['id'] ? -1 : 1;
            }
            return $a[$key] < $b[$key] ? -1 : 1;
        };
    }

    protected function alphaStringComp($key)
    {
        return function($a, $b) use ($key) {
            return strcasecmp(
                preg_replace('/[^ \w]+/', '', $a[$key]),
                preg_replace('/[^ \w]+/', '', $b[$key])
            );
        };
    }

    public function getCoiSort()
    {
        if (isset($_GET['sort'])) {
            return $_GET['sort'];
        } else {
            return $this->config['MyAccount']['checkedOutItemsSort'] ?? 'dueDate';
        }
    }
}

