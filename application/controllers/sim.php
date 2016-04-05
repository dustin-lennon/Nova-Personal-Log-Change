<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODPATH.'core/controllers/nova_sim.php';

class Sim extends Nova_sim {

	public function __construct()
	{
		parent::__construct();
	}

	public function viewlog($id = false)
	{
		// load the model
		$this->load->model('personallogs_model', 'logs');

		// sanity check
		$id = (is_numeric($id)) ? $id : false;

		if ($this->session->userdata('userid') !== false and isset($_POST['submit']))
		{
			$comment_text = $this->input->post('comment_text');

			if ( ! empty($comment_text))
			{
				$status = $this->user->checking_moderation('log_comment', $this->session->userdata('userid'));

				// build the insert array
				$insert = array(
					'lcomment_content' => $comment_text,
					'lcomment_log' => $id,
					'lcomment_date' => now(),
					'lcomment_author_character' => $this->session->userdata('main_char'),
					'lcomment_author_user' => $this->session->userdata('userid'),
					'lcomment_status' => $status
				);

				// insert the data
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
						// set the array of data for the email
						$email_data = array(
							'author' => $this->session->userdata('main_char'),
							'log' => $id,
							'comment' => $comment_text);

						// send the email
						$email = ($this->options['system_email'] == 'on') ? $this->_email('log_comment_pending', $email_data) : false;
					}
					else
					{
						// get the user id
						$user = $this->logs->get_log($id, 'log_author_user');

						// get the author's preference
						$pref = $this->user->get_pref('email_new_log_comments', $user);

						if ($pref == 'y')
						{
							// set the array of data for the email
							$email_data = array(
								'author' => $this->session->userdata('main_char'),
								'log' => $id,
								'comment' => $comment_text);

							// send the email
							$email = ($this->options['system_email'] == 'on') ? $this->_email('log_comment', $email_data) : false;
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

			$this->_regions['flash_message'] = Location::view('flash', $this->skin, 'main', $flash);
		}

		// fire the methods to get the log and its comments
		$logs = $this->logs->get_log($id);
		$comments = $this->logs->get_log_comments($id);

		if ($logs !== false)
		{
			$canView = false;

			if (! Auth::is_logged_in())
			{
				if ($logs->log_status == 'activated')
				{
					$canView = true;
				}
			}
			else
			{
				if ($logs->log_status == 'activated')
				{
					$canView = true;
				}
				else
				{
					if (Auth::get_access_level('manage/logs') == 1 and (int) $this->session('userid') == $logs->log_author_user)
					{
						$canView = true;
					}

					if (Auth::get_access_level('manage/logs') == 2)
					{
						$canView = true;
					}
				}
			}

			if (! $canView)
			{
				$data['header'] = sprintf(lang('error_title_invalid_char'), ucwords(lang('global_personallog')));

				// figure out where the view should be coming from
				$view_loc = 'error';
				$js_loc = false;

				// write the title
				$this->_regions['title'] .= lang('error_pagetitle');
			}
			else
			{
				$view_loc = 'sim_viewlog';

				// grab the next and previous IDs
				$next = $this->logs->get_link_id($id);
				$prev = $this->logs->get_link_id($id, 'prev');

				// set the date format
				$datestring = $this->options['date_format'];

				// set the date
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
				$data['author'] = $this->char->get_character_name($logs->log_author_character, true, false, true);
				$data['tags'] = ( ! empty($logs->log_tags)) ? $logs->log_tags : NULL;
				$data['location'] = $logs->log_location;
				$data['stardate'] = $logs->log_stardate;

				// determine if they can edit the log
				if (Auth::is_logged_in() === true and ( (Auth::get_access_level('manage/logs') == 2) or
					(Auth::get_access_level('manage/logs') == 1 and $this->session->userdata('userid') == $logs->log_author_user)))
				{
					$data['edit_valid'] = true;
				}
				else
				{
					$data['edit_valid'] = false;
				}

				if ($next !== false)
				{
					$data['next'] = $next;
				}

				if ($prev !== false)
				{
					$data['prev'] = $prev;
				}

				// image parameters
				$data['images'] = array(
					'next' => array(
						'src' => Location::img('next.png', $this->skin, 'main'),
						'alt' => ucfirst(lang('actions_next')),
						'class' => 'image'),
					'prev' => array(
						'src' => Location::img('previous.png', $this->skin, 'main'),
						'alt' => ucfirst(lang('status_previous')),
						'class' => 'image'),
					'feed' => array(
						'src' => Location::img('feed.png', $this->skin, 'main'),
						'alt' => lang('labels_subscribe'),
						'class' => 'image'),
					'comment' => array(
						'src' => Location::img('comment-add.png', $this->skin, 'main'),
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

						$data['comments'][$i]['author'] = $this->char->get_character_name($c->lcomment_author_character, true, false, true);
						$data['comments'][$i]['content'] = $c->lcomment_content;
						$data['comments'][$i]['date'] = mdate($datestring, $date);

						++$i;
					}
				}

				$data['label'] = array(
					'addcomment' => ucfirst(lang('actions_add')).' '.lang('labels_a').' '.ucfirst(lang('labels_comment')),
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

				$this->_regions['title'].= $data['title'];
			}
		}

		$this->_regions['content'] = Location::view('sim_viewlog', $this->skin, 'main', $data);
		$this->_regions['javascript'] = Location::js('sim_viewlog_js', $this->skin, 'main');

		Template::assign($this->_regions);
		Template::render();
	}
}
