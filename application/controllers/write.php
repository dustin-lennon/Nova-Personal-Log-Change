<?php
/*
|---------------------------------------------------------------
| ADMIN - WRITE CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/write.php
| System Version: 1.0
|
| Controller that handles the WRITE section of the admin system.
|
*/

require_once APPPATH . 'controllers/base/write_base.php';

class Write extends Write_base {

	function Write()
	{
		parent::Write_base();
	}

	function personallog()
	{
		/* check access */
		$this->auth->check_access();

		/* load the models */
		$this->load->model('personallogs_model', 'logs');

		if ($this->options['system_email'] == 'off')
		{
			$flash['status'] = 'info';
			$flash['message'] = lang_output('flash_system_email_off');
			
			/* write everything to the template */
			$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);
		}

		/* set the variables */
		$id = $this->uri->segment(3, FALSE, TRUE);
		$data['key'] = '';
		$content = FALSE;
		$title = FALSE;
		$tags = FALSE;
		$location = FALSE;
		$stardate = FALSE;

		if (isset($_POST['submit']))
		{
			/* define the POST variables */
			$title = $this->input->post('title', TRUE);
			$content = $this->input->post('content', TRUE);
			$author = $this->input->post('author', TRUE);
			$tags = $this->input->post('tags', TRUE);
			$location = $this->input->post('location', TRUE);
			$stardate = $this->input->post('stardate', TRUE);
			$action = strtolower($this->input->post('submit', TRUE));
			$status = FALSE;
			$flash = FALSE;

			if ($author == 0)
			{
				$flash['status'] = 'error';
				$flash['message'] = lang_output('flash_personallogs_no_author');

				/* write everything to the template */
				$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);
			}
			else
			{
				switch ($action)
				{
					case 'delete':
						/* get the log information */
						$row = $this->logs->get_log($id);

						if ($row !== FALSE)
						{
							if ($row->log_status == 'saved' &&
									$row->log_author_user == $this->session->userdata('userid'))
							{
								/* delete the log */
								$delete = $this->logs->delete_log($id);

								if ($delete > 0)
								{
									$message = sprintf(
										lang('flash_success'),
										ucfirst(lang('global_personallog')),
										lang('actions_deleted'),
										''
									);

									$flash['status'] = 'success';
									$flash['message'] = text_output($message);
								}
								else
								{
									$message = sprintf(
										lang('flash_failure'),
										ucfirst(lang('global_personallog')),
										lang('actions_deleted'),
										''
									);

									$flash['status'] = 'error';
									$flash['message'] = text_output($message);
								}
							}
							else
							{
								redirect('admin/error/4');
							}

							/* add an automatic redirect */
							$this->template->add_redirect('write/index');
						}

						break;

					case 'save':
						if ($id !== FALSE)
						{ /* if there is an ID, it is a previously saved post */
							$update_array = array(
								'log_author_user' => $this->session->userdata('userid'),
								'log_author_character' => $author,
								'log_title' => $title,
								'log_content' => $content,
								'log_tags' => $tags,
								'log_stardate' => $stardate,
								'log_location' => $location,
								'log_status' => 'saved',
								'log_last_update' => now(),
							);

							/* do the update */
							$update = $this->logs->update_log($id, $update_array);

							if ($update > 0)
							{
								$message = sprintf(
									lang('flash_success'),
									ucfirst(lang('global_personallog')),
									lang('actions_saved'),
									''
								);

								$flash['status'] = 'success';
								$flash['message'] = text_output($message);
							}
							else
							{
								$message = sprintf(
									lang('flash_failure'),
									ucfirst(lang('global_personallog')),
									lang('actions_saved'),
									''
								);

								$flash['status'] = 'error';
								$flash['message'] = text_output($message);
							}
						}
						else
						{
							/* build the insert array */
							$insert_array = array(
								'log_author_user' => $this->session->userdata('userid'),
								'log_author_character' => $author,
								'log_title' => $title,
								'log_content' => $content,
								'log_tags' => $tags,
								'log_stardate' => $stardate,
								'log_location' => $location,
								'log_status' => 'saved',
								'log_last_update' => now()
							);

							/* do the insert */
							$insert = $this->logs->create_personal_log($insert_array);

							/* grab the insert id */
							$insert_id = $this->db->insert_id();

							/* optimize the table */
							$this->sys->optimize_table('personallogs');

							if ($insert > 0)
							{
								$message = sprintf(
									lang('flash_success'),
									ucfirst(lang('global_personallog')),
									lang('actions_saved'),
									''
								);

								$flash['status'] = 'success';
								$flash['message'] = text_output($message);

								/* reset the fields if everything worked */
								$content = FALSE;
								$title = FALSE;
								$tags = FALSE;
								$location = FALSE;
								$stardate = FALSE;
							}
							else
							{
								$message = sprintf(
									lang('flash_failure'),
									ucfirst(lang('global_personallog')),
									lang('actions_saved'),
									''
								);

								$flash['status'] = 'error';
								$flash['message'] = text_output($message);
							}

							/* add a quick redirect */
							$this->template->add_redirect('write/personallog/'. $insert_id);
						}

						break;

					case 'post':
						/* check the moderation status */
						$status = $this->user->checking_moderation('log', $this->session->userdata('userid'));

						if ($id !== FALSE)
						{ /* if there is an ID, it is a previously saved post */
							$update_array = array(
								'log_author_user' => $this->session->userdata('userid'),
								'log_author_character' => $author,
								'log_date' => now(),
								'log_title' => $title,
								'log_content' => $content,
								'log_tags' => $tags,
								'log_location' => $location,
								'log_stardate' => $stardate,
								'log_status' => $status,
								'log_last_update' => now()
							);

							/* do the update */
							$update = $this->logs->update_log($id, $update_array);

							if ($update > 0)
							{
								$array = array('last_post' => now());
								$this->user->update_user($this->session->userdata('userid'), $array);
								$this->char->update_character($author, $array);

								$message = sprintf(
									lang('flash_success'),
									ucfirst(lang('global_personallog')),
									lang('actions_posted'),
									''
								);

								$flash['status'] = 'success';
								$flash['message'] = text_output($message);

								/* set the array of data for the email */
								$email_data = array(
									'author' => $author,
									'title' => $title,
									'stardate' => $stardate,
									'location' => $location,
									'content' => $content
								);

								if ($status == 'pending')
								{
									/* send the email */
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log_pending', $email_data) : FALSE;
								}
								else
								{
									/* send the email */
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : FALSE;
								}
							}
							else
							{
								$message = sprintf(
									lang('flash_failure'),
									ucfirst(lang('global_personallog')),
									lang('actions_posted'),
									''
								);

								$flash['status'] = 'error';
								$flash['message'] = text_output($message);
							}
						}
						else
						{
							/* build the insert array */
							$insert_array = array(
								'log_author_user' => $this->session->userdata('userid'),
								'log_author_character' => $author,
								'log_date' => now(),
								'log_title' => $title,
								'log_content' => $content,
								'log_tags' => $tags,
								'log_stardate' => $stardate,
								'log_location' => $location,
								'log_status' => $status,
								'log_last_update' => now()
							);

							/* do the insert */
							$insert = $this->logs->create_personal_log($insert_array);

							if ($insert > 0)
							{
								$array = array('last_post' => now());
								$this->user->update_user($this->session->userdata('userid'), $array);
								$this->char->update_character($author, $array);

								$message = sprintf(
									lang('flash_success'),
									ucfirst(lang('global_personallog')),
									lang('actions_posted'),
									''
								);

								$flash['status'] = 'success';
								$flash['message'] = text_output($message);

								/* set the array of data for the email */
								$email_data = array(
									'author' => $author,
									'title' => $title,
									'stardate' => $stardate,
									'location' => $location,
									'content' => $content
								);

								if ($status == 'pending')
								{
									/* send the email */
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log_pending', $email_data) : FALSE;
								}
								else
								{
									/* send the email */
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : FALSE;
								}

								/* reset the fields if everything worked */
								$content = FALSE;
								$title = FALSE;
								$tags = FALSE;
								$stardate = FALSE;
								$location = FALSE;
							}
							else
							{
								$message = sprintf(
									lang('flash_failure'),
									ucfirst(lang('global_personallog')),
									lang('actions_posted'),
									''
								);

								$flash['status'] = 'error';
								$flash['message'] = text_output($message);
							}
						}

						break;

					default:
						$flash['status'] = 'error';
						$flash['message'] = lang_output('error_generic', '');
				}

				/* write everything to the template */
				$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);
			}
		}

		/* run the methods */
		$char = $this->session->userdata('characters');

		if (count($char) > 1)
		{ /* only continue if there's more than 1 character in the array */
			$data['characters'][0] = ucwords(lang('labels_please') .' '. lang('actions_select')
				.' '. lang('labels_an') .' '. lang('labels_author'));

			foreach ($char as $item)
			{ /* loop through all the characters */
				$type = $this->char->get_character($item, 'crew_type');

				if ($type == 'active' || $type == 'npc')
				{ /* split the characters out between active and npcs */
					if ($type == 'active')
					{
						$label = ucwords(lang('status_playing') .' '. lang('global_characters'));
					}
					else
					{
						$label = ucwords(lang('abbr_npcs'));
					}

					/* toss them in the array */
					$data['characters'][$label][$item] = $this->char->get_character_name($item, TRUE);
				}
			}
		}
		else
		{
			/* set the ID and name */
			$data['character']['id'] = $char[0];
			$data['character']['name'] = $this->char->get_character_name($char[0], TRUE);
		}

		/* get the data if it is not a new PM */
		$row = ($id !== FALSE) ? $this->logs->get_log($id) : FALSE;

		if ($row !== FALSE)
		{
			if ($row->log_author_user != $this->session->userdata('userid'))
			{ /* sorry, if you aren't the author, you're not allowed here */
				redirect('admin/error/4');
			}

			if (!isset($action) && ($row->log_status == 'pending' || $row->log_status == 'activated'))
			{ /* sorry, if the item is pending or activated, you're not allowed here */
				redirect('admin/error/5');
			}

			/* fill the content in */
			$title = $row->log_title;
			$stardate = $row->log_stardate;
			$location = $row->log_location;
			$content = $row->log_content;
			$tags = $row->log_tags;

			/* set the key in prep for searching */
			$data['key'] = 0;

			if (isset($data['characters']) && $data['key'] == 0)
			{ /* if there are multiple characters and the key hasn't been set already */
				foreach ($data['characters'] as $a)
				{ /* go through each part of the array */
					if (is_array($a))
					{ /* make sure the item is an array and then look for the author in that array */
						$data['key'] = (array_key_exists($row->log_author_character, $a)) ? $row->log_author_character : 0;
					}
				}
			}
		}

		/* set the data used by the view */
		$data['inputs'] = array(
			'title' => array(
				'name' => 'title',
				'id' => 'title',
				'value' => $title),
			'content' => array(
				'name' => 'content',
				'id' => 'content',
				'rows' => 20,
				'value' => $content),
			'tags' => array(
				'name' => 'tags',
				'id' => 'tags',
				'value' => $tags),
			'stardate' => array(
				'name' => 'stardate',
				'id' => 'stardate',
				'value' => $stardate),
			'location' => array(
				'name' => 'location',
				'id' => 'location',
				'value' => $location),
			'post' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'post',
				'id' => 'submitPost',
				'content' => ucwords(lang('actions_post'))),
			'save' => array(
				'type' => 'submit',
				'class' => 'button-sec',
				'name' => 'submit',
				'value' => 'save',
				'content' => ucwords(lang('actions_save'))),
			'delete' => array(
				'type' => 'submit',
				'class' => 'button-sec',
				'name' => 'submit',
				'value' => 'delete',
				'id' => 'submitDelete',
				'content' => ucwords(lang('actions_delete')))
		);

		/* set the header */
		$data['header'] = ucwords(lang('actions_write') .' '. lang('global_personallog'));

		/* set the form location */
		$data['form_action'] = ($id !== FALSE) ? 'write/personallog/'. $id : 'write/personallog';

		$data['label'] = array(
			'author' => ucwords(lang('labels_author')),
			'content' => ucwords(lang('labels_content')),
			'stardate' => ucwords(lang('labels_stardate')),
			'location' => ucfirst(lang('labels_location')),
			'tags' => ucwords(lang('labels_tags')),
			'tags_sep' => lang('tags_separated'),
			'title' => ucwords(lang('labels_title')),
		);

		/* figure out where the view files should be coming from */
		$view_loc = view_location('write_personallog', $this->skin, 'admin');
		$js_loc = js_location('write_personallog_js', $this->skin, 'admin');

		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);

		/* render the template */
		$this->template->render();
	}

	function _email($type = '', $data = '')
	{
		/* load the libraries */
		$this->load->library('email');
		$this->load->library('parser');

		/* define the variables */
		$email = FALSE;

		switch ($type)
		{
			case 'news':
				/* set some variables */
				$from_name = $this->char->get_character_name($data['author'], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $data['author']);
				$subject = $data['category'] .' - '. $data['title'];

				/* set the content */
				$content = sprintf(
					lang('email_content_news_item'),
					$from_name,
					$data['content']
				);

				/* set the email data */
				$email_data = array(
					'email_subject' => $subject,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('write_newsitem', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->user->get_crew_emails(TRUE, 'email_news_items');

				/* make a string of email addresses */
				$to = implode(',', $emails);

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $subject);
				$this->email->message($message);

				break;

			case 'news_pending':
				/* set some variables */
				$from_name = $this->char->get_character_name($data['author'], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $data['author']);
				$subject = $data['category'] .' - '. $data['title'];

				/* set the content */
				$content = sprintf(
					lang('email_content_entry_pending'),
					lang('global_newsitem'),
					$data['title'],
					$from_name,
					lang('global_newsitem'),
					$data['content'],
					lang('global_newsitem'),
					site_url('login/index')
				);

				/* set the email data */
				$email_data = array(
					'email_subject' => $subject,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('entry_pending', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->user->get_crew_emails(TRUE, 'email_news_items');

				/* make a string of email addresses */
				$to = implode(',', $this->user->get_emails_with_access('manage/news', 2));

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. lang('email_subject_news_pending'));
				$this->email->message($message);

				break;

			case 'log':
				/* set some variables */
				$from_name = $this->char->get_character_name($data['author'], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $data['author']);
				$subject = $from_name ."'s ". lang('email_subject_personal_log') ." - ". $data['title'];
				$location = lang('email_content_post_location') . $data['location'];
				$stardate = lang('email_content_post_stardate') . $data['stardate'];

				/* set the content */
				$content = sprintf(
					lang('email_content_personal_log'),
					$from_name,
					$location,
					$stardate,
					$data['content']
				);

				/* set the email data */
				$email_data = array(
					'email_subject' => $subject,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('write_personallog', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->user->get_crew_emails(TRUE, 'email_personal_logs');

				/* make a string of email addresses */
				$to = implode(',', $emails);

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $subject);
				$this->email->message($message);

				break;

			case 'log_pending':
				/* set some variables */
				$from_name = $this->char->get_character_name($data['author'], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $data['author']);
				$subject = $from_name ."'s ". lang('email_subject_personal_log') ." - ". $data['title'];
				$location = lang('email_content_post_location') . $data['location'];
				$stardate = lang('email_content_post_stardate') . $data['stardate'];

				/* set the content */
				$content = sprintf(
					lang('email_content_entry_pending'),
					lang('global_personallog'),
					$data['title'],
					$from_name,
					lang('global_personallog'),
					$location,
					$stardate,
					$data['content'],
					lang('global_personallog'),
					site_url('login/index')
				);

				/* set the email data */
				$email_data = array(
					'email_subject' => $subject,
					'email_from' => $from_name,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('entry_pending', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$to = implode(',', $this->user->get_emails_with_access('manage/logs', 2));

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. lang('email_subject_log_pending'));
				$this->email->message($message);

				break;

			case 'post':
				/* set some variables */
				$subject = $data['mission'] ." - ". $data['title'];
				$mission = lang('email_content_post_mission') . $data['mission'];
				$authors = lang('email_content_post_author') . $this->char->get_authors($data['authors'], TRUE);
				$timeline = lang('email_content_post_timeline') . $data['timeline'];
				$location = lang('email_content_post_location') . $data['location'];

				/* figure out who it needs to come from */
				$my_chars = array();

				/* find out how many of the submitter's characters are in the string */
				foreach ($this->session->userdata('characters') as $value)
				{
					if (strstr($data['authors'], $value) !== FALSE)
					{
						$my_chars[] = $value;
					}
				}

				/* set who the email is coming from */
				$from_name = $this->char->get_character_name($my_chars[0], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $my_chars[0]);

				/* set the content */
				$content = sprintf(
					lang('email_content_mission_post'),
					$authors,
					$mission,
					$location,
					$timeline,
					$data['content']
				);

				/* set the email data */
				$email_data = array(
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('write_missionpost', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->user->get_crew_emails(TRUE, 'email_mission_posts');

				/* make a string of email addresses */
				$to = implode(',', $emails);

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $subject);
				$this->email->message($message);

				break;

			case 'post_delete':
				/* set some variables */
				$subject = lang('email_subject_deleted_post');

				/* figure out who it needs to come from */
				$my_chars = array();

				/* find out how many of the submitter's characters are in the string */
				foreach ($this->session->userdata('characters') as $value)
				{
					if (strstr($data['authors'], $value) !== FALSE)
					{
						$my_chars[] = $value;
					}
				}

				/* set who the email is coming from */
				$from_name = $this->char->get_character_name($my_chars[0], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $my_chars[0]);

				/* set the content */
				$content = sprintf(
					lang('email_content_mission_post_deleted'),
					$data['title'],
					$from_name
				);

				/* set the email data */
				$email_data = array(
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('write_missionpost_deleted', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->char->get_character_emails($data['authors']);

				foreach ($emails as $key => $value)
				{
					$pref = $this->user->get_pref('email_mission_posts_delete', $key);

					if ($pref == 'y')
					{
						/* don't do anything */
					}
					else
					{
						unset($emails[$key]);
					}
				}

				/* make a string of email addresses */
				$to = implode(',', $emails);

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $subject);
				$this->email->message($message);

				break;

			case 'post_pending':
				$chars = explode(',', $data['authors']);

				$from_name = $this->char->get_character_name($chars[0], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $chars[0]);
				$subject = $data['mission'] ." - ". $data['title'];

				/* set the content */
				$content = sprintf(
					lang('email_content_entry_pending'),
					lang('global_missionpost'),
					$data['title'],
					$from_name,
					lang('global_missionpost'),
					$data['content'],
					lang('global_missionpost'),
					site_url('login/index')
				);

				/* set the email data */
				$email_data = array(
					'email_subject' => $subject,
					'email_from' => $from_name,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('entry_pending', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$to = implode(',', $this->user->get_emails_with_access('manage/posts', 2));

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. lang('email_subject_post_pending'));
				$this->email->message($message);

				break;

			case 'post_save':
				/* set some variables */
				$subject = $data['mission'] ." - ". $data['title'] . lang('email_subject_saved_post');
				$mission = lang('email_content_post_mission') . $data['mission'];
				$authors = lang('email_content_post_author') . $this->char->get_authors($data['authors'], TRUE);
				$timeline = lang('email_content_post_timeline') . $data['timeline'];
				$location = lang('email_content_post_location') . $data['location'];

				/* figure out who it needs to come from */
				$my_chars = array();

				/* find out how many of the submitter's characters are in the string */
				foreach ($this->session->userdata('characters') as $value)
				{
					if (strstr($data['authors'], $value) !== FALSE)
					{
						$my_chars[] = $value;
					}
				}

				/* set who the email is coming from */
				$from_name = $this->char->get_character_name($my_chars[0], TRUE, TRUE);
				$from_email = $this->user->get_email_address('character', $my_chars[0]);

				/* set the content */
				$content = sprintf(
					lang('email_content_mission_post_saved'),
					$data['title'],
					site_url('login/index'),
					$authors,
					$mission,
					$location,
					$timeline,
					$data['content']
				);

				/* set the email data */
				$email_data = array(
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);

				/* where should the email be coming from */
				$em_loc = email_location('write_missionpost_saved', $this->email->mailtype);

				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);

				/* get the email addresses */
				$emails = $this->char->get_character_emails($data['authors']);

				foreach ($emails as $key => $value)
				{
					$pref = $this->user->get_pref('email_mission_posts_save', $key);

					if ($pref == 'y')
					{
						/* don't do anything */
					}
					else
					{
						unset($emails[$key]);
					}
				}

				/* make a string of email addresses */
				$to = implode(',', $emails);

				/* set the parameters for sending the email */
				$this->email->from($from_email, $from_name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $subject);
				$this->email->message($message);

				break;
		}

		/* send the email */
		$email = $this->email->send();

		/* return the email variable */
		return $email;
	}
}

/* End of file write.php */
/* Location: ./application/controllers/write.php */