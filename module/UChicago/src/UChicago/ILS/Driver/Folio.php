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
                if ($item->status->name == 'Checked out') {
                    $dueDate = $this->getDuedate($item->id);
                }

                // Override holdings copy number with item copy number if it exists.
                $itemCopyNumber = $item->copyNumber ?? '';
                if (!empty($itemCopyNumber)) {
                    $UCcopyNumber = $itemCopyNumber;
                }
                $items[] = $callNumberData + [
                    'id' => $bibId,
                    'item_id' => $item->id,
                    'holding_id' => $holding->id,
                    'number' => $enum ? $UCcopyNumber . ' : ' . $enum : $UCcopyNumber,
                    'barcode' => $item->barcode ?? '',
                    'status' => $item->status->name,
                    'availability' => $item->status->name == 'Available',
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

}

