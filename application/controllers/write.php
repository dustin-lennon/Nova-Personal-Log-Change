<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/controllers/nova_write.php';

class Write extends Nova_write {

	public function __construct()
	{
		parent::__construct();
	}

	public function personallog($id = false)
	{
		Auth::check_access();

		// sanity check
		$id = (is_numeric($id)) ? $id : false;

		// load the resources
		$this->load->model('personallogs_model', 'logs');

		if ($this->options['system_email'] == 'off')
		{
			$flash['status'] = 'info';
			$flash['message'] = lang_output('flash_system_email_off');

			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'admin', $flash);
		}

		// set the variables
		$data['key'] = '';
		$content = false;
		$title = false;
		$tags = false;
		$location = false;
		$stardate = false;

		if (isset($_POST['submit']))
		{
			$title = $this->input->post('title', true);
			$content = $this->input->post('content', true);
			$author = $this->input->post('author', true);
			$tags = $this->input->post('tags', true);
			$location = $this->input->post('location', true);
			$stardate = $this->input->post('stardate', true);
			$action = strtolower($this->input->post('submit', true));
			$status = false;
			$flash = false;

			if ($author == 0)
			{
				$flash['status'] = 'error';
				$flash['message'] = lang_output('flash_personallogs_no_author');
			}
			else
			{
				switch ($action)
				{
					case 'delete':
						$row = $this->logs->get_log($id);

						if ($row !== false)
						{
							if ($row->log_status == 'saved' and
									$row->log_author_user == $this->session->userdata('userid'))
							{
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

							// add an automatic redirect
							$this->_regions['_redirect'] = Template::add_redirect('write/index');
						}
					break;

					case 'save':
						if ($id !== false)
						{
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
								'log_date' => now(),
							);

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
							$insert_array = array(
								'log_author_user' => $this->session->userdata('userid'),
								'log_author_character' => $author,
								'log_title' => $title,
								'log_content' => $content,
								'log_tags' => $tags,
								'log_stardate' => $stardate,
								'log_location' => $location,
								'log_status' => 'saved',
								'log_last_update' => now(),
								'log_date' => now(),
							);

							$insert = $this->logs->create_personal_log($insert_array);

							// grab the insert id
							$insert_id = $this->db->insert_id();

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

								// reset the fields if everything worked
								$content = false;
								$title = false;
								$tags = false;
								$location = false;
								$stardate = false;
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

							// add an automatic redirect
							$this->_regions['_redirect'] = Template::add_redirect('write/personallog/'.$insert_id);
						}
					break;

					case 'post':
						// check the moderation status
						$status = $this->user->checking_moderation('log', $this->session->userdata('userid'));

						if ($id !== false)
						{
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

								// set the array of data for the email
								$email_data = array(
									'author' => $author,
									'title' => $title,
									'stardate' => $stardate,
									'location' => $location,
									'content' => $content
								);

								if ($status == 'pending')
								{
									// send the email
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log_pending', $email_data) : false;
								}
								else
								{
									// send the email
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : false;
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

								// set the array of data for the email
								$email_data = array(
									'author' => $author,
									'title' => $title,
									'stardate' => $stardate,
									'location' => $location,
									'content' => $content
								);

								if ($status == 'pending')
								{
									// send the email
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log_pending', $email_data) : false;
								}
								else
								{
									// send the email
									$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : false;
								}

								// reset the fields if everything worked
								$content = false;
								$title = false;
								$tags = false;
								$stardate = false;
								$location = false;
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
					break;
				}
			}

			// set the flash message
			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'admin', $flash);
		}

		// run the methods
		$char = $this->session->userdata('characters');

		if (count($char) > 1)
		{
			$data['characters'][0] = ucwords(lang('labels_please').' '.lang('actions_select'))
				.' '.lang('labels_an').' '.ucfirst(lang('labels_author'));

			foreach ($char as $item)
			{
				$type = $this->char->get_character($item, 'crew_type');

				if ($type == 'active' or $type == 'npc')
				{
					if ($type == 'active')
					{
						$label = ucwords(lang('status_playing') .' '. lang('global_characters'));
					}
					else
					{
						$label = ucwords(lang('abbr_npcs'));
					}

					$data['characters'][$label][$item] = $this->char->get_character_name($item, true);
				}
			}
		}
		else
		{
			// set the ID and name
			$data['character']['id'] = $char[0];
			$data['character']['name'] = $this->char->get_character_name($char[0], true);
		}

		$row = ($id !== false) ? $this->logs->get_log($id) : false;

		if ($row !== false)
		{
			if ($row->log_author_user != $this->session->userdata('userid'))
			{
				redirect('admin/error/4');
			}

			if ( ! isset($action) and ($row->log_status == 'pending' or $row->log_status == 'activated'))
			{
				redirect('admin/error/5');
			}

			// fill the content in
			$title = $row->log_title;
			$stardate = $row->log_stardate;
			$location = $row->log_location;
			$content = $row->log_content;
			$tags = $row->log_tags;

			// set the key in prep for searching
			$data['key'] = 0;

			if (isset($data['characters']) and $data['key'] == 0)
			{
				foreach ($data['characters'] as $a)
				{
					if (is_array($a))
					{
						$data['key'] = (array_key_exists($row->log_author_character, $a)) ? $row->log_author_character : 0;
					}
				}
			}
		}

		$data['inputs'] = array(
			'title' => array(
				'name' => 'title',
				'id' => 'title',
				'value' => $title),
			'content' => array(
				'name' => 'content',
				'id' => 'content-textarea',
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

		$data['header'] = ucwords(lang('actions_write') .' '. lang('global_personallog'));

		$data['form_action'] = ($id !== false) ? 'write/personallog/'. $id : 'write/personallog';

		$data['label'] = array(
			'author' => ucwords(lang('labels_author')),
			'content' => ucwords(lang('labels_content')),
			'stardate' => ucwords(lang('labels_stardate')),
			'location' => ucfirst(lang('labels_location')),
			'tags' => ucwords(lang('labels_tags')),
			'tags_sep' => lang('tags_separated'),
			'title' => ucwords(lang('labels_title')),
			'select' => ucwords(lang('labels_please').' '.lang('actions_select')).' '.lang('labels_an').' '.ucfirst(lang('labels_author')),
		);

		$this->_regions['content'] = Location::view('write_personallog', $this->skin, 'admin', $data);
		$this->_regions['javascript'] = Location::js('write_personallog_js', $this->skin, 'admin');
		$this->_regions['title'].= $data['header'];

		Template::assign($this->_regions);

		Template::render();
	}
}
