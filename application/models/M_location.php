<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class M_location extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

        // Load database library
        $this->load->database();

        // Database table name
        $this->tbl_name = 'locations';
    }

    /**
     * Get location by id
     *
     * @param int $id
     *
     * @return stdClass
     */
    function getLocation(int $id)
    {
        return $this->db->where('deleted_at', NULL)->get_where($this->tbl_name, array('id' => $id))->row();
    }

    /**
     * Get all location
     *
     * @param array $filterData
     *
     * @return array
     */
    function getLocationData(array $filterData = [])
    {
        // Checking apply fliter
        foreach ($filterData as $filterKey => $filterValue) {
            $this->db->or_where($filterKey, $filterValue);
        }

        // Ignoring soft delete data
        $this->db->where('deleted_at', NULL);

        return $this->db->get($this->tbl_name)->result();
    }

    /**
     * Create location
     *
     * @param array $data
     *
     * @return bool
     */
    public function add($data)
    {
        return $this->db->insert($this->tbl_name, $data);
    }

    /**
     * Edit location
     *
     * @param int $id
     * @param array $data
     *
     * @return bool
     */
    public function edit(int $id, array $data)
    {
        return $this->db->update($this->tbl_name, $data, array('id' => $id));
    }
}