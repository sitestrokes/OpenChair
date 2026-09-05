<html>
<body>
<div style="width:40%;margin:0 auto;font-size: 16px;">
<div><br></div>
<div>Hello,</div>
<div><br></div>
<div>I am <?= sprintf($first_name) ?> and this is my website\online directory.</div>
<div><br></div>
<div><a  class="ql-link" href="<?= $directory_site ?>" rel="noopener noreferrer" target="_blank"><?= $directory_site ?></a></div>
<div><br></div>
<div>You can enhance your listings by adding  profile page, photographs, reviews, etc.</div>
<div><br></div>
<div>And many other advantages!</div>
<div><br></div>
<div><strong>you can  login here</strong></div>
<div><br></div>
<div><strong>Login Url:&nbsp;<a class="ql-link" href="<?= base_url('login') ?>" rel="noopener noreferrer" target="_blank"><?= base_url('login?wl='.$userId) ?></a></strong></div>
<div><br></div>
<div><strong>Username:&nbsp;&nbsp;<?php echo sprintf($identity);?></strong></div>
<div><br></div>
<div><strong>Password:&nbsp;&nbsp;<?php echo sprintf($password);?></strong></div>
<div><br></div>
<div>So, take control of how visitors see you and attract them to your business!</div>
<div><br></div>
<div>Cheers,</div>
<div><br></div>
<div><br></div>
</div>
</body>
</html>