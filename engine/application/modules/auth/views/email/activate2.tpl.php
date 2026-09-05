	<html>
<body>

<div class="mainDiv" style="margin: 0 auto;max-width: 600px;min-width: 320px;width: 320px;width: calc(28000% - 167400px);word-wrap: break-word;word-break: break-word;box-shadow: 2px 10px 7px 0pc grey">
	<div class="header" style="border-collapse: collapse;display: table;width: 100%;background-color: #ffffff;line-height: inherit;">
	    <div class="inner" style="box-shadow: 0px 0px 0px 2px #80808073;text-align: left;color: #353638;font-size: 17px;line-height: 26px;font-family: Merriweather,Georgia,serif;">
			<div style="padding: 15px 139px;background: #333547;">
				<div style="font-size:12px;font-style:normal;font-weight:normal;line-height:19px;margin-bottom:0px" align="center">
				  <a href="">
				    <img src="<?= base_url() ?>assets/images/ezdlogo.png" style="border:0;display:block;height:auto;width:78%;max-width:900px" width="560">
				  </a>
				</div>
			</div>
		    <div style="margin-left:20px;margin-right:20px;margin-top: 12px;">
	        	<div style="vertical-align:middle">
	        		<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">Hey, <br>Thanks for Purchasing EzDeals</p>
					<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			            Welcome to the Engageleads community. Thanks a lot for showing your trust in EzDeals. I am sure this product will help you to take your business to the next level.
			        </p>

			        <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			          We have 2 apps in this software both are integrated with each other.
			        </p>

			        <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			          Here you can find access of both apps:
			        </p>

			        <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			          <b>EzDeals Access Url:</b>
			        </p>

			          <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Login Url </b>: <a href="https://appezdeals.com/"><b>https://appezdeals.com/</b></a> </p>
			       	<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Username</b>:<?php echo sprintf($identity);?></p>

					<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Password</b>: <?php echo sprintf($password);?></p>

					<?php
					$accessArr = explode(",", $access);
					if(in_array(2, $accessArr) | in_array(3, $accessArr) || in_array(4, $accessArr) || in_array(6, $accessArr) || in_array(7, $accessArr)){
					?>
					 <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			          <b>Ezleadz Access Url:</b>
			        </p>

			          <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Login Url </b>: <a href="https://ezleadz.app/"><b>https://ezleadz.app/</b></a> </p>
			       	<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Username</b>:<?php echo sprintf($identity);?></p>

					<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif"><b>Password</b>: <?php echo sprintf($password);?></p>
					<?php } ?>
					
	        		<p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			            <span>=======================</span><br>
			            Tutorial Videos<br>
			            <span>=======================</span>  
			          </p>
			          <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			            We have added tutorial videos in EzDeals/Ezleadz Members area.
			          </p>

			          <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			            <span>=====================</span><br>
			            Support<br>
			            <span>=====================</span>  
			          </p>

			           <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			            If you any queries you can reach out to us through our support desk here:

			            <a href="https://engageleads.zendesk.com/">https://engageleads.zendesk.com/</a>
			          </p>

			         
			          <p style="margin-top:20px;margin-bottom:20;font-family:open sans,sans-serif">
			          	Thanks Again, <br><br>
			            Thanks,<br>
						Prady & Nakul<br>
						EzDeals Creators<br>
						Engageleads Team<br>
			          </p>
	        	</div>
	        </div>
			<div class="bottom" style="margin-top:12px;background:gray;color: white;padding: 14px 0;text-align: center;margin-bottom: 13px;">
				<p style="font-family:open sans,sans-serif">Made with <img style="vertical-align:middle;" src="<?= base_url() ?>assets/images/heart.png"> by Engageleads, INC. | All Rights Reserved 2021</p>
			</div>
	  	</div>
	</div>
</div>


</body>
</html>