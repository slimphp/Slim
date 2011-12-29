<?php
class Slim_Stream_File {
    protected $path;
    protected $options;

    public function __construct( $path, $options = array() ) {
        if ( !is_file($path) ) {
            throw new InvalidArgumentException('Cannot stream file. File does not exist.');
        }
        if ( !is_readable($path) ) {
            throw new InvalidArgumentException('Cannot stream file. File is not readable.');
        }
        $this->path = $path;
        $this->options = array_merge(array( 
            'buffer_size' => 8192
        ), $options);
    }

    public function process() {
        $handle = fopen($this->path, 'rb');
        if ( $handle ) {
            while ( feof($handle) === false && connection_status() === 0 ) {
                echo fread($handle, $this->options['buffer_size']);
                flush();
            }
            fclose($handle);
        }
    }
}