<?php

/**
 * Research Highlights engine
 * 
 * Copyright (c) 2014 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

namespace RH\User;

/**
 * Controller for submissions made by users.
 * 
 * @author Martin Porcheron <martin@porcheron.uk>
 */
class Controller implements \RH\Singleton {

	/** @var string File name for standard users who can log in */
	const USER_FILE = '/login-users.txt';

	/** @var string File name for administrative users who can log in */
	const ADMIN_FILE = '/login-admins.txt';

	/** @var string File name for funding statements */
	const FUNDING_FILE = '/funding.txt';

	/** @var string File name for word counts */
	const WORD_COUNT_FILE = '/wordCount.txt';

	/** @var string File name for submission deadlines */
	const DEADLINES_FILE = '/deadlines.txt';

	/** @var User Currently logged in user */
	private $user = null;

	/** @var Users Cache of user details */
	private $userCache;

	/** @var FundingStatements Cache of funding statements */
	private $fundingCache;

	/** @var Deadlines Cache of deadline statements */
	private $deadlineCache;

	/** @var WordCounts Cache of word counts */
	private $wordCountCache;

	/** Construct the User model */
	public function __construct() {
		$this->userCache = new Users();
	}

	/**
	 * Log a user into the system.
	 * 
	 * @param bool $requireAdmin if `true`, is an administrator account required
	 * @return the \RH\User\User object
	 * @throws \RH\Error\NoUser if the account is disabled
	 * @throws \RH\Error\NotAuthorised if an admin account is required and the 
	 * 	login request is for a non-admin account
	 * @throws \RH\Error\AccountDisabled if the account is disabled
	 */
	public function login ($requireAdmin = false) {
		$oPageInput = \I::rh_page_input ();

		try {
			$username = \strtolower ($oPageInput->username);
			$temp = $this->get ($username);

			if ($oPageInput->password !== $temp->getPassword ()) {
				throw new \RH\Error\NoUser();
			}

			if ($requireAdmin && !$temp->admin) {
				throw new \RH\Error\NotAuthorised();
			}

			if (!$temp->enabled) {
				throw new \RH\Error\AccountDisabled();
			}

			$this->user = $temp;

			return $this->user;
		} catch (\InvalidArgumentException $e) {
			throw new \RH\Error\NoUser();
		}
	}

	/**
	 * Allow a user to masquerade as another user (must be currently logged in
	 * as an administrator).
	 * 	
	 * @param User $oUser User of the person who we are going to 
	 * 	pretend to be.
	 * @throws \RH\Error\NotAuthorised if an admin account is required and the 
	 * 	login request is for a non-admin account
	 * @throws \RH\Error\NoUser if the account is disabled
	 * @return User new User object
	 */
	public function overrideLogin (\RH\User\User $oUser) {
		$oPageInput = \I::rh_page_input ();

		if (!isSet ($this->user->admin)) {
			throw new \RH\Error\NotAuthorised();
		}

		$this->user = $oUser;
		$this->fundingCache = null;
		$this->deadlineCache = null;
		$this->wordCountCache = null;
		return $this->user;
	}

	/**
	 * Retrieve the details of a user.
	 * 
	 * @param string|null $user User to retrieve full details for, or 
	 * 	if `null`, retrieve the currently logged in user, or if a User object,
	 * 	the function will return this object.
	 * @return User Details of the user
	 */
	public function get ($user = null) {
		if (\is_null ($user)) {
			return $this->user;
		} else if ($user instanceof User) {
			return $user;
		}

		if (isSet ($this->userCache->$user)) {
			return $this->userCache->$user;
		}

		$ret = $this->getData (self::USER_FILE, $user);
		if ($ret->count () == 0) {
			$ret = $this->getData (self::ADMIN_FILE, $user);
			if ($ret->count () > 0) {
				$ret->admin = true;
			} else {
				return $ret;
			}
		} else {
			$ret->admin = false;
		}

		$ret->deadline = $this->getDeadline ($ret);
		$ret->wordCount = $this->getWordCount ($ret);
		$ret->fundingStatement = $this->getFunding ($ret);

		$this->userCache->offsetSet ($user, $ret);
		return $ret;
	}

	/**
	 * Retrieve the details of all users.
	 * 
	 * @param function|null $sortFn How to sort the user list; if `null`, reverse 
	 * 	sort by cohort, then sort by name
	 * @param function|null $filterFn How to filter the user list; if `null`, all
	 * 	users are included
	 * @return Users Data on all requested users.
	 */
	public function getAll ($sortFn = null, $filterFn = null) {
		if (\is_null ($sortFn)) {
			$sortFn = function ($a, $b) {
				if ($a->cohort === $b->cohort) {
					if ($a->surname === $b->surname) {
						return \strcmp ($a->firstName, $b->firstName);
					} else {
						return \strcmp ($a->surname, $b->surname);
					}
				} else {
					return \strcmp ($b->cohort, $a->cohort);
				}
			};
		}

		if (\is_null ($filterFn)) {
			$filterFn = function ($oUser) {
				return true;
			};
		}

		$data = $this->getData (self::USER_FILE);
		$data->merge ($this->getData (self::ADMIN_FILE));
		$data->uasort ($sortFn);
		$data->filter ($filterFn);

		return $data;
	}

	/**
	 * Retrieve the cohorts.
	 * 
	 * @param function|null $sortFn How to sort the cohort list; if `null`,
	 * 	reverse sort by cohort
	 * @param function|null $filterFn How to filter the cohort list; if `null`, all
	 * 	cohort are included
	 * @return string[] Array of details of the cohorts
	 */
	public function getCohorts ($sortFn = null, $filterFn = null) {
		if (\is_null ($sortFn)) {
			$sortFn = function ($a, $b) {
				return \strcmp ($b, $a);
			};
		}

		if (\is_null ($filterFn)) {
			$filterFn = function ($cohort) {
				return true;
			};
		}

		$users = $this->getAll ();
		$cohorts = new Cohorts();
		foreach ($users as $user) {
			$cohort = $user->cohort;
			if (!isSet ($cohorts->$cohort)) {
				$cohorts->$cohort = $cohort;
			}
		}

		$cohorts->filter ($filterFn);
		$cohorts->uasort ($sortFn);

		return $cohorts;
	}

	/**
	 * Retrieve a user's data from a file, or all users.
	 * 
	 * @param string $file File to get the user's data from.
	 * @param string $username Username of the user to retrieve, or `null` to 
	 * 	get all users in the file.
	 * @return Users|User Details of the user(s).
	 */
	private function getData ($file, $username = null) {
		$oFileReader = \I::rh_file_reader ();

		$readRowFn = function ($cols) use ($username) {
			return \is_null ($username) || $cols[2] === $username;
		};
		$calcValuesFn = function (&$data, $cols) {
			// get the latest version
			$dir = DIR_DAT . '/'. $cols[1] . '/' . $cols[2];
			$data['dir'] = $dir;
			$versions = \glob ($dir . '/*', GLOB_ONLYDIR);

			if (empty ($versions)) {
				$data['latestVersion'] = false;
			} else {
				$data['latestVersion'] = \str_replace ($dir . '/', '', \end ($versions));
				$data['latestSubmission'] = $dir . '/' . $data['latestVersion'];
			}
		};

		$data = $oFileReader->read (DIR_USR . $file, 'username', $readRowFn, $calcValuesFn);
		return \is_null ($username) || empty ($data)
			? new Users ($data)
			: new User (\array_pop ($data));
	}

	/**
	 * Retrieve the word count for a particular user.
	 * 
	 * @param User $oUser User to retrieve the word count 
	 * 	for, if `null`, gets the currently logged in user
	 * @return string Word count of the user
	 */
	private function getWordCount (User $oUser = null) {
		if (\is_null ($this->wordCountCache)) {
			$oFileReader = \I::rh_file_reader ();
			$data = $oFileReader->read (DIR_USR . self::WORD_COUNT_FILE, 'cohort');
			$this->wordCountCache = new WordCounts ($data);
		}

		$cohort = \is_null ($oUser)
			? $this->user->cohort
			: $oUser->cohort;

		return $this->wordCountCache->$cohort->wordCount;
	}

	/**
	 * Retrieve the funding statement for a particular user.
	 * 
	 * @param User $oUser User to retrieve the funding statement for, if 
	 * 	`null`, gets the currently logged in user
	 * @return string Funding statement of the user
	 */
	private function getFunding (User $oUser = null) {
		if (\is_null ($this->fundingCache)) {
			$oFileReader = \I::rh_file_reader ();
			$data = $oFileReader->read (DIR_USR . self::FUNDING_FILE, 'fundingStatementId');
			$this->fundingCache = new FundingStatements ($data);
		}

		$id = \is_null ($oUser)
			? $this->user->fundingStatementId
			: $oUser->fundingStatementId;

		return $this->fundingCache->$id->fundingStatement;
	}

	/**
	 * Retrieve the deadline for a particular user.
	 * 
	 * @param User $oUser User to retrieve the deadline for, if `null`,
	 * 	gets the currently logged in user
	 * @return string Deadline of the user
	 */
	private function getDeadline (User $oUser = null) {
		if (\is_null ($this->deadlineCache)) {
			$oFileReader = \I::rh_file_reader ();
			$data = $oFileReader->read (DIR_USR . self::DEADLINES_FILE, 'cohort');
			$this->deadlineCache = new Deadlines ($data);
		}

		$cohort = \is_null ($oUser)
			? $this->user->cohort
			: $oUser->cohort;

		return $this->deadlineCache->$cohort->deadline;
	}
}