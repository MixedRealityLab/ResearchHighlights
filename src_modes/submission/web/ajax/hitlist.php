<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Fetch a list of users who have not submitted

$oUserController = I::RH_User_Controller ();

$oUsers = $oUserController->getAll (null, function ($oUser) {
	return !$oUser->latestVersion && $oUser->countSubmission;
});

print $oUsers->toArrayJson ();