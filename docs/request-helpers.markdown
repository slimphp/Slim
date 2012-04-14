# Request Helpers [request-helpers] #

Slim provides several helper methods that help you fetch common HTTP request information.

    //Fetch HTTP request content type (e.g. "application/json;charset=utf-8"
    $contentType = $app->request()->getContentType();
    
    //Fetch HTTP request media type (e.g "application/json")
    $mediaType = $app->request()->getMediaType();
    
    //Fetch HTTP request media type params (e.g [charset => "utf-8"])
    $mediaTypeParams = $app->request()->getMediaTypeParams();
    
    //Fetch HTTP request content type charset (e.g. "utf-8")
    $charset = $app->request()->getContentCharset();
    
    //Fetch HTTP request content length
    $contentLength = $app->request()->getContentLength();
    
    //Fetch HTTP request host (e.g. "slimframework.com")
    $host = $app->request()->getHost();
    
    //Fetch HTTP request host with port (e.g. "slimframework.com:80")
    $hostAndPort = $app->request()->getHostWithPort();
    
    //Fetch HTTP request port (e.g. 80)
    $port = $app->request()->getPort();
    
    //Fetch HTTP request scheme (e.g. "http" or "https")
    $scheme = $app->request()->getScheme();
    
    //Fetch HTTP request URI path (root URI + resource URI)
    $path = $app->request()->getPath();
    
    //Fetch HTTP request URL (scheme + host [ + port if non-standard ])
    $url = $app->request()->getUrl();
    
    //Fetch HTTP request IP address
    $ip = $app->request()->getIp();
    
    //Fetch HTTP request referer
    $ref = $app->request()->getReferer();
    $ref = $app->request()->getReferrer();
    
    //Fetch HTTP request user agent string
    $ua = $app->request()->getUserAgent();