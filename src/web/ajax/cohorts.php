<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Fetch a list of cohorts

$oUserController = \I::rh_user_controller ();

print $oUserController->getCohorts ()->toArrayJson();

