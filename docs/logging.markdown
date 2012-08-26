# Logging [logging] #

A Slim application provides a `Slim_Log` object that logs data to a specific output via a log writer.

## How to Log Messages ##

To log messages from a Slim application, get a reference to the `Slim_Log` object like this:

    $log = $app->getLog();

The `Slim_Log` object provides the following public interface:

    $log->debug( mixed $object );
    $log->info( mixed $object );
    $log->warn( mixed $object );
    $log->error( mixed $object );
    $log->fatal( mixed $object );

Each `Slim_Log` method accepts one mixed argument. The argument is usually a string, but the argument can be anything. The argument will be passed to the log writer. It is the log writer's responsibility to log arbitrary input appropriately.

## Log Writers ##

The Slim application's `Slim_Log` instance has a log writer. The log writer is responsible for sending a logged message to the appropriate output (e.g. STDERR, a log file, a remote web service, Twitter, or a database). Out of the box, the Slim application's `Slim_Log` object has a log writer instance of class `Slim_LogFileWriter`; this log writer directs output to the resource handle referenced by the application environment's **slim.errors** key (by default, this is "php://stderr"). You may also define and use a custom log writer.

### How to Use a Custom Log Writer ###

A custom log writer must implement the following public interface:

        public function write( mixed $message );

You must tell the Slim application's `Slim_Log` instance to use your writer. You can do so in your application's settings during instantiation like this:

    $app = new Slim(array(
        'log.writer' => new MyLogWriter()
    ));

You may also set a custom log writer with middleware like this:

    class CustomLogWriterMiddleware extends Slim_Middleware {
        public function call() {
            //Set the new log writer
            $log = $this->app->getLog()->setWriter( new MyLogWriter() );
            
            //Call next middleware
            $this->next->call();
        }
    }

You can set the log writer similarly in an application [hook](#hooks) or [route](#routing-get) callback like this: 

    $app->hook('slim.before', function () use ($app) {
        $app->getLog()->setWriter( new MyLogWriter() );
    });

If you only need to redirect error output to a different resource, I recommend you [update the Environment's **slim.errors** element](#errors-output) instead of writing and entirely new LogWriter.

## How to Enable or Disable Logging ##

The Slim application's `Slim_Log` object provides the following public methods to enable or disable logging during runtime.

    //Enable logging
    $app->getLog()->setEnabled(true);
    
    //Disable logging
    $app->getLog()->setEnabled(false);

You may enable or disable the `Slim_Log` object during application instantiation like this:

    $app = new Slim(array(
        'log.enabled' => true
    ));

If logging is disabled, the `Slim_Log` object will ignore all logged messages until it is enabled.

## Log Levels ##

The Slim application's `Slim_Log` object provides the following public methods to define the _level_ of messages it will log. It also provides a set of _const_ values that you can use instead of the actual number values. When you invoke the `Slim_Log` objects's `debug()`, `info()`, `warn()`, `error()`, or `fatal()` methods, you are inherently assigning a level to the logged message:

DEBUG
:   Level 4

INFO
:   Level 3

WARN
:   Level 2

ERROR
:   Level 1

FATAL
:   Level 0

Only messages that have a level _less than_ the current `Slim_Log` object's level will be logged. For example, if the `Slim_Log` object's level is WARN (2), the `Slim_Log` object will ignore DEBUG and INFO messages but will accept WARN, ERROR, and FATAL messages.

You can set the `Slim_Log` object's level like this:

    $app->getLog()->setLevel(Slim_Log::WARN);

You can set the `Slim_Log` object's level during application instantiation like this:

    $app = new Slim(array(
        'log.level' => Slim_Log::WARN
    ));