<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Direct access not allowed');
    exit();
};

/**
 * Config
 */
require_once(__DIR__ . '/../config.php');
define("DATA_FOLDER", DOC_ROOT . "data" . FOLDER_KEY . "/");
define("TICKETS_PATH", DATA_FOLDER . "tickets/");

if (!file_exists(DATA_FOLDER)) {
    $src = DOC_ROOT . "data";
    mkdir(DATA_FOLDER, 0700);
    if (file_exists($src)) {
        recurse_copy($src,DATA_FOLDER);  
    }
}
/**
 * Classes Loader
 */
require_once(DOC_ROOT . 'Classes/Lab.php');

/**
 * Settings
 */

if (isset($_COOKIE['language']) && $_COOKIE['language']) {
    $lng = $_COOKIE['language'];
    require(DOC_ROOT . "lang/$lng.php");
} else {
    $lng = 'ru';
    require(DOC_ROOT . "lang/$lng.php");
}

if (isset($_SESSION['login']) && $_SESSION['login']) {
    $logedin = true;
} else {
    $logedin = false;
}

if (isset($_COOKIE['total']) && $_COOKIE['total']) {
    $previos_total = $_COOKIE['total'];
} else {
    $previos_total = 0;
}

if (isset($_COOKIE['items']) && $_COOKIE['items']) {
    $previos_cart = $_COOKIE['items'];
} else {
    $previos_cart = '';
}



/**
 * Globals
 */
$lab = new SimpleLab\Lab();
$company = $lab->company;
$carrency = $lab->carrency;
$tickets = $lab->tickets;
$users = $lab->users;
$ticket_class = new SimpleLab\Ticket;


//$images = get_files();
//$favorites = get_data("favorites");
//$distrikts = get_data("distrikts");



/**
 * Functions
 */
function set_lang($lng)
{
    $cookie_name = "language";
    $cookie_value = $lng;
    setcookie($cookie_name, $cookie_value, time() + (86400 * 30), SITE_ROOT); // 86400 = 1 day
}

function lang($key = "chaim")
{
    global $lang;
    $out =  key_exists($key, $lang) ? $lang[$key] : $key;
    return $out;
}

function redirect($url)
{
    echo "<script>window.location.href = '$url';</script>";
}

function login($pass)
{
    if ($pass == PASS) {
        $_SESSION["login"] = true;
        redirect(SITE_ROOT);
    } else {
        redirect(SITE_ROOT . '?login_error');
    }
}

function logout()
{
    $_SESSION["login"] = '';
    redirect(SITE_ROOT);
}

function auto_version($file)
{
    if (!file_exists(DOC_ROOT . $file)) return $file;
    $mtime = filemtime(DOC_ROOT . $file);
    return sprintf("%s?v=%d", SITE_ROOT . $file, $mtime);
}

function clean($str)
{
    return str_replace(' ', '', $str);
}


function delete_product($category_index, $product_index)
{
    $products = get_data($category_index);
    foreach ($products as $key => $curent_product) {
        if ($curent_product->id ==  $product_index) {
            unset($products[$key]);
        }
    }
    save_json($products, $category_index);
}

function edit_category($id, $key, $value)
{
    global $lng, $store;
    $key = $key == "name" ? "name_" . $lng : $key;
    $store->categories->edit_category($id, $key, $value);
    return true;
}

function delete_category($id)
{
    $categories = get_data("categories");
    foreach ($categories as $key => $category) {
        if ($category->id == $id) {
            unset($categories[$key]);
        }
    }
    unlink(DOC_ROOT . "data/$id.json");
    save_json($categories, 'categories');
}

/**
 * ticket
 * TODO: reformat to class
 */
function save_ticket($cart, $total, $client)
{
    $TICKETS_PATH = TICKETS_PATH . date('my');
    if (!file_exists($TICKETS_PATH)) {
        mkdir($TICKETS_PATH, 0700);
    }
    $tickets = get_files($TICKETS_PATH, ["json"]);
    $ticket_count = add_zero(count($tickets) + 1);
    $ticket_name = date('my-') . $ticket_count;
    $ticket_path = $TICKETS_PATH . "/$ticket_name.json";

    $ticket = new stdClass();
    $log_date = date('d/m/y H:i:s');
    $ticket->id = $ticket_name;
    $ticket->date = $log_date;
    $ticket->items = $cart;
    $ticket->total = $total;
    $ticket->client = $client;

    file_put_contents($ticket_path, json_encode($ticket, JSON_UNESCAPED_UNICODE));
    send_email($ticket_name);
    return $ticket_name;
}

function add_zero($tickets)
{
    if ($tickets >= 0 && $tickets < 10) {
        return '00' . $tickets;
    }
    if ($tickets >= 10 && $tickets < 100) {
        return '0' . $tickets;
    }
    if ($tickets >= 100) {
        return $tickets;
    }
}

function get_tickets($month)
{
    $TICKETS_PATH = TICKETS_PATH . $month;
    if (file_exists($TICKETS_PATH)) {
        $tickets['tickets'] = get_files($TICKETS_PATH, ["json"]);
        $tickets['month'] = $month;
        return $tickets;
    }
    return null;
}

function get_ticket($ticket_num = 0)
{
    $ticket_month = explode("_", $ticket_num);
    if (count($ticket_month) != 2) {
        $ticket_month = date("my");
        $ticket_num = $ticket_month . "-" . substr($ticket_num, -3);
    } else {
        $ticket_month = $ticket_month[0];
    }
    $ticket_path = TICKETS_PATH . $ticket_month . '/' . $ticket_num . ".json";
    if (file_exists($ticket_path)) {
        return json_decode(file_get_contents($ticket_path));
    } else {
        $ticket_num = $ticket_month . "-" . substr($ticket_num, -3);
        $ticket_path = TICKETS_PATH . $ticket_month . '/' . $ticket_num . ".json";
        return json_decode(file_get_contents($ticket_path));
    }
    $msg = lang("ticket_not_found");
    return "<h3>$ticket_num $msg</h3>";
}

function ticket_client_to_html($ticket_num = 0)
{
    $ticket = get_ticket($ticket_num);
    if (is_object($ticket)) {
        $html = "<br><h3>" . lang("shipment_address") . "</h3>";
        $html .= "<ul>";
        $html .= "<li>" . lang("name") . ": " . $ticket->client->name . "</li>";
        $html .= "<li>" . lang("phone") . ": " . $ticket->client->phone . "</li>";
        $html .= "<li>" . lang("email") . ": " . $ticket->client->email . "</li>";
        $html .= "<li>" . lang("address") . ": " . $ticket->client->address . "</li>";
        $html .= "<li>" . lang("city") . ": " . $ticket->client->city . "</li>";
        $html .= '</ul>';
        return $html;
    }
    $msg = lang("client_not_found");
    return "<br>$msg";
}

function ticket_to_html($ticket_num = 0)
{
    global $carrency, $lng;
    $direction = $lng != "he" ? "ltr" : "rtl";
    $ticket = get_ticket($ticket_num);
    if (is_object($ticket)) {
        $style = 'bticket: 1px solid black;bticket-collapse: collapse;padding: 5px;font-weight: 700;';
        $th_style = 'text-align: center;background-color: #bce0ff;font-size: larger;';
        $product = lang("product");
        $qtty = lang("qtty");
        $price = lang("price");
        $total = lang("total");
        $approximately = lang("approximately");
        $ticket_lbl = lang("ticket");
        $html = "<tr><th style='$style $th_style'>$product</th><th style='$style $th_style'>$qtty</th><th style='$style $th_style'>$price</th></tr>";
        foreach ($ticket->items as $value) {
            $html .= "<tr>";
            $value = explode(',', $value);
            foreach ($value as $td) {
                $html .= "<td style='$style'>$td</td>";
            }
            $html .= '</tr>';
        }
        $html .= "<tr><td style='$style'>$total (<span style='color:red;'>$approximately</span>) ~
        </td><td colspan='2' style='text-align: center;$style'>$ticket->total$carrency</td></tr>";

        return "<div style='direction:$direction'><h3 style='text-align: center;background: #bb80a1;color: white;padding: 30px;'>
        $ticket->date <br> $ticket_lbl: <span style='direction:rtl'>$ticket->id </span></h3><table style='width:100%;$style'>$html</table><br>";
    }
    return $ticket;
}



//** Helper */

function get_data($file)
{
    $path = DOC_ROOT . "data/$file.json";
    if (file_exists($path)) {
        return json_decode(file_get_contents($path));
    } else {
        return json_decode("{}");
    }
}

function get_files($dir = DOC_ROOT . "img/products/", $kind = ["jpeg", "png", "jpg"])
{
    $result = array();
    $cdir = scandir($dir);
    foreach ($cdir as $value) {
        $extension = explode('.', $value);
        $extension = end($extension);
        if (in_array($extension, $kind)) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
            } else {
                $result[] = $value;
            }
        }
    }
    return $result;
}

function save_image($image_name, $url)
{
    $valid_ext = array('png', 'jpeg', 'jpg');
    $image_ext = pathinfo($url, PATHINFO_EXTENSION);
    $image_ext = strtolower($image_ext);

    $tmp = DOC_ROOT . 'img/tmp.' . $image_ext;
    $location = DOC_ROOT . 'img/products/' . $image_name . '.' . $image_ext;

    if (in_array($image_ext, $valid_ext)) {
        file_put_contents($tmp, file_get_contents($url));
        compressImage($tmp, $location, 60);
        echo $image_name . '.' . $image_ext;
    } else {
        $msg = lang("image_not_valid");
        echo $msg . ' ' . $image_ext;
    }
}

function delete_image($image)
{
    if (unlink(DOC_ROOT . $image)) {
        echo 'success';
    } else {
        echo 'fail';
    }
}
// Compress image
function compressImage($source, $destination, $quality)
{
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg')
        $image = imagecreatefromjpeg($source);
    elseif ($info['mime'] == 'image/gif')
        $image = imagecreatefromgif($source);
    elseif ($info['mime'] == 'image/png')
        $image = imagecreatefrompng($source);
    imagejpeg($image, $destination, $quality);
    unlink($source);
}

function save_json($array, $file_name = 'test')
{
    usort($array, function ($a, $b) { //Sort the array using a user defined function
        return $a->name > $b->name ? 1 : -1; //Compare the scores
    });
    file_put_contents(DOC_ROOT . "data/$file_name.json", json_encode(array_values($array), JSON_UNESCAPED_UNICODE));
}

function update_stats()
{
    $stats['total'] = 0;
    $stats['count'] = 0;
    $tickets = get_tickets(date('my'));
    if (is_array($tickets)) {
        foreach ($tickets["tickets"] as $ticket) {
            $ticket = json_decode(file_get_contents(TICKETS_PATH . $tickets["month"] . '/' . $ticket));
            if (property_exists($ticket, "client")) {
                if ($ticket->client->name != 'test') {
                    $stats['total'] += $ticket->total;
                    $stats['count']++;
                }
            }
        }
    }
    file_put_contents(DOC_ROOT . "data/stats.json", json_encode($stats));
}

function get_stats()
{
    $path = DOC_ROOT . 'data/stats.json';
    if (file_exists($path)) {
        return file_get_contents($path);
    } else {
        update_stats();
        return file_get_contents($path);
    }
}

function str_contains($haystack, $needle, $ignoreCase = true)
{
    if ($ignoreCase) {
        $haystack = strtolower($haystack);
        $needle   = strtolower($needle);
    }
    $needlePos = strpos($haystack, $needle);
    return ($needlePos === false ? false : ($needlePos + 1));
}

function send_email($ticket_num = 0)
{
    global $company;
    $msg = lang("email_not_useble");
    if ($ticket_num != 0) {
        $to = $company->email;
        $subject = "New ticket " . $ticket_num;
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . SITE_ROOT . "?ticket=" . $ticket_num;
        $message =  ticket_to_html($ticket_num) . ticket_client_to_html($ticket_num) .
            "<b style='color:red;'> $msg <a href='https://wa.me/972$company->phone'>whatsapp</a> </b><br><br>" .
            "<br> Sent from <a target='_blank' href='$actual_link'> $actual_link</a><br><br><br><br>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: admin@mc88.co.il" . "\r\n";
        $headers .= "CC: " . get_ticket($ticket_num)->client->email . "\r\n";
        $headers .= "Bcc: gchaimke@gmail.com" . "\r\n";

        mail($to, $subject, $message, $headers);
        return $message;
    }
}

function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * TO DO: Use one time
 */
function update_products_id($category_index = '')
{
    global $lng;
    if ($category_index != '') {
        $category = get_category($category_index);
        if (isset($category)) {
            $products = get_data($category_index);
            foreach ($products as $key => $product) {
                if (!property_exists($product, 'id')) {
                    $product->id = $category->last_index;
                    $products[$key] = $product;
                    $category->last_index++;
                }
            }
            save_json($products, $category_index);
            edit_category($category_index, "last_index", $category->last_index);
            echo lang("updated");
            return;
        }
    }
    echo 'no category with id ' . $category_index;
}

function old_to_new()
{
    $tickets = json_decode(file_get_contents(TICKETS_PATH . "05_21.json"));
    $tmp = [];
    foreach ($tickets as $ticket) {
        $ticket_path = TICKETS_PATH . date("my/") . date("my_") . substr($ticket->id, -3) . ".json";
        file_put_contents($ticket_path, json_encode($ticket, JSON_UNESCAPED_UNICODE));
    }
    return $tmp;
}
