<?php

$VERSION='1.0.0';
require_once "lib/serversalt.php";
require_once "lib/vizhash_gd_zero.php";

// trafic_limiter : Make sure the IP address makes at most 1 request every 10 seconds.
// Will return false if IP address made a call less than 10 seconds ago.
function trafic_limiter_canPass($ip)
{
    $tfilename='./data/trafic_limiter.php';
    if (!is_file($tfilename))
    {
        file_put_contents($tfilename,"<?php\n\$GLOBALS['trafic_limiter']=array();\n?>", LOCK_EX);
        chmod($tfilename,0705);
    }
    require $tfilename;
    $tl=$GLOBALS['trafic_limiter'];
    if (!empty($tl[$ip]) && ($tl[$ip]+10>=time()))
    {
        return false;
        // FIXME: purge file of expired IPs to keep it small
    }
    $tl[$ip]=time();
    file_put_contents($tfilename, "<?php\n\$GLOBALS['trafic_limiter']=".var_export($tl,true).";\n?>", LOCK_EX);
    return true;
}

// Constant time string comparison.

function slow_equals($a, $b)
{
    $diff = strlen($a) ^ strlen($b);
    for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
    {
        $diff |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $diff === 0;
}


/* Convert paste id to storage path.
   The idea is to creates subdirectories in order to limit the number of files per directory.
   (A high number of files in a single directory can slow things down.)
   eg. "f468483c313401e8" will be stored in "data/f4/68/f468483c313401e8"
  
*/
function dataid2path($dataid)
{
    return 'data/'.substr($dataid,0,2).'/'.substr($dataid,2,2).'/';
}

/* Convert paste id to discussion storage path.
   eg. 'e3570978f9e4aa90' --> 'data/e3/57/e3570978f9e4aa90.discussion/'
*/
function dataid2discussionpath($dataid)
{
    return dataid2path($dataid).$dataid.'.discussion/';
}

// Checks if a json string is a proper SJCL encrypted message.
// False if format is incorrect.
function validSJCL($jsonstring)
{
    $accepted_keys=array('iv','v','iter','ks','ts','mode','adata','cipher','salt','ct');

    // Make sure content is valid json
    $decoded = json_decode($jsonstring);
    if ($decoded==null) return false;
    $decoded = (array)$decoded;

    // Make sure required fields are present
    foreach($accepted_keys as $k)
    {
        if (!array_key_exists($k,$decoded))  { return false; }
    }
    return true;
}

// Delete a paste and its discussion.
// Input: $pasteid : the paste identifier.
function deletePaste($pasteid)
{
    // Delete the paste itself
    unlink(dataid2path($pasteid).$pasteid);

  
}

if (!empty($_POST['data'])) // Create new paste/comment
{
    

    header('Content-type: application/json');
    $error = false;

    // Create storage directory if it does not exist.
    if (!is_dir('data'))
    {
        mkdir('data',0705);
        file_put_contents('data/.htaccess',"Allow from none\nDeny from all\n", LOCK_EX);
    }

    // Make sure last paste from the IP address was more than 10 seconds ago.
    if (!trafic_limiter_canPass($_SERVER['REMOTE_ADDR']))
        { echo json_encode(array('status'=>1,'message'=>'Please wait 10 seconds between each post.')); exit; }

    // Make sure content is not too big.
    $data = $_POST['data'];
    if (strlen($data)>2000000)
        { echo json_encode(array('status'=>1,'message'=>'Paste is limited to 2 Mb of encrypted data.')); exit; }

    // Make sure format is correct.
    if (!validSJCL($data))
        { echo json_encode(array('status'=>1,'message'=>'Invalid data.')); exit; }

    // Read additional meta-information.
    $meta=array();

    // Read expiration date
    if (!empty($_POST['expire']))
    {
        $expire=$_POST['expire'];
        if ($expire=='5min') $meta['expire_date']=time()+5*60;
        elseif ($expire=='10min') $meta['expire_date']=time()+10*60;
        elseif ($expire=='1hour') $meta['expire_date']=time()+60*60;
        elseif ($expire=='1day') $meta['expire_date']=time()+24*60*60;
        elseif ($expire=='1week') $meta['expire_date']=time()+7*24*60*60;
        elseif ($expire=='1month') $meta['expire_date']=time()+30*24*60*60; 
        elseif ($expire=='1year') $meta['expire_date']=time()+365*24*60*60;
    }

    // Destroy the paste when it is read.
    if (!empty($_POST['burnafterreading']))
    {
        $burnafterreading = $_POST['burnafterreading'];
        if ($burnafterreading!='0' && $burnafterreading!='1') { $error=true; }
        if ($burnafterreading!='0') { $meta['burnafterreading']=true; }
    }


    if ($error)
    {
        echo json_encode(array('status'=>1,'message'=>'Invalid data.'));
        exit;
    }

    // Add post date to meta.
    $meta['postdate']=time();

    // We just want a small hash to avoid collisions: Half-MD5 (64 bits) will do the trick.
    $dataid = substr(hash('md5',$data),0,16);

    $is_comment = (!empty($_POST['parentid']) && !empty($_POST['pasteid'])); // Is this post a comment ?
    $storage = array('data'=>$data);
    if (count($meta)>0) $storage['meta'] = $meta;  // Add meta-information only if necessary.

    if ($is_comment) // The user posts a comment.
    {
        $pasteid = $_POST['pasteid'];
        $parentid = $_POST['parentid'];
        if (!preg_match('/\A[a-f\d]{16}\z/',$pasteid)) { echo json_encode(array('status'=>1,'message'=>'Invalid data.')); exit; }
        if (!preg_match('/\A[a-f\d]{16}\z/',$parentid)) { echo json_encode(array('status'=>1,'message'=>'Invalid data.')); exit; }

        unset($storage['expire_date']); // Comment do not expire (it's the paste that expires)
        unset($storage['opendiscussion']);
        unset($storage['syntaxcoloring']);

        // Make sure paste exists.
        $storagedir = dataid2path($pasteid);
        if (!is_file($storagedir.$pasteid)) { echo json_encode(array('status'=>1,'message'=>'Invalid data.')); exit; }

        // Make sure the discussion is opened in this paste.
        $paste=json_decode(file_get_contents($storagedir.$pasteid));
        if (!$paste->meta->opendiscussion) { echo json_encode(array('status'=>1,'message'=>'Invalid data.')); exit; }

        $discdir = dataid2discussionpath($pasteid);
        $filename = $pasteid.'.'.$dataid.'.'.$parentid;
        if (!is_dir($discdir)) mkdir($discdir,$mode=0705,$recursive=true);
        if (is_file($discdir.$filename)) // Oups... improbable collision.
        {
            echo json_encode(array('status'=>1,'message'=>'You are unlucky. Try again.'));
            exit;
        }

        file_put_contents($discdir.$filename,json_encode($storage), LOCK_EX);
        echo json_encode(array('status'=>0,'id'=>$dataid)); // 0 = no error
        exit;
    }
    else // a standard paste.
    {
        $storagedir = dataid2path($dataid);
        if (!is_dir($storagedir)) mkdir($storagedir,$mode=0705,$recursive=true);
        if (is_file($storagedir.$dataid)) // Oups... improbable collision.
        {
            echo json_encode(array('status'=>1,'message'=>'You are unlucky. Try again.'));
            exit;
        }
        // New paste
        file_put_contents($storagedir.$dataid,json_encode($storage), LOCK_EX);

        // Generate the "delete" token.
        // The token is the hmac of the pasteid signed with the server salt.
        // The paste can be delete by calling http://myserver.com/zerobin/?pasteid=<pasteid>&deletetoken=<deletetoken>
        $deletetoken = hash_hmac('sha1', $dataid , getServerSalt());

        echo json_encode(array('status'=>0,'id'=>$dataid,'deletetoken'=>$deletetoken)); // 0 = no error
        exit;
    }

echo json_encode(array('status'=>1,'message'=>'Server error.'));
exit;
}

function processPasteDelete($pasteid,$deletetoken)
{
    if (preg_match('/\A[a-f\d]{16}\z/',$pasteid))  // Is this a valid paste identifier ?
    {
        $filename = dataid2path($pasteid).$pasteid;
        if (!is_file($filename)) // Check that paste exists.
        {
            return array('','Message Does Not Exist, Has Expired or Has Been Deleted.','');
        }
    }
    else
    {
        return array('','Invalid data','');
    }

    if (!slow_equals($deletetoken, hash_hmac('sha1', $pasteid , getServerSalt()))) // Make sure token is valid.
    {
        return array('','Wrong Deletion Token. Paste Was Not Deleted.','');
    }

    // Paste exists and deletion token is valid: Delete the paste.
    deletePaste($pasteid);
    return array('','','Message Was Properly Deleted.');
}


function processPasteFetch($pasteid)
{
    if (preg_match('/\A[a-f\d]{16}\z/',$pasteid))  // Is this a valid paste identifier ?
    {
        $filename = dataid2path($pasteid).$pasteid;
        if (!is_file($filename)) // Check that paste exists.
        {
            return array('','Message Does not exist, has expired or has been deleted.','');
        }
    }
    else
    {
        return array('','Invalid data','');
    }

    // Get the paste itself.
    $paste=json_decode(file_get_contents($filename));

    // See if paste has expired.
    if (isset($paste->meta->expire_date) && $paste->meta->expire_date<time())
    {
        deletePaste($pasteid);  // Delete the paste
        return array('','Paste does not exist, has expired or has been deleted.','');
    }


    // We kindly provide the remaining time before expiration (in seconds)
    if (property_exists($paste->meta, 'expire_date')) $paste->meta->remaining_time = $paste->meta->expire_date - time();

    $messages = array($paste); // The paste itself is the first in the list of encrypted messages.
 
    $CIPHERDATA = json_encode($messages);

    // If the paste was meant to be read only once, delete it.
    if (property_exists($paste->meta, 'burnafterreading') && $paste->meta->burnafterreading) deletePaste($pasteid);

    return array($CIPHERDATA,'','');
}


$CIPHERDATA='';
$ERRORMESSAGE='';
$STATUS='';

if (!empty($_GET['deletetoken']) && !empty($_GET['pasteid'])) // Delete an existing paste
{
    list ($CIPHERDATA, $ERRORMESSAGE, $STATUS) = processPasteDelete($_GET['pasteid'],$_GET['deletetoken']);
}
else if (!empty($_SERVER['QUERY_STRING']))  // Return an existing paste.
{
    list ($CIPHERDATA, $ERRORMESSAGE, $STATUS) = processPasteFetch($_SERVER['QUERY_STRING']);    
}

require_once "lib/rain.tpl.class.php";
header('Content-Type: text/html; charset=utf-8');
$page = new RainTPL;
$page->assign('CIPHERDATA',htmlspecialchars($CIPHERDATA,ENT_NOQUOTES));  // We escape it here because ENT_NOQUOTES can't be used in RainTPL templates.
$page->assign('VERSION',$VERSION);
$page->assign('ERRORMESSAGE',$ERRORMESSAGE);
$page->assign('STATUS',$STATUS);
$page->draw('page');
?>
