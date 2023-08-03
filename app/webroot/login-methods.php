<?php
/**
 * This is a helper script for Commons User Self-Service Dashboards. It outputs
 * a simple HTML page listing a user's login methods. It is intended to be
 * called from a dashboard iframe.
 *
 * The script requires the user's co_person id to be passed as a GET parameter.
 * It also requires the uid to be set in the $_SERVER superglobal.
 *
 * The script ensures that it is not leaking private data by checking that the
 * uid matches one of the the user's identifiers in the database.
 */

const DB_HOST            = 'hcommons-dev-registry.cfphjclhp7g9.us-east-1.rds.amazonaws.com';
const DB_USER            = 'rdsroot';
const DB_PASSWORD        = 'MSUnihc2020';
const DB_NAME            = 'registry';

define( 'IDENTIFIER_METHOD_MAP',
	[
		'commons.mla.org' => 'Google',
		'orcid-gateway.hcommons-dev.org' => 'Orcid',
		'dev.mla.org' => 'MLA',
        'msu.edu' => 'MSU',
	]
);

function get_login_methods() {
	if ( ! isset( $_SERVER['uid'] ) ) {
		die( 'uid must be set' );
	}
	$uid = $_SERVER['uid'];

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		die( 'id must be set and numeric' );
	}
	$id = intval( $_GET['id'] );
	
	$db_connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	if ( ! $db_connection) {
		echo "Could not connect to database.\n";
		die('Could not connect: ' . mysqli_error($db_connection) . "\n");
	}

	$sql =
		"
		SELECT i.identifier
		FROM cm_identifiers AS i
		JOIN cm_co_org_identity_links AS o
		ON i.org_identity_id = o.org_identity_id
		WHERE o.co_person_id = $id
		AND i.deleted = false
		AND o.deleted = false
		";

	$result = mysqli_query($db_connection, $sql);

	$login_methods = [];

	$same_user = false;

	foreach( $result as $row ) {
		$identifier = $row['identifier'];
		if ( $identifier === $uid ) {
			$same_user = true;
		}
		$identifier_domain = substr( $identifier, strpos( $identifier, '@' ) + 1 );
		$identifier_method = IDENTIFIER_METHOD_MAP[ $identifier_domain ];
		if ( $identifier_method && ! in_array( $identifier_method, $login_methods ) ) {
			$login_methods[] = $identifier_method;
		}
	}

	if ( ! $same_user ) {
		echo ""; // Avoid leaking information about other users.
		return;
	}

	echo "Existing login methods: ";
	echo implode( ', ', $login_methods );
}

?>
<html>
	<head>
		<style>
			body {
				font-family: 'proxima-nova', Verdana, sans-serif;
				font-size: 12px;
				font-weight: 400;
				line-height: 1.5;
				margin: 0;
				overflow: hidden;
			}
		</style>
	</head>
	<body>
		<p>
			<?php get_login_methods(); ?>
		</p>
	</body>
</html>
