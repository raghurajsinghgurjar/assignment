<?php

require APPPATH . 'libraries/REST_Controller.php';

class Location extends REST_Controller {

  public function __construct() {
      parent::__construct();

      $this->load->model("m_location");
      $this->load->library(array("form_validation"));
      $this->load->helper("security");
  }

  /**
   * Create location
   *
   * @return jsonResponse
   */
  public function create_post()
  {
    try {
      // Collecting form post data
      $postData = $this->input->post();

      // Validation for post data
      $this->form_validation->set_rules('latitude', 'Longitude', 'required|decimal|is_unique[locations.latitude]',
          array('required' => 'You must provide a %s', 'is_unique' => 'This %s already exists')
      );

      $this->form_validation->set_rules('longitude', 'Longitude', 'required|decimal|is_unique[locations.longitude]',
        array('required' => 'You must provide a %s', 'is_unique' => 'This %s already exists')
      );

      // Checking form validation have any error or not
      if ($this->form_validation->run() === false) {
        // We have some errors
        $this->response(array('status' => false, 'message' => validation_errors()) , REST_Controller::HTTP_NOT_FOUND);

      } else {
        // Remove XSS exploits from the post data and returns the cleaned string
        $postData = $this->security->xss_clean($postData);

        // Checking post data
        if (!empty($postData['latitude']) && !empty($postData['longitude'])) {
          if ($this->m_location->add($postData)) {
            $this->response(array('status' => true, 'message' => 'Location added successfully'), REST_Controller::HTTP_OK);
          } else {
            $this->response(array('status' => false, 'message' => 'Location not added'), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
          }
        } else {
          // we have some empty field
          $this->response(array('status' => false, 'message' => 'All fields are required'), REST_Controller::HTTP_NOT_FOUND);
        }
      }
    } catch(Exception $e) {
      $this->response(array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Update location
   *
   * @param int $locationId
   *
   * @return jsonResponse
   */
  public function edit_put($locationId)
  {
    try {
      // Collecting form update data
      $updataData = $this->put();

      // Set put data on form
      $this->form_validation->set_data($updataData);

      // Validation for post data
      $this->form_validation->set_rules('latitude', 'Longitude', 'required|decimal', array('required' => 'You must provide a %s'));
      $this->form_validation->set_rules('longitude', 'Longitude', 'required|decimal', array('required' => 'You must provide a %s'));

      // Checking form validation have any error or not
      if ($this->form_validation->run() === false) {
        // We have some errors
        $this->response(array('status' => 0, 'message' => validation_errors()) , REST_Controller::HTTP_NOT_FOUND);

      } else {
        // Remove XSS exploits from the post data and returns the cleaned string
        $updataData = $this->security->xss_clean($updataData);

        if (!empty($updataData['latitude']) && !empty($updataData['longitude'])) {
          // Set location data
          $locationData = array('latitude' => $updataData['latitude'], 'longitude' => $updataData['longitude']);

          // Get location data
          $locationArray = $this->m_location->getLocationData($locationData);

          // Ignoring update id and checking duplicate latitude entry
          if (!empty($locationArray) && ($locationArray[0]->id !== $locationId) && ($locationArray[0]->latitude === $locationData['latitude'])) {
            $this->response(array('status' => false, 'message' => 'This latitude already exists'), REST_Controller::HTTP_NOT_FOUND);
          }

          // Ignoring update id and checking duplicate longitude entry
          if (!empty($locationArray) && ($locationArray[0]->id !== $locationId) && ($locationArray[0]->longitude === $locationData['longitude'])) {
            $this->response(array('status' => false, 'message' => 'This longitude already exists'), REST_Controller::HTTP_NOT_FOUND);
          }

          if ($this->m_location->edit($locationId, $locationData)) {
            $this->response(array('status' => true, 'message' => 'Location updated successfully'), REST_Controller::HTTP_OK);
          } else {
            $this->response(array('status' => false, 'message' => 'Location not updated'), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
          }

        } else {
          // we have some empty field
          $this->response(array('status' => false, 'message' => 'All fields are required'), REST_Controller::HTTP_NOT_FOUND);
        }
      }
    } catch(Exception $e) {
      $this->response(array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Delete location by locationId
   *
   * @param int $locationId
   *
   * @return jsonResponse
   */
  public function destory_delete(int $locationId)
  {
    try {
      // Get location data
      $locationData = $this->m_location->getLocation($locationId);

      if (empty($locationData)) {
        $this->response(array('status' => false, 'message' => 'Invalid location #'.$locationId), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      }

      // Update record soft delete
      $updateData = array('deleted_at' => date('Y-m-d h:i:s'));

      if ($this->m_location->edit($locationId, $updateData)) {
        $this->response(array('status' => true, 'message' => 'Location deleted successfully'), REST_Controller::HTTP_OK);
      } else {
        $this->response(array('status' => false, 'message' => 'Location not deleted'), REST_Controller::HTTP_NOT_FOUND);
      }
    } catch(Exception $e) {
      $this->response(array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Get all location data
   *
   * @return jsonResponse
   */
  public function index_get()
  {
    try {
      // Get all location data
      $locationData = $this->m_location->getLocationData();

      if (count($locationData) > 0) {

        $this->response(array('status' => true, 'message' => count($locationData) . ' record found', 'data' => $locationData), REST_Controller::HTTP_OK);

      } else {

        $this->response(array('status' => false, 'message' => 'No record found', 'data' => $locationData), REST_Controller::HTTP_NOT_FOUND);

      }
    } catch(Exception $e) {
      $this->response(array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Calculates the distance between two points, given their 
   * latitude and longitude, and returns an array of values 
   * of the most common distance units
   *
   * @param  {coord} $lat1 Latitude of the first point
   * @param  {coord} $lon1 Longitude of the first point
   * @param  {coord} $lat2 Latitude of the second point
   * @param  {coord} $lon2 Longitude of the second point
   * @return {array} Array of values in many distance units
   */
  public function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2) {
      $theta = $lon1 - $lon2;
      $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
      $miles = acos($miles);
      $miles = rad2deg($miles);
      $miles = $miles * 60 * 1.1515;
      $kilometers = $miles * 1.609344;
      return compact('miles', 'kilometers'); 
  }

  /**
   * Calculate distance
   *
   * @return jsonResponse
   */
  public function distance_get()
  {
    try {
      /* These are two points */
      $point1 = array('lat' => 40.770623, 'long' => -73.964367);
      $point2 = array('lat' => 40.758224, 'long' => -73.917404);

      $distance = $this->getDistanceBetweenPoints($point1['lat'], $point1['long'], $point2['lat'], $point2['long']);

      $message = [];

      foreach ($distance as $unit => $value) {
          $message[$unit] =  number_format($value, 4);
      }

      $this->response(array('status' => true, 'message' => 'Distance calculate successfully', 'data' => $message), REST_Controller::HTTP_OK);

    } catch(Exception $e) {
      $this->response(array('status' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
?>