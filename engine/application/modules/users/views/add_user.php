<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$firstname = $lastname = $email = '';
$access = array();
$isEdit = (isset($userData) && !empty($userData));
if($isEdit){
    $id = $userData['user']->id;
    $firstname = $userData['user']->first_name;
    $lastname = $userData['user']->last_name;
    $email = $userData['user']->email;
    $access = explode(',', $userData['user']->access);
}
$mainAccess = explode(",", $user_row->access);
$product_access = $this->config->item('access_specifier');
$pid = '1';
$oto3pid = '';
if(!empty($product_access[4])){
    $oto3pid = '4';
}
// The superadmin is user 1 (its access is '1,51', not '5'); treating it as a
// JV Admin hid the Agency level from the one account allowed to grant it.
$isAdmin = in_array(5, $mainAccess) || (int) $user_row->id === 1;
$isJvAdmin = in_array(51, $mainAccess) && !$isAdmin;
$showAccess = ($isAdmin||$isJvAdmin||in_array(9,$mainAccess)||in_array(94,$mainAccess)||in_array(91,$mainAccess)||in_array(7,$mainAccess)||in_array(71,$mainAccess)||in_array(72,$mainAccess));
?>

<style>
/* Users create/edit — access-level chips + header, scoped to the page. */
.uadd-back{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--txt2);
  background:var(--surface);border:1px solid var(--line2);border-radius:999px;padding:8px 16px;text-decoration:none;
  transition:border-color .14s,color .14s,background .14s}
.uadd-back:hover{border-color:var(--mute);color:var(--txt);background:var(--surface2)}
.uadd-back svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
/* Access groups: one full-width labeled section each, comfortable chip grid. */
.uacc-grp{margin-top:22px}
.uacc-grp:first-child{margin-top:0}
.uacc-head{display:flex;align-items:center;gap:9px;margin:0 0 11px}
.uacc-head .t{font-family:var(--disp);font-size:13px;font-weight:700;color:var(--txt)}
.uacc-head .c{font-family:var(--mono);font-size:10px;font-weight:700;color:var(--faint);background:var(--surface2);border-radius:999px;padding:2px 8px}
.uacc-chips{display:grid;grid-template-columns:repeat(auto-fill,minmax(248px,1fr));gap:10px}
.uchip{display:flex;align-items:center;gap:12px;padding:13px 15px;border:1.5px solid var(--line);border-radius:var(--r2);
  background:var(--surface);cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s;margin:0;font-weight:500;position:relative;min-height:52px}
.uchip:hover{border-color:var(--line2);box-shadow:0 10px 22px -18px rgba(90,50,35,.5)}
.uchip input{position:absolute;opacity:0;width:0;height:0}
.uchip .box{width:22px;height:22px;border-radius:7px;border:2px solid var(--line2);flex-shrink:0;display:grid;place-items:center;transition:.15s;background:var(--surface)}
.uchip .box svg{width:13px;height:13px;stroke:#fff;fill:none;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;opacity:0;transform:scale(.6);transition:.15s}
.uchip .lbl{font-family:var(--body);font-size:12.5px;font-weight:600;letter-spacing:0;text-transform:none;color:var(--txt);line-height:1.35}
.uchip input:checked ~ .box{background:linear-gradient(135deg,var(--coral),var(--coral2));border-color:transparent}
.uchip input:checked ~ .box svg{opacity:1;transform:none}
.uchip:has(input:checked){border-color:var(--coral);background:var(--coraldim)}
.uadd-avatar{width:52px;height:52px;border-radius:15px;background:radial-gradient(circle at 34% 30%,var(--coral),var(--coral2));
  display:grid;place-items:center;color:#fff;font-family:var(--disp);font-weight:700;font-size:22px;flex-shrink:0;box-shadow:0 10px 24px -12px rgba(240,117,94,.6)}
/* Note under the JV access group: explains the one-time API-key copy on create. */
.uacc-note{display:flex;gap:10px;margin:12px 1px 0;padding:10px 13px;font-size:12px;line-height:1.55;color:var(--txt2);
  background:var(--surface2);border:1px solid var(--line);border-left:2.5px solid var(--coral);border-radius:var(--r2)}
.uacc-note svg{width:16px;height:16px;flex-shrink:0;margin-top:1px;stroke:var(--coral);fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
.uacc-note b{color:var(--txt);font-weight:700}
</style>

<div class="headrow" style="align-items:center">
    <div class="pagehead" style="display:flex;align-items:center;gap:16px">
        <div class="uadd-avatar"><?= $isEdit ? html_escape(strtoupper(substr($firstname ?: $email ?: 'U', 0, 1))) : '+' ?></div>
        <div>
            <h1 style="margin:0"><?= $isEdit ? 'Edit User' : 'Create a new User' ?></h1>
            <div class="psub" style="margin-top:4px"><?= $isEdit ? 'Update this user\'s credentials and access levels.' : 'Provision a new user with credentials and access to the system.' ?></div>
        </div>
    </div>
    <a href="<?= site_url('users') ?>" class="uadd-back">
        <svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back to Users
    </a>
</div>

<div class="card pad" style="margin-top:20px">
    <div id="UserModal">
    <form role="form" action="" id="<?= $isEdit ? 'editUser' : 'addNewuser' ?>">
        <?php if($isEdit): ?>
            <input type="hidden" id="userID" name="userID" value="<?= html_escape($id) ?>">
        <?php endif; ?>

        <div class="modal-alerts"></div>

        <div class="cardhead" style="margin-bottom:14px"><h3>Credentials</h3></div>

        <div class="grid2">
            <div class="field">
                <label>First Name</label>
                <input type="text" class="input" value="<?= html_escape($firstname) ?>" id="firstname" name="firstname" placeholder="Enter first name…">
            </div>
            <div class="field">
                <label>Last Name</label>
                <input type="text" class="input" value="<?= html_escape($lastname) ?>" id="lastname" name="lastname" placeholder="Enter last name…">
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" class="input" value="<?= html_escape($email) ?>" <?= empty($email)?'':'readonly' ?> id="email" name="email" placeholder="Enter email address…">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" class="input" value="" id="password" name="password" placeholder="Enter password…">
            </div>
        </div>

        <?php if($showAccess): ?>
        <div class="cardhead" style="margin:28px 0 4px"><h3>Access levels</h3></div>
        <div style="font-size:12.5px;color:var(--mute);margin-bottom:18px">
          <?= $isAdmin
              ? 'FE is required. Add Agency for an account that runs its own team (its members share its pipeline).'
              : 'Team members get FE access and share your agency\'s pipeline, share links and digests.' ?></div>
        <?php
        // One chip renderer + label overrides (some codes show custom text).
        $lbl_override = []; // all funnel codes are labelled in access_specifier
        $render_chip = function ($code) use ($product_access, $access, $lbl_override) {
            if (empty($product_access[$code]) && !isset($lbl_override[$code])) { return; }
            $text = isset($lbl_override[$code]) ? $lbl_override[$code] : $product_access[$code];
            $on   = in_array($code, $access) ? 'checked' : '';
            echo '<label class="uchip" for="' . html_escape($code) . '">'
               . '<input name="access[]" type="checkbox" ' . $on . ' class="usercheck" id="' . html_escape($code) . '" value="' . html_escape($code) . '" data-access="' . html_escape($code) . '">'
               . '<span class="box"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>'
               . '<span class="lbl">' . html_escape($text) . '</span></label>';
        };
        // Access groups (only rendered when the operator can grant them).
        // Codes come from config access_specifier. An agency can only add FE
        // members to its own team; only the superadmin can create an agency.
        $groups = [];
        $groups[] = ['Front-end', [1]];
        if ($isAdmin) {
            $groups[] = ['Agency', [51]];
        }
        foreach ($groups as $g):
            list($gtitle, $codes) = $g;
            // Count how many chips this group will actually render (so we can skip empties).
            $visible = array_filter($codes, function ($c) use ($product_access, $lbl_override) {
                return !empty($product_access[$c]) || isset($lbl_override[$c]);
            });
            if (empty($visible)) { continue; }
        ?>
        <div class="uacc-grp">
            <div class="uacc-head"><span class="t"><?= html_escape($gtitle) ?></span><span class="c"><?= count($visible) ?></span></div>
            <div class="uacc-chips">
                <?php foreach ($codes as $code) { $render_chip($code); } ?>
            </div>
            <?php if (in_array(93, $codes) && !empty($product_access[93]) && !$isEdit): ?>
            <div class="uacc-note">
                <svg viewBox="0 0 24 24"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3"/><path d="M18.5 4.5 21 7"/><path d="M15 8l2.5 2.5"/></svg>
                <span>Turning on <b>JV access</b> for a brand-new account copies the super admin's API keys (OpenAI, Gemini, ElevenLabs, Twilio and SMTP) into it, so this user can build and run Bridges right away. This happens only when the account is first created, never on an update.</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Footer -->
        <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:26px;padding-top:18px;border-top:1px solid var(--line)">
            <button type="submit" class="btn pri usersubmit"><?= $isEdit ? 'Update User' : 'Create User' ?></button>
        </div>

    </form>
    </div>
</div>

<script type="text/javascript">
// App-styled toast (bottom-right, auto-dismiss) — defined in ic_shell/layout.php.
// Falls back to a plain alert only if the shell helper isn't present.
function uToast(msg, isErr){ icToast(msg, !!isErr); }

$(document).ready(function(){

    $(".usercheck").click(function(){
        var pid=$(this).attr("data-id");
        var access_id=$(this).attr("data-access");
        if(access_id == 101){
            if($(this).is(":checked")){
                $(this).prop("checked",true);
            }else{
                $(this).prop("checked",false);
            }
        }else if($(this).is(":checked")){
            if($("#8").is(':checked') || $("#81").is(':checked')){
                $("#1,#11,#2,#211,#3,#4,#6").prop("checked",true);
            }else if(!$("#1").is(':checked')){
                uToast("Check the Front-End access first.", true);
                return false;
            }
        }else{
            if(!$("#1").is(':checked')){
                uToast("Check the Front-End access first.", true);
                return false;
            }
        }
    });

    $("#addNewuser").on("submit",function(){
        $('#pageLoader').show();
        var data1 = $(this).serialize();
        $.ajax({
            url : "<?= base_url('createNewUser') ?>",
            data : data1,
            type : "POST",
            dataType: "json",
            success(ret){
                $('#pageLoader').hide();
                if( ret.responseCode == 0 ){
                    uToast(ret.displayMessage, true);
                } else {
                    uToast(ret.displayMessage || 'User created.');
                    setTimeout(function(){ location.href = '<?= site_url('users') ?>'; }, 900);
                }
            },
            error(xhr){
                $('#pageLoader').hide();
                uToast('Something went wrong. Please try again.', true);
            }
        });
        return false;
    });

    $("#editUser").on("submit",function(){
        $('#pageLoader').show();
        var data1 = $(this).serialize();
        $.ajax({
            url : "<?= base_url('updateUser') ?>",
            data : data1,
            type : "POST",
            dataType: "json",
            success(ret){
                $('#pageLoader').hide();
                if( ret.responseCode == 0 ){
                    uToast(ret.displayMessage, true);
                } else {
                    uToast(ret.displayMessage || 'User updated.');
                    setTimeout(function(){ location.reload(); }, 900);
                }
            },
            error(xhr){
                $('#pageLoader').hide();
                uToast('Something went wrong. Please try again.', true);
            }
        });
        return false;
    });

    $(".assistcheck").click(function(){
        if($(this).is(":checked")){
            $(this).val(1);
        }else{
            $(this).val(0);
        }
    });

});
</script>
