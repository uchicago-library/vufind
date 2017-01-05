<?php
namespace UChicago\StorageRequest;

use SimpleXMLElement,
    VuFindSearch\Backend\Exception\HttpErrorException,
    Zend\Http\Request,
    Zend\Http\Client;

class StorageRequest
{
    protected $config;

    protected $patron_barcode;

    public function __construct($config, $patron_barcode) 
    {
        $this->config = $config;
        $this->patron_barcode = $patron_barcode;

        // Use a session variable for the time being.
        // Switch to the database later. 
        if (!array_key_exists('asr', $_SESSION)) {
            $_SESSION['asr'] = [];
        }
    }

    public function getRequests() 
    {
        return $_SESSION['asr'];
    }

    public function addRequest($bib, $barcode, $catalog)
    {
        $in_array = false;
        foreach ($_SESSION['asr'] as $e) {
            if ($e['bib'] == $bib && $e['barcode'] == $barcode) {
                $in_array = true;
                break;
            }
        }
        if (!$in_array) {
            $xml = $catalog->getRecord($bib);

            // get the first title that appears.
            $title = '';
            $p = sprintf('/response/result/doc/arr[@name="ItemBarcode_search" and str/text()="%s"]/parent::doc', $barcode);
            $elements = $xml->xpath($p);
            foreach ($elements as $e) {
                $se = $e->xpath('arr[@name="Title_display"]/str');
                $title = $se[0]->asXML();
                if ($title != '') {
                    break;
                }
            }

            $callNumber = '';
            $copyNumber = '';
            $volumeNumber = '';
            $p = sprintf('/response/result/doc/arr[@name="ItemBarcode_search" and str/text()="%s"]/parent::doc', $barcode);
            $elements = $xml->xpath($p);
            foreach ($elements as $e) {
                $se = $e->xpath('arr[@name="HoldingsCallNumber_display"]/str');
                if ($se[0] and $se[0]->asXML()) {
                    $callNumber = $se[0]->asXML();
                }
                $se = $e->xpath('str[@name="CopyNumber_search"]');
                if ($se[0] and $se[0]->asXML()) {
                    $copyNumber = $se[0]->asXML();
                }
                $se = $e->xpath('str[@name="Enumeration_display"]');
                if ($se[0] and $se[0]->asXML()) {
                    $volumeNumber = $se[0]->asXML();
                }
            }

		    $_SESSION['asr'][] = [
		        'bib' => $bib,
		        'barcode' => $barcode,
		        'title' => $title,
		        'callNumber' => $callNumber,
		        'copyNumber' => $copyNumber,
		        'volumeNumber' => $volumeNumber
		    ];
		}
        return;
    }

    public function removeRequest($bib, $barcode) 
    {
        $a = 0;
        foreach ($_SESSION['asr'] as $e) {
            if ($e['bib'] == $bib && $e['barcode'] == $barcode) {
                unset($_SESSION['asr'][$a]);
                // re-index
                $_SESSION['asr'] = array_values($_SESSION['asr']);
                break;
            }
            $a++;
        }
    }

    public function removeAllRequests() 
    {
        $_SESSION['asr'] = [];
    }

    public function placeRequest($barcodes, $bibs, $location)
    {
        $items = [];
        $i = 0;
        while ($i < count($barcodes)) {
            $items[] = [
                'barcode' => $barcodes[$i],
                'bib' => $bibs[$i]
            ];
            $i++;
        }
        foreach ($items as $item) {
            $this->placeIndividualRequest($item, $location);
        }

        $this->removeAllRequests();
    }

    public function placeIndividualRequest($item, $location)
    {
        $s = "<placeASRRequest/>";
        $xml = new SimpleXMLElement($s);

        $xml->addChild('itemBarcode', $item['barcode']);
        $xml->addChild('patronBarcode', $this->patron_barcode);
        $xml->addChild('operatorId', $this->config['Catalog']['place_asr_request_operator_id']);
        $xml->addChild('pickUpLocation', $location);

		$request = new Request();
		$request->setMethod(Request::METHOD_POST);
        $request->setContent($xml->asXML());
		$request->setUri($this->config['Catalog']['place_asr_request_url']);
        $request->getHeaders()->addHeaders(
            [
             'Accept' => 'application/xml',
             'Content-Type' => 'application/xml'
            ]
        );
    
		$client = new Client();
		$client->setOptions(array('timeout' => 60));
				
		try {
		    $response = $client->dispatch($request);
            $responseXML = new SimpleXMLElement($response->getBody());

            $nl = $responseXML->xpath('/asrResponse/code');
            $code = (string)($nl[0]);

            $nl = $responseXML->xpath('/asrResponse/message');
            $message = (string)($nl[0]);
            // figure out how to throw exceptions correctly here. 
            if ($code != '001') {
                echo $code;
                echo $message;
            }
		} catch (Exception $e) {
		    throw new ILSException($e->getMessage());
		}

		if (!$response->isSuccess()) {
		    throw HttpErrorException::createFromResponse($response);
		}
    }
}
