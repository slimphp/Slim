<?php
class Slim_Http_Stream {
    /**
     * @var mixed
     */
    protected $streamer;

    /**
     * Constructor
     * @param mixed $streamer
     * @param array $options
     * @return void
     */
    public function __construct( $streamer, $options ) {
        $this->streamer = $streamer;
        $settings = array_merge(array(
            'type' => 'application/octet-stream',
            'filename' => 'foo',
            'disposition' => 'attachment',
            'encoding' => 'binary', //or "ascii"
        ), $options);
    }

    public function write( $body ) {}

    /**
     * Finalize
     * @return array[status, header, body]
     */
    public function finalize() {
        $headers = new Slim_Http_Headers();
        $headers['Content-Type'] = $this->options['type'];
        $headers['Content-Disposition'] = sprintf('%s; filename=%s', $this->options['disposition'], basename($this->options['filename']));
        $headers['Content-Transfer-Encoding'] = $this->options['encoding'];

        return array(200, $headers, $this->streamer);
    }
}