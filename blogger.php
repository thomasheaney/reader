<?php

//https://en.wikipedia.org/wiki/Old_Sarum_Castle


$blogs = array();
$blogs["341832496341749426"] = "Somewhere The Tea's Getting Cold";
$blogs["4122834195131500531"] = "The Black Castle (WarlordPaul)";
$blogs["4266200265116124990"] = "Goblin Lee";
$blogs["3283315887973123007"] = "Realm of Chaos 80s";
$blogs["5135517998184661387"] = "Where The Sea Pours Out";
$blogs["2812208972474813089"] = "Give Em Lead";
$blogs["59617730633617003"] = "Tales From The Big Board";
$blogs["1958522416503442248"] = "Coins and Scrolls";
$blogs["2221418482531324115"] = "Lead Plague";
$blogs["3298356674700006690"] = "The Leadpile";


$blogId = isset($_GET["blogid"]) && !empty($_GET["blogid"]) ? $_GET["blogid"] : null;

$postId = isset($_GET["postid"]) && !empty($_GET["postid"]) ? $_GET["postid"] : null;

$label = isset($_GET["label"]) && !empty($_GET["label"]) ? $_GET["label"] : null;

$content = "";


if(!isset($_GET["blogid"])){
  foreach($blogs as $k => $v){
    echo '<li><a href="./blogger.php?blogid=' . $k .'">' . $v. '</a></li>';
  }
  die();
}




$fileContent = getBlog($blogId);



  

if (!empty($postId)){
#region if postid set then get post from cahced json file
######################################

  $postFile = './' .$blogId . '/' . $postId. '/' . $postId . '.htm';


  if (!file_exists($postFile)) {
    $fileContent = getBlogPost($blogId, $postId, $postFile);
  }
  else { 
    $fileContent = file_get_contents($postFile);
  }
  

  echo '<html>

  <head>
    <link rel="stylesheet" href="./reader.css" media="all" />
  </head>

  <body>';

  echo $fileContent;
  echo '</body>

  </html>';
#endregion if postid set then get post from cahced json file
######################################
######################################
} 
else {
  print_r("<pre>");
  //print_r($fileContent);

  print_r("</pre>");
  $blogPosts = listBlogPosts($blogId);
  echo $blogPosts;

}

function getBlog($blogId){



$fileContent = '';
$folderPath = './' .$blogId . '/';
$filePath = $folderPath  . $blogId . '.json';

if (!file_exists($folderPath)) {
  // Folder does not exist, so create it
  if (mkdir($folderPath, 0777, true)) {
      // You can also create a new file or copy a file here if needed
  } else {
      echo "Failed to create folder.\n";
  }
} else {
  // Folder exists, try to read the file
  if (file_exists($filePath)) {

      $fileContent = json_decode(file_get_contents($filePath),true);
 
      

  } else {
      echo "File does not exist.\n";
      $fileData = getBlogPosts($blogId);

      if(isset($fileData["nextPageToken"])){
        echo "Next - " . $fileData["nextPageToken"] . "<br/>";
        $nextPage = getBlogPosts($blogId,$fileData["nextPageToken"]);
        $nextPageItems = $nextPage["items"];

        foreach($nextPageItems as $nextPageItem){
          $fileData["items"][] = $nextPageItem;
        }

      }


      if (file_put_contents($filePath, json_encode($fileData))) {
        echo "Data written to file successfully.\n";
        $fileContent = $fileData;
    } else {
        echo "Failed to write data to file.\n";
    }
  }
}

  return $fileContent;
}

function getBlogPost($blogId, $postId, $postFile){

  global $fileContent;

  $folderPath = './' .$blogId . '/'. $blogId . '.json';


  // Get post from cached file
  foreach ($fileContent["items"] as $item){
  if ($item["id"] == $postId){
    $post = $item;
    break;
  }
  }

 //Clean file

  $toRemove = array( '<p>&nbsp;</p>', '<p></p>');
  $content = str_replace($toRemove, '', $post["content"]);
  $publishedDate = $post["updated"];

  $date=date_create($publishedDate);
  $formattedDate =  date_format($date,"d M Y");
 

  $dom = new DOMDocument;     
  $dom->loadHTML($content);
  $xpath = new DOMXPath($dom);

  $nodes = $xpath->query('//*');  // Find elements with a style attribute
  $imageList = array();

  foreach ($nodes as $node) {              // Iterate over found elements
    $node->removeAttribute('style');    // Remove style attribute
    $node->removeAttribute('width');
    $node->removeAttribute('height');
    $node->removeAttribute('class');

    if($node->tagName == "body"){

      $heading = $dom->createElement('h1', $post["title"] );
      $date = $dom->createElement('p', "Published " . $formattedDate  );

      $firstChild = $node->firstChild;
      
      $node->insertBefore($heading, $firstChild);
      $node->insertBefore($date, $firstChild);

    }

    if($node->tagName == "a"){
      $images = $xpath->query('img', $node); // Query for <img> tags within the context of the current <a> node
      if ($images->length == 1){
        $imageNode = $images->item(0); // Get the single image node
        processImage($imageNode, $blogId, $postId, $imageList); // Process this image
        $node->parentNode->replaceChild($imageNode, $node);
      
      }
    }
    else if($node->tagName == "img"){
      processImage($node, $blogId, $postId, $imageList); // Process this image
    }

  }

  $file = $dom->saveHTML();

  file_put_contents($postFile, $file);

  return $file;
}

function listBlogPosts($blogId){

  global $blogs,$fileContent,$label;

  $content = '<html>

  <head>
    <link rel="stylesheet" href="./reader.css" media="all" />
  </head>

  <body>
    <h1>' . $blogs[$blogId] . '</h1>';

  $tagList = array();



  $contentPosts = '<ul class="blog-posts">';
  

  foreach($fileContent["items"] as $entry){

    $entryLabels = array();

    $showEntry = !empty($label) ? false : true;

    if(isset($entry["labels"])){
      foreach($entry["labels"] as $labelItem){

        if($label == $labelItem) {
          $showEntry = true;
        }

        // Add to master label list
        if(isset($tagList[$labelItem])){
          $tagList[$labelItem] = $tagList[$labelItem] +1;
        }
        else {
          $tagList[$labelItem] = 1;
        }

        // add to entry label list

        $entryLabels[] = '<a class="entry-label" href="./blogger.php?blogid=' . $blogId . '&label=' . $labelItem . '">' . $labelItem . '</a>';
      }


    }

   
    $labels = !empty($entryLabels) ? " " . implode('',$entryLabels) : '';
    $title = !empty($entry["title"]) ? $entry["title"] : $entry["id"];

    if ($showEntry){
      $contentPosts .= '<li class="blog-posts-item"><a class="blog-post-link" href="./blogger.php?blogid=' . $blogId . '&postid=' . $entry["id"] .'">' . $title . '</a><span class="entry-labels">' . $labels . '</span></li>';
    }
     

  }


    $contentLabels ='<ul class="blog-labels">';

    ksort($tagList);

    foreach($tagList as $k=>$v){
      $contentLabels .= '<li class="blog-label"> <a href="./blogger.php?blogid=' . $blogId . '&label=' . $k . '">' . $k . '(' . $v . ')</a></li>';

      
    }
    $contentLabels .= "</ul>";
    $content .= $contentLabels;
    $content .= $contentPosts;


  $content .= '</body></html>';

  return $content;
}

function getBlogPosts($blogId, $nextPageToken = null){


  if(!empty($nextPageToken)){
    $url = "https://www.googleapis.com/blogger/v3/blogs/" . $blogId . "/posts?key=AIzaSyCMMhsZHmPQrfNyhXAdqU2Wuq8SpGWaI9Q&maxResults=500&pageToken=" . $nextPageToken;
  }
  else {
    $url = "https://www.googleapis.com/blogger/v3/blogs/" . $blogId . "/posts?key=AIzaSyCMMhsZHmPQrfNyhXAdqU2Wuq8SpGWaI9Q&maxResults=500";
  }
  

  // Initialize cURL session
  $ch = curl_init();

  // Set the URL and other options
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  // Execute the session and store the response
  $response = curl_exec($ch);

  // Close the cURL session
  curl_close($ch);

  // Decode the JSON response
  $data = json_decode($response, true);



  if (isset($data["nextPageToken"])){

  }
  return $data;
}

function downloadImage($imageUrl, $blogId, $postId) {

  $file_parts = pathinfo($imageUrl);
 

  $folderPath = './' .$blogId . '/' . $postId . '/';

// Extract filename from URL
  $filename = substr(basename($imageUrl), 0, 30);

  $filePath = $folderPath . str_replace('%20', '_', $filename);

  // Ensure the folder exists
  if (!file_exists($folderPath)) {
      mkdir($folderPath, 0777, true);
  }

  if(file_exists($filePath)){

    return $filePath;   
  }
  
  

  // Initialize cURL session
  $ch = curl_init($imageUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  $imageData = curl_exec($ch);
  $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
  curl_close($ch);
  //echo "Downloading image" . $imageUrl . ":" . $blogId . ":" . $postId . " </br>";
  // Check if the download was successful
  if ($httpStatusCode == 200 && $imageData !== false) {
      file_put_contents($filePath, $imageData);
      return $filePath;
  } else {
      return false;
  }
}

function processImage($imageNode, $blogId, $postId, &$imageList) {
  $src = $imageNode->getAttribute('src');
  $src = str_replace("s320", "s2400", $src); // Enhance image resolution
  $src = str_replace("%25", "%", $src); // Deal with double encoding
  $src = urldecode($src);
  $imageNode->setAttribute('src', $src);
  $imageNode->setAttribute('loading', "lazy");
  $imageNode->setAttribute('class', "full-width");

  $filePath = downloadImage($src, $blogId, $postId);
  if (!empty($filePath)) {
      $imageNode->setAttribute('src', $filePath); // Update src to local path after downloading
  }
  $imageList[] = $src; // Add to image list for tracking or further use

}

?>