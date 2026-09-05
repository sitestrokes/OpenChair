<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// error_reporting(0);
$uAccess = explode(",", $user_row->access);
$product_access = $this->config->item('access_specifier');
?>

<!-- Heading -->
<div class="headrow">
    <div class="pagehead">
        <h1>Manage Users</h1>
        <div class="psub">Search, provision, and govern every credential that touches the system.</div>
    </div>
</div>

<!-- Toolbar -->
<div class="headrow" style="margin-top:18px">
    <form action="<?= site_url('users') ?>" method="post" style="margin:0">
        <input class="input" style="max-width:360px" name="search" type="text" placeholder="Search by name or email…">
    </form>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <span style="font-family:var(--mono);font-size:12px;color:var(--faint)">
            <?= $total_row ?> personnel on file
        </span>
        <a href="<?= base_url('useroperation') ?>" class="btn pri">
            <i class="fa fa-plus"></i> New Personnel File
        </a>
    </div>
</div>

<!-- Table card -->
<div class="card" style="margin-top:22px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="border-bottom:1px solid var(--line)">
                <th class="lbl" style="text-align:left;padding:13px 16px">#</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Full Name</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Email</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Status</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Clearance</th>
                <?php if($user_id == 1): ?>
                    <th class="lbl" style="text-align:left;padding:13px 16px">Parent</th>
                <?php endif; ?>
                <th class="lbl" style="text-align:left;padding:13px 16px">Filed</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Last Seen</th>
                <th class="lbl" style="text-align:left;padding:13px 16px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if(isset($users) && !empty($users)):
            foreach($users as $key1 => $user):
                $u = $user['allusers'];
                $isActive = ($u->active == 1);

                // User type
                $usertype = '';
                if(isset($u->access)){
                    $access = explode(',', $u->access);
                    foreach($access as $value){
                        $usertype = $product_access[$value];
                        if($value == 822) $usertype = 'User';
                        elseif($value == 833) $usertype = 'ListingUser';
                    }
                }
                if($u->parent_user_id != 1) $usertype = 'Sub User: '.$usertype;
                $usertype = str_ireplace('AI LocalFunnels','',$usertype);

                // Lock icon
                $lock = $isActive ? 'fa-lock' : 'fa-unlock-alt';
                $lockClass = $isActive ? 'is-locked' : 'is-unlocked';

                // Parent user
                if($u->parent_user_id != 1){
                    $wh = array('id' => $u->parent_user_id);
                    $ud = $this->Usermodel->getUser($wh);
                    $parent_user_name = $ud['user']->first_name.' '.$ud['user']->last_name;
                    $pstatus = $ud['user']->active;
                } else {
                    $parent_user_name = 'Admin';
                    $pstatus = 1;
                }

                $date1 = $this->app_lib->utc_to_local(gmdate('Y-m-d H:i:s', $u->created_on), 'd M Y');
                $date2 = $this->app_lib->utc_to_local(gmdate('Y-m-d H:i:s', $u->last_login), 'd M Y');
        ?>
            <tr style="border-bottom:1px solid var(--line)">
                <td style="padding:13px 16px;font-family:var(--mono);font-size:12px;color:var(--faint)"><?= str_pad($key1+1, 2, '0', STR_PAD_LEFT) ?></td>
                <td style="padding:13px 16px"><?= $u->first_name ?> <?= $u->last_name ?></td>
                <td style="padding:13px 16px;font-family:var(--mono);font-size:12px;color:var(--txt2)"><?= $u->email ?></td>
                <td style="padding:13px 16px">
                    <span class="chip <?= $isActive ? 'green' : 'mute' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                </td>
                <td style="padding:13px 16px"><?= $usertype ?></td>
                <?php if($user_id == 1): ?>
                    <td style="padding:13px 16px"><span class="chip <?= $pstatus == 1 ? 'green' : 'mute' ?>"><?= $parent_user_name ?></span></td>
                <?php endif; ?>
                <td style="padding:13px 16px;font-family:var(--mono);font-size:12px;color:var(--faint)"><?= $date1 ?></td>
                <td style="padding:13px 16px;font-family:var(--mono);font-size:12px;color:var(--faint)"><?= $date2 ?></td>
                <td style="padding:13px 16px">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                        <a href="javascript:void(0);" class="btn gho sm resetAccount" data-userid="<?= $u->id ?>" data-email="<?= $u->email ?>" title="Reset Password"><i class="fas fa-sync-alt"></i></a>
                        <a href="<?= base_url('useroperation/'.$u->id) ?>" class="btn gho sm" data-userid="<?= $u->id ?>" data-action="edit" title="Edit User"><i class="fa fa-edit"></i></a>
                        <a href="javascript:void(0);" class="btn gho sm deleteSelected2" data-userid="<?= $u->id ?>" title="Delete User" style="border-color:#5a2630;color:#ffb4b4"><i class="fa fa-trash"></i></a>
                        <a href="javascript:void(0);" class="btn gho sm toggleuser <?= $lockClass ?>" data-status="<?= $u->active ?>" data-userid="<?= $u->id ?>" title="Lock/Unlock"><i class="fa <?= $lock ?>"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach;
        else: ?>
            <tr>
                <td colspan="<?= ($user_id == 1) ? 9 : 8 ?>" style="padding:60px 16px;text-align:center;color:var(--mute)">
                    No personnel files found in the register. Open a new file to begin.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if (!empty(trim((string) (isset($links) ? $links : '')))): ?>
<style>
  .pg{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:6px;margin-top:22px}
  .pg .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 12px;
    font-family:var(--mono);font-size:13px;font-weight:600;line-height:1;border-radius:var(--r1);text-decoration:none;
    border:1px solid var(--line);background:var(--surface);color:var(--txt2);transition:border-color .15s,color .15s,background .15s}
  .pg a.page-link:hover{border-color:var(--coral);color:var(--coral2);background:var(--coraldim)}
  .pg .pg-cur{background:linear-gradient(135deg,var(--coral),var(--coral2));border-color:transparent;color:#fff;cursor:default}
</style>
<?= $links ?>
<?php endif; ?>

<script type="text/javascript">
// App-styled toast — defined in ic_shell/layout.php (icToast). icConfirm() is the
// app's styled confirm dialog (Promise<bool>). Fallbacks only if the shell isn't loaded.
function uToast(msg, isErr){ icToast(msg, !!isErr); }
// The real icConfirm lives in ic_shell/_ic_dialogs.php, which the shell includes
// AFTER this page's markup. Installing a native fallback now would pre-empt it
// (that partial bails if window.icConfirm already exists), so only fall back on
// window 'load' — by then the real dialog has registered and this is a no-op.
window.addEventListener('load', function(){
  if (typeof window.icConfirm !== 'function'){
    window.icConfirm = function(o){ o=o||{}; return Promise.resolve(window.confirm((o.title?o.title+'\n\n':'')+(o.message||''))); };
  }
});
$(document).ready(function()
  {
	   $('#helpsupport').click(function(){
			$('#helpsupportBox').modal('show');
	   });


	   $(".toggleuser").click(function(){

	   	 var userid = $(this).attr('data-userid');
	   	 var userStatus = $(this).attr('data-status');
	   		if (userStatus == 1 ) {

	   			var status = 0;
	   			$(this).attr('data-status',status );
	   			$(this).find('i').removeClass('fa-lock');
	   			$(this).find('i').removeClass('text-primary');
	   			$(this).find('i').addClass('fa-unlock-alt');
	   			$(this).find('i').addClass('text-danger');
	   			var msg = 'Account has been Deactivated.';
	   		}else{
	   			var status = 1;
	   			$(this).attr('data-status',status );
	   			$(this).find('i').addClass('fa-lock');
	   			$(this).find('i').addClass('text-primary');
	   			$(this).find('i').removeClass('fa-unlock-alt');
	   			$(this).find('i').removeClass('text-danger');
	   			var msg = ' Account has been Activated.';
	   		}

	   		$.ajax({
			type: "POST",
			url: "<?= site_url('users/userStatus') ?>",
			data: { userid, status },
			dataType: 'text',
			success: function(r){
					uToast(msg);
					setTimeout(function(){ location.reload(); }, 900);
				}

			});


	   });


		//Activate Account Manually click function
		$(".activateAcc").click(function(){
			$userid = $(this).attr('data-userid');
			icConfirm({ title:'Activate this user?', message:'The account will be activated manually.', confirmText:'Activate' }).then(function(ok){
				if (!ok) return;
				$.ajax({
					type: "POST",
					url: "<?php echo site_url('Users/makeActive') ?>",
					data: 'id=' + $userid,
					cache: false,
					success: function(r){
						uToast("The user's account has been activated.");
						setTimeout(function(){ location.reload(); }, 900);
					}
				});
			});
		});

		// Delete All users Click Function
		$(".del_all").on('click', function(e) {
			e.preventDefault();
			icConfirm({ title:'Delete selected users?', message:'This can\'t be undone — the selected users will be permanently removed.', confirmText:'Delete', danger:true }).then(function(ok){
				if (!ok) { $('#selecctall').prop('checked', false); return; }
				var checkValues = $('.chk_sel:checked').map(function(){ return $(this).val(); }).get();
				$.each( checkValues, function( i, val ) { $("#"+val).remove(); });
				$.ajax({
					type: "POST",
					url: "<?php echo site_url('deleteSelectedUsers') ?>",
					data: 'ids=' + checkValues,
					cache: false,
					success: function(r){
						uToast('Selected users deleted.');
						setTimeout(function(){ location.reload(); }, 900);
					}
				});
			});
		});
		// Delete selected user click function
		$(".deleteSelected2").click(function(){
			$userid = $(this).attr('data-userid');
			var thisbt = $(this);
			icConfirm({ title:'Delete this user?', message:'This can\'t be undone — the user will be permanently removed.', confirmText:'Delete', danger:true }).then(function(ok){
				if (!ok) return;
				$.ajax({
					type: "POST",
					url: "<?php echo site_url('deleteUser') ?>",
					data: 'id=' + $userid,
					cache: false,
					success: function(r){
						uToast('User deleted.');
						var row = thisbt.closest('tr');
						if (row.length) { row.slideUp(200); }
						// Reload sits outside the slideUp callback so a refresh is guaranteed
						// even if the row can't be found/animated.
						setTimeout(function(){ location.reload(); }, 700);
					}
				});
			});
		});
 });
</script>
<script>
$(document).ready(function()
	{
		//reset account
		$('.resetAccount').click(function(){

			var userid = $(this).attr('data-userid');
			var email = $(this).attr('data-email');
			icConfirm({
				title:'Reset this user\'s password?',
				message:'The user will be emailed and their current password will stop working. Continue?',
				confirmText:'Reset password', danger:true
			}).then(function(ok){
				if (!ok) return;
				$.ajax({
					url: '<?php echo base_url('resetUser');?>',
					type: 'post',
					dataType: 'json',
					data: {userID:userid, email:email}
				}).done(function(ret){
					if( ret.responseCode == 0 ) {
						uToast('Could not reset the password. Please try again.', true);
					} else {
						// Show the new password in a styled dialog so the admin can copy it.
						icConfirm({
							title:'Password reset',
							message:'New password: ' + ret.responseHTML + '  —  copy it now to share with the user.',
							confirmText:'Done'
						});
					}
				});
			});



        });


		function isValidEmailAddress(emailAddress) {
		    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
		    return pattern.test(emailAddress);
		};
});

function checkallsettings(id){
	 if(id=='check_all'){
	var check = jQuery('#'+id).is(":checked");
	//alert(check);
	if(check == true) {

		jQuery('.chk_sel').prop('checked', true);

	  }else{

		 jQuery('.chk_sel').prop('checked', false);

	  }
  }
}
</script>
