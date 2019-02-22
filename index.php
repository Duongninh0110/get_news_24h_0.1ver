<?php
set_time_limit(0);

include 'libs/Curl/CaseInsensitiveArray.php'; 
include 'libs/Curl/Curl.php'; 
include 'libs/Curl/MultiCurl.php';

include 'libs/DiDom/Document.php';
include 'libs/DiDom/Query.php';
include 'libs/DiDom/Element.php';

include 'libs/medoo.php';

use \Curl\Curl;
use \DiDom\Document;
use \DiDom\Query;
use \DiDom\Element;

define('BASE_URL','http://m.24h.com.vn');

// Initialize
$database = new medoo([
    'database_type' => 'mysql',
    'database_name' => 'news',
    'server' => 'localhost',
    'username' => 'root',
    'password' => 'mau0954010058',
    'charset' => 'utf8'
]);
// $database->set_charset("utf8");

$root = 'https://www.24h.com.vn/tin-tuc-trong-ngay-c46.html';

if(get_data($root,$content)){
    save_all_post($content);
}else{
    echo 'Cannot get data for this page ' . PHP_EOL;
}

function save_all_post($html){
    $dom = new Document();
    $dom->load($html);
    // print_r($dom);
    // print_r('test', $dom->find('#block'));
    $block = $dom->find('div[class=postx]')[0];
    // print_r($block);
    // $block = $dom->find('div[id=block]')[0];
    
    $newsItems = $block->find('article[class^=bxDoiSbIt]');
    // print_r($newsItems);
    
    if(isset($newsItems) && count($newsItems) > 1){
            for($i = 1; $i < count($newsItems); ++$i){
            $newsItem = $newsItems[$i];
            // print_r($newsItem->find('span[class^=nwsTit]')[0]->find('a')[0]->text());
            $title = $newsItem->find('span[class^=nwsTit]')[0]->find('a')[0]->text();
            echo ($title);
            $thumb = $newsItem->find('span[class^=imgFlt]')[0]->find('img')[0]->getAttribute('src');
            echo ($thumb);
            $link = $newsItem->find('span[class^=nwsTit]')[0]->find('a')[0]->getAttribute('href');
            echo ($link);
            $description = $newsItem->find('span[class^=nwsSp]')[0]->text();
            echo ($description);
            $content = get_content($link);

            $post = array();  
            $post['post_title'] = $title;
            $post['post_thumb']  = $thumb;
            $post['post_link'] = $link;
            $post['post_description'] = $description;
            $post['post_content'] = $content;
            
            insert_post($post); 
            
           echo 'time: '.$description.' thumb: '.$thumb.' title: '.$title.' link: '.$link.'<br />';
           echo 'content: '. htmlspecialchars($content);
        }
    }
    
    
    
}

function get_content($link){
    if(get_data($link, $content)){
         $dom = new Document();
         $dom->load($content);
         
         $html = $dom->find('article[class^=nwsHt]')[0]->html();
         return $html; 
    }
    
    return '';
}



function get_data($link , &$content){
    $curl = new Curl();
    
    echo 'Start craw: ' .$link.PHP_EOL;
    
    $curl->setTimeout(60);
    $curl->setConnectTimeout(60);
    
    $curl->get($link);
    
    $error = $curl->error;
    
    if(!$error){
        $content = $curl->response;
        echo 'End craw: ' .$link.' Sucess !!!'.PHP_EOL;
    }else{
        echo 'End craw: ' .$link.' Failt !!!'.PHP_EOL;
    }
    
    $curl->close();
    
    return !$error;
}



function insert_post($post){
    $title = $post['post_title'];
    $thumb = $post['post_thumb'];
    $link = $post['post_link'];
    $description = $post['post_description'];
    $content = $post['post_content'];
    
    $sql = "INSERT INTO post (post_title, post_thumb, post_link, post_description, post_content)".
     " SELECT '$title', '$thumb', '$link', '$description', :html FROM DUAL".
     " WHERE NOT EXISTS (SELECT * FROM post".
     " WHERE post_link = '$link') LIMIT 1";
     
    //echo $sql;
    
    global $database;
    
    $sth = $database->pdo->prepare($sql); 
    $sth->bindValue(':html', $content, PDO::PARAM_STR);
    $sth->execute();
    
    $data = $database->query("SELECT * FROM post WHERE post_link = '$link'")->fetch();
    
    return $data;
}


?>