<?php 

class Election extends CI_Controller {


	function index()
	{

		$data['mode'] = 'choose';
		if ($this->input->post('postcode')) {
			$user_postcode = $this->input->post('postcode');
			$user_postcode = trim($user_postcode);

			// first get the ward code according to postcode
			$this->db->select('wards.old_code, wards.new_code, codes.ward, codes.district, codes.postcode');
			$this->db->join('wards', 'codes.ward_code = wards.new_code');
			$ward_data = $this->db->get_where('codes', array('codes.postcode' => $user_postcode))->row_array();
			$this->session->set_flashdata('postcode', $user_postcode);
			redirect('london/2008/' . url_title($ward_data['district'], 'dash', TRUE) . '/' . $ward_data['new_code']);
		} else {
			$this->db->order_by('district_name');
			$this->db->select('district_name');
			$this->db->group_by('district_name');
			$this->db->join('votes_normalised', 'wards.new_code = votes_normalised.ward_id');
			$data['districts'] = $this->db->get('wards')->result_array();
			$this->load->view('results', $data);
		}
	}

	function render($new_code) {
		
		$this->output->cache(60);

		$data['mode'] = 'votes';
		//$this->output->enable_profiler(TRUE);

		// used for calculating if voting average is statistically significant
		$data['bounds'] = array('lower' => 75, 'upper' => 120);

		// get national results first
		$this->db->select('votes_normalised.candidate_id, candidate_name, AVG(votes) AS average');
		$this->db->join('vote_candidates', 'votes_normalised.candidate_id = vote_candidates.candidate_id');
		$this->db->group_by('votes_normalised.candidate_id');
		$this->db->order_by('average DESC');
		$overall_votes = $this->db->get_where('votes_normalised', array('vote_candidates.category_id' => '1'))->result_array();

		// now process them into a comparable form
		foreach($overall_votes as $v) {
			$data['overall_votes'][$v['candidate_id']] = $v['average'];
		}

		// get ward data
		$data['ward_data'] = $this->db->get_where('wards', array('new_code' => $new_code))->row_array();

		// get ward votes
		$this->db->order_by('votes DESC');
		$this->db->join('vote_candidates', 'votes_normalised.candidate_id = vote_candidates.candidate_id');
		$this->db->join('vote_categories', 'vote_candidates.category_id = vote_categories.cat_id');
		$data['votes'] = $this->db->get_where('votes_normalised', array('ward_id' => $new_code))->result_array();

		if (!empty($data['votes'])) {

			$data['votes_totals'] = array();
			$data['votes_candidates'] = array();
			$highest = 0;
			$data['winner'] = array();
			
			foreach($data['votes'] as $v) {

				// find highest first pref
				if($v['votes'] > $highest && $v['cat_id'] == 1) {
					$highest = $v['votes'];
					$data['winner'] = array('candidate' => $v['candidate_name']);
				}

				if(!isset($data['votes_totals'][$v['cat_id']])) {
					$data['votes_totals'][$v['cat_id']]['votes'] = $v['votes'];
					$data['votes_totals'][$v['cat_id']]['candidates'] = 1;
				} else {
					$data['votes_totals'][$v['cat_id']]['votes'] += $v['votes'];
					$data['votes_totals'][$v['cat_id']]['candidates'] += 1;
				}

				if(!isset($data['votes_candidates'][$v['candidate_id']])) {
					$data['votes_candidates'][$v['candidate_id']] = $v['votes'];
				} else {
					$data['votes_candidates'][$v['candidate_id']] += $v['votes'];
				}
			}

		} else {
			$data['mode'] = "error";
		}

		$this->load->view('results', $data);
	}

	/*
	function fix() {

		set_time_limit(0);
		$this->db->truncate('votes_normalised');

		$this->db->join('vote_categories', 'vote_candidates.category_id = vote_categories.cat_id');
		$all_candidates = $this->db->get('vote_candidates')->result_array();

		foreach($all_candidates as $c) {
			$candidates['candidate_' . $c['candidate_id']] = array('name' => $c['candidate_name'], 'category' => $c['cat_name'], 'id' => $c['candidate_id']);
		}

		$this->db->join('wards', 'votes.ward_code = wards.old_code');
		$votes = $this->db->get('votes')->result_array();

		$to_insert = array();

		foreach($votes as $v) {

			$ward = $v['new_code'];

			foreach($v as $key=>$value) {
				// check it's a vote
				if(strpos($key, 'candidate_') !== false) {
					$current_candidate = $candidates[$key];
					$arr = array(
						'ward_id' => $ward,
						'candidate_id' => $current_candidate['id'],
						'votes' =>  $value
					);
					$to_insert[] = $arr;
					//$this->db->insert('votes_normalised', $arr);
				}
			}


		}

		$this->db->insert_batch('votes_normalised', $to_insert); 

		echo "all done!";
		flush();
	}

	function fix2() {
		set_time_limit(0);
		$wards = $this->db->get('wards')->result_array();
		foreach($wards as $w) {
			$data = $this->db->get_where('codes', array('ward_code' => $w['new_code']))->row_array();
			$arr = array(
				'ward_name' => $data['ward'],
				'district_name' => $data['district'],
				'postcode' => $data['postcode']
			);
			$this->db->where('id', $w['id']);
			$this->db->update('wards', $arr);
		}
		echo "finished";
	}
	*/

	function district($district_name) {
		$data['mode'] = 'wards';
		$district_name = str_replace('-', ' ', $district_name);
		$this->db->select('new_code, ward_name, district_name');
		$data['wards'] = $this->db->get_where('wards', array('district_name' => $district_name))->result_array();
		$this->load->view('results', $data);
	}

	function stats($mode) {
		switch($mode) {
			case "most-bnp":
				$this->db->limit(1);
				$this->db->order_by('votes DESC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 1))->row_array();
				break;
			case "most-tory":
				$this->db->limit(1);
				$this->db->order_by('votes DESC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 6))->row_array();
				break;
			case "most-labour":
				$this->db->limit(1);
				$this->db->order_by('votes DESC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 7))->row_array();
				break;
			case "most-green":
				$this->db->limit(1);
				$this->db->order_by('votes DESC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 3))->row_array();
				break;
			case "least-bnp":
				$this->db->limit(1);
				$this->db->order_by('votes ASC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 1))->row_array();
				break;
			case "least-tory":
				$this->db->limit(1);
				$this->db->order_by('votes ASC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 6))->row_array();
				break;
			case "least-labour":
				$this->db->limit(1);
				$this->db->order_by('votes ASC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 7))->row_array();
				break;
			case "least-green":
				$this->db->limit(1);
				$this->db->order_by('votes ASC');
				$this->db->join('wards', 'votes_normalised.ward_id = wards.new_code');
				$ward = $this->db->get_where('votes_normalised', array('candidate_id' => 3))->row_array();
				break;
		}

		redirect('london/2008/' . url_title($ward['district_name'], 'dash', TRUE) . '/' . $ward['new_code']);
	}


}