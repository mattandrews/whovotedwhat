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
		
		//$this->output->cache(60);
		//$this->output->enable_profiler(TRUE);

		$data['mode'] = 'votes';

		// used for calculating if voting average is statistically significant
		$data['bounds'] = array('lower' => 75, 'upper' => 120);

		// get national results first
		$this->db->select('election_votes.person_id, election_people.name AS person_name, AVG(election_votes.votes) AS average, election_parties.name AS party_name, election_parties.colour');
		$this->db->join('election_people', 'election_votes.person_id = election_people.id');
		$this->db->join('election_parties', 'election_people.party_id = election_parties.id');
		$this->db->group_by('election_votes.person_id');
		$this->db->order_by('average DESC');
		$where = array(
			'election_votes.category_id' => 1,
			'election_votes.election_id' => 1
		);
		$overall_votes = $this->db->get_where('election_votes', $where)->result_array();

		// now process them into a comparable form
		foreach($overall_votes as $v) {
			$data['overall_votes'][$v['person_id']] = $v['average'];
		}

		// get ward data
		$data['ward_data'] = $this->db->get_where('wards', array('new_code' => $new_code))->row_array();

		// get ward votes
		$this->db->select('election_votes.person_id AS candidate_id, votes, election_people.name AS candidate_name, cat_name, cat_id, election_parties.name AS party_name');
		$this->db->join('election_people', 'election_votes.person_id = election_people.id');
		$this->db->join('election_parties', 'election_people.party_id = election_parties.id');
		$this->db->join('election_categories', 'election_votes.category_id = election_categories.cat_id');
		$this->db->order_by('votes DESC');
		$where = array(
			'election_votes.category_id' => 1,
			'election_votes.election_id' => 1,
			'ward_id' => $new_code
		);
		$data['votes'] = $this->db->get_where('election_votes', $where)->result_array();

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

		print_r($data['votes']);
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

	function fix3() {
		set_time_limit(0);
		
		$this->db->truncate('election_votes');
		$this->db->join('election_candidates', 'votes_normalised.candidate_id = election_candidates.id');
		$this->db->join('election_people', 'election_candidates.person_id = election_people.id');
		$votes = $this->db->get('votes_normalised')->result_array();

		foreach($votes as $v) {
			$arr = array(
				'vote_id' => $v['vote_id'],
				'ward_id' => $v['ward_id'],
				'person_id' => $v['person_id'],
				'votes' => $v['votes'],
				'election_id' => 1,
				'category_id' => $v['category_id']
			);
			$this->db->insert('election_votes', $arr);
		}
		echo 'all done!';
	}


	function district($district_name) {
		$data['mode'] = 'wards';
		$district_name = str_replace('-', ' ', $district_name);
		$this->db->select('new_code, ward_name, district_name');
		$data['wards'] = $this->db->get_where('wards', array('district_name' => $district_name))->result_array();
		$this->load->view('results', $data);
	}

	function stats($mode) {

		$this->db->limit(1);
		$this->db->join('wards', 'election_votes.ward_id = wards.new_code');
		$this->db->join('election_people', 'election_votes.person_id = election_people.id');
		$this->db->join('election_parties', 'election_people.party_id = election_parties.id');
		$where = array('election_id' => 1, 'category_id' => 1);

		switch($mode) {
			case "most-bnp":
				$this->db->order_by('votes DESC');
				$where['election_parties.id'] = 1;
				break;
			case "most-tory":
				$this->db->order_by('votes DESC');
				$where['election_parties.id'] = 6;
				break;
			case "most-labour":
				$this->db->order_by('votes DESC');
				$where['election_parties.id'] = 7;
				break;
			case "most-green":
				$this->db->order_by('votes DESC');
				$where['election_parties.id'] = 3;
				break;
			case "least-bnp":
				$this->db->order_by('votes ASC');
				$where['election_parties.id'] = 1;
				break;
			case "least-tory":
				$this->db->order_by('votes ASC');
				$where['election_parties.id'] = 6;
				break;
			case "least-labour":
				$this->db->order_by('votes ASC');
				$where['election_parties.id'] = 7;
				break;
			case "least-green":
				$this->db->order_by('votes ASC');
				$where['election_parties.id'] = 3;
				break;
		}

		$ward = $this->db->get_where('election_votes', $where)->row_array();
		redirect('london/2008/' . url_title($ward['district_name'], 'dash', TRUE) . '/' . $ward['new_code']);
	}


}