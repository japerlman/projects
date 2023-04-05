<?php 
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<style>
body{
	background: #bbb;
	color: #333;
}

table {
	width: 95%;
	margin: auto;
}

th{
	font-size: 140%;
	border-bottom: 1px solid black;
	text-align: center;
}

td{
	font-size: 120%;
	font-weight: bold;

}

.row1{
	background: #ccc;
}

.row0{
	background: #bbb;
}

tr:hover {
  background-color: lightyellow;
}

</style>
</head>
<?php

/* Take access code and turn it into an access token */

if(!isset($_SESSION['token'])){ //Check if we have a valid authentication token in the session, if not request one.

	$ch = curl_init ();
	$timeout = 0; // 100; // set to zero for no timeout
	$url = "https://psa.3rdelementconsulting.com/auth/token?tenant=3rdelementconsulting";
	$data=	"&grant_type=authorization_code".
			"&client_id=824785ce-b618-4470-8dfc-3bf2facca322".
			"&redirect_uri=https://projects.3rdelementconsulting.com".
			"&code=".$_GET['code'].
			"&scope=all";
		
	curl_setopt ($ch, CURLOPT_URL, $url );
	curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/x-www-form-urlencoded",
		));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	//curl_setopt($ch, CURLOPT_STDERR, fopen('c:/dev/var/log/curl.log', 'a+')); // a+ to append...
	$token = curl_exec ( $ch );

	if (curl_errno ( $ch )) {
		echo curl_error ( $ch );
		curl_close ( $ch );
		exit ();
	}

	curl_close ( $ch );
	$token = json_decode($token);
	$_SESSION['token'] = $token->{"access_token"};

	
}

/* Access token now acquired */

$ch2 = curl_init ();
$timeout = 0; // 100; // set to zero for no timeout
if(!$_GET['pid'])
	$url2 = "https://psa.3rdelementconsulting.com/api/Projects/?list_id=49&order=client_name&requesttype=5";
else
	$url2 = "https://psa.3rdelementconsulting.com/api/Projects/".$_GET['pid']."?list_id=49&requesttype=5&includechildids=true&orderdesc=true&order=summary";
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

$httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

	if (curl_errno ( $ch2 )) {
		echo curl_error ( $ch2 );
		curl_close ( $ch2 );
		exit ();
	}

	if($httpcode == '401'){
		session_destroy();
	}

curl_close ( $ch2 );
$projects = json_decode($projectsJson, true);


?>

<!-- Build list of agents and their IDs -->
<?php if(!isset($_SESSION['agents'])) : ?>
<?php	$_SESSION['agents'] = aquireAgentIds(); ?>
<?php endif; ?>
<!-- Build list of ticket statuses and their IDs -->
<?php if(!isset($_SESSION['statusids'])) : ?>
<?php	$_SESSION['statusids'] = aquireStatusIds(); ?>
<?php $_SESSION['sids'] = aquireStatusIds(); ?>
<?php endif; ?>

<?php if(!$_GET['pid']) : ?>
<?php $x=0; ?>
<table>
	<tr><th>Client Name</td><th>Project</td><th>Agent</td><th>Target Date</td></tr>
	<?php foreach($projects['tickets'] as $project) : ?>
		<?php $agentName = getAgentName($project['agent_id']); ?>
		<tr class='row<?php echo $x%2; ?>'><td style='padding-left: 20px;'><?= $project['client_name'];?></td><td><?php echo '<a href=?pid='.$project['id'].'>' . $project['summary']; ?></a></td>
			<td><?= getAgentName($project['agent_id']);  ?></td>
			<td><?= substr($project['targetdate'],0,10);?></td>
			<td><?php echo '<a href=https://psa.3rdelementconsulting.com/ticket?id='.$project['id'].'&showmenu=false target="_blank">'; ?>View in HALOPSA</a></td>

		</tr>

	<tr></tr><tr></tr><tr></tr>	
	<?php $x++; ?>
	<?php endforeach ?>
	
</table>
<?php endif; ?>

<?php if($_GET['pid']) : ?>
<table>
	<tr><th>Client Name</td><th>Project</th><th>Agent</th><th>Target Date</th><th>Status</th></tr>
		<?php $agentName = getAgentName($projects['agent_id']); ?>
		<tr><td><?= $projects['client_name'];?></td><td><?php echo '<a href=https://psa.3rdelementconsulting.com/ticket?id='.$projects['id'].'&showmenu=false target="_blank">' . $projects['summary']; ?></a></td>
			<td><?= getAgentName($projects['agent_id']);  ?></td>
			<td><?= substr($projects['targetdate'],0,10);?></td>
			<td style="background-color: <?=$_SESSION['sids'][$projects['status_id']]['color'];?>"><?= $_SESSION['sids'][$projects['status_id']]['name'];?> </td>
		</tr>	
	<?php foreach ($projects['child_ticket_ids'] as $child) :?>
		<?php $childDetails = getChild($child);?>
		<?php $agentChild = getAgentName($childDetails['agent_id']) ?>
		<tr><td>&nbsp;</td><td style='padding-left: 30px;'><?php echo '<a href=https://psa.3rdelementconsulting.com/ticket?id='.$childDetails['id'].'&showmenu=false target="_blank">' . $childDetails['id'] . ' - ' .$childDetails['summary'];?></td>
		<td><?= getAgentName($childDetails['agent_id']); ?></td>
		<td><?= substr($childDetails['targetdate'],0,10);?></td>
		<td style="background-color: <?=$_SESSION['sids'][$childDetails['status_id']]['color'];?>"><?= $_SESSION['sids'][$childDetails['status_id']]['name'];?> </td>
		</tr>

		<?php foreach ($childDetails['child_ticket_ids'] as $child2) :?>
			<?php $childDetails2 = getChild($child2);?>
			<?php $agentChild2 = getAgentName($childDetails2['agent_id']) ?>
			<tr><td>&nbsp;</td><td style='padding-left: 60px;'><?php echo '<a href=https://psa.3rdelementconsulting.com/ticket?id='.$childDetails2['id'].'&showmenu=false target="_blank">' . $childDetails2['id'] . ' - ' . $childDetails2['summary'];?></td>
				<td><?= getAgentName($childDetails2['agent_id']); ?></td>
				<td><?= substr($childDetails2['targetdate'],0,10);?></td>
				<td style="background-color: <?=$_SESSION['sids'][$childDetails2['status_id']]['color'];?>"><?= $_SESSION['sids'][$childDetails2['status_id']]['name'];?> </td>
			</tr>
		<?php endforeach; ?>
	<?php endforeach; ?>
<?php endif; ?>

<?php 

function getChild($id = null){
	
$ch3 = curl_init ();
$timeout = 0; // 100; // set to zero for no timeout
$url3 = "https://psa.3rdelementconsulting.com/api/Projects/".$id."?includedetails=true&includechildids=true&order=id&orderdesc=false";
$authorization = "Authorization: Bearer ".$_SESSION['token'];
	
curl_setopt ($ch3, CURLOPT_URL, $url3 );
curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "GET"); 
curl_setopt($ch3, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
curl_setopt ($ch3, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ($ch3, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt ($ch3, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch3, CURLOPT_CONNECTTIMEOUT, $timeout );
curl_setopt($ch3, CURLOPT_VERBOSE, true);
curl_setopt($ch3, CURLOPT_STDERR, fopen('c:/dev/var/log/curl3.log', 'w')); // a+ to append...
$childJson = curl_exec ( $ch3 );

if (curl_errno ( $ch3 )) {
    echo curl_error ( $ch3 );
    curl_close ( $ch3 );
    exit ();
}

curl_close ( $ch3 );
$children = json_decode($childJson, true);
	
return $children;
}

function getAgentName($id = null){

	$agentName = $_SESSION['agents'][$id];

	return $agentName;	
	
	
}

function aquireAgentIds(){
	
$ch4 = curl_init ();
$timeout = 0; // 100; // set to zero for no timeout
$url4 = "https://psa.3rdelementconsulting.com/api/Agent/";
$authorization = "Authorization: Bearer ".$_SESSION['token'];
	
curl_setopt ($ch4, CURLOPT_URL, $url4 );
curl_setopt($ch4, CURLOPT_CUSTOMREQUEST, "GET"); 
curl_setopt($ch4, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
curl_setopt ($ch4, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ($ch4, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt ($ch4, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch4, CURLOPT_CONNECTTIMEOUT, $timeout );
curl_setopt($ch4, CURLOPT_VERBOSE, true);
curl_setopt($ch4, CURLOPT_STDERR, fopen('c:/dev/var/log/curl4.log', 'w')); // a+ to append...
$agentsJson = curl_exec ( $ch4 );

if (curl_errno ( $ch4 )) {
    echo curl_error ( $ch4 );
    curl_close ( $ch4 );
    exit ();
}

curl_close ( $ch4 );

$agents = json_decode($agentsJson,true);
$agentArray = array();
	foreach ( $agents as $agent ){
	
		$name = explode( '"', $agent['name']);
		$agentArray[$agent['id']] = $name[0];
		
	}


return $agentArray;	

}

function aquireStatusIds(){
	
$ch5 = curl_init ();
$timeout = 0; // 100; // set to zero for no timeout
$url5 = "https://psa.3rdelementconsulting.com/api/Status/";
$authorization = "Authorization: Bearer ".$_SESSION['token'];
	
curl_setopt ($ch5, CURLOPT_URL, $url5 );
curl_setopt($ch5, CURLOPT_CUSTOMREQUEST, "GET"); 
curl_setopt($ch5, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
curl_setopt ($ch5, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt ($ch5, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt ($ch5, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch5, CURLOPT_CONNECTTIMEOUT, $timeout );
curl_setopt($ch5, CURLOPT_VERBOSE, true);
curl_setopt($ch5, CURLOPT_STDERR, fopen('c:/dev/var/log/curl4.log', 'w')); // a+ to append...
$statusidsJson = curl_exec ( $ch5 );

if (curl_errno ( $ch5 )) {
    echo curl_error ( $ch5 );
    curl_close ( $ch5 );
    exit ();
}

curl_close ( $ch5 );

$statusids = json_decode($statusidsJson,true);

$statusArray = array();
	foreach ( $statusids as $sid ){
	
		$statusArray[$sid['id']]['name'] = $sid['name'];
		$statusArray[$sid['id']]['color'] = $sid['colour'];
	}


return $statusArray;	

}

?>