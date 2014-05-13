<?php
class Customers_model extends CI_Model {

    public function record_count($filter = array()) {
		if (!empty($filter['filter_search'])) {
			$this->db->like('first_name', $filter['filter_search']);
			$this->db->or_like('last_name', $filter['filter_search']);
			$this->db->or_like('email', $filter['filter_search']);
		}

		if (isset($filter['filter_status']) AND is_numeric($filter['filter_status'])) {
			$this->db->where('status', $filter['filter_status']);
		}

		if (!empty($filter['filter_date'])) {
			$date = explode('-', $filter['filter_date']);
			$this->db->where('YEAR(date_added)', $date[0]);
			$this->db->where('MONTH(date_added)', $date[1]);
		}

		$this->db->from('customers');
		return $this->db->count_all_results();
    }
    
	public function getList($filter = array()) {
		if ($filter['page'] !== 0) {
			$filter['page'] = ($filter['page'] - 1) * $filter['limit'];
		}
		
        if ($this->db->limit($filter['limit'], $filter['page'])) {	
			$this->db->from('customers');
			
			if (!empty($filter['sort_by']) AND !empty($filter['order_by'])) {
				$this->db->order_by($filter['sort_by'], $filter['order_by']);
			} else {
				$this->db->order_by('date_added', 'DESC');
			}

			if (!empty($filter['filter_search'])) {
				$this->db->like('first_name', $filter['filter_search']);
				$this->db->or_like('last_name', $filter['filter_search']);
				$this->db->or_like('email', $filter['filter_search']);
			}

			if (isset($filter['filter_status']) AND is_numeric($filter['filter_status'])) {
				$this->db->where('status', $filter['filter_status']);
			}

			if (!empty($filter['filter_date'])) {
				$date = explode('-', $filter['filter_date']);
				$this->db->where('YEAR(date_added)', $date[0]);
				$this->db->where('MONTH(date_added)', $date[1]);
			}

			$query = $this->db->get();
			$result = array();
		
			if ($query->num_rows() > 0) {
				$result = $query->result_array();
			}
		
			return $result;
		}
	}

	public function getCustomers() {
		$this->db->from('customers');

		$query = $this->db->get();
		$result = array();
		
		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}
		
		return $result;
	}

	public function getCustomer($customer_id) {
		$this->db->from('customers');		
		$this->db->where('customer_id', $customer_id);
		
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
		}
	}

	public function getCustomerDates() {
		$this->db->select('date_added, MONTH(date_added) as month, YEAR(date_added) as year');
		$this->db->from('customers');
		$this->db->group_by('MONTH(date_added)');
		$this->db->group_by('YEAR(date_added)');
		$query = $this->db->get();
		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}
	
	public function getCustomerByEmail($email) {
		$this->db->from('customers');		
		$this->db->where('email', strtolower($email));
		
		$query = $this->db->get();

		if ($query->num_rows() === 1) {
			$row = $query->row_array();
			
			return $row;
		}
	}

	public function getCustomerAddresses($customer_id) {
		$this->db->from('address');
		$this->db->join('countries', 'countries.country_id = address.country_id', 'left');

		$this->db->where('customer_id', $customer_id);
		
		$query = $this->db->get();
		
		$address_data = array();
		
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $result) {

				$address_data[$result['address_id']] = array(
					'address_id'     => $result['address_id'],
					'address_1'      => $result['address_1'],
					'address_2'      => $result['address_2'],
					'city'           => $result['city'],
					'state'          => $result['state'],
					'postcode'       => $result['postcode'],
					'country_id'     => $result['country_id'],
					'country'        => $result['country_name'],
					'iso_code_2'     => $result['iso_code_2'],
					'iso_code_3'     => $result['iso_code_3'],
					'format'		 => $result['format']	
				);
			}
		}

		return $address_data;
	}

	public function getCustomerAddress($customer_id, $address_id) {
		if (($customer_id !== '0') AND ($address_id !== '0')) {
			$this->db->from('address');
			$this->db->join('countries', 'countries.country_id = address.country_id', 'left');
			
			$this->db->where('address_id', $address_id);
			$this->db->where('customer_id', $customer_id);

			$query = $this->db->get();

			$address_data = array();
			
			if ($query->num_rows() > 0) {
				$row = $query->row_array();

				$address_data = array(
					'address_id'     => $row['address_id'],
					'address_1'      => $row['address_1'],
					'address_2'      => $row['address_2'],
					'city'           => $row['city'],
					'state'          => $row['state'],
					'postcode'       => $row['postcode'],
					'country_id'     => $row['country_id'],
					'country'        => $row['country_name'],
					'iso_code_2'     => $row['iso_code_2'],
					'iso_code_3'     => $row['iso_code_3'],
					'format'     	 => $row['format']	
				);
			}

			return $address_data;
		}
	}
	
	public function getCustomerDefaultAddress($address_id, $customer_id) {
		if (($address_id !== '0') && ($customer_id !== '0')) {
			$this->db->from('address');
			$this->db->join('countries', 'countries.country_id = address.country_id', 'left');
			
			$this->db->where('address_id', $address_id);
			$this->db->where('customer_id', $customer_id);

			$query = $this->db->get();

			if ($query->num_rows() > 0) {
				return $query->row_array();
			}
		}
	}
	
	public function getAutoComplete($filter_data = array()) {
		if (is_array($filter_data) && !empty($filter_data)) {
			$this->db->from('customers');
	
			if (!empty($filter_data['customer_name'])) {
				$this->db->like('CONCAT(first_name, last_name)', $filter_data['customer_name']);		
			}
	
			$query = $this->db->get();
			$result = array();
		
			if ($query->num_rows() > 0) {
				$result = $query->result_array();
			}
		
			return $result;
		}
	}
	
	public function changePassword($customer_id, $password) {
		if (!empty($password)) {
			$this->db->set('password', sha1($password));
				
			$this->db->where('customer_id', $customer_id);
			$this->db->update('customers');
			
			if ($this->db->affected_rows() > 0) {
				return TRUE;
			}
		}
	}	

	public function resetPassword($customer_id = FALSE, $email = FALSE, $security_question_id = FALSE, $security_answer = FALSE) {
		
		if ($customer_id != FALSE && $email != FALSE && $security_question_id != FALSE && $security_answer != FALSE) {

			$this->db->from('customers');
			$this->db->where('customer_id', $customer_id);
			$this->db->where('email', strtolower($email));
			$this->db->where('security_question_id', $security_question_id);
			$this->db->where('security_answer', $security_answer);
			$this->db->where('status', '1');

			$query = $this->db->get();

			if ($query->num_rows() === 1) {
				$row = $query->row_array();

				//Randome Password
				$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
				$pass = array();
				for ($i = 0; $i < 8; $i++) {
					$n = rand(0, strlen($alphabet)-1);
					$pass[$i] = $alphabet[$n];
				}
		
				$password = implode('',$pass);

				$this->db->set('salt', $salt = substr(md5(uniqid(rand(), TRUE)), 0, 9));
				$this->db->set('password', sha1($salt . sha1($salt . sha1($password))));
				$this->db->where('customer_id', $row['customer_id']);
				$this->db->where('email', $row['email']);
			
				$this->db->update('customers');

				if ($this->db->affected_rows() > 0) {
					$this->lang->load('main/password_reset');
				
					$data['text_success_message'] = sprintf($this->lang->line('text_success_message'), site_url('main/login'), $row['email'], $password);
				
					$subject = $this->lang->line('text_subject');
					$message = $this->load->view('main/password_reset_email', $data, TRUE);

					$this->sendMail($email, $subject, $message);
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}

	public function updateCustomer($update = array()) {
		$query = FALSE;

		if (!empty($update['first_name'])) {
			$this->db->set('first_name', $update['first_name']);
		}
		
		if (!empty($update['last_name'])) {
			$this->db->set('last_name', $update['last_name']);			
		}
		
		if (!empty($update['email'])) {
			$this->db->set('email', strtolower($update['email']));
		}

		if (!empty($update['password'])) {
			$this->db->set('salt', $salt = substr(md5(uniqid(rand(), TRUE)), 0, 9));
			$this->db->set('password', sha1($salt . sha1($salt . sha1($update['password']))));
		}
			
		if (!empty($update['telephone'])) {
			$this->db->set('telephone', $update['telephone']);
		}
		
		if (!empty($update['security_question_id'])) {
			$this->db->set('security_question_id', $update['security_question_id']);
		}
		
		if (!empty($update['security_answer'])) {
			$this->db->set('security_answer', $update['security_answer']);
		}
		
		if (!empty($update['date_added'])) {
			$this->db->set('date_added', $update['date_added']);
		}
		
		if ($update['status'] === '1') {
			$this->db->set('status', $update['status']);
		} else {
			$this->db->set('status', '0');
		}
		
		if (!empty($update['customer_id'])) {
			$this->db->where('customer_id', $update['customer_id']);
			$this->db->update('customers');
		}
			
		if (!empty($update['address']) && !empty($update['customer_id'])) {
			foreach ($update['address'] as $address) {
				if (!empty($address['address_id'])) {
					$this->updateCustomerAddress($update['customer_id'], $address['address_id'], $address);
				} else {
					$this->updateCustomerAddress($update['customer_id'], '', $address);
				}
			}
		}
		
		if ($this->db->affected_rows() > 0) {
			$query = TRUE;
		}
		
		return $query;
	}	

	public function addCustomer($add = array()) {
		if (!empty($add['first_name'])) {
			$this->db->set('first_name', $add['first_name']);
		}
		
		if (!empty($add['last_name'])) {
			$this->db->set('last_name', $add['last_name']);
		}
		
		if (!empty($add['email'])) {
			$this->db->set('email', $add['email']);
		}

 		if (!empty($add['password'])) {
			$this->db->set('salt', $salt = substr(md5(uniqid(rand(), TRUE)), 0, 9));
			$this->db->set('password', sha1($salt . sha1($salt . sha1($add['password']))));
		}
		
		if (!empty($add['telephone'])) {
			$this->db->set('telephone', $add['telephone']);
		}
		
		if (!empty($add['security_question_id'])) {
			$this->db->set('security_question_id', $add['security_question_id']);
		}
		
		if (!empty($add['security_answer'])) {
			$this->db->set('security_answer', $add['security_answer']);
		}
		
		if (!empty($add['date_added'])) {
			$this->db->set('date_added', $add['date_added']);
		}
		
		if ($add['status'] === '1') {
			$this->db->set('status', $add['status']);
		} else {
			$this->db->set('status', '0');
		}
		
		$this->db->insert('customers');

		if ($this->db->affected_rows() > 0) {
			$customer_id = $this->db->insert_id();
			
			if (!empty($add['address']) AND $customer_id) {
				foreach ($add['address'] as $address) {
					$this->addCustomerAddress($customer_id, $address);
				}

				$query = TRUE;
			}
			
			$this->lang->load('main/login_register');
			
			$data['text_success_message'] = $this->lang->line('text_success_message');
			$data['text_signature'] = sprintf($this->lang->line('text_signature'), $this->config->item('site_name'));
			
			$subject = $this->lang->line('text_subject');
			$message = $this->load->view('main/register_email', $data, TRUE);

			$this->sendMail($add['email'], $subject, $message);
			
			return TRUE;
		}
	}

	public function updateCustomerAddress($customer_id = FALSE, $address_id = FALSE, $address = array()) {
		if ($customer_id) {
			$this->db->set('customer_id', $customer_id);
		}

		if (!empty($address['address_1'])) {
			$this->db->set('address_1', $address['address_1']);
		}

		if (!empty($address['address_2'])) {
			$this->db->set('address_2', $address['address_2']);
		}

		if (!empty($address['city'])) {
			$this->db->set('city', $address['city']);
		}

		if (!empty($address['postcode'])) {
			$this->db->set('postcode', $address['postcode']);
		}

		if (!empty($address['country'])) {
			$this->db->set('country_id', $address['country']);
		}
			
		if ($address_id) {
			$this->db->where('address_id', $address_id);
			$this->db->update('address');
		} else {
			$this->db->insert('address');
		}

		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}
	
	public function updateCustomerDefaultAddress($customer_id = '', $address_id = '') {
		if ($address_id !== '' AND $customer_id !== '') {
			$this->db->set('address_id', $address_id);
			$this->db->where('customer_id', $customer_id);
		}

		$this->db->update('customers');
		
		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}
	
	public function addCustomerAddress($customer_id, $address = array()) {

		if ($customer_id) {
			$this->db->set('customer_id', $customer_id);
		}

		if (!empty($address['address_1'])) {
			$this->db->set('address_1', $address['address_1']);
		}

		if (!empty($address['address_2'])) {
			$this->db->set('address_2', $address['address_2']);
		}

		if (!empty($address['city'])) {
			$this->db->set('city', $address['city']);
		}

		if (!empty($address['postcode'])) {
			$this->db->set('postcode', $address['postcode']);
		}

		if (!empty($address['country'])) {
			$this->db->set('country_id', $address['country']);
		}
			
		$this->db->insert('address');

		if ($this->db->affected_rows() > 0) {
			$address_id = $this->db->insert_id();			
			return $address_id;
		}
	}	

	public function deleteCustomer($customer_id) {
		if ($customer_id) {
			$this->db->where('customer_id', $customer_id);
			$this->db->delete('customers');
		
			$this->db->where('customer_id', $customer_id);
			$this->db->delete('address');
		}
		
		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}

	public function deleteAddress($customer_id, $address_id) {

		$this->db->where('customer_id', $customer_id);
		$this->db->where('address_id', $address_id);
		
		$this->db->delete('address');
		
		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}

	public function sendMail($email, $subject, $message) {
		//loading upload library
	   	$this->load->library('email');

		//setting upload preference
		$this->email->set_protocol($this->config->item('protocol'));
		$this->email->set_mailtype($this->config->item('mailtype'));
		$this->email->set_smtp_host($this->config->item('smtp_host'));
		$this->email->set_smtp_port($this->config->item('smtp_port'));
		$this->email->set_smtp_user($this->config->item('smtp_user'));
		$this->email->set_smtp_pass($this->config->item('smtp_pass'));
		$this->email->set_newline("\r\n");
		$this->email->initialize();

		$this->email->from($this->config->item('site_email'), $this->config->item('site_name'));
		$this->email->to(strtolower($email));

		$this->email->subject($subject);
		$this->email->message($message);
	   	//$this->email->message( $this->load->view( 'emails/message', $data, true ) );

		return $this->email->send();
		
		//return $this->email->print_debugger();
        //$result = mysql_query("DELETE FROM food_details WHERE food_id='$id'")
		$delete_data = array(
			'customer_id' => $customer_id
		);
		
		return $this->db->delete('customers', $delete_data);
	}

	public function validateCustomer($customer_id) {
		if (!empty($customer_id)) {
			$this->db->from('customers');		
			$this->db->where('customer_id', $customer_id);
		
			$query = $this->db->get();

			if ($query->num_rows() > 0) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
}

/* End of file customers_model.php */
/* Location: ./application/models/customers_model.php */