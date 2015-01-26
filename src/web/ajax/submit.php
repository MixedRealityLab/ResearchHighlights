<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Save a user's submission

try {
	$oUserController = I::RH_User_Controller ();
	$oUser = $oUserController->login ();

	$oSubmissionController = I::RH_Submission_Controller ();
	$oPageInput = I::RH_Page_Input ();

	if (!isSet ($oPageInput->saveAs)) {
		throw new \RH\Error\InvalidInput ('Must provide saveAs attribute');
	}

	if ($oPageInput->username !== $oPageInput->saveAs) {
		$oUserController->login (true);
	}

	// Go ahead and save the submission!
	if (!isSet ($oPageInput->cohort) && !isSet ($oPageInput->title)
		&& !isSet ($oPageInput->keywords) && !isSet ($oPageInput->text)) {
		throw new \RH\Error\InvalidInput ('Missing provide a cohort, title, keywords and your submission text.');
	}

	$oUser = $oUserController->get ($oPageInput->saveAs);
	$cohortDir = DIR_DAT . '/' . $oPageInput->cohort;
	if ($oPageInput->cohort !== $oUser->cohort
		|| !is_numeric ($oPageInput->cohort) || !is_dir ($cohortDir)) {
		throw new \RH\Error\InvalidInput ('Invalid cohort supplied');
	}

	$oSubmission = new \RH\Submission\Submission ($oPageInput);

	$html = $oSubmissionController->markdownToHtml ($oSubmission->text);

	$images = array();
	\preg_match_all ('/(<img).*(src\s*=\s*("|\')([a-zA-Z0-9\.;:\/\?&=\-_|\r|\n]{1,})\3)/isxmU', $html, $images, PREG_PATTERN_ORDER);

	$id = 0;
	foreach ($images[4] as $url) {
		$path_parts = \pathinfo ($url);
		$ext = $path_parts['extension'];
		if (\strpos ($ext, '?') !== false) {
			$ext = \substr ($ext, 0, \strpos ($ext, '?'));	
		}

		$filename = 'img-' . $id++ . '.' . $ext;

		$oSubmission->addImage ($filename, $url);
		$oSubmission->text = \str_replace ($url, '<imgDir>' . $filename, $oSubmission->text);
	}

	$oSubmission->keywords = \strtolower ($oSubmission->keywords);

	$oSubmission->website = !\is_null ($oSubmission->website) && $oSubmission->website != 'http://' ? \trim ($oSubmission->website) : '';
	$oSubmission->twitter = \strlen ($oSubmission->twitter) > 0 && $oSubmission->twitter[0] != '@' ? '@' . $oSubmission->twitter : $oSubmission->twitter;

	$oSubmission->save ();

	print \json_encode (array ('success' => '1'));
} catch (\RH\Error\UserError $e) {
	print $e->toJson ();
} catch (\RH\Error\SystemError $e) {
	print $e->toJson ();
}