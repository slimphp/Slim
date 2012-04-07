# Settings [settings]

Slim's constructor accepts an associative array of settings to customize the Slim application during instantiation. These settings may be retrieved or changed during runtime as demonstrated below. Before I list the available settings, I want to quickly explain how you may define and inspect settings with your Slim application.

To define settings upon instantiation, pass an associative array into the Slim constructor:

    $app = new Slim(array(
        'debug' => true,
        'templates.path' => '../templates'
    ));

To define settings after instantiation, use the `config` application instance method; the first argument is the setting name and the second argument is the setting value.

    $app->config('debug', false);

You may also define multiple settings at once using an associative array:

    $app->config(array(
        'debug' => true,
        'templates.path' => '../templates'
    ));

To retrieve the value of a setting, you also use the `config` application instance method; however, you only pass one argument — the name of the setting you wish to inspect. If the setting you request does not exist, `NULL` is returned.

    $settingValue = $app->config('templates.path'); //returns "../templates"

You are not limited to the settings shown below; you may also define your own.

## mode [settings-mode] ##

Declare the Slim application mode (e.g. "test", "development", "production"). However, this may be anything you want.

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

This sets the application log writer upon instantiation. This is optional. By default the application log will write logged messages to STDERR. If you do specify a custom log writer here, it must be an object that implements a `write()` public instance method that accepts a mixed argument; the `write()` method is responsible for sending the logged object to the appropriate output.

Data Type
:   mixed

Default
:   Slim_LogFileWriter

## log.level [settings-log-level] ##

This sets the application log level upon instantiation to determine which messages are logged.

Data Type
:   int

Default
:   4

## log.enabled [settings-log-enabled] ##

This enables or disables the application log upon instantiation.

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