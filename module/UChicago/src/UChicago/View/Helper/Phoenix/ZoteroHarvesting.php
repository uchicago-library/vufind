<?
namespace UChicago\View\Helper\Phoenix;
use Laminas\View\Helper\AbstractHelper;

/**
 * Zotero meta tag view helper. 
 *
 * @category View_Helpers
 * @package ServiceLinks
 * @author Matt Teichman <teichman@uchicago.edu>
 */
class ZoteroHarvesting extends \Laminas\View\Helper\AbstractHelper {

    public function __construct($config=false) {
         $this->bibHarvestingConfig = $config;
    }

    public function happy() {
        return '<meta name="DC.Type" content="Dissertations"> <meta name="DC.Creator" content="Teichman, Matthew"> <meta name="DC.Title" content="Characterizing kinds: A semantics for generic sentences /"> <meta name="DC.Coverage" content="Ann Arbor :"> <meta name="DC.Date" content="2015"> <meta name="DC.Language" content="English">';
    }

    public function addMetaTags() {
      function get_json($bib_id) {
	$host = "https://dldc2.lib.uchicago.edu";
	// $host = "https://catalog.lib.uchicago.edu";
	$api_route = "/vufind/api/v1/record";
	$query_string = "?field[]=rawData&id=";
	$url = $host . $api_route . $query_string . $bib_id;
	$example_str = file_get_contents($url);
	$example_json = json_decode($example_str);
	return $example_json;
      }
    
      function quote($str) {
	return '"' . $str . '"';
      }
    
      function tag($str) {
	return '<' . $str . '>';
      }

      function mk_meta_element($key, $value) { 
	$tag_type = 'meta';
	$name_attr = 'name';
	$content_attr = 'content';

	$body = $tag_type .
	  ' '  .
	  $name_attr .
	  '=' .
	  quote($key) .
	  ' ' .
	  $content_attr .
	  '=' .
	  quote($value);

	$element = tag($body);

	return $element;
      }

      function get_from_json($path, $object) {
	$current = $object;
	try {
	  foreach ($path as $step) {
	    if (gettype($current) === "object") {
	      $current = $current->$step;
	    } else {
	      $current = $current[$step];
	    } 
	  };
	  return $current;
	} catch (Exception $exn) {
	  return NULL;
	};
      }

      function pair_to_element($pair, $record) {
	$dc_key = $pair[0];
	$path = $pair[1];
	$val = get_from_json($path, $record);
	if ($val) {
	  return mk_meta_element($dc_key, $val);
	} else {
	  return '';
	}
      }

      function table_to_html($table, $record) {
	$output_list = [];
	foreach ($table as $row) {
	  $next = pair_to_element($row, $record);
	  if ($next) {
	    array_push($output_list, $next);
	  }
	}
	return join(PHP_EOL, $output_list);
      }

      function build_meta_tags($bib_id) {
	$json = get_json($bib_id);
	$record = $json->records[0]->rawData;

	$book_pairs = [
		       ['DC.Type', ['format', 1]],
		       ['DC.Creator', ['author', 0]],
		       ['DC.Title', ['title']],
		       ['DC.Coverage', ['publication_place', 0]], #check this too
		       ['DC.Date', ['publishDate', 0]],
		       ['DC.Language', ['language', 0]],
		       ];
    
	return table_to_html($book_pairs, $record);
      }

      $config = $this->bibHarvestingConfig;
      $harvesting_is_on = $config['harvest_zotero'];
      $route = $_SERVER['REQUEST_URI'];
      $record_page = preg_match('`^/vufind/record/\d+$`i', $route);

      if ($harvesting_is_on and $record_page) {
	$url_params = explode('/', $route);
	$bib_id = array_pop($url_params);
	return build_meta_tags($bib_id);
      } else {
	'';
      }
    }
}
