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
			if(in_array($input['token'], array(Config::get('app.slack.token')))) {
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

			$client->post('https://times.slack.com/services/hooks/slackbot?token=' . Config::get('app.slack.slackbot.token') . '&channel=' . $channel, ['body' => $message]);
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}


}
