<?php
// Implements the del.icio.us API request for all a user's posts, optionally filtered by tag.

// del.icio.us behavior:
// - doesn't include the filtered tag as an attribute on the root element (we do)

// Force HTTP authentication first!
require_once 'httpauth.inc.php';
require_once '../header.inc.php';

// set user as logged in so private bookmarks are exported
$loggedon = true;

$bookmarkservice =& ServiceFactory::getServiceInstance('BookmarkService');
$userservice     =& ServiceFactory::getServiceInstance('UserService');

// Check to see if a tag was specified.
if (isset($_REQUEST['tag']) && (trim($_REQUEST['tag']) != ''))
    $tag = trim($_REQUEST['tag']);
else
    $tag = NULL;

// Get the posts relevant to the passed-in variables.
$bookmarks =& $bookmarkservice->getBookmarks(0, NULL, $userservice->getCurrentUserId(), $tag);

$currentuser = $userservice->getCurrentUser();
$currentusername = $currentuser[$userservice->getFieldName('username')];

// Set up the XML file and output all the posts.
header('Content-Type: text/xml');
echo '<?xml version="1.0" standalone="yes" ?'.">\r\n";
echo '<posts update="'. gmdate('Y-m-d\TH:i:s\Z') .'" user="'. htmlspecialchars($currentusername) .'"'. (is_null($tag) ? '' : ' tag="'. htmlspecialchars($tag) .'"') .">\r\n";

foreach($bookmarks['bookmarks'] as $row) {
    if (is_null($row['bDescription']) || (trim($row['bDescription']) == ''))
        $description = '';
    else
        $description = 'extended="'. filter($row['bDescription'], 'xml') .'" ';

    $taglist = '';
    if (count($row['tags']) > 0) {
        foreach($row['tags'] as $tag)
            $taglist .= convertTag($tag) .' ';
        $taglist = substr($taglist, 0, -1);
    } else {
        $taglist = 'system:unfiled';
    }
    // The privacy setting in scuttle is to set bStatus to 2 in the database.
    if(trim($row['bStatus']) == '2') {
        $shared = "no";
    } else {
        $shared = "yes";
    }

    echo "\t<post href=\"". filter($row['bAddress'], 'xml') .'" shared="' . $shared . '" description="'. filter($row['bTitle'], 'xml') .'" '. $description .'hash="'. md5($row['bAddress']) .'" tag="'. filter($taglist, 'xml') .'" time="'. gmdate('Y-m-d\TH:i:s\Z', strtotime($row['bDatetime'])) ."\" />\r\n";
}

echo '</posts>';
