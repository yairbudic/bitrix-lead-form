<?php

/**
* Simple PHP Form
*
* Open source automatic PHP form handling module with validation, helpers, warnings and more. 
* Supports text fields, text areas, dropdowns, checkboxes, radio buttons and hidden fields.
* Validation flags supported: required, email, phone, number, lengthmax *, lengthmin *, sizemax *, sizemin *
* 
* See ./examples/basic.php and ./examples/advanced.php and ./examples/centered.php for usage.
*
* @author Nathaniel Sabanski
* @link http://github.com/gnat/simple-php-form
* @license zlib/libpng license
*/
class SimplePHPForm
{
	const STATE_NEW = 0;
	const STATE_SUCCESS = 1;
	const STATE_VALIDATE = 2;
	const STATE_FAIL = 3;
	const STATE_ERROR = 4;
	const STATE_DUPLICATE = 5;
	
	var $state = 0;
	var $input_list = array();

	var $url_action = '';

	var $message_new = 'Registration Form';
	var $message_success = 'Form submitted successfully!';
	var $message_success_2 ='You should receive a confirmation email shortly!';
	var $message_fail = 'Oops! We had trouble accepting your form. Details below.';
	var $message_error = 'You have discovered an internal error. Please contact us!';
	var $message_duplicate = 'You are already registered. If there is an issue, please contact us.';
	
	/**
	* Constructor.
	* @param string $url_action Override re-direct URL.
	*/
	function __construct($url_action = '')
	{
		// Set custom <form> action.
		$this->url_action = $url_action;
	}

	/**
	* Add new field to form.
	* @param string $type Field type.
	* @param string $name Field name which can be refernced internally.
	* @param string $data Default data displayed in field.
	* @param string $data_validation_flags Data entered will be sanitized against this list of tests.
	* @param string $text_title Title of field.
	* @param string $text_help Helper text which is displayed to the User at fill-out time.
	* @param string $text_error Error text which is displayed to the User when validation fails.
	*/
	function Add($type, $name, $data, $data_validation_flags, $text_title, $text_help, $text_error)
	{
		// Set default data.
		$data_default = $data;

		// Get form submission data from $_POST if it exists.
		if(isset($_POST['simplephpform_'.$name]))  
		{
			$data = $_POST['simplephpform_'.$name]; 
			$this->state = self::STATE_VALIDATE; // We've got data during this pass. Form should be validated.
		}

		$this->input_list[$name] = new SimplePHPFormInput($type, $name, $data, $data_validation_flags, $data_default, $text_title, $text_help, $text_error);
	
		// Special logic for checkbox types because browsers simply do not $_POST them if they are unchecked.
		if($type == 'checkbox' && $this->state != self::STATE_NEW)
		{
			if(isset($_POST['simplephpform_'.$name]))
				$this->input_list[$name]->data = true;
			else
				$this->input_list[$name]->data = false;
		}
	}

	/**
	* Display form state to User.
	* @return string Display in HTML format.
	*/
	function DisplayState()
	{
		$output = '';

		if($this->state == self::STATE_NEW) {
			$output = '<div class="simplephpform_state_untouched">'.$this->message_new.'</div>';
		} if($this->state == self::STATE_SUCCESS) {
			$output = '<div class="simplephpform_state_success">'.$this->message_success.'</div> <p>'.$this->message_success_2.'</p> <br />';
		} if($this->state == self::STATE_FAIL) {
			$output = '<div class="simplephpform_state_fail">'.$this->message_fail.'</div>';			
		} if($this->state == self::STATE_ERROR) {
			$output = '<div class="simplephpform_state_fail">'.$this->message_error.'</div>';
		} if($this->state == self::STATE_DUPLICATE) {
			$output = '<div class="simplephpform_state_success">'.$this->message_duplicate.'</div>';
		}

		return $output."\n";
	}

	/**
	* Display form field.
	* @param string Given field name.
	* @return string Display in HTML format.
	*/
	function Display($name = '')
	{
		// No InputEntry specified? Return them all in the order they were defined.
		if($name == '')
		{
			$output = '';

			$output .= $this->DisplayState();
                $output .= '<form id="myForm" method="post" action="'.$this->url_action.'" class="simplephpform">';
			foreach($this->input_list as $input)
				$output .= $this->Display($input->name)."\n";
			$output .= '<input type="submit" value="Submit Form"     class="simplephpform_submit" />';
			$output .= '</form>';

			return $output;
		}

		// Generate output if the specified Form Input exists.
		if(array_key_exists($name, $this->input_list)){
            $output = '';

            $output .= '<div class="simplephpform_title">'.$this->input_list[$name]->text_title.'</div>'."\n";

            $output .= '<label><input type="'.$this->input_list[$name]->type.'" name="simplephpform_'.$this->input_list[$name]->name.'" value="'.$this->input_list[$name]->data.'" />'."\n";

            if($this->input_list[$name]->state == self::STATE_FAIL)
                $output .= '<div class="simplephpform_error">'.$this->input_list[$name]->text_error.'</div>'."\n";
            else if($this->input_list[$name]->text_help != NULL)
                $output .= '<div class="simplephpform_help">'.$this->input_list[$name]->text_help.'</div>'."\n";

            $output .= '</label>';

            $output .= '<div class="simplephpform_clear"></div>';

            return $output;
        }
	}

	/**
	* Reset all form data to defaults.
	*/
	function Reset()
	{
		foreach($this->input_list as $input)
			$input->data = $input->data_default;
	}

	/**
	* Validate form data.
	* @return boolean True = Success. False = Failure.
	*/
	function Validate()
	{
		// Was this form submitted? Or is this page new?
		if($this->state == self::STATE_NEW)
			return false; // Invalid by default.
		
		// Set state as successfull first, then run validation test gauntlet ...
		$this->state = self::STATE_SUCCESS;

		foreach($this->input_list as $input)
		{
			// Set individual input entry state successful at first, then run validation test gauntlet ...
			$input->state = self::STATE_SUCCESS;

			// What validation tests need to be run?
			for($i = 0; $i < count($input->data_validation_flags); $i += 1)
				if(!empty($input->data_validation_flags[$i]))
				{
					// Sanitize flag by stripping whitespace, and making lowercase.
					$flag = strtolower(trim($input->data_validation_flags[$i])); 
					
					// *** If we have a test for this flag, run it! ***
					
					// Test: Is the entry required?
					if($flag == 'required')
						if(!$this->ValidateExists($input->data)){
                            $input->state = self::STATE_FAIL;
                            $input->text_error = $input->text_title . " is required";
                            break;
                        }

							
					// Test: Is the entry an email?
					if($flag == 'email')
						if(!$this->ValidateEmail($input->data))
							$input->state = self::STATE_FAIL;
							
					// Test: Is the entry a phone number?
					if($flag == 'phone')
						if(!$this->ValidatePhone($input->data))
							$input->state = self::STATE_FAIL;

					// Test: Is the entry a number?
					if($flag == 'number')
						if(!$this->ValidateNumber($input->data))
							$input->state = self::STATE_FAIL;

                    // Test: Is the entry a number?
                        if($flag == 'name')
                            if(!$this->ValidateName($input->data))
                                $input->state = self::STATE_FAIL;
				}
		}
		
		// Did ALL individual input entries validate successfully? If no, set form state to fail.
		foreach($this->input_list as $input)
			if($input->state == self::STATE_FAIL)
				$this->state = self::STATE_FAIL;
				
		// No input entries? Also fail.
		if(count($this->input_list) < 1)
			$this->state = self::STATE_FAIL;
	
		if($this->state == self::STATE_SUCCESS)
			return true;
		else
			return false;
	}

	/**
	* Validation test. Does the data exist?
	* @param string Data.
	* @return boolean True = Yes. False = No.
	*/
	function ValidateExists($data)
	{
		if($data != '')
			return true;
		else
			return false;
	}

	/**
	* Validation test. Valid email?
	* @param string Data.
	* @return boolean True = Yes. False = No.
	*/
	function ValidateEmail($data)
	{
		if(strlen($data) < 5 || strpos($data, '@') == false || strpos($data, '.') == false || stripos($data, ' ') != false)
			return false;
		else
			return true;
	}

	/**
	* Validation test. Valid phone number?
	* @param string Data.
	* @return boolean True = Yes. False = No.
	*/
	function ValidatePhone($data)
	{
		if(!is_numeric($data) || strlen($data) < 10 || strlen($data) > 20)
			return false;
		else
			return true;
	}

	/**
	* Validation test. Valid number?
	* @param string Data.
	* @return boolean True = Yes. False = No.
	*/
	function ValidateNumber($data)
	{
		if(is_numeric($data))
			return true;
		else
			return false;
	}

	/**
	* Validation test. Valid number?
	* @param string Data.
	* @return boolean True = Yes. False = No.
	*/
	function ValidateName($data)
	{
		if(ctype_alpha($data))
			return true;
		else
			return false;
	}

}

/**
* Used internally by SimplePHPForm to hold field data.
*/
class SimplePHPFormInput
{
	var $type = 'text';
	var $name = NULL;
	var $data = '';
	var $data_default = '';
	var $data_validation_flags = array();
	var $state = SimplePHPForm::STATE_NEW;
	
	var $text_title = '';
	var $text_help = '';
	var $text_error = '';

	function __construct($type, $name, $data, $data_validation_flags, $data_default, $text_title, $text_help, $text_error)
	{
		$this->type = $type;
		$this->name = $name;
		$this->data = $data;
		$this->data_default = $data_default;
		$this->data_validation_flags = $data_validation_flags;
		
		$this->text_title = $text_title;
		$this->text_help = $text_help;
		$this->text_error = $text_error;

	}
}

