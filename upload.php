<?php

require_once __DIR__ . "/libs/class-uploader.php";

$file   = $argv[1];
$bucket = isset( $argv[2] ) ? $argv[2] : "<DEFAULT_BUCKET>";
$path   = isset( $argv[3] ) ? $argv[3] : "<DEFAULT_UPLOADS>";




if( $file ) {

    $uploader = new Uploader( array(
        "key" => "<AWS_S3_KEY>",
        "secret" => "<AWS_S3_SECRET>"
    ));

    $uploader->set_bucket( $bucket );
    $uploader->set_path( $path );
    $uploader->set_max_files( 5 );

    echo "Uploading $file \n";
    $success = $uploader->upload( $file );

    echo "Cleaning archives \n";
    $uploader->clear_archived();

    $message = "";
    $subject = "";
    if( $success ) {

        // set message to good
        $message = "Successfully archived and uploaded $file";
        $subject = "Successful Backup";
    } else  {
        // set message to bad
        $message = "There was an error uploading the file $file. \r\n";
        $message .= $uploader->error->getMessage();
        $subject = "Error during Backup";
    }

    // Email
    mail( "<ADMIN_EMAIL_ADDRESS>", $subject, $message, "<HEADERS>" );

    return true;
}