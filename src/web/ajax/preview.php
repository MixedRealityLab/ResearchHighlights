<?php

/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

// Generate a submission preview from MD to HTML

\header ('Content-type: application/json');

try {
	$cUser = I::RH_User ();
	$mInput = I::RH_Model_Input ();

	$mUser = $cUser->login ($mInput->editor, $mInput->password);

	if ($mInput->username !== $mInput->editor) {
		$cUser->login ($mInput->username, $mInput->password, true);
	}

	$cSubmission = I::RH_Submission ();
	$mSubmission = new \RH\Model\Submission ();

	$textMd = \trim ($mInput->text);
	$mSubmission->text = !empty ($textMd)
		? \RH\Submission::markdownToHtml ($textMd)
		: '<em>No text.</em>';

	$referencesMd = \trim ($mInput->references);
	$mSubmission->references = !empty ($referencesMd)
		?  \RH\Submission::markdownToHtml ("# References\n\n" . $referencesMd)
		: '<em>No references.</em>';

	$publicationsMd = \trim ($mInput->publications);
	$mSubmission->publications = !empty ($publicationsMd)
		? \RH\Submission::markdownToHtml ("# Publications in the Last Year\n\n" . $publicationsMd)
		: '<em>No publications in the last year.</em>';

	$mSubmission->fundingStatement = $mUser->fundingStatement;

	print $mSubmission->toJson ();
} catch (\RH\Error $e) {
	print $e->toJson ();
}
