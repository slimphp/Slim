# Logging [logging] #

A Slim application provides a Log object that sends messages to a specific output via a LogWriter object.

## Log Messages ##

To log messages from a Slim application, first fetch a reference to the Log object like this:

    $log = $app->getLog();

The Log object provides the following public interface:

    $log->debug();
    $log->info();
    $log->warn();
    $log->error();
    $log->fatal();

Each Log instance method accepts one required argument. The argument is usually a string, but the argument may also be anything that responds to `__toString()`. The argument will be passed to the LogWriter.

The Slim application's Log object will always be available in the application's Environment settings with key **slim.log**. This is helpful if you need to fetch a reference to the Log object in middleware.

## Log Writers ##

Every Log instance has a LogWriter instance. The LogWriter is responsible for sending the logged message to the appropriate output location (e.g. STDERR, a log file, a remote web service, Twitter, or a database). Out of the box, Slim's Log object has an instance of `LogFileWriter` which directs output to the resource referenced by the application Environment's **slim.errors** key. You may also define and use a custom LogWriter.

### Custom Log Writer ###

A custom LogWriter must implement the following public interface:

    class MyLogWriter {
        public function write( mixed $message ) {
            //Do something...
        }
    }

Next, you must tell the Slim application's Log instance to use your writer. I recommend you do this with middleware like this:

    class CustomLogWriterMiddleware {
        protected $app;
        protected $settings;
        public function __construct( $app, $settings = array() ) {
            $this->app = $app;
            $this->settings = $settings;
        }
        public function call( &$env ) {
            $log = $env['slim.log'];
            $log->setWriter( new MyLogWriter() );
            return $this->app->call($env);
        }
    }

However, you may also do so within an application [hook](#hooks) or [route](#routing-get) callback like this: 

    $app->getLog()->setWriter( new MyLogWriter() );

If you only need to redirect error output to a different resource, I recommend you [update the Environment's **slim.errors** element](#errors-output) instead of writing and entirely new LogWriter.

## Enable or Disable Logging ##

The Slim application's Log object also provides the following public methods to enable or disable logging during runtime.

    //Enable logging
    $app->getLog()->setEnabled(true);
    
    //Disable logging
    $app->getLog()->setEnabled(false);

If logging is disabled, the Log will ignore all logged messages until the Log is enabled.

## Log Levels ##

The Slim application's Log object also provides the following public methods to define the level of messages it will log. When you invoke the Log instance's `debug()`, `info()`, `warn()`, `error()`, or `fatal()` methods, you are inherently assigning a level to the logged message:

Debug
:   Level 4

Info
:   Level 3

Warn
:   Level 2

Error
:   Level 1

Fatal
:   Level 0

Only messages that have a level less than the current Log level will be logged. For example, if the Log's level is "2", the Log will ignore debug and info messages but will accept warn, error, and fatal messages.

You can set the Log instance's level like this:

    $app->getLog()->setLevel(2);