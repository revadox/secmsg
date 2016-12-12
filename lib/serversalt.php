<?php

// Generate a large random hexadecimal salt.
function generateRandomSalt()
{
    $randomSalt='';
    if (function_exists("mcrypt_create_iv"))
    {
        $randomSalt = bin2hex(mcrypt_create_iv(256, MCRYPT_DEV_URANDOM));
    }
    else // fallback to mt_rand()
    {
        for($i=0;$i<16;$i++) { $randomSalt.=base_convert(mt_rand(),10,16); }
    }
    return $randomSalt;
}


function getServerSalt()
{
    $saltfile = 'data/salt.php';
    if (!is_file($saltfile))
        file_put_contents($saltfile,'<?php /* |'.generateRandomSalt().'| */ ?>',LOCK_EX);
    $items=explode('|',file_get_contents($saltfile));
    return $items[1];

}

?>