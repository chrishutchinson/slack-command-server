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
		// Get the input from Slack
		$input = Input::all();

		// Check if the request is from the expected token
		if($input['token'] === Config::get('app.slack.token')) {
			// This is a verified request

			// If a command is set
			if(isset($input['command']) && !empty($input['command'])) {
				switch($input['command']) {
					case '/define':
						return $this->defineCommand($input['text']);
						break;
					default:
						return false;
						break;
				}
			}
		}

		return false;
	}

	private function defineCommand($string)
	{
		try {
			$client = new Picnik\Client;
			$client->setApiKey(Config::get('app.wordnik.key'));
			$definition = $client->wordDefinitions($string)
	                      ->limit(1)
	                      ->includeRelated(false)
	                      ->useCanonical(true)
	                      ->get();

	        print_r($definitions);

			return $string;
		} catch(Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}


}
