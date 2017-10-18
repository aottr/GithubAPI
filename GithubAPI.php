<?php
/**
 *	GithubAPI Class for an easy access to some features of the Github API (v3)
 * 	@author staubrein <me@staubrein.com>
 *	@version 1.0
 */
class GithubAPI {

	protected $_username;
	protected $_jason_cache_path;

	/**
	 * Create a new GithubAPI Client
	 * @param string $username from Github
	 */
	public function __construct($username) {

		$this->_username = $username;
		$this->_jason_cache_path = NULL;
	}

	/**
	 * Sets the Path for json caching (and enables it)
	 * @param path to cache directory
	 */
	public function setJSONCachePath(string path) {

		$this->_jason_cache_path = path.trim() == '' ? $this->_jason_cache_path : path;
	}

	/**
	 * Gets the Path for json caching
	 * @return path to cache directory
	 */
	public function getJSONCachePath() {

		return $this->_jason_cache_path;
	}

	/**
	 * Get all public Repositories from the given user.
	 * @return assoc array
	 */
	public function getRepositories() {

		// set file preferences for the cache file.
		//TODO Needs to be here fore access if no cache-file exists
		$file_name = $this->_username . "-repositories.json";
		$file_full = $this->_jason_cache_path . $file_name;

		// if chache-path is given -> check for chached files
		if($this->_jason_cache_path != NULL) {

			$current_time = time(); 
			$expire_time = 12 * 60 * 60; 		/* renew jason cache every 12h */
			$file_time = filemtime($file_full);

			// if file exists and is newer than 12h return its content
			if( file_exists( $file_full ) && ($current_time - $expire_time < $file_time) ) {

				return json_decode($file_full);
			} 
		} 

		// receive repository data and save as assoc array
		$repositories_assoc = json_decode(

				$this->curl_get(
					'https://api.github.com/users/' . $this->_username . '/repos', 
					NULL, 
					array(
						CURLOPT_USERAGENT => $this->_username
					)
				),
				true
			);

		// if cache-path is given but no file existent or older than 12h => save data
		if($this->_jason_cache_path != NULL) {
			file_put_contents($file_full, json_encode($repositories_assoc));
		}

		return $repositories_assoc;
	}

	/**
	 * Send a GET request using cURL
	 * posted by David (http://php.net/manual/de/function.curl-exec.php#98628)
	 * @param string $url to request 
	 * @param array $post values to send 
	 * @param array $options for cURL 
	 * @return string 
	 */ 
	private function curl_get($url, array $get = NULL, array $options = array()) 
	{    
	    $defaults = array( 
	        CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
	        CURLOPT_HEADER => 0, 
	        CURLOPT_RETURNTRANSFER => TRUE, 
	        CURLOPT_TIMEOUT => 4,
	    ); 
	    
	    $ch = curl_init(); 
	    curl_setopt_array($ch, ($options + $defaults)); 
	    if( ! $result = curl_exec($ch)) 
	    { 
	        trigger_error(curl_error($ch)); 
	    } 
	    curl_close($ch); 
	    return $result; 
	} 
}