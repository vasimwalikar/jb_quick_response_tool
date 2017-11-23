<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use mikehaertl\wkhtmlto\Pdf;
use League\Csv\Reader;
use League\Csv\Writer;


require '../vendor/autoload.php';
require '../includes/functions.php';
require '../vendor/league/csv/autoload.php';


session_start();
$app = init();


$authenticate = function ($request, $response, $next) {
    if (!isset($_SESSION['user'])) {

        return $response->withRedirect('/login/');
    } else {
        return $next($request, $response);
    }
};


$UrlCheckauthenticate = function ($request, $response, $next) {
    if (isset($_SESSION['user'])) {
        if ($_SESSION['user'] == "jb_mis" || $_SESSION['user'] == "jb_admin") {
            return $next($request, $response);

        } else {
            return $response->withRedirect('/branch/');

        }

    } else {
        return $next($request, $response);
    }
};


$app->get('/logout/', function (Request $request, Response $response) {
    unset($_SESSION['user']);
    unset($_SESSION['branch_id']);
    return $response->withRedirect('/login/');
});




$app->get('/', function (Request $request, Response $response) {
    $success_msg = $request->getQueryParams()['success_msg'];
    $error_msg = $request->getQueryParams()['error_msg'];

    $response = $this->view->render($response, 'login.mustache', ['success_msg'=> $success_msg, 'error_msg'=> $error_msg]);
    return $response;
});

$app->get('/login/', function (Request $request, Response $response) {
    $success_msg = $request->getQueryParams()['success_msg'];
    $error_msg = $request->getQueryParams()['error_msg'];

    $response = $this->view->render($response, 'login.mustache', ['success_msg'=> $success_msg, 'error_msg'=> $error_msg]);
    return $response;
});


$app->get('/register/', function (Request $request, Response $response) {

    $success_msg = $request->getQueryParams()['success_msg'];
    $error_msg = $request->getQueryParams()['error_msg'];

    $response = $this->view->render($response, 'register.mustache', ['success_msg'=> $success_msg, 'error_msg'=> $error_msg]);
    return $response;
});





$app->post('/login_validate_normal/', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $username = $data['username'];
    $password = $data['password'];

//    if ($username == 'jb_admin' && $password == 'miskiosk') {
//        $admin = 0;
//        $_SESSION['flag'] = $admin;
//        $_SESSION['user'] = $username;
//        return $response->withRedirect('/home/');
//    }
//
//    if ($username == 'jb_mis' && $password == 'misadmin') {
//        $_SESSION['user'] = $username;
//        $admin = 1;
//        $_SESSION['flag'] = $admin;
//        return $response->withRedirect('/home/');
//    }

    if ($username == 'quick_link_u' && $password == 'quick_link_p') {
        $_SESSION['user'] = $username;
        $admin = 2;
        $_SESSION['flag'] = $admin;
        return $response->withRedirect('/user-data/');
    }
    
    else {
        return $response->withRedirect('/login/?error_msg="User Name / Password is wrong, Please try again!"');
    }


});



$app->get('/home/', function (Request $request, Response $response) {


    return $this->view->render($response, 'home.mustache', ["user" => $_SESSION['user'], 'flag' => (int)$_SESSION['flag']]);
})->add($authenticate);





$app->get('/user-data/', function (Request $request, Response $response) {


    $con = $this->db;

    $query = "select * from QUICK_USER_DATA";


    $result = oci_parse($con, $query);
    oci_execute($result);

    while ($row = oci_fetch_array($result)) {

        $data[] = $row;

    }
//    echo json_encode($data);
//    die;


    return $this->view->render($response, 'user_data.mustache',["user" => $_SESSION['user'],'data' => $data, 'flag' => (int)$_SESSION['flag']]);
})->add($authenticate);


$app->post('/insertData/', function (Request $request, Response $response) {

    $files = $request->getUploadedFiles();
    if (empty($files['newfile'])) {
        throw new Exception('Expected a newfile');
    }

    $newfile = $files['newfile'];
    //load the CSV document from a file path
    //$csv = Reader::createFromPath($newfile->file);
    //$res = $csv->fetchAll();
    //$res = $csv->setOffset(10)->setLimit(25)->fetchAll();
    //echo json_encode($res);
    //die;


    $reader = Reader::createFromPath($newfile->file);
    $input_bom = $reader->getInputBOM();

    if ($input_bom === Reader::BOM_UTF16_LE || $input_bom === Reader::BOM_UTF16_BE) {
        $reader->appendStreamFilter('convert.iconv.UTF-16/UTF-8');
    }

    foreach ($reader->fetchAssoc(0) as $row) {
        //echo json_encode($row, JSON_PRETTY_PRINT), PHP_EOL;

        $FIRST_NAME =$row['FIRST_NAME'];
        $LAST_NAME = $row['LAST_NAME'];
        $EMAIL = $row['EMAIL'];
        $MOBILE_NO = $row['MOBILE_NO'];
        $PIN_CODE = $row['PIN_CODE'];
        $MEMBERSHIP_NO = $row['MEMBERSHIP_NO'];
        $TYPE = $row['TYPE'];
        $PLAN_NAME = $row['PLAN_NAME'];
        $BOOKS = $row['BOOKS'];
        $MONTHS = $row['MONTHS'];
        $COUPON_CODE = $row['COUPON_CODE'];
        $PROMO_CODE = $row['PROMO_CODE'];
        $AMOUNT = $row['AMOUNT'];
        $STATUS = $row['STATUS'];
        $EXPIRY_DATE = $row['EXPIRY_DATE'];

        $query = "insert into QUICK_USER_DATA (ID, FIRST_NAME, LAST_NAME, EMAIL, MOBILE_NO, PIN_CODE, MEMBERSHIP_NO, TYPE, PLAN_NAME, BOOKS, MONTHS,COUPON_CODE, PROMO_CODE,AMOUNT,STATUS,EXPIRY_DATE,CREATED_AT) 
 values (QUICK_USER_DATA_SEQ.NEXTVAL,'$FIRST_NAME','$LAST_NAME','$EMAIL','$MOBILE_NO','$PIN_CODE','$MEMBERSHIP_NO','$TYPE','$PLAN_NAME','$BOOKS','$MONTHS','$COUPON_CODE','$PROMO_CODE','$AMOUNT','$STATUS',to_date('". $EXPIRY_DATE ."','yyyy-mm-dd hh24:mi:ss'),CURRENT_TIMESTAMP)";


        $con = $this->db;
        $compiled = oci_parse($con, $query);
        $result = oci_execute($compiled);

    }
    if (!$result) {
        $e = oci_error($compiled);  // For oci_parse errors pass the connection handle
        echo htmlentities($e['message']);
    }else{
        return $response->withRedirect('/user-data/');
    }


})->add($authenticate);



$app->any('/insertDtataFromInput/', function(Request $request, Response $response){

    $parse = $request->getParsedBody();


    $user_id = $_SESSION['id'];
    $first_name = $parse['first_name'];
    $last_name = $parse['last_name'];
    $email = $parse['email'];
    $mobile = $parse['mobile'];
    $zip = $parse['zip'];
    $membership_no = $parse['membership_no'];
    $type = $parse['type'];
    $coupon_code = $parse['coupon_code'];
    $promo_code = $parse['promo_code'];
    $amount = $parse['amount'];
    $plane_name = $parse['plane_name'];
    $books = $parse['books'];
    $months = $parse['months'];


    $expiry_date = $parse['expiry_date'];



    $con = $this->db;


    $query = "insert into QUICK_USER_DATA (ID, FIRST_NAME, LAST_NAME, EMAIL, MOBILE_NO, PIN_CODE, MEMBERSHIP_NO, TYPE ,PLAN_NAME, BOOKS, MONTHS, COUPON_CODE, PROMO_CODE, AMOUNT, EXPIRY_DATE, CREATED_AT)  
    values (QUICK_USER_DATA_SEQ.NEXTVAL, '$first_name','$last_name','$email','$mobile','$zip','$membership_no','$type','$plane_name','$books','$months','$coupon_code','$promo_code','$amount',to_date('". $expiry_date ."','yyyy-mm-dd hh24:mi:ss'),CURRENT_TIMESTAMP)";



    $compiled = oci_parse($con, $query);
    $result = oci_execute($compiled);

    if (!$result) {
        $e = oci_error($compiled);  // For oci_parse errors pass the connection handle
        echo htmlentities($e['message']);
    }else{
        return $response->withRedirect('/user-data/');
    }





});


$app->post('/send_req/', function(Request $request, Response $response){

    $parse = $request->getParsedBody();


    $id = $parse['id'];


    $con = $this->db;

    $query = "select * from QUICK_USER_DATA where id=$id";


    $result = oci_parse($con, $query);
    oci_execute($result);

    $row = oci_fetch_assoc($result);

    $FIRST_NAME = $row['FIRST_NAME'];
    $LAST_NAME = $row['LAST_NAME'];
    $EMAIL = $row['EMAIL'];
    $MOBILE_NO = $row['MOBILE_NO'];
    $PIN_CODE = $row['PIN_CODE'];
    $MEMBERSHIP_NO = $row['MEMBERSHIP_NO'];
    $TYPE = $row['TYPE'];
    $COUPON_CODE = $row['COUPON_CODE'];
    $PROMO_CODE = $row['PROMO_CODE'];
    $AMOUNT = $row['AMOUNT'];
    $STATUS = $row['STATUS'];
    $EXPIRY_DATE = $row['EXPIRY_DATE'];
    $CREATED_AT = $row['CREATED_AT'];
    $UPDATED_AT = $row['UPDATED_AT'];
    $PLAN_NAME = $row['PLAN_NAME'];
    $BOOKS = $row['BOOKS'];
    $MONTHS = $row['MONTHS'];

    if($TYPE == 'signup'){

        $m = new SimpleEmailServiceMessage();
        //$m->addTo(array('sridhar.rajaram@justbooksclc.com','akhil.kamalasan@justbooksclc.com'));
        $m->addTo(array($EMAIL));
        $m->setFrom('customercare@justbooksclc.com');
        $m->setSubject('JustBooks- Signup');
        //$m->setMessageFromString("Recieved Franchisee Request from below person.<br><p>First Name:</p> $fname<br>");
        $text = "Recieved franchisee request from below person";
        $html = '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldnt be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

    <!-- Web Font / @font-face : BEGIN -->
    <!-- NOTE: If web fonts are not required, lines 10 - 27 can be safely removed. -->

    <!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
    <!--[if mso]>
        <style>
            * {
                font-family: sans-serif !important;
            }
        </style>
    <![endif]-->

    <!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
    <!--[if !mso]><!-->
    <!-- insert web font reference, eg: <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet" type="text/css"> -->
    <!--<![endif]-->

    <!-- Web Font / @font-face : END -->

    <!-- CSS Reset : BEGIN -->
    <style>

        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */
        *[x-apple-data-detectors],  /* iOS */
        .x-gmail-data-detectors,    /* Gmail */
        .x-gmail-data-detectors *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: Arial!important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying an download button on large, non-linked images. */
        .a6S {
           display: none !important;
           opacity: 0.01 !important;
       }
       /* If the above doesnt work, add a .g-img class to any image in question. */
       img.g-img + div {
           display: none !important;
       }

       /* What it does: Prevents underlining the button text in Windows 10 */
        .button-link {
            text-decoration: none !important;
        }

        /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
        /* Create one of these media queries for each additional viewport size youd like to fix */
        /* Thanks to Eric Lepetit (@ericlepetitsf) for help troubleshooting */
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) { /* iPhone 6 and 6+ */
            .email-container {
                min-width: 375px !important;
            }
        }

    </style>
    <!-- CSS Reset : END -->

    <!-- Progressive Enhancements : BEGIN -->
    <style>

        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }
        .button-td:hover,
        .button-a:hover {
            background: #555555 !important;
            border-color: #555555 !important;
        }

        /* Media Queries */
        @media screen and (max-width: 600px) {

            .email-container {
                width: 100% !important;
                margin: auto !important;
            }

            /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
            .fluid {
                max-width: 100% !important;
                height: auto !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }
            table.center-on-narrow {
                display: inline-block !important;
            }

            /* What it does: Adjust typography on small screens to improve readability */
            .email-container p {
                font-size: 17px !important;
                line-height: 22px !important;
            }
        }

    </style>
    <!-- Progressive Enhancements : END -->

    <!-- What it does: Makes background images in 72ppi Outlook render at correct size. -->
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->

</head>
<body width="100%" bgcolor="#fff" style="margin: 0; mso-line-height-rule: exactly;">
    <center style="width: 100%; background: #fff; text-align: left;">

        

        <!-- Email Header : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
            <tr>
                <td style="padding: 20px 0; text-align: center">
                    <img src="https://www.justbooks.in/assets/img/logo.png" width="200" alt="alt_text" border="0" style="height: auto; background: #dddddd; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;">
                </td>
            </tr>
        </table>
        <!-- Email Header : END -->

        <!-- Email Body : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">

            

        <tr>
            <td bgcolor="#ffffff" style="padding: 40px 40px 20px; text-align: justify; border-top: solid 1px #444;">
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Dear '.$FIRST_NAME.' '.$LAST_NAME.',</h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Thanks for your interest in becoming a member of JustBooks, the country\'s largest online library. We will be delighted to have you on board with us. </h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">It was a pleasure speaking to you and in continuation of our discussion, kindly find below the QuickPay button that will allow you sign up to our services in just one simple step. You will led to the payment page from where you can sign up by rechecking you plan details.</h1>
            </td>
        </tr>

          <!-- 3 Even Columns : BEGIN -->
          
        <tr>
            <td bgcolor="#ffffff" style="padding: 0 40px 40px; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"><br>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: auto">
                    <tr>
                        <td style="border-radius: 3px; background: #D32F2E; text-align: center;" class="button-td">
                            <a href="https://srv3.justbooks.in/quickpay/'.$id.'" style="background: #D32F2E; border: 10px solid #D32F2E; font-family: Arial; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; font-weight: bold; box-shadow: 6px 8px 11px #7f7f7f;" class="button-a">
                                &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff;">SIGNUP NOW</span>&nbsp;&nbsp;&nbsp;&nbsp;
                            </a>
                        </td>
                    </tr>
                </table><br>
                <!-- Button : END -->
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">We look forward to serving your reading needs. Happy reading!</h1><br>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: bold;">Team JustBooks.</h1>
            </td>
        </tr>
        <!-- 3 Even Columns : END -->

        

        

        <!-- Thumbnail Right, Text Left : BEGIN -->
        <tr>
            <td bgcolor="#ffffff" dir="rtl" align="center" valign="top" width="100%" style="padding: 0px; border-top: solid 1px #444444;">
                <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <!-- Column : BEGIN -->
                        <td width="33.33%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="padding: 0 0px;">
                                        <a href="https://play.google.com/store/apps/details?id=com.justbooks.in&hl=en"><img src="https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="height: auto; background: #fff; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"></a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                        <!-- Column : BEGIN -->
                        <td width="66.66%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="font-family: Arial; font-size: 15px; line-height: 20px; color: #555555; padding: 0px; text-align: left;" class="center-on-narrow">
                                        <a href="https://justbooks.in" style="text-decoration: none!important;"><h2 style="margin: 0 0 0 30px; font-family: Arial; font-size: 14px; line-height: 21px; color: #000; font-weight: 200">www.justbooks.in</h2></a>
                                        
                                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Thumbnail Right, Text Left : END -->

        <!-- Clear Spacer : BEGIN -->
        <tr>
            <td aria-hidden="true" height="40" style="font-size: 0; line-height: 0;">
                &nbsp;
            </td>
        </tr>
        <!-- Clear Spacer : END -->

       

    </table>

    </center>
</body>
</html>';
        $m->setMessageFromString($text, $html);

        $ses = new SimpleEmailService('AKIAJUYFFWPTDDSSNO3Q', 'Z+8AGSX8z3lWAhmHXVgISHkOwcKD3O9TSfkvImfX');
        print_r($ses->sendEmail($m));

    }elseif ($TYPE == 'renew'){

        $m = new SimpleEmailServiceMessage();
        //$m->addTo(array('sridhar.rajaram@justbooksclc.com','akhil.kamalasan@justbooksclc.com'));
        $m->addTo(array($EMAIL));
        $m->setFrom('customercare@justbooksclc.com');
        $m->setSubject('JustBooks- Renew Plan');
        //$m->setMessageFromString("Recieved Franchisee Request from below person.<br><p>First Name:</p> $fname<br>");
        $text = "Recieved franchisee request from below person";
        $html = '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldnt be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

    <!-- Web Font / @font-face : BEGIN -->
    <!-- NOTE: If web fonts are not required, lines 10 - 27 can be safely removed. -->

    <!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
    <!--[if mso]>
        <style>
            * {
                font-family: sans-serif !important;
            }
        </style>
    <![endif]-->

    <!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
    <!--[if !mso]><!-->
    <!-- insert web font reference, eg: <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet" type="text/css"> -->
    <!--<![endif]-->

    <!-- Web Font / @font-face : END -->

    <!-- CSS Reset : BEGIN -->
    <style>

        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */
        *[x-apple-data-detectors],  /* iOS */
        .x-gmail-data-detectors,    /* Gmail */
        .x-gmail-data-detectors *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: Arial!important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying an download button on large, non-linked images. */
        .a6S {
           display: none !important;
           opacity: 0.01 !important;
       }
       /* If the above doesnt work, add a .g-img class to any image in question. */
       img.g-img + div {
           display: none !important;
       }

       /* What it does: Prevents underlining the button text in Windows 10 */
        .button-link {
            text-decoration: none !important;
        }

        /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
        /* Create one of these media queries for each additional viewport size youd like to fix */
        /* Thanks to Eric Lepetit (@ericlepetitsf) for help troubleshooting */
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) { /* iPhone 6 and 6+ */
            .email-container {
                min-width: 375px !important;
            }
        }

    </style>
    <!-- CSS Reset : END -->

    <!-- Progressive Enhancements : BEGIN -->
    <style>

        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }
        .button-td:hover,
        .button-a:hover {
            background: #555555 !important;
            border-color: #555555 !important;
        }

        /* Media Queries */
        @media screen and (max-width: 600px) {

            .email-container {
                width: 100% !important;
                margin: auto !important;
            }

            /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
            .fluid {
                max-width: 100% !important;
                height: auto !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }
            table.center-on-narrow {
                display: inline-block !important;
            }

            /* What it does: Adjust typography on small screens to improve readability */
            .email-container p {
                font-size: 17px !important;
                line-height: 22px !important;
            }
        }

    </style>
    <!-- Progressive Enhancements : END -->

    <!-- What it does: Makes background images in 72ppi Outlook render at correct size. -->
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->

</head>
<body width="100%" bgcolor="#fff" style="margin: 0; mso-line-height-rule: exactly;">
    <center style="width: 100%; background: #fff; text-align: left;">

        

        <!-- Email Header : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
            <tr>
                <td style="padding: 20px 0; text-align: center">
                    <img src="https://www.justbooks.in/assets/img/logo.png" width="200" alt="alt_text" border="0" style="height: auto; background: #dddddd; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;">
                </td>
            </tr>
        </table>
        <!-- Email Header : END -->

        <!-- Email Body : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">

            

        <tr>
            <td bgcolor="#ffffff" style="padding: 40px 40px 20px; text-align: justify; border-top: solid 1px #444;">
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Dear '.$FIRST_NAME.' '.$LAST_NAME.',</h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Thanks for being a valued customer of JustBooks and for the continued partronage of our services.</h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">In order to renew your account , please click on the QuickPay button given below. You will be led to a page where you can quickly check and pay your renewal amount in one simple step.</h1>
            </td>
        </tr>

          <!-- 3 Even Columns : BEGIN -->
          
        <tr>
            <td bgcolor="#ffffff" style="padding: 0 40px 40px; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"><br>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: auto">
                    <tr>
                        <td style="border-radius: 3px; background: #D32F2E; text-align: center;" class="button-td">
                            <a href="https://srv3.justbooks.in/quickpay/'.$id.'" style="background: #D32F2E; border: 10px solid #D32F2E; font-family: Arial; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; font-weight: bold; box-shadow: 6px 8px 11px #7f7f7f;" class="button-a">
                                &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff;">RENEW PLAN</span>&nbsp;&nbsp;&nbsp;&nbsp;
                            </a>
                        </td>
                    </tr>
                </table><br>
                <!-- Button : END -->
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">We look forward to serving your reading needs. Happy reading!</h1><br>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: bold;">Team JustBooks.</h1>
            </td>
        </tr>
        <!-- 3 Even Columns : END -->

        

        

        <!-- Thumbnail Right, Text Left : BEGIN -->
        <tr>
            <td bgcolor="#ffffff" dir="rtl" align="center" valign="top" width="100%" style="padding: 0px; border-top: solid 1px #444444;">
                <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <!-- Column : BEGIN -->
                        <td width="33.33%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="padding: 0 0px;">
                                        <a href="https://play.google.com/store/apps/details?id=com.justbooks.in&hl=en"><img src="https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="height: auto; background: #fff; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"></a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                        <!-- Column : BEGIN -->
                        <td width="66.66%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="font-family: Arial; font-size: 15px; line-height: 20px; color: #555555; padding: 0px; text-align: left;" class="center-on-narrow">
                                        <a href="https://justbooks.in" style="text-decoration: none!important;"><h2 style="margin: 0 0 0 30px; font-family: Arial; font-size: 14px; line-height: 21px; color: #000; font-weight: 200">www.justbooks.in</h2></a>
                                        
                                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Thumbnail Right, Text Left : END -->

        <!-- Clear Spacer : BEGIN -->
        <tr>
            <td aria-hidden="true" height="40" style="font-size: 0; line-height: 0;">
                &nbsp;
            </td>
        </tr>
        <!-- Clear Spacer : END -->

       

    </table>

    </center>
</body>
</html>';
        $m->setMessageFromString($text, $html);

        $ses = new SimpleEmailService('AKIAJUYFFWPTDDSSNO3Q', 'Z+8AGSX8z3lWAhmHXVgISHkOwcKD3O9TSfkvImfX');
        print_r($ses->sendEmail($m));

    }elseif ($TYPE == 'changeplan'){

        $m = new SimpleEmailServiceMessage();
        //$m->addTo(array('sridhar.rajaram@justbooksclc.com','akhil.kamalasan@justbooksclc.com'));
        $m->addTo(array($EMAIL));
        $m->setFrom('customercare@justbooksclc.com');
        $m->setSubject('Franchisee Request');
        //$m->setMessageFromString("Recieved Franchisee Request from below person.<br><p>First Name:</p> $fname<br>");
        $text = "Recieved franchisee request from below person";
        $html = '<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldnt be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

    <!-- Web Font / @font-face : BEGIN -->
    <!-- NOTE: If web fonts are not required, lines 10 - 27 can be safely removed. -->

    <!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
    <!--[if mso]>
        <style>
            * {
                font-family: sans-serif !important;
            }
        </style>
    <![endif]-->

    <!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
    <!--[if !mso]><!-->
    <!-- insert web font reference, eg: <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet" type="text/css"> -->
    <!--<![endif]-->

    <!-- Web Font / @font-face : END -->

    <!-- CSS Reset : BEGIN -->
    <style>

        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. Fix for Yahoo mail table alignment bug. Applies table-layout to the first 2 tables then removes for anything nested deeper. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */
        *[x-apple-data-detectors],  /* iOS */
        .x-gmail-data-detectors,    /* Gmail */
        .x-gmail-data-detectors *,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: Arial!important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying an download button on large, non-linked images. */
        .a6S {
           display: none !important;
           opacity: 0.01 !important;
       }
       /* If the above doesnt work, add a .g-img class to any image in question. */
       img.g-img + div {
           display: none !important;
       }

       /* What it does: Prevents underlining the button text in Windows 10 */
        .button-link {
            text-decoration: none !important;
        }

        /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
        /* Create one of these media queries for each additional viewport size youd like to fix */
        /* Thanks to Eric Lepetit (@ericlepetitsf) for help troubleshooting */
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) { /* iPhone 6 and 6+ */
            .email-container {
                min-width: 375px !important;
            }
        }

    </style>
    <!-- CSS Reset : END -->

    <!-- Progressive Enhancements : BEGIN -->
    <style>

        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }
        .button-td:hover,
        .button-a:hover {
            background: #555555 !important;
            border-color: #555555 !important;
        }

        /* Media Queries */
        @media screen and (max-width: 600px) {

            .email-container {
                width: 100% !important;
                margin: auto !important;
            }

            /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
            .fluid {
                max-width: 100% !important;
                height: auto !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }
            table.center-on-narrow {
                display: inline-block !important;
            }

            /* What it does: Adjust typography on small screens to improve readability */
            .email-container p {
                font-size: 17px !important;
                line-height: 22px !important;
            }
        }

    </style>
    <!-- Progressive Enhancements : END -->

    <!-- What it does: Makes background images in 72ppi Outlook render at correct size. -->
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->

</head>
<body width="100%" bgcolor="#fff" style="margin: 0; mso-line-height-rule: exactly;">
    <center style="width: 100%; background: #fff; text-align: left;">

        

        <!-- Email Header : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
            <tr>
                <td style="padding: 20px 0; text-align: center">
                    <img src="https://www.justbooks.in/assets/img/logo.png" width="200" alt="alt_text" border="0" style="height: auto; background: #dddddd; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;">
                </td>
            </tr>
        </table>
        <!-- Email Header : END -->

        <!-- Email Body : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">

            

        <tr>
            <td bgcolor="#ffffff" style="padding: 40px 40px 20px; text-align: justify; border-top: solid 1px #444;">
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Dear \'.$FIRST_NAME.\' \'.$LAST_NAME.\',</h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">Thanks for being a valued customer of JustBooks and for the continued partronage of our services.</h1>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">In order to change plan , please click on the QuickPay button given below. You will be led to a page where you can quickly check and pay your change plan amount in one simple step.</h1>
            </td>
        </tr>

          <!-- 3 Even Columns : BEGIN -->
          
        <tr>
            <td bgcolor="#ffffff" style="padding: 0 40px 40px; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"><br>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: auto">
                    <tr>
                        <td style="border-radius: 3px; background: #D32F2E; text-align: center;" class="button-td">
                            <a href="https://srv3.justbooks.in/quickpay/\'.$id." style="background: #D32F2E; border: 10px solid #D32F2E; font-family: Arial; font-size: 13px; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; font-weight: bold; box-shadow: 6px 8px 11px #7f7f7f;" class="button-a">
                                &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ffffff;">Change Plan</span>&nbsp;&nbsp;&nbsp;&nbsp;
                            </a>
                        </td>
                    </tr>
                </table><br>
                <!-- Button : END -->
                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: normal;">We look forward to serving your reading needs. Happy reading!</h1><br>

                <h1 style="margin: 0; font-family: Arial; font-size: 18px; line-height: 25px; color: #333333; font-weight: bold;">Team JustBooks.</h1>
            </td>
        </tr>
        <!-- 3 Even Columns : END -->

        

        

        <!-- Thumbnail Right, Text Left : BEGIN -->
        <tr>
            <td bgcolor="#ffffff" dir="rtl" align="center" valign="top" width="100%" style="padding: 0px; border-top: solid 1px #444444;">
                <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <!-- Column : BEGIN -->
                        <td width="33.33%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="padding: 0 0px;">
                                        <a href="https://play.google.com/store/apps/details?id=com.justbooks.in&hl=en"><img src="https://play.google.com/intl/en_us/badges/images/generic/en_badge_web_generic.png" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="height: auto; background: #fff; font-family: Arial; font-size: 15px; line-height: 20px; color: #555555;"></a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                        <!-- Column : BEGIN -->
                        <td width="66.66%" class="stack-column-center">
                            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td dir="ltr" valign="top" style="font-family: Arial; font-size: 15px; line-height: 20px; color: #555555; padding: 0px; text-align: left;" class="center-on-narrow">
                                        <a href="https://justbooks.in" style="text-decoration: none!important;"><h2 style="margin: 0 0 0 30px; font-family: Arial; font-size: 14px; line-height: 21px; color: #000; font-weight: 200">www.justbooks.in</h2></a>
                                        
                                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <!-- Column : END -->
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Thumbnail Right, Text Left : END -->

        <!-- Clear Spacer : BEGIN -->
        <tr>
            <td aria-hidden="true" height="40" style="font-size: 0; line-height: 0;">
                &nbsp;
            </td>
        </tr>
        <!-- Clear Spacer : END -->

       

    </table>

    </center>
</body>
</html>';
        $m->setMessageFromString($text, $html);

        $ses = new SimpleEmailService('AKIAJUYFFWPTDDSSNO3Q', 'Z+8AGSX8z3lWAhmHXVgISHkOwcKD3O9TSfkvImfX');
        print_r($ses->sendEmail($m));

    }

});



$app->any('/testdata/', function (Request $request, Response $response) {
    //$id = $_SESSION['branch_id'];

    $data = $request->getParsedBody();
    $data_collection = [];

    $con = $this->db;
    //$query = "select * from titles where isbn = '9781471133060'";
    $query = "select * from jbprod.authorprofile where FIRSTNAME = 'arjun'";

    //$query = "select * from FN_BO_BATCH";

//    $query = "select * from jbprod.category";

    //$query = "delete from jbprod.authorprofile where FIRSTNAME = 'vasim'";
    //$query = "delete from FN_BO_BATCH";
    //$query = "SELECT fbb.ISBN, fbb.BATCH_ID, fbb.TITLE, fbb.AUTHOR, fbb.MRP, fbb.DISCOUNT, fbb.CATEGORY, nvl(ttl.isbn,1) AS title_isbn FROM FN_BO_BOOKS fbb LEFT JOIN titles ttl ON fbb.ISBN = ttl.isbn";



    $result = oci_parse($con, $query);
    oci_execute($result);

    while ($row = oci_fetch_array($result)) {

        $data1[] = $row;

    }
    echo json_encode($data1);

    die;

    $response = $this->view->render($response, 'testdata.mustache', ['flag' => (int)$_SESSION['flag'], 'data' => $data1, 'from' => $from, 'to' => $to]);
    return $response;
})->add($authenticate);



$app->any('/test/', function (Request $request, Response $response) {


    $response = $this->view->render($response, 'test.mustache');
    return $response;
});





$app->run();

?>
