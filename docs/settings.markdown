# Settings [settings]

There are two ways to apply settings to the Slim application.  First during Slim application instantiation and second after instantiation.  All settings can be applied at instatiation time by passing Slim's constructor an associative array.  All settings can be retrieved and modified after instantiation, however some of them can not be done simply by using the `config` application instance method but will be demonstrated as necessary below. Before I list the available settings, I want to quickly explain how you may define and inspect settings with your Slim application.

## Settings during Instantiation [settings-instantiation]

To define settings upon instantiation, pass an associative array into the Slim constructor:

    $app = new Slim(array(
        'debug' => true,
        'templates.path' => '../templates',
        'log.level' => Slim_Log::DEBUG
    ));

All settings may be defined using this method.

## Settings after Instantiation [settings-afterinstantiation]

To define settings after instantiation, the majority can use the `config` application instance method; the first argument is the setting name and the second argument is the setting value.

    $app->config('debug', false);

You may also define multiple settings at once using an associative array:

    $app->config(array(
        'debug' => true,
        'templates.path' => '../templates'
    ));

To retrieve the value of a setting, you also use the `config` application instance method; however, you only pass one argument - the name of the setting you wish to inspect. If the setting you request does not exist, `NULL` is returned.

    $settingValue = $app->config('templates.path'); //returns "../templates"

*Please see each specific setting below to determine if the `config` method is applicable for reading/writing the setting.*

You are not limited to the settings shown below; you may also define your own.

## mode [settings-mode] ##

Declare the Slim application mode (e.g. "test", "development", "production") upon instantiation. However, this may be anything you want.  The mode is determined during application creation (constructor) and cached there after so attempting to update it via `config()` once Slim is created has no effect when using the `getMode()` and `configureMode()` Slim instance methods.  You can read more about all of the ways the [mode is determined](#what-is-a-mode).

Data Type
:   string

Default
:   development

## debug [settings-debug]

Enable or disable application debugging. If true, Slim will display debugging information for errors and exceptions. If false, Slim will instead invoke the default or custom error handler, passing the exception into the handler as the first and only argument.

Data Type
:   boolean

Default
:   true

## log.writer [settings-log-writer] ##

This sets the application log writer *upon instantiation*. This is optional. By default the application log will write logged messages to STDERR. If you do specify a custom log writer here, it must be an object that implements a `write()` public instance method that accepts a mixed argument; the `write()` method is responsible for sending the logged object to the appropriate output.

To read and write this setting after instantiation you need to access the log directly and use the `getWriter` and `setWriter` functions.

    $log = $app->getLog();
    $logWriter = $log->getWriter();
    $log->setWriter(new MyLogWriter());
    

Data Type
:   mixed

Default
:   Slim_LogFileWriter

## log.level [settings-log-level] ##

This sets the application log level *upon instantiation* to determine which messages are logged.

To read and write this setting after instantiation you need to access the log directly and use the `getLevel` and `setLevel` functions.

    $log = $app->getLog();
    $level = $log->getLevel();
    $log->setLevel(Slim_Log::WARN);

Data Type
:   int

Default
:   Slim_Log::DEBUG (4)

## log.enabled [settings-log-enabled] ##

This enables or disables the application log *upon instantiation*.

To read and write this setting after instantiation you need to access the log directly and use the `getEnabled` and `setEnabled` functions.

    $log = $app->getLog();
    $enabled = $log->getEnabled();
    $log->setEnabled(true);

Data Type
:   boolean

Default
:   true

## templates.path [settings-templates-path]

The relative or absolute filesystem path to template files directory. This is referenced by the application View to fetch and render templates.

Data Type
:   string

Default
:   ./templates

## view [settings-view]

Determines the View class used by the Slim application.

Data Type
:   If string, the name of the custom View class;
    If object, a subclass of `Slim_View`;

Default
:   Slim_View

## cookies.lifetime [settings-cookies-lifetime]

Determines the lifetime of HTTP cookies created by the Slim application.

Data Type
:   If integer, a valid UNIX timestamp at which the cookie expires;
    If string, a description parsed by `strtotime` to extrapolate a valid UNIX timestamp.

Default
:   20 minutes

## cookies.path [settings-cookies-path]

Determines the default HTTP cookie path if none specified when invoking the Slim::setCookie or Slim::setEncryptedCookie application instance methods.

Data Type
:   string

Default
:   /

## cookies.domain [settings-cookies-domain]

Determines the default HTTP cookie domain if none specified when invoking the Slim::setCookie or Slim::setEncryptedCookie application instance methods.

Data Type
:   string

Default
:   null

## cookies.secure [settings-cookies-secure]

Should the Slim application transfer HTTP cookies over SSL/HTTPS only?

Data Type
:   boolean

Default
:   false

## cookies.httponly [settings-cookies-httponly]

Should the Slim application transfer HTTP cookies using the HTTP protocol only?

Data Type
:   boolean

Default
:   false

## cookies.secret_key [settings-cookies-secret-key]

The secret key used for HTTP cookie encryption. This field is required if you use encrypted HTTP cookies in your Slim application.

Data Type
:   string

Default
:   CHANGE_ME

## cookies.cipher [settings-cookies-cipher]

The mcrypt cipher used for HTTP cookie encryption. You can see a list of available ciphers at http://php.net/manual/en/mcrypt.ciphers.php.

Data Type
:   PHP constant (see URL above)

Default
:   MCRYPT_RIJNDAEL_256

## cookies.cipher_mode [settings-cookies-cipher-mode]

The mcrypt cipher mode used for HTTP cookie encryption. You can see a list of available cipher modes at http://php.net/manual/en/mcrypt.ciphers.php.

Data Type
:   PHP constant (see URL above)

Default
:   MCRYPT_MODE_CBC

## http.version [settings-http-version]

By default, Slim returns an HTTP/1.1 response to the client. Use this setting if you need to return an HTTP/1.0 response. This is useful if you use PHPFog or an nginx server configuration where you communicate with backend proxies rather than directly with the HTTP client.

Data Type
:   string

Default
:   1.1

Possible Values
:   "1.1" or "1.0"