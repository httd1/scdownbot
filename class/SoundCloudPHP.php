<?php

class SoundCloudPHP {

	const API_INFO = 'https://api-v2.soundcloud.com';
	public $client_id;

	function __construct (){

		foreach ($this->_getScripts () as $script){

			$result = $this->searchClientId ($script);

			if (is_null ($result)){
				continue;
			}

			$this->client_id = $result;
		}

	}

	public function _getHome ()
	{

		return $this->rquest ('https://soundcloud.com/');

	}

	public function _getScripts ()
	{

		$home = $this->_getHome ();

		preg_match_all ('/<script[^>]+src="([^"]+)/m', $home, $matchs);

		return $matchs [1];

	}

	public function searchClientId ($script)
	{

		$data = $this->rquest ($script);
		preg_match_all ('/client_id\s*:\s*"([0-9a-zA-Z]{32})"/m', $data, $match);

		return $match [1][0] ?? null;

	}

	public function getMusicInfo ($url)
	{

		$info = json_decode ($this->rquest (self::API_INFO."/resolve?url={$url}&client_id={$this->client_id}"), true);

		if (isset ($info ['media'])){
			for ($i = 0; $i < count ($info ['media']['transcodings']); $i++){

				$url = $info ['media']['transcodings'][$i]['url'];
				$info ['media']['transcodings'][$i]['url'] = "{$url}?client_id={$this->client_id}";

			}
		}

			return $info;
	}

	public function getMusic ($url)
	{

		$data = @json_decode($this->rquest ($url), true);
			return $data ['url'] ?? null;

	}

	private function rquest ($url, $method = 'GET', $data = null, $header = [])
	{

		$connect = curl_init ();
		
		curl_setopt ($connect, CURLOPT_URL, $url);
		curl_setopt ($connect, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($connect, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($connect, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt ($connect, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36');
		curl_setopt ($connect, CURLOPT_HTTPHEADER, $header);

		if ($method == 'POST')
		{

			curl_setopt ($connect, CURLOPT_POST, true);
			curl_setopt ($connect, CURLOPT_POSTFIELDS, $query);

		}

		$request = curl_exec ($connect);

		if ($request === false){
			throw new Exception (curl_error($connect));
		}

			return $request;

	}

}