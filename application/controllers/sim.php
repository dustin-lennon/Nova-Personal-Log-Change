<?php
/*
|---------------------------------------------------------------
| SIM CONTROLLER
|---------------------------------------------------------------
|
| File: controllers/sim.php
| System Version: 1.0
|
| Controller that handles the SIM part of the system.
|
*/

require_once APPPATH . 'controllers/base/sim_base.php';

class Sim extends Sim_base {
	
	function Sim()
	{
		parent::Sim_base();
	}

	function viewlog()
	{
		/* set the some variables */
		$id = $this->uri->segment(3, FALSE, TRUE);

		/* load the model */
		$this->load->model('personallogs_model', 'logs');

		if ($this->session->userdata('userid') !== FALSE && isset($_POST['submit']))
		{
			$comment_text = $this->input->post('comment_text');
			
			if (!empty($comment_text))
			{
				$status = $this->user->checking_moderation('log_comment', $this->session->userdata('userid'));
				
				/* build the insert array */
				$insert = array(
					'lcomment_content' => $comment_text,
					'lcomment_log' => $id,
					'lcomment_date' => now(),
					'lcomment_author_character' => $this->session->userdata('main_char'),
					'lcomment_author_user' => $this->session->userdata('userid'),
					'lcomment_status' => $status
				);

				/* insert the data */
				$add = $this->logs->add_log_comment($insert);

				if ($add > 0)
				{
					$message = sprintf(
						lang('flash_success'),
						ucfirst(lang('labels_comment')),
						lang('actions_added'),
						''
					);
					
					$flash['status'] = 'success';
					$flash['message'] = text_output($message);

					if ($status == 'pending')
					{
						/* set the array of data for the email */
						$email_data = array(
							'author' => $this->session->userdata('main_char'),
							'log' => $id,
							'comment' => $comment_text);

						/* send the email */
						$email = ($this->options['system_email'] == 'on') ? $this->_email('log_comment_pending', $email_data) : FALSE;
					}
					else
					{
						/* get the user id */
						$user = $this->logs->get_log($id, 'log_author_user');

						/* get the author's preference */
						$pref = $this->user->get_pref('email_new_log_comments', $user);

						if ($pref == 'y')
						{
							/* set the array of data for the email */
							$email_data = array(
								'author' => $this->session->userdata('main_char'),
								'log' => $id,
								'comment' => $comment_text);

							/* send the email */
							$email = ($this->options['system_email'] == 'on') ? $this->_email('log_comment', $email_data) : FALSE;
						}
					}
				}
				else
				{
					$message = sprintf(
						lang('flash_failure'),
						ucfirst(lang('labels_comment')),
						lang('actions_added'),
						''
					);

					$flash['status'] = 'error';
					$flash['message'] = text_output($message);
				}
			}
			else
			{
				$flash['status'] = 'error';
				$flash['message'] = lang_output('flash_add_comment_empty_body');
			}

			/* write everything to the template */
			$this->template->write_view('flash_message', '_base/main/pages/flash', $flash);
		}

		/* fire the methods to get the log and its comments */
		$logs = $this->logs->get_log($id);
		$comments = $this->logs->get_log_comments($id);

		if ($logs !== FALSE)
		{
			/* grab the next and previous IDs */
			$next = $this->logs->get_link_id($id);
			$prev = $this->logs->get_link_id($id, 'prev');

			/* set the date format */
			$datestring = $this->options['date_format'];

			/* set the date */
			$date = gmt_to_local($logs->log_date, $this->timezone, $this->dst);

			if ($logs->log_date < $logs->log_last_update)
			{
				$edited = gmt_to_local($logs->log_last_update, $this->timezone, $this->dst);
				$data['update'] = mdate($datestring, $edited);
			}

			$data['id'] = $logs->log_id;
			$data['title'] = $logs->log_title;
			$data['content'] = $logs->log_content;
			$data['date'] = mdate($datestring, $date);
			$data['author'] = $this->char->get_character_name($logs->log_author_character, TRUE);
			$data['tags'] = (!empty($logs->log_tags)) ? $logs->log_tags : NULL;
			$data['location'] = $logs->log_location;
			$data['stardate'] = $logs->log_stardate;

			/* determine if they can edit the log */
			if ($this->auth->is_logged_in() === TRUE && ( ($this->auth->get_access_level('manage/logs') == 2) ||
				($this->auth->get_access_level('manage/logs') == 1 && $this->session->userdata('userid') == $logs->log_author_user)))
			{
				$data['edit_valid'] = TRUE;
			}
			else
			{
				$data['edit_valid'] = FALSE;
			}

			if ($next !== FALSE)
			{
				$data['next'] = $next;
			}

			if ($prev !== FALSE)
			{
				$data['prev'] = $prev;
			}
		}

		/* image parameters */
		$data['images'] = array(
			'next' => array(
				'src' => img_location('next.png', $this->skin, 'main'),
				'alt' => ucfirst(lang('actions_next')),
				'class' => 'image'),
			'prev' => array(
				'src' => img_location('previous.png', $this->skin, 'main'),
				'alt' => ucfirst(lang('status_previous')),
				'class' => 'image'),
			'feed' => array(
				'src' => img_location('feed.png', $this->skin, 'main'),
				'alt' => lang('labels_subscribe'),
				'class' => 'image'),
			'comment' => array(
				'src' => img_location('comment-add.png', $this->skin, 'main'),
				'alt=' => '',
				'class' => 'inline_img_left image'),
		);

		$data['comment_count'] = $comments->num_rows();

		if ($comments->num_rows() > 0)
		{
			$i = 1;
			foreach ($comments->result() as $c)
			{
				$date = gmt_to_local($c->lcomment_date, $this->timezone, $this->dst);

				$data['comments'][$i]['author'] = $this->char->get_character_name($c->lcomment_author_character, TRUE);
				$data['comments'][$i]['content'] = $c->lcomment_content;
				$data['comments'][$i]['date'] = mdate($datestring, $date);

				++$i;
			}
		}

		$data['label'] = array(
			'addcomment' => ucfirst(lang('actions_add')) .' '. lang('labels_a') .' '.
				ucfirst(lang('labels_comment')),
			'by' => lang('labels_by'),
			'comments' => ucfirst(lang('labels_comments')),
			'edit' => '[ '. ucfirst(lang('actions_edit')) .' ]',
			'edited' => ucfirst(lang('actions_edited') .' '. lang('labels_on')),
			'on' => lang('labels_on'),
			'posted' => ucfirst(lang('actions_posted') .' '. lang('labels_on')),
			'tags' => ucfirst(lang('labels_tags')) .':',
			'title' => ucfirst(lang('labels_title')),
			'view_log' => ucwords(lang('actions_view') .' '. lang('global_log')),
			'location' => ucfirst(lang('labels_location')),
			'stardate' => ucfirst(lang('labels_stardate')),
		);

		/* figure out where the view should be coming from */
		$view_loc = view_location('sim_viewlog', $this->skin, 'main');
		$js_loc = js_location('sim_viewlog_js', $this->skin, 'main');

		/* write the data to the template */
		$this->template->write('title', $data['title']);
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);

		/* render the template */
		$this->template->render();
	}
}

/* End of file sim.php */
/* Location: ./application/controllers/sim.php */