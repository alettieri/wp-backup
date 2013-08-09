<?php

require __DIR__ . "/sdk/aws/aws-autoloader.php";

use Aws\S3\S3Client as S3Client;
use Aws\Common\Enum\Size;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;


class Uploader {

    private static $extensions = array(
        "gz",
        "zip",
        "tar",
        "bz2"
    );

    private $client = null;
    
    private $bucket = "";

    private $path = "";

    public $max_files = 3;

    public $error = "";

    function __construct( $keys ) {
       date_default_timezone_set('America/Los_Angeles');
       
       $this->client = S3Client::factory( $keys );

       return $this;
    }


    public function set_max_files( $count ) {
        $this->max_files = $count;
        return $this;
    }

    public function set_bucket( $bucket ) {
        $this->bucket = $bucket;
        return $this;
    }

    public function set_path( $path ) {
        $this->path = $path;
        return $this;
    }

    function clear_archived() {

        $deleted = false;
        $this->error = "";
            
        if( ! $this->max_files || $this->max_files < 0 ) {
            return $deleted;
        }

        $files = $this->client->getIterator('ListObjects', array( 'Bucket' => $this->bucket, 'Prefix' => $this->path ) );

        $path_files = array();
        $files_to_delete = array();
        
        foreach( $files as $file ) {

            $the_file = $file["Key"];
            $ext = pathinfo($the_file, PATHINFO_EXTENSION);
            $time = strtotime( $file[ 'LastModified' ] ) + ( date('Z') * 3600 );
            
            if( $ext && in_array( $ext, self::$extensions ) ) {
                $path_files[$time] = $file;
            }

        }
        if( $this->max_files > 0 && ( count( $path_files ) > $this->max_files ) ) {
            
            // Sort by time
            ksort( $path_files );

            while( $file = array_shift( $path_files ) ) {
               
               if( count( $path_files ) < $this->max_files ) { 
                 break;
               }
               
               $files_to_delete[] = $file;
               
            }
        

            if( !empty( $files_to_delete ) ) {
                // Clear All Objects
                $args = array(
                    "Bucket" => $this->bucket,
                    "Objects" => $files_to_delete
                );

                // Delete Objects
                $deleted = $this->client->deleteObjects( $args );

            }

        }

        return $deleted;        

    }


    public function upload( $file, $dispatch_cb ) {
        
        $path = pathinfo($this->path . "/" . $file);            
        $this->error = "";

        $uploader = UploadBuilder::newInstance()
            ->setClient($this->client)
            ->setSource($file)
            ->setBucket($this->bucket)
            ->setKey( $path["dirname"] . "/" . $path["basename"] )
            ->build();

        try {
            
            if( $dispatch_cb ) {
                $uploader->getEventDispatcher()->addListener( $uploader::AFTER_PART_UPLOAD, $dispatch_cb );
            }

            $uploader->upload();
            return true;

        } catch( MultipartUploadException $e ) {
            $uploader->abort();
            $this->error = $e;
            return false;
        }

    }




}