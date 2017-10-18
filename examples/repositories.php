<?php
/**
 *	Example of how to use the GithubAPI Class and list the public repositories of a github user.
 * 	@author staubrein <me@staubrein.com>
 *	@version 1.0
 */

// include the class
require_once 'GithubAPI.php';

// create an instance
$client = new GithubAPI('staubrein');

// save the assoc array
$github_assoc = $client->getRepositories();

if($github_assoc) {
	
	// iterate through all repositories 
	foreach ($github_json as $repos => $repo) {
		echo '<p><a href="' . $repo['html_url'] . '">' . $repo['name'] . '</a><p>';
	}
}
