<?php
// HTTP/s	100 000	0,60 USD por millón
/*
	Success—The recipient's ISP accepts your email. If you have set up delivery notifications as described in Using Notifications with Amazon SES, Amazon SES sends you a delivery notification through Amazon Simple Notification Service (Amazon SNS). Otherwise, you will not receive any confirmation about this successful delivery other than the API return value.	

	Bounce—The recipient's ISP rejects your email with an SMTP 550 5.1.1 response code ("Unknown User"). Amazon SES generates a bounce notification and sends it to you via email or by using an Amazon SNS notification, depending on how you set up your system. This mailbox simulator email address will not be placed on the Amazon SES suppression list as one normally would when an email hard bounces. The bounce response that you receive from the mailbox simulator is compliant with RFC 3464. For information about how to receive bounce feedback, see Using Notifications with Amazon SES.	

	Complaint—The recipient's ISP accepts your email and delivers it to the recipient’s inbox. The recipient, however, does not want to receive your message and clicks "Mark as Spam" within an email application that uses an ISP that sends a complaint response to Amazon SES. Amazon SES then forwards the complaint notification to you via email or by using an Amazon SNS notification, depending on how you set up your system. The complaint response that you receive from the mailbox simulator is compliant with RFC 5965. For information about how to receive bounce feedback, see Using Notifications with Amazon SES.	

	Out of the Office—The recipient's ISP accepts your email and delivers it to the recipient’s inbox. The ISP sends an out-of-the-office (OOTO) message to Amazon SES. Amazon SES then forwards the OOTO message to you via email or by using an Amazon SNS notification, depending on how you set up your system. The OOTO response that you receive from the Mailbox Simulator is compliant with RFC 3834. For information about how to set up your system to receive OOTO responses, follow the same instructions for setting up how Amazon SES sends you notifications in Using Notifications with Amazon SES.	

	Address on Suppression List—Amazon SES treats your email as a hard bounce because the address you are sending to is on the Amazon SES suppression list.	
*/

// Fetch the raw POST body containing the message
$postBody = file_get_contents('php://input');

// JSON decode the body to an array of message data
$message = json_decode($postBody, true);

if ($message) {
	$link = mysqli_connect($h, $u, $p) or die(mysqli_error());
	mysqli_select_db($link, $d) or die(mysqli_error());
    // Do something with the data
	$myfile = fopen("sns-log.log", "a") or die("Unable to open file!");
	$txt = $message['Message'];
	fwrite($myfile, "\n". $txt."\n".$postBody);
	fclose($myfile);
	$not = json_decode($message['Message'],true);
	$not["mail"]["timestamp"] = strtotime($not["mail"]["timestamp"]);
	$not["mail"]["timestamp"] = date("Y-m-d H:i:s",$not["mail"]["timestamp"]);
	
	if ($not["notificationType"]=="Delivery"){
		// Entregado Success
		$notificationTimestamp = date("Y-m-d H:i:s",strtotime($not["delivery"]["timestamp"]));
		foreach($not["delivery"]["recipients"] as $value){
			$query = "INSERT INTO sns_log (notificationType,mailTimestamp,mailMessageId,message,recipients,notificationTimestamp) VALUES ('{$not["notificationType"]}','{$not["mail"]["timestamp"]}','{$not["mail"]["messageId"]}','{$message['Message']}','$value','$notificationTimestamp');";
			mysqli_query($link, $query) or die(mysqli_error());
		}
	}elseif ($not["notificationType"]=="Bounce"){
		// Bounce
		$notificationTimestamp = date("Y-m-d H:i:s",strtotime($not["bounce"]["timestamp"]));
		foreach($not["bounce"]["bouncedRecipients"] as $value){
			$query = "INSERT INTO sns_log (notificationType,mailTimestamp,mailMessageId,message,recipients,notificationTimestamp) VALUES ('{$not["notificationType"]}/{$not["bounce"]["bounceType"]}','{$not["mail"]["timestamp"]}','{$not["mail"]["messageId"]}','{$message['Message']}','{$value["emailAddress"]}','$notificationTimestamp');";
			mysqli_query($link, $query) or die(mysqli_error());
		
			// Do something
			if ($not["bounce"]["bounceType"]=="Permanent"){
				// Not exists
			} elseif ($not["bounce"]["bounceType"]=="Transient"){
				// Out of the Office	
			} elseif ($not["bounce"]["bounceType"]=="Suppressed"){
				// Suppressed
			} else {
				// Default Bounce
			}
		}
	}elseif ($not["notificationType"]=="Complaint"){
		//Complaint
		$notificationTimestamp = date("Y-m-d H:i:s",strtotime($not["complaint"]["timestamp"]));
		foreach($not["complaint"]["complainedRecipients"] as $value){
			$query = "INSERT INTO sns_log (notificationType,mailTimestamp,mailMessageId,message,recipients,notificationTimestamp) VALUES ('{$not["notificationType"]}','{$not["mail"]["timestamp"]}','{$not["mail"]["messageId"]}','{$message['Message']}','{$value["emailAddress"]}','$notificationTimestamp');";
			mysqli_query($link, $query) or die(mysqli_error());
		}
	}else{
		$query = "INSERT INTO sns_log (notificationType,mailTimestamp,mailMessageId,message) VALUES ('{$not["notificationType"]}','{$not["mail"]["timestamp"]}','{$not["mail"]["messageId"]}','{$message['Message']}');";
		mysqli_query($link, $query) or die(mysqli_error());
	}
	mysqli_close($link) or die(mysqli_error());
}
?>
