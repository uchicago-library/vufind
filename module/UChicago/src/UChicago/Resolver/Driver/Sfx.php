<?php
namespace Chicago\Resolver\Driver;

class Sfx extends \VuFind\Resolver\Driver\Sfx
{
    public function parseLinks($xmlstr)
    {
        $records = array(); // array to return
        try {
            $xml = new \SimpleXmlElement($xmlstr);
        } catch (\Exception $e) {
            return $records;
        }

        $root = $xml->xpath("//ctx_obj_targets");
        $xml = $root[0];
   
        foreach ($xml->children() as $target) {
            $record = array();
            $record['title'] = (string)$target->target_public_name;
            $record['href'] = (string)$target->target_url;
            $record['service_type'] = (string)$target->service_type;
            $record['coverage'] = (string)$target->coverage->coverage_text
                ->threshold_text->coverage_statement . ' ' .
            (string)$target->coverage->coverage_text->embargo_text->embargo_statement;

            array_push($records, $record);
        }
        return $records;
    }
}
