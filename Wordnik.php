<?
/**
 * This is a simple interface to the Wordnik API, which wraps API calls with
 * PHP methods, and returns arrays of standard php objects containing the results.
 *
 * These examples only show a few of the available API calls (for getting definitions,
 * examples, related words, Wordnik's word of the say, and random words). See the full list here:
 * http://docs.wordnik.com/api/methods
 *
 * To use the API you'll need a key, which you can apply for here:
 * http://api.wordnik.com/signup/
 *
 * After you receive your key assign it to the API_KEY constant, below.
 * Then, to get an array of definition objects, do something like this:
 *
 * require_once('Wordnik.php');
 * $definitions = Wordnik::instance()->getDefinitions('donkey');
 *
 * $definitions will hold an array of objects, which can be accessed individually:
 * $definitions[0]->headword
 *
 * Or you can loop through the results and display info about each,
 * which could look something like this in a template context:
 * 
 * <ul>
 * <? foreach ($definitions as $definition): ?>
 *     <li>
 *       <strong><?= $definition->headword ?></strong: 
 *       <?= $definition->text ?>
 *     </li>
 * <? endforeach; ?>
 * </ul>
 *
 * Please send comments or questions to apiteam@wordnik.com.
 *
 */
class Wordnik {
	
	const API_KEY = "85b993ddaabe04346e0090d379b02d18ad04bda75d4e0ecca";//"YOUR_API_KEY_HERE";
	const BASE_URI = 'http://api.wordnik.com/api';
	
	/** If there's an existing Wordnik instance, return it, otherwise create and return a new one. */
	private static $instance;
	public static function instance() {
		if (self::$instance == NULL) {
			self::$instance = new Wordnik();
		}
		
		return self::$instance;
	}

  /*
   *Pass in a word as a string, get back an array of phrases containing this word (bigrams).
   *Optional params:
   *  count : the number of results returned (default=10)
   *More info: http://docs.wordnik.com/api/methods#phrases 
   */
	public function getPhrases($word, $count=10) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getPhrases expects word to be a string");
		}
    $params = array();
    $params['count'] = $count;
		return $this->call_api('/word.json/' . rawurlencode($word) . '/phrases', $params);
	}
	
  /*
	 *Pass in a word as a string, get back an array of definitions.
   *Optional params:
   *  count : the number of results returned (default=10)
   *  part_of_speech : only get definitions with a specific part(s) of speech. 
   *More info: http://docs.wordnik.com/api/methods#defs 
   */
	public function getDefinitions($word, $count=10, $part_of_speech=null) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getDefinitions expects word to be a string");
		}
    $params = array();
    $params['count'] = $count;
    if (isset($part_of_speech)) {
      $params['partOfSpeech'] = $part_of_speech;
    }
		return $this->call_api('/word.json/' . rawurlencode($word) . '/definitions', $params);
	}

  /*
   *Pass in a word as a string, get back an array of example sentences. 
   *More info: http://docs.wordnik.com/api/methods#examples
   */
	public function getExamples($word) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getExamples expects word to be a string");
		}
		return $this->call_api( '/word.json/' . rawurlencode($word) . '/examples' );
	}
	
  /*
	 *Pass in a word as a string, get back an array of related words.
   *Optional params:
   *  count : the number of results returned (default=10)
   *  type : only get definitions with a specific type of relation (e.g., 'synonym' or 'antonym')
   *More info: http://docs.wordnik.com/api/methods#relateds
   */
	public function getRelatedWords($word, $count=10, $type=null) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getRelatedWords expects word to be a string");
		}
    $params = array();
    $params['count'] = $count;
    if (isset($type)) {
      $params['type'] = $type;
    }
		return $this->call_api('/word.json/' . rawurlencode($word) . '/related', $params);
	}

  /*
   *Pass in a word as a string, get back frequency data.
   *More info: http://docs.wordnik.com/api/methods#frequency
   */
	public function getFrequency($word) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getFrequency expects word to be a string");
		}
		return $this->call_api('/word.json/' . rawurlencode($word) . '/frequency');
	}

  /*
   *Pass in a word as a string, get back punctuation factor.
   *More info: http://docs.wordnik.com/api/methods#frequency
   */
	public function getPunctuation($word) {
		if(is_null($word) || trim($word) == '') {
			throw new InvalidParameterException("getPunctuation expects word to be a string");
		}
		return $this->call_api('/word.json/' . rawurlencode($word) . '/punctuationFactor');
	}

	/** Pass in a word as a string, get back the Word of the Day. */
	public function getWordOfTheDay() {
		return $this->call_api( '/wordoftheday.json/' );
	}
	
	/** Pass in a word as a string, get back a random word. */
	public function getRandomWord() {
		return $this->call_api( '/words.json/randomWord' );
	}
	
	/** Utility method to call json apis.
	  * This presumes you want JSON back; could be adapted for XML pretty easily. */
	private function call_api($url, $params=array(), $method='get') {
		
		$data = null;

		$headers = array();
		$headers[] = "Content-type: application/json";
		$headers[] = "api_key: " . self::API_KEY;

		$url = (self::BASE_URI . $url);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_TIMEOUT, 5); // 5 second timeout
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the result on success, rather than just TRUE
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    if ($method=='get' && !empty($params)) { // set query params if method is get
		  $url = ($url . '?' . http_build_query($params));
    } else if ($method=='post') { // set post data if the method is post
      curl_setopt($curl,CURLOPT_POST,true);
      curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($params));
    }

		curl_setopt($curl, CURLOPT_URL, $url);
		
    // make the request
		$response = curl_exec($curl);
		$response_info = curl_getinfo($curl);

    // handle the response based on the http code
		if ($response_info['http_code'] == 0) {
			throw new Exception( "TIMEOUT: api call to " . $url . " took more than 5s to return" );
		} else if ($response_info['http_code'] == 200) {
			$data = json_decode($response);
		} else if ($response_info['http_code'] == 401) {
			throw new Exception( "Unauthorized API request to " . $url . " . Have you specified your API Key?" );
		} else if ($response_info['http_code'] == 404) {
			$data = null;
		} else {
			throw new Exception("Can't connect to the api: " . $url . " response code: " . $response_info['http_code']);
		}

		return $data;
		
	}
	
}
