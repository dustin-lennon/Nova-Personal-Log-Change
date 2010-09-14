<?php
/*
|---------------------------------------------------------------
| ADMIN - MANAGE CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/manage.php
| System Version: 1.0
|
| Controller that handles the MANAGE section of the admin system.
|
*/

require_once APPPATH . 'controllers/base/manage_base.php';

class Manage extends Manage_base {

	function Manage()
	{
		parent::Manage_base();
	}

	function logs()
	{
		$this->auth->check_access();

		$this->load->model('personallogs_model', 'logs');

		$values = array('activated', 'saved', 'pending', 'edit');
		$section = $this->uri->segment(3, 'activated', FALSE, $values);
		$offset = $this->uri->segment(4, 0, TRUE);

		if (isset($_POST['submit']))
		{
			switch ($this->uri->segment(5))
			{
				case 'approve':
					$id = $this->input->post('id', TRUE);
					$id = (is_numeric($id)) ? $id : FALSE;

					/* set the array data */
					$approve_array = array('log_status' => 'activated');

					/* approve the post */
					$approve = $this->logs->update_log($id, $approve_array);

					if ($approve > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_personallog')),
							lang('actions_approved'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);

						/* grab the post details */
						$row = $this->logs->get_log($id);

						/* set the array of data for the email */
						$email_data = array(
							'author' => $row->log_author_character,
							'title' => $row->log_title,
							'location' => $row->log_location,
							'stardate' => $row->log_stardate,
							'content' => $row->log_content
						);

						/* send the email */
						$email = ($this->options['system_email'] == 'on') ? $this->_email('log', $email_data) : FALSE;
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_personallog')),
							lang('actions_approved'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}

					/* write everything to the template */
					$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);

					break;

				case 'delete':
					$id = $this->input->post('id', TRUE);
					$id = (is_numeric($id)) ? $id : FALSE;

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

					$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);

					break;

				case 'update':
					$id = $this->uri->segment(4, 0, TRUE);

					$update_array = array(
						'log_title' => $this->input->post('log_title', TRUE),
						'log_tags' => $this->input->post('log_tags', TRUE),
						'log_location' => $this->input->post('log_location', TRUE),
						'log_stardate' => $this->input->post('log_stardate', TRUE),
						'log_content' => $this->input->post('log_content', TRUE),
						'log_status' => $this->input->post('log_status', TRUE),
						'log_author_user' => $this->user->get_userid($this->input->post('log_author')),
						'log_author_character' => $this->input->post('log_author', TRUE),
						'log_last_update' => now()
					);

					$update = $this->logs->update_log($id, $update_array);

					if ($update > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_personallog')),
							lang('actions_updated'),
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
							lang('actions_updated'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}

					/* write everything to the template */
					$this->template->write_view('flash_message', '_base/admin/pages/flash', $flash);

					break;
			}
		}

		if ($section == 'edit')
		{
			/* grab the ID from the URL */
			$id = $this->uri->segment(4, 0, TRUE);

			/* grab the post data */
			$row = $this->logs->get_log($id);

			if ($this->auth->get_access_level() < 2)
			{
				if ($this->session->userdata('userid') != $row->log_author_user || $row->log_status == 'pending')
				{
					redirect('admin/error/6');
				}
			}

			/* get all characters */
			$all = $this->char->get_all_characters('user_npc');

			if ($all->num_rows() > 0)
			{
				foreach ($all->result() as $a)
				{
					if ($a->crew_type == 'active' || $a->crew_type == 'npc')
					{ /* split the characters out between active and npcs */
						if ($a->crew_type == 'active')
						{
							$label = ucwords(lang('status_playing') .' '. lang('global_characters'));
						}
						else
						{
							$label = ucwords(lang('abbr_npcs'));
						}

						/* toss them in the array */
						$data['all'][$label][$a->charid] = $this->char->get_character_name($a->charid, TRUE);
					}
				}
			}

			/* set the data used by the view */
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
					'rows' => 20,
					'value' => $row->log_content),
				'tags' => array(
					'name' => 'log_tags',
					'value' => $row->log_tags),
				'author' => $row->log_author_character,
				'character' => $this->char->get_character_name($row->log_author_character, TRUE),
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

			/* figure out where the view should be coming from */
			$view_loc = view_location('manage_logs_edit', $this->skin, 'admin');
			$js_loc = js_location('manage_logs_js', $this->skin, 'admin');
		}
		else
		{
			switch ($section)
			{
				case 'activated':
					$js_data['tab'] = 0;
					break;

				case 'saved':
					$js_data['tab'] = 1;
					break;

				case 'pending':
					$js_data['tab'] = 2;
					break;

				default:
					$js_data['tab'] = 0;
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

			/* figure out where the view should be coming from */
			$view_loc = view_location('manage_logs', $this->skin, 'admin');
			$js_loc = js_location('manage_logs_js', $this->skin, 'admin');
		}

		/* write the data to the template */
		$this->template->write('title', $data['header']);
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc, $js_data);

		/* render the template */
		$this->template->render();
	}
}

/* End of file manage.php */
/* Location: ./application/controllers/manage.php */