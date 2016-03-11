<?php
//initialize sessions
session_start();

require_once('vendor/autoload.php'); //register the autoloader
require_once('config.php'); //load the configuration file

$page = file_get_contents('views/page.tpl'); //create an empty page
$page = str_replace('{name}', $config['url'], $page); //replace base url

//the OAuth application
$client = new \League\OAuth2\Client\Provider\GenericProvider([
  'clientId' => $config['client_id'],
  'clientSecret' => $config['client_secret'],
  'redirectUri' => $config['redirect_uri'],
  'urlAuthorize' => $config['authorize_uri'],
  'urlAccessToken' => $config['token_uri'],
  'urlResourceOwnerDetails' => $config['api_uri']
]);

if (!isset($_GET['code'])) {

  //make index page
  $page = str_replace('{page}', file_get_contents('views/introduction.tpl'), $page);
  $page = str_replace('{url}', $client->getAuthorizationUrl(), $page);

	//save state session
  $_SESSION['state'] = $client->getState();

	//show page
  exit($page);

} elseif (empty($_GET['state']) || !isset($_SESSION['state']) || ($_GET['state'] !== $_SESSION['state'])) {

	//Give error for incorrect state
	$page = str_replace('{page}', file_get_contents('views/error.tpl'), $page);
	$page = str_replace('{result}', 'Invalid state: ' . $_GET['state'] . "\nCorrect state: " . (isset($_SESSION['state']) ? $_SESSION['state'] : '?'), $page);
	unset($_SESSION['state']);
	exit($page);

} else {
  try {

    //Try to get an access token using the authorization code grant.
    $accessToken = $client->getAccessToken('authorization_code', ['code' => $_GET['code']]);

		//Give result on page
    $result = 'access-token: ' . $accessToken->getToken() . "\n";
    $result .= 'refresh-token: ' . $accessToken->getRefreshToken() . "\n";
    $result .= json_encode($client->getResourceOwner($accessToken)->toArray(), JSON_PRETTY_PRINT);

    $page = str_replace('{page}', file_get_contents('views/result.tpl'), $page);
    $page = str_replace('{result}', $result, $page);
    exit($page);

  } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

		//If an error occured: display error
		$page = str_replace('{page}', file_get_contents('views/error.tpl'), $page);
    $page = str_replace('{result}', $e->getMessage(), $page);
    exit($page);

  }
}
?>
