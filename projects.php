<?php
session_start();
?>
<!DOCTYPE html>
<?php
/* Begin API pull of actual project data */

$ch2 = curl_init ();
$timeout = 0; // 100; // set to zero for no timeout
$url2 = "https://psa.3rdelementconsulting.com/api/Projects?list_id=49&order=client_name";
$authorization = "Authorization: Bearer ".$_SESSION['token'];
	
curl_setopt ($ch2, CURLOPT_URL, $url2 );
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET"); 
curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
curl_setopt ($ch2, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ($ch2, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt ($ch2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch2, CURLOPT_CONNECTTIMEOUT, $timeout );
curl_setopt($ch2, CURLOPT_VERBOSE, true);
curl_setopt($ch2, CURLOPT_STDERR, fopen('c:/dev/var/log/curl2.log', 'w')); // a+ to append...
$projectsJson = curl_exec ( $ch2 );

if (curl_errno ( $ch2 )) {
    echo curl_error ( $ch2 );
    curl_close ( $ch2 );
    exit ();
}

curl_close ( $ch2 );
$projects = json_decode($projectsJson, true);



	foreach($projects['tickets'] as $project){
	
		echo "Client Name: " . $project['client_name'];
		
		
		
	}
?>