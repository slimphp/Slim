# Error Reporting [errors-reporting] #

By default, Slim will report **E\_ALL** and **E\_STRICT** errors. You can change the error reporting by editing **Slim/Slim.php**; the error reporting definition is at the top of that file. Only reported errors will be handled by Slim. Unreported errors will be ignored or handled elsewhere.