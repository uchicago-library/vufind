<?php 
    if ( $_SERVER['HTTP_HOST'] == "dldc2.lib.uchicago.edu" ) {
        $target="/OIDC.sso/redirect_uri?target_link_uri=https%3A%2F%2Fdldc2.lib.uchicago.edu%2Fvufind%2FMyResearch%2FHome%3Fauth_method%3DShibboleth&iss=https%3A%2F%2Fuchicago.okta.com" ;
    } elseif ( $_SERVER['HTTP_HOST'] == "catalog-test.lib.uchicago.edu" ) {
        $target="/redirect_uri?target_link_uri=https%3A%2F%2Fcatalog-test.lib.uchicago.edu%2Fvufind%2FMyResearch%2FHome%3Fauth_method%3DShibboleth&iss=https%3A%2F%2Fuchicago.okta.com" ;
    } else {
        $target="/redirect_uri?target_link_uri=https%3A%2F%2Fcatalog-test.lib.uchicago.edu%2Fvufind%2FMyResearch%2FHome%3Fauth_method%3DShibboleth&iss=https%3A%2F%2Fuchicago.okta.com" ;
    }
?><!DOCTYPE html>
<html lang="en" style="font-family: Helvetica, Arial, sans-serif;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Library catalog temporarily unavailable from off campus</title>
</head>
<body>
    <div>
        <div style="width: 600px; max-width: 100%; margin:0 auto; padding: 15px; box-sizing: border-box;">
            <h2>Library catalog temporarily unavailable from off campus</h2>
            <p>
Due to system issues, the library catalog is currently available to UChicago affiliates and those with CNetIDs. If you are not affiliated with the University of Chicago, we apologize, and are working to restore service soon.
</p>
            <p>Please <a href="<?php echo $target; ?>">Login</a> to access the catalog from off campus.</p>
            <br>
        </div>
    </div>
</body>
</html>
