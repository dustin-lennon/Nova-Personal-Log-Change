<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/controllers/nova_manage.php';

class Manage extends Nova_manage {

	public function __construct()
	{
		parent::__construct();
	}

	public function logs($section = 'activated', $offset = 0)
	{
		Auth::check_access();
		$level = Auth::get_access_level();

		$this->load->model('personallogs_model', 'logs');

		// arrays to check uri against
		$values = array('activated', 'saved', 'pending', 'edit');

		// sanity checks
		$section = (in_array($section, $values)) ? $section : 'activated';
		$offset = (is_numeric($offset)) ? $offset : 0;

		if (isset($_POST['submit']))
		{
			switch ($this->uri->segment(5))
			{
				case 'approve':
					if ($level == 2)
					{
						$id = $this->input->post('id', true);
						$id = (is_numeric($id)) ? $id : false;

						// set the array data
						$approve_array = array('log_status' => 'activated');

						// approve the post
						$approve = $this->logs->update_log($id, $approve_array);

						$message = sprintf(
							($approve > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_personallog')),
							lang('actions_approved'),
							''
						);
						$flash['status'] = ($approve > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);

						if ($approve > 0)
						{
							// grab the post details
							$row = $this->logs->get_log($id);

							// set the array of data for the email
							$email_data = array(
								'author' => $row->log_author_character,
								'title' => $row->log_title,
								'location' => $row->log_location,
								'stardate' => $row->log_stardate,
								'content' => $row->log_content
							);

							// send the email
							$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : false;
						}
					}
				break;

				case 'delete':
					$id = $this->input->post('id', true);
					$id = (is_numeric($id)) ? $id : false;

					// get the log we're trying to delete
					$item = $this->logs->get_log($id);

					// make sure the user is allowed to be deleting the log
					if (($level == 1 and ($item->log_author_user == $this->session->userdata('userid'))) or $level == 2)
					{
						$delete = $this->logs->delete_log($id);

						$message = sprintf(
							($delete > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_personallog')),
							lang('actions_deleted'),
							''
						);
						$flash['status'] = ($delete > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;

				case 'update':
					$id = $this->uri->segment(4, 0, true);

					// get the log we're trying to delete
					$item = $this->logs->get_log($id);

					// make sure the user is allowed to be deleting the log
					if (($level == 1 and ($item->log_author_user == $this->session->userdata('userid'))) or $level == 2)
					{
						$update_array = array(
							'log_title' => $this->input->post('log_title', true),
							'log_tags' => $this->input->post('log_tags', true),
							'log_location' => $this->input->post('log_location', TRUE),
							'log_stardate' => $this->input->post('log_stardate', TRUE),
							'log_content' => $this->input->post('log_content', true),
							'log_status' => $this->input->post('log_status', true),
							'log_author_user' => $this->user->get_userid($this->input->post('log_author')),
							'log_author_character' => $this->input->post('log_author', true),
							'log_last_update' => now()
						);

						$update = $this->logs->update_log($id, $update_array);

						$message = sprintf(
							($update > 0) ? lang('flash_success') : lang('flash_failure'),
							ucfirst(lang('global_personallog')),
							lang('actions_updated'),
							''
						);
						$flash['status'] = ($update > 0) ? 'success' : 'error';
						$flash['message'] = text_output($message);
					}
				break;
			}

			// set the flash message
			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'admin', $flash);
		}

		if ($section == 'edit')
		{
			// grab the ID from the URL
			$id = $this->uri->segment(4, 0, true);

			// grab the post data
			$row = $this->logs->get_log($id);

			if ($level < 2)
			{
				if ($this->session->userdata('userid') != $row->log_author_user or $row->log_status == 'pending')
				{
					redirect('admin/error/6');
				}
			}

			// get all characters
			$all = $this->char->get_all_characters('user_npc');

			if ($all->num_rows() > 0)
			{
				foreach ($all->result() as $a)
				{
					if ($a->crew_type == 'active' or $a->crew_type == 'npc')
					{
						if ($a->crew_type == 'active')
						{
							$label = ucwords(lang('status_playing') .' '. lang('global_characters'));
						}
						else
						{
							$label = ucwords(lang('abbr_npcs'));
						}

						// toss them in the array
						$data['all'][$label][$a->charid] = $this->char->get_character_name($a->charid, true);
					}
				}
			}

			// set the data used by the view
			$data['inputs'] = array(
				'title' => array(
					'name' => 'log_title',
					'value' => $row->log_title),
				'location' => array(
					'name' => 'log_location',
					'value' => $row->log_location),
				'stardate' => array(
					'name' => 'log_stardate',
					'value' => $row->log_stardate),
				'content' => array(
					'name' => 'log_content',
					'id' => 'content-textarea',
					'rows' => 20,
					'value' => $row->log_content),
				'tags' => array(
					'name' => 'log_tags',
					'value' => $row->log_tags),
				'author' => $row->log_author_character,
				'character' => $this->char->get_character_name($row->log_author_character, true),
				'status' => $row->log_status,
			);

			$data['status'] = array(
				'activated' => ucfirst(lang('status_activated')),
				'saved' => ucfirst(lang('status_saved')),
				'pending' => ucfirst(lang('status_pending')),
			);

			$data['buttons'] = array(
				'update' => array(
					'type' => 'submit',
					'class' => 'button-main',
					'name' => 'submit',
					'value' => 'update',
					'content' => ucfirst(lang('actions_update'))),
			);

			$data['header'] = ucwords(lang('actions_edit') .' '. lang('global_personallogs'));
			$data['id'] = $id;

			$data['label'] = array(
				'back' => LARROW .' '. ucfirst(lang('actions_back')) .' '. lang('labels_to')
					.' '. ucwords(lang('global_personallogs')),
				'status' => ucfirst(lang('labels_status')),
				'title' => ucfirst(lang('labels_title')),
				'location' => ucfirst(lang('labels_location')),
				'stardate' => ucfirst(lang('labels_stardate')),
				'content' => ucfirst(lang('labels_content')),
				'tags' => ucfirst(lang('labels_tags')),
				'tags_inst' => ucfirst(lang('tags_separated')),
				'addauthor' => ucwords(lang('actions_add') .' '. lang('labels_author')),
				'author' => ucwords(lang('labels_author'))
			);

			$js_data['tab'] = 0;

			// figure out where the view should be coming from
			$view_loc = 'manage_logs_edit';
		}
		else
		{
			switch ($section)
			{
				case 'activated':
				default:
					$js_data['tab'] = 0;
				break;

				case 'saved':
					$js_data['tab'] = 1;
				break;

				case 'pending':
					$js_data['tab'] = 2;
				break;
			}

			$offset_activated = ($section == 'activated') ? $offset : 0;
			$offset_saved = ($section == 'saved') ? $offset : 0;
			$offset_pending = ($section == 'pending') ? $offset : 0;

			$data['activated'] = $this->_entries_ajax($offset_activated, 'activated', 'logs');
			$data['saved'] = $this->_entries_ajax($offset_saved, 'saved', 'logs');
			$data['pending'] = $this->_entries_ajax($offset_pending, 'pending', 'logs');

			$data['label'] = array(
				'activated' => ucfirst(lang('status_activated')),
				'pending' => ucfirst(lang('status_pending')),
				'saved' => ucfirst(lang('status_saved')),
			);

			$data['header'] = ucwords(lang('actions_manage') .' '. lang('global_personallogs'));

			// figure out where the view should be coming from
			$view_loc = 'manage_logs';
		}

		$this->_regions['content'] = Location::view($view_loc, $this->skin, 'admin', $data);
		$this->_regions['javascript'] = Location::js('manage_logs_js', $this->skin, 'admin', $js_data);
		$this->_regions['title'].= $data['header'];

		Template::assign($this->_regions);

		Template::render();
	}
}
