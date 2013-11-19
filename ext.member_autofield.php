<?php

/*
=====================================================
 Member Autofield
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2013 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.member_autofield.php
-----------------------------------------------------
 Purpose: Fill in member's custom field when he is registered or logged in
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}


class Member_autofield_ext {

	var $name	     	= "Member Autofield";
	var $version 		= 0.1;
	var $description	= "Send automatic response when Freeform submission is moderatedFill in member's custom field when he is registered or logged in";
	var $settings_exist	= 'y';
	var $docs_url		= 'http://githib.com/intoeetive/member_autofield/README';
    
    var $settings 		= array();
    
    var $patterns       = array(
        ''                  => '',
        'membership_id'     => 'membership_id'
    );
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
			array(
    			'hook'		=> 'member_member_register',
    			'method'	=> 'insert',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'member_member_login_single',
    			'method'	=> 'check_and_insert',
    			'priority'	=> 10
    		),
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
        
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    
    function settings()
    {
        $settings = array();
        
        $custom_fields = array();
        $custom_fields[''] = '';
        $this->EE->db->select('m_field_name');
        $this->EE->db->order_by('m_field_order', 'asc');
        $q = $this->EE->db->get('exp_member_fields');
        foreach ($q->result() as $obj)
        {
            $settings[$obj->m_field_name]    = array('s', $this->patterns, '');
        }        

        return $settings;
    }
    
    
    function _do_insert($member_id, $field_name, $pattern)
    {
        $this->EE->db->select('m_field_id');
        $this->EE->db->where('m_field_name', $field_name);
        $q = $this->EE->db->get('exp_member_fields');
        if ($q->num_rows()==0) return;
        $field_name = 'm_field_id_'.$q->row('m_field_id');
        
        $q = $this->EE->db->select($field_name)
            ->from('member_data')
            ->where('member_id', $member_id)
            ->get();

        if ($q->num_rows()==0) return;

        if ($q->row($field_name)!='') return;
        
        $val = $this->$pattern($member_id);
        $data = array("$field_name" => "$val");
        
        $this->EE->db->where('member_id', $member_id);
        $this->EE->db->update('member_data', $data);
    }
    
    
    function insert($data, $member_id)
    {
        foreach ($this->settings as $field_name=>$pattern)
        {
            if ($pattern!='')
            {
                $this->_do_insert($member_id, $field_name, $pattern);
            }
        }
    }
    
    
    function check_and_insert()
    {
        foreach ($this->settings as $field_name=>$pattern)
        {
            if ($pattern!='')
            {
                $this->_do_insert($this->EE->session->userdata('member_id'), $field_name, $pattern);
            }
        }
    }
    
    
    
    function membership_id($member_id)
    {
        //member_id-sex-yearmonth
        
        $q = $this->EE->db->select('m_field_id_3, join_date')
                ->from('members')
                ->join('member_data', 'members.member_id=member_data.member_id', 'left')
                ->where('members.member_id', $member_id)
                ->get();
        if ($q->num_rows()==0) return '';
        
        $sex = ($q->row('m_field_id_3')!='')?$q->row('m_field_id_3'):'n';
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$y = $this->EE->localize->format_date('%Y', $q->row('join_date'));
        }
        else
        {
        	$y = $this->EE->localize->decode_date('%Y', $q->row('join_date'));
        }
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$m = $this->EE->localize->format_date('%m', $q->row('join_date'));
        }
        else
        {
        	$m = $this->EE->localize->decode_date('%m', $q->row('join_date'));
        }
        
        $val = $y.'/'.$m.'/'.$sex.'/'.$member_id;
        
        
        return $val;
        
    }

 
    
    
  

}
// END CLASS
