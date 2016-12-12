<?php if(!class_exists('raintpl')){exit;}?><html>
<head>
<title>create</title>
<link type="text/css" rel="stylesheet" href="css/zcss.css" />



<script src="js/jq.js"></script>
<script src="js/sjcl.js"></script>
<script src="js/base64.js"></script>
<script src="js/rawdeflate.js"></script>
<script src="js/rawinflate.js"></script>
<script src="js/zbin.js"></script>


</head>
  <body>
    
      <div id="aboutbox">
          This is a safely and secure online service where the server has ZERO knowledge of DATA.
          Data is encrypted/decrypted <i>in the browser</i> using 256 bits AES key. 
         </br>
         ENJOY OUR SERVICE FREE...
</div>
    <h1>Secret MESSAGE</h1><br>
    <h2>Your Security is Our Concern.</h2><br>
   
    <noscript><div class="nonworking">Javascript is required for to work.<br>Sorry for the inconvenience.</div></noscript>
    
    <div id="status"><?php echo $STATUS;?></div>
    <div id="errormessage" style="display:none"><?php echo htmlspecialchars( $ERRORMESSAGE );?></div>
    <div id="toolbar">
    <button id="newbutton" onclick="window.location.href=scriptLocation();return false;" style="display:none;"><img src="img/icon_new.png" width="11" height="15" />New</button>
    <button id="sendbutton" onclick="send_data();return false;" style="display:none;"><img src="img/icon_send.png" width="18" height="15" />Send</button>
    <button id="clonebutton" onclick="clonePaste();return false;" style="display:none;"><img src="img/icon_clone.png" width="15" height="17" />Clone</button>
    <button id="rawtextbutton" onclick="rawText();return false;" style="display:none; "><img src="img/icon_raw.png" width="15" height="15" style="padding:1px 0px 1px 0px;"/>Raw text</button>
      <div id="expiration" style="display:none;">Expires: 
      <select id="pasteExpiration" name="pasteExpiration">
        <option value="5min">5 minutes</option>
        <option value="10min">10 minutes</option>
        <option value="1hour">1 hour</option>
        <option value="1day">1 day</option>
        <option value="1week">1 week</option>
        <option value="1month" selected="selected">1 month</option>
        <option value="1year">1 year</option>
        <option value="never">Never</option>
      </select>
      </div>
      <div id="remainingtime" style="display:none;"></div>
      <div id="burnafterreadingoption" class="button" style="display:none;">
         <input type="checkbox" id="burnafterreading" name="burnafterreading" />
         <label for="burnafterreading">Burn after reading</label>
      </div>
    
      <div id="dalu" class="button">

      <label  onclick="window.location.href='logout.php'" for="logout">logout</label>
 </div>
    </div>
    <div id="pasteresult" style="display:none;">
      <div id="deletelink"></div>
      <div id="pastelink"></div>
    </div>
    <div id="cleartext" style="display:none;"></div>
    <textarea id="message" name="message" cols="80" rows="25" style="display:none;"></textarea>
  
    <div id="cipherdata" style="display:none;"><?php echo $CIPHERDATA;?></div>
    <div class="footer">
  <footer>

    <p>Created By : <b> vdbazinga:))</b></p><br>
    Feel free to ASK any QUESTION<a href="mailto:vdbazinga79@gmail.com" target="_top" title="click here"><i><font color="#81FBFF"> i am here...</font><i></a>
   </footer>
</div>
  </body>
</html>
