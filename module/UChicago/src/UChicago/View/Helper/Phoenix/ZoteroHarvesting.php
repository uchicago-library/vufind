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

	/* pull down JSON response from VuFind API based on Bib Id */
	function get_json($bib_id) {
	    $host = "https://dldc2.lib.uchicago.edu";
	    // $host = "https://catalog.lib.uchicago.edu";
	    $api_route = "/vufind/api/v1/record";
	    $query_string = "?field[]=rawData&id=";
	    $url = $host . $api_route . $query_string . $bib_id;
	    $response_contents = file_get_contents($url);
	    $response_json = json_decode($response_contents);
	    return $response_json;
	}

	/* retrieve item from JSON based on an array index/object
	   attribute path, hushing key error exceptions

	   the convention for the path is e.g. ['key1', 1, 'key2'] means
	   'look up key1', then access array by index 1, then look up
	   key2'. */
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
	    } catch (\Throwable $exn) {
		return '';
	    };
	}

	/* put quotes around a string */
	function quote($str) {
	    return '"' . $str . '"';
	}

	/* put angle brackets around a string */
	function tag($str) {
	    return '<' . $str . '>';
	}

	/* given a key and value, make an HTML meta element string
	   that the Zotero plugin will interpret as a key and value to go
	   into a bibliographic entry */
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
	    return join(PHP_EOL, array_unique($output_list));
	}

	function format_switchboard($format) {
	    $book_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['citation_series_title', ['series', 0]],
		['citation_author', ['author', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
		['DC.Identifier', ['issn', 0]],
		['citation_abstract', ['contents', 2]],
	    ];

	    $map_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['DC.Creator', ['author', 0]],
		['citation_author', ['author', 0]],
		['DC.Creator', ['author2', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
	    ];

	    $audio_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['citation_author', ['author', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
	    ];

	    $thesis_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['citation_author', ['author', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
		['citation_abstract', ['contents', 2]],
	    ];

	    $video_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['citation_author', ['author', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
	    ];

	    $archive_schema = [
		['DC.Location', ['publication_place', 0]],
		['DC.Publisher', ['publisher', 0]],
		['DC.Date', ['publishDate', 0]],
		['DC.Language', ['language', 0]],
		['DC.Title', ['title']],
		['citation_series_title', ['series', 0]],
		['citation_author', ['author', 0]],
		['citation_author', ['author2', 0]],
		['citation_author', ['author3', 0]],
		['citation_author', ['author4', 0]],
		['citation_author', ['author5', 0]],
		['DC.Identifier', ['isbn', 0]],
	    ];

	    switch ($format) {
		case "Dissertations":
		    return $thesis_schema;
		case "Book":
 		    return $book_schema;
		case "Map":
 		    return $map_schema;
		case "Audio":
		    return $audio_schema;
		case "Video":
		    return $video_schema;
		case "Archives/Manuscripts":
		    return $archive_schema;
		default:
		    return $book_schema;
	    }
	}

	function vufind_to_zotero($format) {
	    switch($format) {
 		case "Audio":
 		    return "audioRecording";
 		case "Dissertations":
 		    return "Thesis";
		case "Video":
 		    return "videoRecording";
		case "Archives/Manuscripts":
 		    return "manuscript";
		default:
		    return $format;
	    }
	}

	function array_to_format($format_array) {
	    foreach ($format_array as $format) {
		switch ($format) {
		    case "Dissertations":
			return "Dissertations";
		    case "Book":
			return "Book";
		    case "Audio":
			return "Audio";
		    case "Map":
			return "Map";
		    case "Video":
			return "Video";
		    case "Archives/Manuscripts":
			return "Archives/Manuscripts";
		    case "Journal":
			return "Book";
		}
	    }
	    return get_from_json([1], $format_array);
	}

	function build_meta_tags($bib_id) {
	    $json = get_json($bib_id);
	    $record = $json->records[0]->rawData;
	    $format_array = get_from_json(['format'], $record);
	    $format = array_to_format($format_array);
	    $schema = format_switchboard($format);
	    $vformat = vufind_to_zotero($format);
	    $format_tag= mk_meta_element("DC.Type", $vformat);

	    /* return $format; */
	    
	    if ($schema) {
		return $format_tag . "\n". table_to_html($schema, $record);
	    } else {
		return '';
	    }
	}
	
	$config = $this->bibHarvestingConfig;
	$harvesting_is_on = $config['harvest_zotero'];
	$route = $_SERVER['REQUEST_URI'];
	$record_page = preg_match('`^/vufind/record/\d+$`i', $route);

	$url_params = explode('/', $route);
	$bib_id = array_pop($url_params);


	/* return build_meta_tags($bib_id); */
	
	if ($harvesting_is_on and $record_page) {
	    $url_params = explode('/', $route);
	    $bib_id = array_pop($url_params);
	    return build_meta_tags($bib_id);
	} else {
	    return '';
	}
    }
}
