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
            if (isset($holdings) && $total === 0 && $holdings->holdingsTypeId != $eHoldingTypeId) {
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

            $UCcopyNumber = $holding->copyNumber ?? '';
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
                $permLoanTypeId = $item->permanentLocationId ?? '';
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
                ];
            }
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
        $query = ['query' => 'userId==' . $patron['id'] . ' and status.name==Open'];
        $transactions = [];
        foreach ($this->getPagedResults(
            'loans', '/circulation/loans', $query
        ) as $trans) {
            $date = date_create($trans->dueDate);
            $transactions[] = [
                'duedate' => date_format($date, "j M Y"),
                'dueTime' => date_format($date, "g:i:s a"),
                // TODO: Due Status
                // 'dueStatus' => $trans['itemId'],
                'id' => $this->getBibId($trans->item->instanceId),
                'item_id' => $trans->item->id,
                'barcode' => $trans->item->barcode,
                'renew' => $trans->renewalCount ?? 0,
                'renewable' => true,
                'title' => $trans->item->title,
                'recalled' => $trans->action == 'recallrequested',
            ];
        }
        return $transactions;
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
}

