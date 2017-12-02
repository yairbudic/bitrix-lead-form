<?php 
	// Simple PHP Form - Centered Example. 
	// Advanced example with center-aligned form.
	require('SimplePHPForm.php');

	// Create new SimplePHPForm with custom action URL.
	$form = new SimplePHPForm('index.php');

	// Add text inputs. (input type, name/id, default data, validation flags, label, helper message, validation warning message).
	$form->Add('text', 'firstName', '', array('required', 'name'), 'First Name', '', 'First name must contain letters only');
	$form->Add('text', 'lastName', '', array('required', 'name'), 'Last Name', '', 'Last name must contain letters only');
	$form->Add('text', 'email', '', array('required', 'email'), 'Email', '', 'Email address must be valid');
	$form->Add('text', 'phone', '', array('required', 'phone'), 'Phone Number', '', 'Your phone number must contain at least 10 numbers');


	// Did the form validate successfully?
	if($form->Validate())
	{
		// Place successful form submission code here ... (Send an email, register in a database, whatever ...)
        $url = 'https://b24-xlq9lp.bitrix24.com/crm/configs/import/lead.php';
        $data = array(
            'LOGIN' => 'ybudic@gmail.com',
            'PASSWORD' => '12345678',
            'TITLE' => $form->input_list['email']->data,
            'NAME' => $form->input_list['firstName']->data,
            'LAST_NAME' => $form->input_list['lastName']->data,
            'PHONE_WORK' => $form->input_list['phone']->data,
            'EMAIL_WORK' => $form->input_list['email']->data,
        );

// use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode(str_replace( "'","\"", $result));
        if ($result === FALSE || $result->error!=201) {
            $form->state = SimplePHPForm::STATE_ERROR;
        } else{
            // Finally, reset the form, clearing it to the default state.
            $form->message_success_2 = "Name - " . $form->input_list['firstName']->data . " " . $form->input_list['lastName']->data .
                ", Email - " . $form->input_list['email']->data .
                ", Phone - " . $form->input_list['phone']->data;
            $form->Reset();
        }
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Bitrix lead form</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <link rel="stylesheet" type="text/css" media="screen" href="css/simplephpform_default.css" />
		<link rel="stylesheet" type="text/css" media="screen" href="css/simplephpform_center.css" />
    </head>
	<body>
        <div class="simplephpform_wrapper">
			<?php echo $form->Display(); ?>
		</div>
        <script language="javascript">
            $('#myForm').submit(function(){
                $(this).find(':input[type=submit]').prop('disabled', true);
            });
        </script>
    </body>
</html>
