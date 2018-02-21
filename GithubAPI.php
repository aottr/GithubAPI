<?php
/**
 *	GithubAPI Class for an easy access to some features of the Github API (v3)
 * 	@author staubrein <me@staubrein.com>
 *	@version 1.2
 *	@example load your repositories /examples/repositories.php
 */
class GithubAPI {

	protected $_username;
	protected $_jason_cache_path;

	protected $_userdata;
	protected $_followersdata;

	/**
	 * Create a new GithubAPI Client
	 * @param string $username from Github
	 */
	public function __construct($username) {

		$this->_username = $username;
		$this->_jason_cache_path = NULL;
		$this->_userdata = NULL;
		$this->_followersdata = NULL;
	}

	public function getUsername() {

		if( !is_null( $this->_userdata ) )
			$this->_userdata = $this->getUserData();
		
		return $this->_userdata['login'];
	}

	public function getAvatar() {

		if( !is_null( $this->_userdata ) )
			$this->_userdata = $this->getUserData();

		return $this->_userdata['avatar_url'];
	}

	public function getName() {

		if( !is_null( $this->_userdata ) )
			$this->_userdata = $this->getUserData();
		
		return $this->_userdata['name'];
	}

	public function getBlog() {

		if( !is_null( $this->_userdata ) )
			$this->_userdata = $this->getUserData();	

		return $this->_userdata['blog'];
	}

	public function getLocation() {

		if( !is_null( $this->_userdata ) )
			$this->_userdata = $this->getUserData();

		return $this->_userdata['location'];
	}

	public function getFollower($id = NULL) {

		if( !is_null( $this->_followersdata ) )
			$this->_followersdata = $this->getFollowerData();

		return $this->_followersdata;
	}

	/**
	 * Sets the Path for json caching (and enables it)
	 * @param path to cache directory
	 */
	public function setJSONCachePath($path) {

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

		// if chache-path is given -> check for chached files
		if($this->_jason_cache_path != NULL) {
		
			$data = $this->get_json('repositories'); 
			if(!is_null($data))
				return $data;
		}
		// receive repository data and save as assoc array
		$data_assoc = json_decode(

				$this->curl_get(
					'https://api.github.com/users/' . $this->_username . '/repos', 
					array(), 
					array(
						CURLOPT_USERAGENT => $this->_username
					)
				),
				true
			);

		// if cache-path is given but no file existent or older than 12h => save data
		if($this->_jason_cache_path != NULL)
			$this->set_json('repositories', $data_assoc);

		return $repositories_assoc;
	}

	private function getUserData() {

		// if chache-path is given -> check for chached files
		if($this->_jason_cache_path != NULL) {
		
			$data = $this->get_json('userdata'); 
			if(!is_null($data))
				return $data;
		}

		// receive repository data and save as assoc array
		$data_assoc = json_decode(

				$this->curl_get(
					'https://api.github.com/users/' . $this->_username, 
					array(), 
					array(
						CURLOPT_USERAGENT => $this->_username
					)
				),
				true
			);

		// if cache-path is given but no file existent or older than 12h => save data
		if($this->_jason_cache_path != NULL)
			$this->set_json('userdata', $data_assoc);

		return $data_assoc;
	}

	private function getFollowerData() {

		// if chache-path is given -> check for chached files
		if($this->_jason_cache_path != NULL) {
		
			$data = $this->get_json('follower'); 
			if(!is_null($data))
				return $data;
		}

		// receive repository data and save as assoc array
		$data_assoc = json_decode(

				$this->curl_get(
					'https://api.github.com/users/' . $this->_username . '/followers', 
					array(), 
					array(
						CURLOPT_USERAGENT => $this->_username
					)
				),
				true
			);

		// filter for wanted keys
		$follower = $this->filter_array (array( 'login', 'id', 'avatar_url', 'html_url'));

		// if cache-path is given but no file existent or older than 12h => save data
		if($this->_jason_cache_path != NULL)
			$this->set_json('follower', $follower);

		return $follower;
	}

	private function get_json( $entity ) {

		$file_name = $this->_username . '-' . $entity . '.json';
		$file_full = $this->_jason_cache_path . $file_name;

		$current_time = time(); 
		$expire_time = 12 * 60 * 60; 		/* renew jason cache every 12h */
		$file_time = filemtime($file_full);

		// if file exists and is newer than 12h return its content
		if( file_exists( $file_full ) && ($current_time - $expire_time < $file_time) ) {

			return json_decode($file_full);
		} 
		return NULL;
	}

	private function set_json($entity, $entity_assoc) {

		$file_name = $this->_username . '-' . $entity . '.json';
		$file_full = $this->_jason_cache_path . $file_name;

		file_put_contents($file_full, json_encode($entity_assoc));
	}

	private function filter_array ( $data, $filter ) {

		$temp = array();
		foreach ($data as $key => $value) {
			
			if( in_array( $key, $filter) )
				$temp[$key] = $value;
		}

		return $temp;
	}

	/**
	 * Send a GET request using cURL
	 * posted by David (http://php.net/manual/de/function.curl-exec.php#98628)
	 * @param string $url to request 
	 * @param array $post values to send 
	 * @param array $options for cURL 
	 * @return string 
	 */ 
	private function curl_get($url, $get = array(), $options = array()) 
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