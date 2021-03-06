<?php
use Picnik\Client;

class CommandController extends \BaseController {

	/**
	 * Display the specified resource.
	 *
	 * @param  string  $id
	 * @return Response
	 */
	public function show()
	{
		try {
			// Get the input from Slack
			$input = Input::all();

			// Check if the request is from an expected token
			$tokens = explode('|', Config::get('app.slack.token'));
			if(in_array($input['token'], $tokens)) {
				// This is a verified request

				// If a command is set
				if(isset($input['command']) && !empty($input['command'])) {
					switch($input['command']) {
						case '/define': // Definition
							return $this->defineCommand($input['text']);
							break;
						case '/urban': // Urban Dictionary Definition
							return $this->urbanCommand($input['text'], $input['channel_id']);
							break;
						case '/phonetic': // Phonetic
							return $this->phoneticCommand($input['text'], $input['channel_id']);
						case '/canteen': // Canteen
							return $this->canteenCommand($input['text']);
						default:
							return false;
							break;
					}
				}
			}

			return false;
		} catch(Exception $e) {
			return View::make('error', array(
				'message' => $e->getMessage()
			));
		}
	}

	/**
	 * Use the Wordnik API to return a definition of the supplied word
	 *
	 * @param  string   $word
	 * @return string 	$definition
	 */
	private function defineCommand($word)
	{
		try {
			$client = new Picnik\Client;
			$client->setApiKey(Config::get('app.wordnik.key'));
			$definition = $client->wordDefinitions($word)
	                      ->limit(1)
	                      ->includeRelated(false)
	                      ->useCanonical(true)
	                      ->get();

	        print_r($definitions);

			return $word;
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}

	/**
	 * Use the Canteen thing to say what is for lunch
	 *
	 * @param 	string 	$word
	 * @return 	string 	$definition
	 */
	private function canteenCommand($day = '')
	{
		try {
			$client = new GuzzleHttp\Client();
			switch($day) {
				case 'monday':
				case 'mondy':
				case 'mnday':
					$response = $client->get(Config::get('app.canteen.menu') . 'monday.json');
					$dayTitle = 'Monday\'s Menu';
					break;
				case 'tuesday':
					$response = $client->get(Config::get('app.canteen.menu') . 'tuesday.json');
					$dayTitle = 'Tuesday\'s Menu';
					break;
				case 'wednesday':
					$response = $client->get(Config::get('app.canteen.menu') . 'wednesday.json');
					$dayTitle = 'Wednesday\'s Menu';
					break;
				case 'thursday':
				case 'thrusday':
					$response = $client->get(Config::get('app.canteen.menu') . 'thursday.json');
					$dayTitle = 'Thursday\'s Menu';
					break;
				case 'friday':
					$response = $client->get(Config::get('app.canteen.menu') . 'friday.json');
					$dayTitle = 'Friday\'s Menu';
					break;
				case 'saturday':
					$response = $client->get(Config::get('app.canteen.menu') . 'saturday.json');
					$dayTitle = 'Saturday\'s Menu';
					break;
				case 'sunday':
					$response = $client->get(Config::get('app.canteen.menu') . 'sunday.json');
					$dayTitle = 'Sunday\'s Menu';
					break;
				case 'today':
				default:
					$response = $client->get(Config::get('app.canteen.menu') . 'today.json');
					$dayTitle = 'Today\'s Menu';
					break;
			}

			if(!$response) {
				return 'There was no response from the canteen, sorry.';
			}

			$data = $response->json();

			$menuText = '';

			foreach($data['locations'] as $key => $location) {
				$menuText .= '<' . $location['location']['url'] . '|' . $location['location']['name'] . '>:' . PHP_EOL;
           		$menuText .= $location['menu'] . PHP_EOL;
			}

			$url = Config::get('app.canteen.webhook');

			$requestData = array(
				'username' => 'Canteen Bot',
				'icon_emoji' => ':fork_and_knife:',
				'attachments' => array(
					array(
			            'fallback' => $dayTitle,
			            'color' => 'good',
			            'fields' => array(
			            	array(
			                    'title' => $dayTitle,
			                    'value' => $menuText,
			                    'short' => false
			                )
			            )
			        )
		        )
			);

			$responseData = $client->post($url, array('body' => json_encode($requestData)));

			//$client->post('https://' . Config::get('app.slack.team') . '.slack.com/services/hooks/slackbot?token=' . Config::get('app.slack.slackbot.token') . '&channel=' . $channel, ['body' => $message]);
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}

	/**
	 * Use the Urban Dictionary API to return the definition of the supplied word
	 *
	 * @param 	string 	$word
	 * @return 	string 	$definition
	 */
	private function urbanCommand($word, $channel)
	{
		try {
			$client = new GuzzleHttp\Client();
			$response = $client->get('http://api.urbandictionary.com/v0/define?term=' . $word);

			if(!$response) {
				return 'There was no response from UrbanDictionary.';
			}

			$data = $response->json();

			if($data['result_type'] === 'no_results') {
				return 'No definition found for ' . $word;
			}

			$message =  '*Urban Dictionary Definition:* ' . $data['list'][0]['definition'] . '

*Example:* ' . $data['list'][0]['example'];

			$client->post('https://' . Config::get('app.slack.team') . '.slack.com/services/hooks/slackbot?token=' . Config::get('app.slack.slackbot.token') . '&channel=' . $channel, ['body' => $message]);
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}

	/**
	 * Return the phonetic translation of the supplied word
	 *
	 * @param 	string 	$word
	 * @return 	string 	$definition
	 */
	private function phoneticCommand($word, $channel)
	{
		try {
			$phoneticAlphabet = array(
				'a' => 'alpha', 
				'b' => 'bravo', 
				'c' => 'charlie', 
				'd' => 'delta', 
				'e' => 'echo', 
				'f' => 'foxtrot', 
				'g' => 'golf', 
				'h' => 'hotel', 
				'i' => 'india', 
				'j' => 'juliet', 
				'k' => 'kilo', 
				'l' => 'lima', 
				'm' => 'mike', 
				'n' => 'november', 
				'o' => 'oscar', 
				'p' => 'papa', 
				'q' => 'quebec', 
				'r' => 'romeo', 
				's' => 'sierra', 
				't' => 'tango', 
				'u' => 'uniform', 
				'v' => 'victor', 
				'w' => 'whisky', 
				'x' => 'x-ray', 
				'y' => 'yankee', 
				'z' => 'zulu',
			);

			$letters = str_split($word);
			$return = '';

			foreach($letters as $key => $letter) {
				$letter = strtolower($letter);
				switch($letter) {
					case ' ':
						$return .= ' ';
						break;
					default:
						if(isset($phoneticAlphabet[$letter])) {
							// We found the letter
							$return .= $phoneticAlphabet[$letter] . ' ';
						}
						break;
				}
			}

			$message =  'The Phonetic Translation of "' . $word . '" is *' . trim($return) . '*';
			
			$client = new GuzzleHttp\Client();
			$client->post('https://' . Config::get('app.slack.team') . '.slack.com/services/hooks/slackbot?token=' . Config::get('app.slack.slackbot.token') . '&channel=' . $channel, ['body' => $message]);
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}


}
