
/**
 * Research Highlights engine
 *
 * Copyright (c) 2015 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

var wordCount = 1500;
var changesMade = false;

function autoResize () {
	$('.stage-editor textarea').each (function () {$(this).trigger ('autosize.resize'); });
}

var loginPrefill = function (response, textStatus, jqXHR) {
	$('#tweet').unbind ('keyup.count');
	$('#text').unbind ('keyup.count');

	$('.wordlimit').text (response.wordCount);

	$('#tweet').bind ('keyup.count', function (e) {
		RH.charCount ($(this), $('.tweet-rem'), 125);
	});
	$('#text').bind ('keyup.count', function (e) {
		RH.wordCount ($(this), $('.text-rem'), response.wordCount);
	});

	$('.name').text (response.firstName + ' ' + response.surname);

	$('#cohort').val (response.cohort);
	$('#name').val (response.firstName + ' ' + response.surname);
	$('#email').val (response.email);

	$('#tweet').val (response.tweet);
	$('#tweet').triggerHandler ('keyup');

	$('#twitter').val (response.twitter);
	$('#website').val (response.website);
	$('#keywords').tagsinput ('removeAll');
	$.each (response.keywords.split (','), function (k,v) {$('#keywords').tagsinput ('add', v)});

	$('#industryName').val (response.industryName);
	$('#industryUrl').val (response.industryUrl);

	$('#title').val (response.title);
	$('#text').val (response.text);
	$('#text').triggerHandler ('keyup');

	$('#references').val (response.references);
	$('#references').triggerHandler ('keyup');

	$('#publications').val (response.publications);
	$('#publications').triggerHandler ('keyup');
	$('.stage-login').fadeOut ({complete : function () {$('.stage-editor').fadeIn (); autoResize (); changesMade = false; }});

	changesMade = false;
};

$(function () {

	$('a[data-toggle="tab"]').on ('shown.bs.tab', function (e) {
		autoResize ();
	});

	var sub = 0;
	$('.collapse').each (function (i) {
		if (!$(this).hasClass ('stage-editor')) {
			$(this).delay (400 * (i - sub)).fadeIn ();
		} else {
			sub++;
		}
	});

	$('.navbar-toggle').addClass ('stage-editor');

//	RH.showAlert ('Welcome!', 'Please enter your credentials to continue.', 'info');

	RH.regSubForm ($('form.stage-login'), $('html').data('uri_root') + '/login.do', function (response, textStatus, jqXHR) {
		if (response.success == '1') {
			RH.hideAlert();
		//	RH.showSuccess ('Welcome!', 'Your login was successful. You can log back in any time to modify your submission before the deadline.');
			$('#username').val ($('#editor').val ());
			$('#admin-user').val ($('#editor').val ());
			$('#admin-pass').val ($('#password').val ());
			$('#editor-user').val ($('#editor').val ());
			$('#editor-pass').val ($('#password').val ());
			loginPrefill (response, textStatus, jqXHR);
			if (response.admin) {
				$.getScript ('web/js/manage-admin' + $('html').data('ext_js'));
			}

			$(document).bind('drop dragover', function (e) {
				e.preventDefault();
			});

			$('#fileupload').fileupload({
        		dataType: 'json',
                autoUpload: true,
                acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
				dropZone: $('#text'),
				pasteZone: $('#text')
			}).on('fileuploaddragover', function(e, data) {
				$('#text').focus();
			}).on('fileuploadprogressall', function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#progress .bar').css('width',progress + '%');
			}).on('fileuploaddone', function (e, data) {
				if (data.result.error != undefined) {
					RH.showError ('Image Upload Error', data.result.error);
				} else if (data.result.length > 0) {
					$.each(data.result, function (index, file) {
						$('#text').insertAtCaret ('![Figure Caption Here...](' + file + ')');
						$('#progress').fadeOut(function () {$('#progress .bar').css('width','0%');});
					});
					$('#text').triggerHandler('keyup');
				}
			}).on('fileuploadstart', function (e, data) {
				$('#progress').fadeIn();
			});

			$(window).keydown (function (e) {
				if ((e.ctrlKey || e.metaKey) && e.which == 83) {
					e.preventDefault();
					$('form.stage-editor').trigger('submit');
					return false;
				}
				return true;
			});
		} else if (response.error != undefined) {
			RH.showError ('Oh, snap!', response.error + ' <a href="mailto:' + $('html').data('email') + '" class="alert-link">Email support</a> for help.');
		} else {
			RH.showError ('Oh, snap!', 'An unknown error occurred. <a href="mailto:' + $('html').data('email') + '" class="alert-link">Email support</a> for help.');
		}

		RH.regAutoForm ($('form.stage-editor'), $('html').data('uri_root') + '/preview.do', function (response, textStatus, jqXHR) {
			if (!response.text == undefined) {
				RH.showError ('Humf!', 'An unknown error occurred generating your preview! <a href="mailto:' + $('html').data('email') + '" class="alert-link">I need help!</a>');
				return;
			}

			$('.preview-text').html (response.text);
			$('.preview-references').html (response.references);
			$('.preview-publications').html (response.publications);

			var iNVal = $('#industryName').val ();
			var iUVal = $('#industryUrl').val ();

			if (iNVal == '') {
				$('.preview-fundingStatement').html (response.fundingStatement + '.');
			} else if (iUVal == '' || iUVal == 'http://') {
				$('.preview-fundingStatement').html (response.fundingStatement + ' and by <span>' + iNVal + '</span>.');
			} else {
				$('.preview-fundingStatement').html (response.fundingStatement + ' and by <a href="' + iUVal + '" target="_blank">' + iNVal + '</a>.');
			}

			changesMade = true;
		}, true);

		RH.regSubForm ($('form.stage-editor'), $('html').data('uri_root') + '/submit.do', function (response, textStatus, jqXHR) {
			if (response.success == '1') {
				if ($('#username').val () == $('#editor').val ()){
					RH.showSuccess ('Good News!', 'Your submission was saved, ' + $('#name').val () + '! It make take some time for your changes to propagate onto the website.');
				} else {
					RH.showSuccess ('Good News!', 'The submission for ' + $('#name').val () + ' was saved! It make take some time for your changes to propagate onto the website.');
				}
			} else if (response.error != undefined) {
				RH.showError ('Goshdarnit!', response.error + ' <a href="mailto:' + $('html').data('email') + '" class="alert-link">I need help!</a>');
			} else  {
				RH.showError ('Fiddlesticks!', 'An unknown error occurred! <a href="mailto:' + $('html').data('email') + '" class="alert-link">I need help!</a>');
			}
		}, 'json');

		$('a[href="#content"]').on ('shown.bs.tab', function (e) {
			autoResize ();
		});

		$('textarea').autosize ();

		$('#keywords').on ('beforeItemAdd', function (e) {
			var ret = false;
			$.each ($('#keywords').tagsinput ('items'), function (k, v) {
				if (!ret && v.toLowerCase () == e.item.toLowerCase ()) {
					ret = true;
				}
			});
			e.cancel = ret;
		});

		$('a').click (function (e) {
			var href = $(this).attr ('href');
			if (href.substring (0, 1) != '#' && $('.stage-editor').is (':visible')) {
				if (!confirm ('If you continue, any unsubmitted changes may be lost')) {
					e.preventDefault ();
					return false;
				}
			}
		});

		$('#logout').click (function (e) {
			if (confirm ('If you logout, any unsubmitted changes will be lost!')) {
				window.location.reload ();
			}
		});

		$('#submit').click (function (e) {
			changedMade = false;
			e.preventDefault ();
			if ($('#title').val ().length == 0) {
				RH.showError ('Whoopsie!', 'You need to give your submission a title!');
			} else if ($('#keywords').tagsinput ('items').length == 0) {
				RH.showError ('Oh dear!', 'You need to enter at least <strong>five</strong> keywords!');
			} else if ($('#keywords').tagsinput ('items').length < 5) {
				RH.showError ('Oh dear!', 'You need to enter <strong>' + (5 - $('#keywords').tagsinput ('items').length) + '</strong> more keywords');
			} else if ($('#tweet').val ().length < 25) {
				RH.showError ('Oh dear!', 'You should enter a better 140-character summary of your PhD');
			} else if ($('#tweet').val ().length > 125) {
				RH.showError ('Oh dear!', 'Your tweet-like summary is too long!');
			} else {
				RH.showAlert ('Just a moment!', 'Saving your submission. Please don\'t leave or refresh this page until a success message appears (resubmit if need be).', 'info');
				setTimeout (function () {$('form.stage-editor').triggerHandler ('submit')}, 500);
			}
		});
	}, 'json');
});
