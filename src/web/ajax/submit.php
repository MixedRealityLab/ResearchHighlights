<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Save a user's submission

try {
	$oUser = I::RH_User ();
	$mInput = I::RH_Model_Input ();

	if ($mInput->username !== $mInput->saveAs) {
		$mUser = $oUser->login ($mInput->username, $mInput->password, true);
	} else {
		$mUser = $oUser->login ($mInput->username, $mInput->password);
	}

	$oSubmission = I::RH_Submission ();

	if (!isSet ($mInput->saveAs)) {
		throw new \RH\Error\InvalidInput ('Must provide saveAs attribute');
	}

	// Go ahead and save the submission!
	if (!isSet ($mInput->cohort) && !isSet ($mInput->title)
		&& !isSet ($mInput->keywords) && !isSet ($mInput->text)) {
		throw new \RH\Error\InvalidInput ('Missing provide a cohort, title, keywords and your submission text.');
	}

	$mUser = $oUser->get ($mInput->saveAs);
	$cohortDir = DIR_DAT . '/' . $mInput->cohort;
	if ($mInput->cohort !== $mUser->cohort
		|| !is_numeric ($mInput->cohort) || !is_dir ($cohortDir)) {
		throw new \RH\Error\InvalidInput ('Invalid cohort supplied');
	}

	$mSubmission = new \RH\Model\Submission ($mInput);

	$html = $oSubmission->markdownToHtml ($mSubmission->text);

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

		$mSubmission->addImage ($filename, $url);
		$mSubmission->text = \str_replace ($url, '<imgDir>' . $filename, $mSubmission->text);
	}

	$mSubmission->keywords = \strtolower ($mSubmission->keywords);

	$mSubmission->website = !\is_null ($mSubmission->website) && $mSubmission->website != 'http://' ? \trim ($mSubmission->website) : '';
	$mSubmission->twitter = \strlen ($mSubmission->twitter) > 0 && $mSubmission->twitter[0] != '@' ? '@' . $mSubmission->twitter : $mSubmission->twitter;

	$mSubmission->save ();

	if (MAIL_ON_CHANGE_USRS !== null) {
		$oEmail = I::RH_Email ();

		$from = '"'. $mUser->firstName . ' ' . $mUser->surname .'" <'. $mUser->email .'>';
		$oEmail->setHeaders ($from, $from);

		$usernames = \explode (',', \trim (MAIL_ON_CHANGE_USRS));
		$unamesMail = array();
		foreach ($usernames as $username) {
			$tempU = $oUser->get ($username);
			if ($tempU->emailOnChange) {
				$unamesMail[] = $username;
			}
		}

		$message = '<strong>Tasks</strong><br>';
		$message .= '&bull; <a href="' . URI_ROOT . '/#read=<username>" target="_blank">Read submission</a><br>';
		$message .= '&bull; <a href="' . URI_ROOT . '/login" target="_blank">Edit submission</a> (login and then enter the username <em><username></em> in the bottom left)';
		$message = $mUser->makeSubsts ($message);
		$subject = $mUser->makeSubsts (MAIL_ON_CHANGE_SUBJ);

		$message .= '<br><br><strong>Account Details</strong><br>Username: <em><username></em><br>Password: <em><password></em>';

		$oEmail->sendAll ($unamesMail, $subject, \strip_tags ($message), $message) ? '1' : '-1';
	}

	print \json_encode (array ('success' => '1'));
} catch (\RH\Error $e) {
	print $e->toJson ();
}