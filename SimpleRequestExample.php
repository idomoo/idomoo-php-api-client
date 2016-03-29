<?php

require_once "IdomooAPI.php";

// change these to your account's
$account_id = 'xxxx';
$storyboard_id = 'xxxx';
$secret_key = 'xxxx';


// create the api request
$request = new IdomooAPIRequest(IdomooAPIRequest::END_POINT_US, $account_id);
$request->setStoryboardId($storyboard_id);
$request->setSecretKey($secret_key);

// optional: set your account's security level
$request->setSecurityLevel(IdomooAPIRequest::AUTHENTICATION_LEVEL_HIGH);


// set your dynamic parameters
$request->setParameter("Koko", "amam");


// make the request
$response = $request->send();


echo $response->getVideoURL();