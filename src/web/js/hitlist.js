
/**
 * Research Highlights engine
 * 
 * Copyright (c) 2016 Martin Porcheron <martin@porcheron.uk>
 * See LICENCE for legal information.
 */

var RHHitlist = {
	register				: function() {
								RH.sendData({
									dataType: 'json',
									url: $('html').data('uri_root') + '/hitlist.do',
									type: 'post',
									success: function(response, textStatus, jqXHR) {
												$('.loading').fadeOut({complete: function() { }});
												var $table = -1;

												var prevCohort = '';
												for(var i = 0; i < response.length; i++) {
													var data = response[i];
													if(data.length != 0) {
														if(prevCohort == '' || prevCohort != data.cohort) {
															if($table != -1) {
																$('.hitlist').append($table.fadeIn()); 
															}
															
															var $thead = $('<thead></thead>').append([$('<th></th>').addClass('center').text('Cohort'), $('<th></th>').text('Name'), $('<th></th>').text('Username'),  $('<th></th>').text('Email Address')]);
															$table = $('<table></table>').addClass('row-fluid').append($thead);
															prevCohort = data.cohort;
														}

														var $cohort = $('<td></td>').addClass('center').text(data.cohort);
														var $name = $('<td></td>').text(data.firstName + ' ' + data.surname);
														var $username = $('<td></td>').text(data.username);
														var $email = $('<td></td>').text(data.email);

														$table.append($('<tr></tr>').append([$cohort, $name, $username, $email]));
													}
												}
												if($table != -1) {
													$('.hitlist').append($table.fadeIn()); 
												}
											}
								});
								$('.container').empty();
								$('.loading').fadeIn();
							},
};

$(RHHitlist.register);